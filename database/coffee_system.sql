DROP DATABASE IF EXISTS coffee_system;
CREATE DATABASE IF NOT EXISTS coffee_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE coffee_system;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT(11) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `sku` VARCHAR(50) DEFAULT NULL,
  `size` VARCHAR(50) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_products_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(50) NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `status` ENUM('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `payment_method` ENUM('cash','card','online') DEFAULT NULL,
  `payment_status` ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_number` (`order_number`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_orders_user` (`user_id`),
  INDEX `idx_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `orders` ADD COLUMN `discount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `total_amount`;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity` INT(11) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `size` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT,
  `total_orders` INT(11) NOT NULL DEFAULT 0,
  `total_spent` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash','card','online') NOT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending','completed','refunded') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `inventory_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity` INT(11) NOT NULL,
  `action` ENUM('in','out','adjust') NOT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (full_name, email, password, role, is_active) VALUES
('Admin User', 'admin@coffee.com', '\$2y\$10\$X4z8q9QqPq9qPq9qPq9qPuOq9qPq9qPq9qPq9qPq9qPq9qPq', 'admin', 1),
('Staff User', 'staff@coffee.com', '\$2y\$10\$X4z8q9QqPq9qPq9qPq9qPuOq9qPq9qPq9qPq9qPq9qPq9qPq', 'staff', 1),
('Customer User', 'customer@coffee.com', '\$2y\$10\$X4z8q9QqPq9qPq9qPq9qPuOq9qPq9qPq9qPq9qPq9qPq9qPq', 'customer', 1);

INSERT INTO categories (name, description, image, is_active) VALUES
('Espresso', 'Rich and concentrated coffee', 'espresso.png', 1),
('Latte', 'Smooth coffee with steamed milk', 'latte.png', 1),
('Cappuccino', 'Foamy espresso with milk', 'cappuccino.png', 1),
('Cold Drinks', 'Iced and chilled beverages', 'cold-drinks.png', 1),
('Pastries', 'Sweet treats and snacks', 'pastries.png', 1),
('Tea', 'Premium teas', 'tea.png', 1),
('Sandwiches', 'Fresh sandwiches', 'sandwiches.png', 1);

INSERT INTO products (name, description, category_id, price, stock_quantity, sku, size) VALUES
('Espresso', 'Rich concentrated coffee shot', 1, 3.50, 100, 'ESP001', 'Small'),
('Latte', 'Smooth espresso with steamed milk', 2, 4.50, 120, 'LAT001', 'Medium'),
('Cappuccino', 'Classic cappuccino with foam', 3, 4.00, 100, 'CAP001', 'Medium'),
('Iced Latte', 'Chilled latte over ice', 4, 4.50, 110, 'ILD001', 'Large'),
('Americano', 'Espresso with hot water', 1, 3.50, 95, 'AME001', 'Small'),
('Mocha', 'Chocolate espresso drink', 2, 5.00, 85, 'MOC001', 'Large'),
('Cold Brew', 'Slow-steeped cold coffee', 4, 4.00, 75, 'CBR001', 'Large'),
('Matcha Latte', 'Green tea latte', 6, 5.00, 60, 'MAT001', 'Medium'),
('Blueberry Muffin', 'Fresh blueberry muffin', 5, 3.00, 50, 'MUF001', NULL),
('Croissant', 'Buttery French croissant', 5, 3.50, 40, 'CRO001', NULL),
('Club Sandwich', 'Triple decker sandwich', 7, 8.50, 30, 'SAN001', NULL),
('Chai Tea', 'Spiced chai tea', 6, 3.50, 80, 'CHA001', 'Medium');

INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Coffee POS', 'Site name'),
('site_email', 'support@coffee.com', 'Site email'),
('site_phone', '', 'Site phone'),
('site_address', '', 'Site address'),
 ('site_map_url', 'https://maps.app.goo.gl/wYzH23uL6NfmT8tH8', 'Google Maps link opened when the site address is clicked'),
('site_description', '', 'Site description'),
('default_payment_method', 'cash', 'Default payment method'),
 ('allow_card_payment', '1', 'Enable card payment'),
('allow_qr_payment', '1', 'Enable QR payment option'),
 ('payment_qr_image', '', 'Uploaded payment QR image file name'),
 ('payment_qr_payload', '', 'EMVCo / ABA payload decoded from the QR image - lets the on-screen QR carry the order total'),
('tax_rate', '0', 'Tax rate percentage'),
('currency', 'KHR', 'Currency code'),
('currency_symbol', '$', 'Currency symbol'),
('default_discount', '0', 'Default discount percentage');
