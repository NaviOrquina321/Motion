-- Quick Buy Retail System - Upgrade SQL script to v2.0
-- Safe non-destructive upgrade: CREATE TABLE IF NOT EXISTS and ALTER TABLE safe queries.

CREATE DATABASE IF NOT EXISTS quick_buy_retail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quick_buy_retail;

-- 1. Activity Logs table
CREATE TABLE IF NOT EXISTS activity_logs (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NULL,
  username   VARCHAR(50) NOT NULL,
  action     VARCHAR(100) NOT NULL,
  details    TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_user (user_id),
  INDEX idx_logs_action (action),
  INDEX idx_logs_date (created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 2. Inventory Movements table
CREATE TABLE IF NOT EXISTS inventory_movements (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  type        ENUM('received', 'sold', 'damaged', 'expired', 'returned', 'adjustment') NOT NULL,
  quantity    INT NOT NULL,
  reason      VARCHAR(255) NULL,
  user_id     INT UNSIGNED NULL,
  username    VARCHAR(50) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_movements_product (product_id),
  INDEX idx_movements_type (type),
  INDEX idx_movements_date (created_at),
  CONSTRAINT fk_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_movements_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Discounts table
CREATE TABLE IF NOT EXISTS discounts (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  type        ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  value       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Settings table
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(50) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Initialize default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('tax_enabled', '0'),
('tax_rate', '12.00'),
('receipt_header', 'QUICK BUY RETAIL'),
('receipt_footer', 'Thank you for shopping!');

-- 5. Products table modifications (barcode, cost_price)
ALTER TABLE products ADD COLUMN IF NOT EXISTS barcode VARCHAR(50) NULL UNIQUE AFTER name;
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER price;

-- 6. Sales table modifications (discounts, tax, payment method, reference number, status, duplicate cart token)
ALTER TABLE sales ADD COLUMN IF NOT EXISTS discount_id INT UNSIGNED NULL AFTER customer_id;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER discount_id;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER discount_amount;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER tax_rate;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS payment_method ENUM('Cash', 'GCash', 'Maya') NOT NULL DEFAULT 'Cash' AFTER change_amount;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) NULL AFTER payment_method;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS payment_status ENUM('verified', 'pending', 'rejected') NOT NULL DEFAULT 'verified' AFTER reference_number;
ALTER TABLE sales ADD COLUMN IF NOT EXISTS cart_token VARCHAR(64) NULL UNIQUE AFTER payment_status;

-- 7. Returns and Return Items tables
CREATE TABLE IF NOT EXISTS returns (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id        INT UNSIGNED NOT NULL,
  user_id        INT UNSIGNED NULL,
  username       VARCHAR(50) NOT NULL,
  total_refund   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  refund_method  ENUM('Cash', 'GCash', 'Maya', 'Store Credit') NOT NULL DEFAULT 'Cash',
  reason         VARCHAR(255) NOT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_returns_sale (sale_id),
  INDEX idx_returns_date (created_at),
  CONSTRAINT fk_returns_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
  CONSTRAINT fk_returns_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS return_items (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  return_id      INT UNSIGNED NOT NULL,
  sale_item_id   INT UNSIGNED NOT NULL,
  product_id     INT UNSIGNED NULL,
  product_name   VARCHAR(120) NOT NULL,
  quantity       INT NOT NULL,
  refund_amount  DECIMAL(10,2) NOT NULL,
  restocked      TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_ret_items_return FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
  CONSTRAINT fk_ret_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. Suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL UNIQUE,
  contact_person VARCHAR(100) NULL,
  email          VARCHAR(120) NULL,
  phone          VARCHAR(20)  NULL,
  address        VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 9. Purchase Orders and Purchase Order Items tables
CREATE TABLE IF NOT EXISTS purchase_orders (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  po_number      VARCHAR(50) NOT NULL UNIQUE,
  supplier_id    INT UNSIGNED NOT NULL,
  user_id        INT UNSIGNED NULL,
  username       VARCHAR(50) NOT NULL,
  status         ENUM('Pending', 'Ordered', 'Partially Received', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  total_cost     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  notes          TEXT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_po_supplier (supplier_id),
  INDEX idx_po_status (status),
  CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_po_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order_items (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purchase_order_id  INT UNSIGNED NOT NULL,
  product_id         INT UNSIGNED NOT NULL,
  unit_cost          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ordered_quantity   INT NOT NULL,
  received_quantity  INT NOT NULL DEFAULT 0,
  subtotal           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_poi_order   FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_poi_product FOREIGN KEY (product_id)        REFERENCES products(id)        ON DELETE RESTRICT
) ENGINE=InnoDB;
