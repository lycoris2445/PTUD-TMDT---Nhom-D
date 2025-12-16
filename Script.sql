-- 1. Tạo Database
CREATE DATABASE IF NOT EXISTS cosmetics_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cosmetics_ecommerce;

-- ==========================================
-- MODULE: USER & AUTH
-- ==========================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    status ENUM('ACTIVE', 'INACTIVE', 'BANNED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    user_id BIGINT,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE addresses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    recipient_name VARCHAR(100),
    phone VARCHAR(20),
    detail_address VARCHAR(255),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: PRODUCTS
-- ==========================================

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    logo_url TEXT
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE products (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    base_price DECIMAL(15, 2) NOT NULL,
    status ENUM('DRAFT', 'ACTIVE', 'INACTIVE') DEFAULT 'DRAFT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE product_variants (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    sku_code VARCHAR(100) NOT NULL UNIQUE,
    price DECIMAL(15, 2) NOT NULL,
    image_url TEXT,
    attributes JSON COMMENT 'Lưu màu sắc, kích thước dạng JSON {"color": "red", "size": "M"}',
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: INVENTORY
-- ==========================================

CREATE TABLE inventory (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_variant_id BIGINT NOT NULL UNIQUE,
    quantity INT DEFAULT 0,
    reserved_quantity INT DEFAULT 0 COMMENT 'Hàng đang được giữ trong đơn chưa thanh toán',
    version BIGINT DEFAULT 0 COMMENT 'Dùng cho Optimistic Locking',
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
);

CREATE TABLE inventory_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    inventory_id BIGINT NOT NULL,
    change_amount INT NOT NULL,
    reason ENUM('IMPORT', 'EXPORT', 'ORDER_PLACED', 'ORDER_CANCELLED', 'RETURN_RESTOCK', 'ADJUSTMENT') NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: CART
-- ==========================================

CREATE TABLE carts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cart_id BIGINT NOT NULL,
    product_variant_id BIGINT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: ORDERS & SHIPPING
-- ==========================================

CREATE TABLE orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT,
    tracking_number VARCHAR(100),
    shipping_carrier VARCHAR(100) COMMENT 'Mới: Đơn vị vận chuyển (GHN, GHTK, ViettelPost...)',
    total_amount DECIMAL(15, 2) NOT NULL,
    final_amount DECIMAL(15, 2) NOT NULL COMMENT 'Giá sau khi trừ khuyến mãi',
    status ENUM(
        'NEW',              -- Mới tạo, chưa thanh toán/chưa xác nhận
        'PENDING',          -- Chờ thanh toán (cho Online Banking)
        'ON_HOLD',          -- Đã thanh toán, chờ xử lý
        'PROCESSING',       -- Đang đóng gói
        'AWAITING_PICKUP',  -- Chờ shipper lấy hàng
        'SHIPPING',         -- Đang giao
        'SHIPPED',          -- Giao thành công
        'COMPLETED',        -- Hoàn tất (sau khi hết hạn đổi trả)
        'CANCELLED',        -- Hủy
        'DECLINED'          -- Thanh toán thất bại
    ) DEFAULT 'NEW',
    shipping_address_snapshot JSONB COMMENT 'Lưu cứng địa chỉ tại thời điểm đặt',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE order_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    product_variant_id BIGINT,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

CREATE TABLE order_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: PAYMENTS & REFUNDS
-- ==========================================

CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    method ENUM('COD', 'ONLINE_BANKING', 'E_WALLET') NOT NULL,
    transaction_ref VARCHAR(100) COMMENT 'Mã giao dịch từ cổng thanh toán',
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('PENDING', 'SUCCESS', 'FAILED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- MỚI: Bảng Refunds (Phương án 2)
CREATE TABLE refunds (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT, -- Có thể null nếu hoàn tiền cho đơn COD chưa thanh toán nhưng khách đã cọc (case hiếm) hoặc liên kết trực tiếp Order
    order_id BIGINT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    reason TEXT COMMENT 'Lý do hoàn tiền',
    status ENUM('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED') DEFAULT 'PENDING',
    refund_transaction_ref VARCHAR(100) COMMENT 'Mã hoàn tiền từ cổng thanh toán',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: RETURNS (RMA) - MỚI HOÀN TOÀN
-- ==========================================

CREATE TABLE returns (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM(
        'REQUESTED',        -- Khách gửi yêu cầu
        'APPROVED',         -- Shop đồng ý cho trả
        'REJECTED',         -- Shop từ chối
        'RETURNED',         -- Shop đã nhận lại hàng
        'REFUNDED'          -- Đã hoàn tiền xong
    ) DEFAULT 'REQUESTED',
    proof_images JSON COMMENT 'Mảng URL ảnh bằng chứng: ["img1.jpg", "img2.jpg"]',
    refund_amount DECIMAL(15, 2) COMMENT 'Số tiền dự kiến hoàn',
    admin_note TEXT COMMENT 'Ghi chú nội bộ của shop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE return_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT NOT NULL,
    order_item_id BIGINT NOT NULL COMMENT 'Liên kết với item trong đơn hàng gốc',
    quantity INT NOT NULL,
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
);