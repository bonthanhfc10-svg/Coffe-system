ALTER TABLE `orders` ADD COLUMN `order_type` ENUM('dine_in','takeaway','delivery') NOT NULL DEFAULT 'dine_in' AFTER `payment_method`;
ALTER TABLE `orders` ADD COLUMN `table_number` INT(11) DEFAULT NULL AFTER `order_type`;
ALTER TABLE `orders` ADD COLUMN `delivery_customer` VARCHAR(100) DEFAULT NULL AFTER `table_number`;
ALTER TABLE `orders` ADD COLUMN `delivery_phone` VARCHAR(20) DEFAULT NULL AFTER `delivery_customer`;
ALTER TABLE `orders` ADD COLUMN `delivery_address` TEXT DEFAULT NULL AFTER `delivery_phone`;
ALTER TABLE `orders` ADD COLUMN `staff_id` INT(11) UNSIGNED DEFAULT NULL AFTER `delivery_address`;
ALTER TABLE `orders` ADD COLUMN `payment_status` ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending' AFTER `payment_method`;
ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('cash','aba','card','qr') DEFAULT NULL;
ALTER TABLE `payments` MODIFY COLUMN `payment_method` ENUM('cash','aba','card','qr') NOT NULL;

CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `type` ENUM('in','out','adjust') NOT NULL,
  `quantity` INT(11) NOT NULL,
  `previous_stock` INT(11) NOT NULL,
  `new_stock` INT(11) NOT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `note` TEXT,
  `created_by` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `payment_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref` VARCHAR(40) NOT NULL,
  `source` ENUM('pos','checkout') NOT NULL DEFAULT 'pos',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `status` ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `order_number` VARCHAR(40) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ref` (`ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
SELECT 'payment_qr_payload', '', 'EMVCo / ABA payload decoded from the QR image - lets the on-screen QR carry the order total', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `setting_key` = 'payment_qr_payload');

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
SELECT 'site_map_url', 'https://maps.app.goo.gl/wYzH23uL6NfmT8tH8', 'Google Maps link opened when the site address is clicked', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `setting_key` = 'site_map_url');
