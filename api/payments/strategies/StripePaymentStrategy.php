<?php
require_once __DIR__ . '/PaymentStrategy.php';
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
class StripePaymentStrategy implements PaymentStrategy
{
    private PDO $pdo;
    private string $stripeSecretKey;
    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
        $this->stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? throw new \Exception('STRIPE_SECRET_KEY not configured in .env');
        \Stripe\Stripe::setApiKey($this->stripeSecretKey);
    }
    public function getPaymentMethod(): string
    {
        return 'STRIPE';
    }
    public function validate(array $orderData): array
    {
        $errors = [];
        if (empty($orderData['cart'])) {
            $errors[] = 'Cart is empty';
        }
        if (empty($orderData['shipping_info']['fullname'])) {
            $errors[] = 'Full name is required';
        }
        if (empty($orderData['shipping_info']['phone'])) {
            $errors[] = 'Phone number is required';
        }
        if (empty($orderData['shipping_info']['address'])) {
            $errors[] = 'Address is required';
        }
        if (!isset($orderData['total_amount']) || $orderData['total_amount'] <= 0) {
            $errors[] = 'Invalid total amount';
        }
        if ($orderData['total_amount'] < 0.50) {
            $errors[] = 'Minimum payment amount is $0.50';
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    public function processPayment(array $orderData): array
    {
        try {
            $validation = $this->validate($orderData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(', ', $validation['errors'])
                ];
            }
            $cart = $orderData['cart'];
            $shippingInfo = $orderData['shipping_info'];
            $totalAmount = (float)$orderData['total_amount'];
            $shippingFee = (float)($orderData['shipping_fee'] ?? 0);
            $shippingCarrier = $orderData['shipping_carrier'] ?? 'GHN';
            $note = $orderData['note'] ?? '';
            $accountId = $_SESSION['user_id'] ?? null;
            $this->pdo->beginTransaction();
            $trackingNumber = 'STR' . date('YmdHis') . rand(1000, 9999);
            $finalAmount = $totalAmount + $shippingFee;
            $shippingAddressJson = json_encode([
                'fullname' => $shippingInfo['fullname'],
                'phone' => $shippingInfo['phone'],
                'email' => $shippingInfo['email'] ?? '',
                'address' => $shippingInfo['address'],
                'city' => $shippingInfo['city'] ?? '',
                'district' => $shippingInfo['district'] ?? '',
                'note' => $note
            ], JSON_UNESCAPED_UNICODE);
            $sqlOrder = "INSERT INTO ORDERS (
                account_id,
                tracking_number,
                total_amount,
                payment_method,
                shipping_fee,
                shipping_carrier,
                final_amount,
                status,
                shipping_address_snapshot,
                created_at
            ) VALUES (?, ?, ?, 'STRIPE', ?, ?, ?, 'pending', ?, NOW())";
            $stmtOrder = $this->pdo->prepare($sqlOrder);
            $stmtOrder->execute([
                $accountId,
                $trackingNumber,
                $totalAmount,
                $shippingFee,
                $shippingCarrier,
                $finalAmount,
                $shippingAddressJson
            ]);
            $orderId = $this->pdo->lastInsertId();
            $this->insertOrderItems($orderId, $cart);
            $amountInCents = (int)round($finalAmount * 100);
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $orderId,
                    'tracking_number' => $trackingNumber
                ],
                'description' => "Order #{$orderId} - {$shippingInfo['fullname']}"
            ]);
            $paymentId = $this->createPaymentRecord($orderId, $finalAmount, $paymentIntent->id);
            $this->pdo->commit();
            return [
                'success' => true,
                'data' => [
                    'order_id' => $orderId,
                    'tracking_number' => $trackingNumber,
                    'payment_id' => $paymentId,
                    'payment_method' => 'STRIPE',
                    'client_secret' => $paymentIntent->client_secret
                ],
                'message' => 'Payment intent created successfully'
            ];
        } catch (\Stripe\Exception\CardException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Card error: ' . $e->getError()->message
            ];
        } catch (\Stripe\Exception\RateLimitException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Too many requests'
            ];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Invalid request: ' . $e->getMessage()
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Stripe API error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ];
        }
    }
    private function insertOrderItems(int $orderId, array $cart): void
    {
        $checkColumns = $this->pdo->query("SHOW COLUMNS FROM ORDER_ITEMS");
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        $hasProductVariantId = in_array('product_variant_id', $columns);
        $hasPriceAtPurchase = in_array('price_at_purchase', $columns);
        $productCol = $hasProductVariantId ? 'product_variant_id' : 'product_id';
        $priceCol = $hasPriceAtPurchase ? 'price_at_purchase' : 'price';
        $sqlItem = "INSERT INTO ORDER_ITEMS (
            order_id,
            $productCol,
            quantity,
            $priceCol
        ) VALUES (?, ?, ?, ?)";
        $stmtItem = $this->pdo->prepare($sqlItem);
        foreach ($cart as $item) {
            $stmtItem->execute([
                $orderId,
                $item['id'],
                $item['qty'],
                $item['price']
            ]);
        }
    }
    private function createPaymentRecord(int $orderId, float $amount, string $stripePaymentIntentId): int
    {
        $sqlPayment = "INSERT INTO PAYMENT (
            order_id,
            amount,
            status,
            stripe_payment_intent_id,
            payment_method_type,
            created_at
        ) VALUES (?, ?, 'pending', ?, 'STRIPE', NOW())";
        $stmtPayment = $this->pdo->prepare($sqlPayment);
        $stmtPayment->execute([$orderId, $amount, $stripePaymentIntentId]);
        return $this->pdo->lastInsertId();
    }
}
