-- 1. Tạo Database
CREATE DATABASE IF NOT EXISTS Darling_cosmetics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Darling_cosmetics;

-- ==========================================
-- MODULE: USER & AUTH
-- ==========================================

CREATE TABLE ROLES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE ACCOUNTS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL
);

CREATE TABLE ACCOUNT_ROLES (
    account_id BIGINT,
    role_id INT,
    PRIMARY KEY (account_id, role_id),
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES ROLES(id) ON DELETE CASCADE
);

CREATE TABLE ADDRESSES (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    recipient_name VARCHAR(100),
    phone VARCHAR(20),
    detail_address VARCHAR(255),
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: PRODUCTS
-- ==========================================

CREATE TABLE CATEGORIES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    FOREIGN KEY (parent_id) REFERENCES CATEGORIES(id) ON DELETE SET NULL
);

CREATE TABLE PRODUCTS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    spu VARCHAR(100) NOT NULL UNIQUE,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    base_price DECIMAL(15, 2) NOT NULL,
    status ENUM('draft', 'active', 'inactive') DEFAULT 'draft',
    image_url TEXT,
    FOREIGN KEY (category_id) REFERENCES CATEGORIES(id) ON DELETE SET NULL
);

CREATE TABLE PRODUCT_VARIANTS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    sku_code VARCHAR(100) NOT NULL UNIQUE,
    price DECIMAL(15, 2) NOT NULL,
    image_url TEXT,
    attributes JSON,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: INVENTORY
-- ==========================================

CREATE TABLE INVENTORY (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_variant_id BIGINT NOT NULL UNIQUE,
    quantity INT DEFAULT 0,
    reserved_quantity INT DEFAULT 0 COMMENT 'Hàng đang được giữ trong đơn chưa thanh toán',
    version BIGINT DEFAULT 0 COMMENT 'Dùng cho Optimistic Locking',
    FOREIGN KEY (product_variant_id) REFERENCES PRODUCT_VARIANTS(id) ON DELETE CASCADE
);

CREATE TABLE INVENTORY_LOGS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    inventory_id BIGINT NOT NULL,
    change_amount INT NOT NULL,
    reason ENUM('import', 'export', 'order_placed', 'order_cancelled', 'Return_restock', 'adjustment') NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES INVENTORY(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: CART
-- ==========================================

CREATE TABLE CARTS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE
);

CREATE TABLE CART_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cart_id BIGINT NOT NULL,
    product_variant_id BIGINT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES CARTS(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES PRODUCT_VARIANTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: ORDERS & SHIPPING
-- ==========================================

CREATE TABLE ORDERS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT,
    tracking_number VARCHAR(100),
    total_amount DECIMAL(15, 2) NOT NULL,
    shipping_fee DECIMAL(15, 2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(15, 2) NOT NULL,
    status ENUM(
        'new',              -- Mới tạo, chưa thanh toán/chưa xác nhận
        'pending',          -- Chờ thanh toán (cho Online Banking)
        'on_hold',          -- Đã thanh toán, chờ xử lý
        'processing',       -- Đang đóng gói
        'awaiting_pickup',  -- Chờ shipper lấy hàng
        'shipping',         -- Đang giao
        'shipped',          -- Giao thành công
        'completed',        -- Hoàn tất (sau khi hết hạn đổi trả)
        'cancelled',        -- Hủy
        'declined'          -- Thanh toán thất bại
    ) DEFAULT 'new',
    shipping_address_snapshot TEXT COMMENT 'Lưu cứng địa chỉ tại thời điểm đặt, có thể sẽ đổi kiểu DL',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipping_carrier VARCHAR(100) COMMENT 'Tên đơn vị vận chuyển',
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE SET NULL
);

CREATE TABLE ORDER_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    product_variant_id BIGINT,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES PRODUCT_VARIANTS(id) ON DELETE SET NULL
);

CREATE TABLE ORDER_HISTORY (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: PAYMENTS & REFUNDS
-- ==========================================

CREATE TABLE PAYMENT (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    method ENUM('COD', 'STRIPE') NOT NULL,
    transaction_ref VARCHAR(100) COMMENT 'Mã giao dịch từ cổng thanh toán',
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

-- MỚI: Bảng Refunds (Phương án 2)
CREATE TABLE REFUND (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT, -- Có thể null nếu hoàn tiền cho đơn COD chưa thanh toán nhưng khách đã cọc (case hiếm) hoặc liên kết trực tiếp Order
    order_id BIGINT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    reason TEXT COMMENT 'Lý do hoàn tiền',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    refund_transaction_ref VARCHAR(100) COMMENT 'Mã hoàn tiền từ cổng thanh toán',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES PAYMENT(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: RETURNS (RMA) - MỚI HOÀN TOÀN
-- ==========================================

CREATE TABLE RETURNS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    account_id BIGINT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM(
        'request_return',    -- Khách yêu cầu trả hàng
        'accept_return',     -- Shop đồng ý cho trả hàng
        'decline_return',    -- Shop từ chối yêu cầu trả hàng
        'receive_return_package',   -- Shop đã nhận hàng trả lại
        'accept_refund'      -- Hoàn tiền cho khách
    ) DEFAULT 'request_return',
    proof_images JSON COMMENT 'Mảng URL ảnh bằng chứng: ["img1.jpg", "img2.jpg"]',
    refund_amount DECIMAL(15, 2) COMMENT 'Số tiền dự kiến hoàn',
    admin_note TEXT COMMENT 'Ghi chú nội bộ của shop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE
);

CREATE TABLE RETURN_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT NOT NULL,
    order_item_id BIGINT NOT NULL COMMENT 'Liên kết với item trong đơn hàng gốc',
    quantity INT NOT NULL,
    FOREIGN KEY (return_id) REFERENCES RETURNS(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES ORDER_ITEMS(id) ON DELETE CASCADE
);

-- Lưu lịch sử thay đổi trạng thái account (suspend/activate) + lý do
CREATE TABLE ACCOUNT_STATUS_LOGS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    action ENUM('suspend', 'activate') NOT NULL,
    reason TEXT NULL,
    changed_by BIGINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES ACCOUNTS(id) ON DELETE SET NULL
);
