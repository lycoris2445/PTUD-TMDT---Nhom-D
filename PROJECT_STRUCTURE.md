# PTUD-TMDT---Nhom-D Project Structure

```
PTUD-TMDT---Nhom-D/
├── admin/                          # Admin dashboard
│   ├── css/
│   │   ├── admin-main.css
│   │   ├── customer-management.css
│   │   └── product-management.css
│   ├── includes/
│   │   ├── admin-sidebar.php
│   │   └── function_product_management.php
│   ├── js/
│   │   └── khach_hang.js          # Customer page search script
│   └── php/
│       ├── customer-management.php  # Customer list (with DB)
│       ├── customer.php             # Legacy customer page
│       ├── dashboard.php
│       ├── login.php
│       ├── orders.php               # Orders management
│       ├── product-management.php
│       ├── products.php
│       ├── search-customer.php
│       ├── staff-management.php
│       └── [other admin pages]
│
├── config/                         # Configuration files
│   ├── cloudinary.php             # Cloudinary API config
│   ├── db_connect.php             # Database connection (.env based)
│   └── test-db.php                # DB connection test
│
├── icons/                         # Logo and icons
│   └── logo_darling.svg
│
├── website/                       # Frontend (customer facing)
│   ├── css/
│   │   ├── dnhap_dki.css         # Login/Register page
│   │   ├── gio-hang.css          # Cart page
│   │   ├── gioi-thieu.css        # About page
│   │   ├── lien-he.css           # Contact page
│   │   ├── policy.css            # Policy page
│   │   ├── product.css
│   │   ├── store.css             # Store/Products page
│   │   ├── style.css             # Main styles
│   │   ├── styles.css
│   │   ├── thanh-toan.css        # Checkout page
│   │   └── trang-chu.css         # Homepage
│   ├── includes/
│   │   ├── footer.php
│   │   ├── function_filter.php
│   │   ├── function_product.php
│   │   ├── function_store.php
│   │   ├── header.php
│   │   └── store_filter_sidebar.php
│   ├── js/
│   │   ├── cart.js               # Shopping cart functionality
│   │   ├── san-pham.js           # Product page script
│   │   └── script.js             # General scripts
│   └── php/
│       ├── about.php             # About Us page
│       ├── cart.html / cart.php  # Shopping cart
│       ├── contact.php           # Contact form
│       ├── index.php             # Homepage
│       ├── login.php             # User login
│       ├── logout.php            # User logout
│       ├── order.html / order.php # Order/Checkout
│       ├── policy.php            # Terms & Policy
│       ├── product.php           # Product detail
│       ├── register.php          # User registration
│       ├── san-pham.html         # Product listing
│       └── store.php             # Store/Filter products
│
├── vendor/                        # Composer dependencies
│   ├── autoload.php              # Composer autoloader
│   ├── cloudinary/               # Cloudinary PHP SDK
│   ├── guzzlehttp/               # HTTP client
│   ├── monolog/                  # Logging
│   ├── psr/                      # PHP Standards
│   ├── ralouphie/                # getallheaders
│   ├── symfony/                  # Symfony components
│   └── composer/                 # Composer metadata
│
├── .gitignore                    # Git ignore rules
├── .env                          # Environment variables (not in repo)
├── composer.json                 # PHP dependencies
├── composer.lock                 # Locked dependencies
├── data.sql                      # Initial database data
├── Script.sql                    # Database schema
├── composer-setup.php            # Composer installer
└── PROJECT_STRUCTURE.md          # This file

```

## Key Directories

### `/admin/` - Admin Dashboard
- **Purpose**: Management interface for staff (products, customers, orders)
- **Files**: PHP scripts for each management section
- **CSS**: Separate stylesheets for different admin pages
- **JS**: Search and AJAX functionality

### `/website/` - Customer Frontend
- **Purpose**: Public-facing e-commerce site
- **Structure**: 
  - `/css/` - Page-specific and global styles
  - `/js/` - Cart, product filtering, search
  - `/php/` - Pages (login, register, product listing, checkout, etc.)
  - `/includes/` - Reusable templates (header, footer, functions)

### `/config/` - Configuration
- **db_connect.php**: Database connection (loads from `.env`)
- **cloudinary.php**: Image upload service configuration
- **test-db.php**: Database connection testing

### `/vendor/` - Third-party Libraries
- **Cloudinary**: Image hosting & CDN
- **Guzzle HTTP**: HTTP requests
- **Monolog**: Logging
- **PSR Standards**: PHP interfaces

## Database Files
- `Script.sql` - Database schema definition
- `data.sql` - Sample/initial data

## Key Features
1. **Product Management** - Add, edit, delete products with image uploads
2. **Customer Management** - View customer list, orders, addresses
3. **Shopping Cart** - Add to cart, checkout, payment methods
4. **User Authentication** - Login, Register, Session management
5. **Image Upload** - Cloudinary integration for product images

## Frontend URLs
- Homepage: `/website/php/index.php`
- Products: `/website/php/store.php`
- Cart: `/website/php/cart.php`
- Checkout: `/website/php/order.php`
- Login: `/website/php/login.php`
- Register: `/website/php/register.php`

## Admin URLs
- Dashboard: `/admin/php/dashboard.php`
- Customers: `/admin/php/customer-management.php`
- Products: `/admin/php/product-management.php`
- Orders: `/admin/php/orders.php`

## Dependencies
- **PHP 7.4+** (uses match expressions, nullable types, etc.)
- **MySQL/MariaDB** (via PDO)
- **Cloudinary API** (for image storage)
- **Bootstrap 5.3.3** (CSS framework)
- **Composer** (PHP package manager)

## Configuration
All sensitive data (DB credentials, API keys) should be in `.env` file:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=Darling_cosmetics
DB_USER=root
DB_PASS=
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
```
