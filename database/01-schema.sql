-- ==========================================
-- DATABASE SCHEMA
-- ==========================================
-- This file contains the complete database schema
-- Automatically executed by Docker on first startup
-- ==========================================

USE darling_cosmetics;

-- ==========================================
-- MODULE: USER & AUTH
-- ==========================================

CREATE TABLE IF NOT EXISTS ROLES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS ACCOUNTS (
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

CREATE TABLE IF NOT EXISTS ACCOUNT_ROLES (
    account_id BIGINT,
    role_id INT,
    PRIMARY KEY (account_id, role_id),
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES ROLES(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ADDRESSES (
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

CREATE TABLE IF NOT EXISTS CATEGORIES (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    FOREIGN KEY (parent_id) REFERENCES CATEGORIES(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS BRANDS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS PRODUCTS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    brand_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES CATEGORIES(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES BRANDS(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS VARIANTS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    color VARCHAR(50),
    size VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS PRODUCT_IMAGES (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    display_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: CART & WISHLIST
-- ==========================================

CREATE TABLE IF NOT EXISTS CART_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    variant_id BIGINT NOT NULL,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES VARIANTS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS WISHLIST_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: ORDERS
-- ==========================================

CREATE TABLE IF NOT EXISTS ORDERS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    tracking_number VARCHAR(100) UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'new',
    shipping_address_snapshot TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipping_carrier VARCHAR(50),
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ORDER_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    product_variant_id BIGINT NOT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES VARIANTS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ORDER_HISTORY (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: REVIEWS
-- ==========================================

CREATE TABLE IF NOT EXISTS REVIEWS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    account_id BIGINT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: NOTIFICATIONS
-- ==========================================

CREATE TABLE IF NOT EXISTS NOTIFICATIONS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES ACCOUNTS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: PROMOTIONS
-- ==========================================

CREATE TABLE IF NOT EXISTS PROMOTIONS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT,
    discount_percent DECIMAL(5,2) CHECK (discount_percent BETWEEN 0 AND 100),
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (product_id) REFERENCES PRODUCTS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS COUPONS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_percent DECIMAL(5,2) CHECK (discount_percent BETWEEN 0 AND 100),
    max_usage INT,
    used_count INT DEFAULT 0,
    start_date DATE,
    end_date DATE
);

-- ==========================================
-- MODULE: PAYMENT
-- ==========================================

CREATE TABLE IF NOT EXISTS PAYMENT (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    final_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'usd',
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

-- ==========================================
-- MODULE: RETURNS & REFUNDS
-- ==========================================

CREATE TABLE IF NOT EXISTS RETURNS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    status VARCHAR(50) DEFAULT 'request_return',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS RETURN_ITEMS (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT NOT NULL,
    order_item_id BIGINT NOT NULL,
    quantity_returned INT NOT NULL,
    FOREIGN KEY (return_id) REFERENCES RETURNS(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES ORDER_ITEMS(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS REFUND (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_id BIGINT NOT NULL,
    order_id BIGINT NOT NULL,
    stripe_refund_id VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES PAYMENT(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES ORDERS(id) ON DELETE CASCADE
);

-- Database version update
INSERT INTO _database_version (version) VALUES ('1.0.1-schema') ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP;
