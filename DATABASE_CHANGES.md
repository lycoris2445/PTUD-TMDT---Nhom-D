# Database Changes - Stripe Payment Integration

## Tóm tắt thay đổi
Các thay đổi database được thực hiện để hỗ trợ thanh toán Stripe và quản lý đơn hàng.

## Bảng PAYMENT

### Cột mới đã thêm:

1. **`stripe_payment_intent_id`** - VARCHAR(100)
   - Mục đích: Lưu Stripe PaymentIntent ID để tracking payment
   - Sử dụng: Confirm payment, webhook processing
   - Index: `idx_stripe_payment_intent`

2. **`stripe_charge_id`** - VARCHAR(100)
   - Mục đích: Lưu Stripe Charge ID để thực hiện refund
   - Sử dụng: Refund processing
   - Index: `idx_stripe_charge`

3. **`payment_method_type`** - VARCHAR(50)
   - Mục đích: Lưu loại payment method (card, wallet, etc.)
   - Giá trị: 'card', 'wallet', 'bank_transfer', etc.
   - Sử dụng: Analytics, reporting

4. **`metadata`** - JSON
   - Mục đích: Lưu thêm thông tin linh hoạt về payment
   - Dữ liệu: Payment method details, customer info, etc.
   - Sử dụng: Debugging, additional tracking

### Cột đã sửa đổi:

**`method`** - ENUM
- **Trước:** `ENUM('COD', 'BANK')`
- **Sau:** `ENUM('COD', 'BANK', 'STRIPE_CARD', 'STRIPE_WALLET')`
- Lý do: Hỗ trợ các phương thức thanh toán Stripe

## Bảng REFUND

### Cột mới đã thêm:

1. **`stripe_refund_id`** - VARCHAR(100)
   - Mục đích: Lưu Stripe Refund ID để tracking refund status
   - Sử dụng: Webhook updates, refund status sync
   - Index: `idx_stripe_refund`

2. **`refunded_at`** - TIMESTAMP NULL
   - Mục đích: Ghi nhận thời điểm refund được hoàn tất
   - Giá trị: NULL khi pending, timestamp khi completed
   - Sử dụng: Reporting, customer notification

## Bảng ORDERS

### Không có thay đổi cấu trúc
Các cột hiện tại đã đủ để hỗ trợ payment flow:
- `payment_method`: Đã có sẵn để lưu 'STRIPE' hoặc 'COD'
- `status`: Đã có để track order lifecycle
- `shipping_address_snapshot`: Đã có để lưu JSON shipping info
- `total_amount`, `shipping_fee`, `final_amount`: Đã hỗ trợ USD

## Bảng ORDER_ITEMS

### Không có thay đổi cấu trúc
- Đã có `product_variant_id` để link với products
- Đã có `quantity`, `price_at_purchase` để tính toán
- Không cần thêm cột mới

## Migration Script

File: `database/stripe_migration.sql`

### Cách chạy migration:

```bash
# Option 1: MySQL command line
mysql -u root -p darling_cosmetics < database/stripe_migration.sql

# Option 2: phpMyAdmin
# Import file stripe_migration.sql vào database darling_cosmetics

# Option 3: Laragon MySQL Console
# Copy nội dung file và execute
```

### Kiểm tra migration đã chạy:

```sql
-- Check PAYMENT columns
SHOW COLUMNS FROM PAYMENT;

-- Check REFUND columns
SHOW COLUMNS FROM REFUND;

-- Verify indexes
SHOW INDEX FROM PAYMENT WHERE Key_name LIKE 'idx_stripe%';
SHOW INDEX FROM REFUND WHERE Key_name = 'idx_stripe_refund';
```

## Backward Compatibility

✅ **Tất cả thay đổi đều backward compatible:**
- Các cột mới đều cho phép NULL hoặc có giá trị default
- Không xóa cột cũ
- Enum mới vẫn giữ các giá trị cũ
- Existing data không bị ảnh hưởng

## Rollback (nếu cần)

Nếu cần rollback migration:

```sql
-- Remove Stripe columns from PAYMENT
ALTER TABLE PAYMENT 
DROP COLUMN stripe_payment_intent_id,
DROP COLUMN stripe_charge_id,
DROP COLUMN payment_method_type,
DROP COLUMN metadata;

-- Remove indexes
DROP INDEX idx_stripe_payment_intent ON PAYMENT;
DROP INDEX idx_stripe_charge ON PAYMENT;

-- Remove Stripe columns from REFUND
ALTER TABLE REFUND
DROP COLUMN stripe_refund_id,
DROP COLUMN refunded_at;

-- Remove index
DROP INDEX idx_stripe_refund ON REFUND;

-- Revert PAYMENT method enum
ALTER TABLE PAYMENT 
MODIFY COLUMN method ENUM('COD', 'BANK') NOT NULL;
```

## Testing Checklist

- [ ] Migration script chạy không lỗi
- [ ] Tất cả indexes đã được tạo
- [ ] COD payment vẫn hoạt động bình thường
- [ ] Stripe payment tạo được PaymentIntent
- [ ] Stripe charge_id được lưu sau payment success
- [ ] Refund request tạo được Stripe refund
- [ ] Webhook cập nhật đúng stripe_refund_id

## Notes

- **Quan trọng:** Chạy migration trước khi test Stripe payment
- Backup database trước khi chạy migration trong production
- Test thoroughly trên development environment trước
- Migration script có `IF NOT EXISTS` để tránh lỗi nếu chạy lại
