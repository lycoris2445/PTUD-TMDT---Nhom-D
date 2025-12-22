# Darling Cosmetics E-Commerce System

Hệ thống thương mại điện tử mỹ phẩm với tích hợp thanh toán Stripe và COD.

## Công nghệ sử dụng

- **Backend:** PHP 8+, PDO
- **Database:** MySQL/MariaDB (darling_cosmetics)
- **Frontend:** JavaScript ES6, Bootstrap 5.3.3
- **Payment:** Stripe API v19.1.0
- **Architecture:** Strategy Design Pattern cho payment processing

## Cài đặt

### 1. Yêu cầu hệ thống

- PHP >= 8.0
- MySQL/MariaDB
- Composer
- Laragon (hoặc XAMPP/WAMP)

### 2. Clone project

```bash
git clone <repository-url>
cd "PTUD TMĐT - Nhóm D"
```

### 3. Install dependencies

```bash
composer install
```

### 4. Cấu hình Database

Tạo file `.env` trong thư mục gốc:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=darling_cosmetics
DB_USER=root
DB_PASS=

# Stripe Configuration (Test Mode)
STRIPE_PUBLISHABLE_KEY=pk_test_your_publishable_key
STRIPE_SECRET_KEY=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 5. Import Database

```bash
mysql -u root -p darling_cosmetics < data.sql
```

### 6. Chạy project

1. Mở Laragon và Start All
2. Truy cập: `http://localhost/PTUD%20TMĐT%20-%20Nhóm%20D`

## Thanh toán Stripe - Local Development

### Bước 1: Cài đặt Stripe CLI

**Windows (Scoop):**
```bash
scoop install stripe
```

**Hoặc download từ:** https://github.com/stripe/stripe-cli/releases/latest

**Mac/Linux:**
```bash
brew install stripe/stripe-cli/stripe
```

### Bước 2: Login Stripe

```bash
stripe login
```

Trình duyệt sẽ mở để xác thực với tài khoản Stripe của bạn.

### Bước 3: Forward Webhooks

```bash
stripe listen --forward-to localhost/PTUD%20TMĐT%20-%20Nhóm%20D/api/payments/stripe-webhook.php
```

**Output:**
```
> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxxxxxxxxxx
```

### Bước 4: Cập nhật .env

Copy webhook secret từ CLI output:

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx
```

### Bước 5: Giữ CLI chạy

⚠️ **Quan trọng:** Terminal chạy Stripe CLI phải luôn mở để nhận webhook events.

Bạn sẽ thấy log real-time:
```
2025-12-22 10:30:15  --> payment_intent.succeeded [evt_xxx]
2025-12-22 10:30:20  <--  [200] POST http://localhost/.../stripe-webhook.php
```

### Test Stripe Payment

**Test Cards:**
- ✅ Success: `4242 4242 4242 4242`
- ❌ Declined: `4000 0000 0000 0002`
- 🔐 3D Secure: `4000 0025 0000 3155`

**Expiry:** Any future date  
**CVC:** Any 3 digits

### Test Webhook Events

```bash
# Test payment success
stripe trigger payment_intent.succeeded

# Test payment failed
stripe trigger payment_intent.payment_failed

# Test refund
stripe trigger charge.refunded
```

### Webhook Events

System xử lý các events:

1. **payment_intent.succeeded**
   - Update PAYMENT → 'paid'
   - Update ORDERS → 'on_hold'
   - Save stripe_charge_id

2. **payment_intent.payment_failed**
   - Update PAYMENT → 'failed'
   - Update ORDERS → 'cancelled'

3. **charge.refunded**
   - Update REFUND → 'completed'
   - Update ORDERS → 'refunded' (nếu full refund)

### Troubleshooting

**Webhook không nhận events:**
```bash
# Kiểm tra CLI đang chạy
stripe listen --forward-to localhost/PTUD%20TMĐT%20-%20Nhóm%20D/api/payments/stripe-webhook.php

# Kiểm tra .env có đúng secret
echo $STRIPE_WEBHOOK_SECRET
```

**Signature verification fails:**
- Đảm bảo `STRIPE_WEBHOOK_SECRET` trong .env khớp với CLI output
- Restart web server sau khi update .env

**Check logs:**
```bash
# PHP error log (Laragon)
C:\laragon\www\PTUD TMĐT - Nhóm D\storage\logs\

# Apache error log
C:\laragon\bin\apache\...\logs\error.log
```

## Cấu trúc Payment System

### Strategy Pattern

```
api/payments/
├── strategies/
│   ├── PaymentStrategy.php          # Interface
│   ├── CODPaymentStrategy.php       # COD implementation
│   └── StripePaymentStrategy.php    # Stripe implementation
├── PaymentContext.php               # Strategy context
├── process-payment.php              # Unified endpoint
├── confirm-payment.php              # Stripe confirmation
├── create-refund.php                # Refund handler
└── stripe-webhook.php               # Webhook receiver
```

### Payment Flow

**COD:**
```
Cart → Checkout → process-payment.php?payment_method=COD → Order Success
```

**Stripe:**
```
Cart → Checkout → process-payment.php?payment_method=STRIPE
    → Get client_secret
    → Stripe.js confirmPayment
    → confirm-payment.php (fallback)
    → Webhook (stripe-webhook.php)
    → Order Success
```

## Database Schema

### ORDERS
- `id`, `account_id`, `tracking_number`, `status`
- `payment_method` (COD/STRIPE)
- `total_amount`, `shipping_fee`, `final_amount`
- `shipping_address_snapshot` (JSON)

### PAYMENT
- `id`, `order_id`, `method`, `status`
- `stripe_payment_intent_id`, `stripe_charge_id`
- `payment_method_type`, `amount`

### ORDER_ITEMS
- `order_id`, `product_id`, `product_variant_id`
- `quantity`, `price_at_purchase`

### REFUND
- `order_id`, `amount`, `status`
- `stripe_refund_id`, `reason`

## Features

### Customer
- ✅ Browse products với filters
- ✅ Shopping cart (localStorage)
- ✅ Checkout với COD/Stripe
- ✅ Order history với search/filter
- ✅ Order detail view
- ✅ Request refund (Stripe only)

### Admin
- ✅ Product management
- ✅ Customer management
- ✅ Order management
- ✅ Category actions

## API Endpoints

### Payment
- `POST /api/payments/process-payment.php` - Create order (COD/Stripe)
- `POST /api/payments/confirm-payment.php` - Confirm Stripe payment
- `POST /api/payments/create-refund.php` - Request refund
- `POST /api/payments/stripe-webhook.php` - Webhook receiver

### Orders
- `GET /website/php/orders.php` - List user orders
- `GET /website/php/order-detail.php?id={id}` - Order details

## Production Deployment

### Stripe Webhook (Production)

1. Go to Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://yourdomain.com/api/payments/stripe-webhook.php`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
4. Copy webhook signing secret
5. Update production `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_production_secret
   ```

### Environment Variables

Update `.env` for production:
```env
DB_HOST=production_host
DB_NAME=production_db
DB_USER=production_user
DB_PASS=strong_password

STRIPE_PUBLISHABLE_KEY=pk_live_xxxxx
STRIPE_SECRET_KEY=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

## Tài liệu tham khảo

- [Stripe CLI Documentation](https://stripe.com/docs/stripe-cli)
- [Stripe Webhooks Guide](https://stripe.com/docs/webhooks)
- [Stripe Testing Cards](https://stripe.com/docs/testing#cards)
- [Stripe API Reference](https://stripe.com/docs/api)

## License

MIT License

## Contributors

- Nhóm D - PTUD TMĐT
