<?php
require_once __DIR__ . '/strategies/PaymentStrategy.php';
require_once __DIR__ . '/strategies/CODPaymentStrategy.php';
require_once __DIR__ . '/strategies/StripePaymentStrategy.php';
class PaymentContext
{
    private ?PaymentStrategy $strategy = null;
    private array $availableStrategies = [];
    public function __construct()
    {
        $this->registerStrategy('COD', new CODPaymentStrategy());
        $this->registerStrategy('STRIPE', new StripePaymentStrategy());
    }
    public function registerStrategy(string $name, PaymentStrategy $strategy): void
    {
        $this->availableStrategies[strtoupper($name)] = $strategy;
    }
    public function setStrategy(string $paymentMethod): bool
    {
        $method = strtoupper($paymentMethod);
        if (!isset($this->availableStrategies[$method])) {
            return false;
        }
        $this->strategy = $this->availableStrategies[$method];
        return true;
    }
    public function getStrategy(): ?PaymentStrategy
    {
        return $this->strategy;
    }
    public function getAvailablePaymentMethods(): array
    {
        return array_keys($this->availableStrategies);
    }
    public function executePayment(array $orderData): array
    {
        if ($this->strategy === null) {
            return [
                'success' => false,
                'message' => 'No payment strategy selected'
            ];
        }
        return $this->strategy->processPayment($orderData);
    }
    public function validate(array $orderData): array
    {
        if ($this->strategy === null) {
            return [
                'valid' => false,
                'errors' => ['No payment strategy selected']
            ];
        }
        return $this->strategy->validate($orderData);
    }
    public static function createWithStrategy(string $paymentMethod): ?self
    {
        $context = new self();
        if (!$context->setStrategy($paymentMethod)) {
            return null;
        }
        return $context;
    }
}
