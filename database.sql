-- ============================================
-- RISER CAP STORE - DATABASE SCHEMA
-- Pakistan-based streetwear snapback e-commerce
-- ============================================

CREATE DATABASE IF NOT EXISTS riser_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE riser_store;

-- ---------------------------------
-- Categories
-- ---------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (name, slug) VALUES
('Snapbacks', 'snapbacks'),
('Trucker Caps', 'trucker-caps'),
('Dad Hats', 'dad-hats'),
('Fitted Caps', 'fitted-caps');

-- ---------------------------------
-- Products
-- ---------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) DEFAULT NULL, -- original price for "sale" strike-through
    category_id INT,
    image VARCHAR(255) DEFAULT 'default-cap.jpg',
    is_featured TINYINT(1) DEFAULT 0,
    is_new_arrival TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ---------------------------------
-- Product Images (gallery, optional multiple shots per cap)
-- ---------------------------------
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---------------------------------
-- Product Variants (size / color / stock)
-- ---------------------------------
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20) NOT NULL DEFAULT 'One Size',
    color VARCHAR(50) NOT NULL DEFAULT 'Black',
    color_hex VARCHAR(7) DEFAULT '#000000',
    stock INT NOT NULL DEFAULT 0,
    sku VARCHAR(60) UNIQUE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_variant (product_id, size, color)
);

-- ---------------------------------
-- Orders (Cash on Delivery, guest checkout)
-- ---------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    address TEXT NOT NULL,
    city VARCHAR(80) NOT NULL,
    province VARCHAR(80) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 200.00,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'COD',
    status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------
-- Order Items
-- ---------------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    variant_id INT,
    product_name VARCHAR(150) NOT NULL,
    size VARCHAR(20),
    color VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    custom_text VARCHAR(20) DEFAULT NULL,     -- Live Embroidery Customizer: customer's stitched text
    thread_color VARCHAR(7) DEFAULT NULL,     -- hex of chosen thread color
    custom_fee DECIMAL(10,2) NOT NULL DEFAULT 0, -- per-unit embroidery customization fee
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- ---------------------------------
-- Performance indexes (dashboard queries, filters, search)
-- ---------------------------------
CREATE INDEX idx_products_active_featured ON products (is_active, is_featured);
CREATE INDEX idx_products_active_new ON products (is_active, is_new_arrival);
CREATE INDEX idx_products_created ON products (created_at);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_created ON orders (created_at);
CREATE INDEX idx_variants_stock ON product_variants (stock);
ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, description);

-- ---------------------------------
-- Admin Users
-- ---------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NOTE: Do not insert an admin row here with a fake password hash.
-- After importing this file, visit /setup.php in your browser ONCE to create
-- your real admin account with a properly generated bcrypt hash, then delete setup.php.

-- ---------------------------------
-- Sample Products
-- ---------------------------------
INSERT INTO products (name, slug, description, price, compare_price, category_id, image, is_featured, is_new_arrival) VALUES
('Riser Classic Snapback - Black', 'riser-classic-snapback-black', 'The cap that started it all. Structured 6-panel snapback with embroidered Riser logo, flat brim, and adjustable strap. Built for the streets of Karachi to Lahore.', 2499.00, 2999.00, 1, 'classic-black.jpg', 1, 0),
('Riser Bold Logo Trucker', 'riser-bold-logo-trucker', 'Mesh-back trucker cap with bold front embroidery. Breathable, lightweight, and made for hot Pakistani summers without compromising street style.', 2199.00, NULL, 2, 'trucker-bold.jpg', 1, 1),
('Riser Heritage Dad Hat', 'riser-heritage-dad-hat', 'Low-profile, unstructured dad hat with curved brim and soft embroidered patch. Minimal, clean, everyday wear.', 1899.00, 2300.00, 3, 'dad-hat-heritage.jpg', 0, 1),
('Riser Midnight Fitted', 'riser-midnight-fitted', 'Premium fitted cap in matte black with tonal stitching. No strap, no adjustments — just a clean fitted silhouette.', 2799.00, NULL, 4, 'fitted-midnight.jpg', 1, 1),
('Riser Camo Tactical Snap', 'riser-camo-tactical-snap', 'Urban camo snapback with side embroidered wordmark. Limited drop colorway built for standing out.', 2599.00, 2999.00, 1, 'camo-tactical.jpg', 1, 0),
('Riser White Out Trucker', 'riser-white-out-trucker', 'All-white mesh trucker with minimal black logo. Clean fit for warm weather and street fits alike.', 2199.00, NULL, 2, 'white-trucker.jpg', 0, 1),
('Riser Crimson Dad Cap', 'riser-crimson-dad-cap', 'Deep crimson dad hat with cream embroidered logo. Soft crown, curved brim, everyday comfort.', 1899.00, NULL, 3, 'crimson-dad.jpg', 0, 0),
('Riser Olive Snapback', 'riser-olive-snapback', 'Earth-tone olive snapback with subtle tonal branding. Versatile colorway for any fit.', 2499.00, NULL, 1, 'olive-snap.jpg', 0, 1);

-- Variants for each product (sizes mostly One Size for snapback-style, colors vary)
INSERT INTO product_variants (product_id, size, color, color_hex, stock, sku) VALUES
(1, 'One Size', 'Black', '#111111', 25, 'RSR-CLS-BLK-OS'),
(1, 'One Size', 'Grey', '#7a7a7a', 15, 'RSR-CLS-GRY-OS'),
(2, 'One Size', 'Black/White', '#222222', 20, 'RSR-TRK-BLW-OS'),
(2, 'One Size', 'Navy/White', '#1c2b4a', 18, 'RSR-TRK-NVW-OS'),
(3, 'One Size', 'Beige', '#d8c4a0', 22, 'RSR-DAD-BEI-OS'),
(3, 'One Size', 'Black', '#111111', 17, 'RSR-DAD-BLK-OS'),
(4, 'S/M', 'Black', '#111111', 10, 'RSR-FIT-BLK-SM'),
(4, 'L/XL', 'Black', '#111111', 12, 'RSR-FIT-BLK-LX'),
(5, 'One Size', 'Camo Green', '#5a6c45', 14, 'RSR-CAM-GRN-OS'),
(6, 'One Size', 'White', '#f5f5f5', 19, 'RSR-WHT-TRK-OS'),
(7, 'One Size', 'Crimson', '#7a1f2b', 16, 'RSR-CRM-DAD-OS'),
(8, 'One Size', 'Olive', '#6b6f4a', 13, 'RSR-OLV-SNP-OS');
