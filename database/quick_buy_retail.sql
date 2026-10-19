-- Quick Buy Retail System - database schema + sample data
-- Import with:  mysql -u root -p < database/quick_buy_retail.sql

CREATE DATABASE IF NOT EXISTS quick_buy_retail CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quick_buy_retail;

DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  full_name     VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  category_id    INT UNSIGNED NOT NULL,
  price          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity INT NOT NULL DEFAULT 0,
  reorder_level  INT NOT NULL DEFAULT 10,
  image          VARCHAR(255) NULL,
  date_added     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_products_name (name),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE customers (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(100) NOT NULL,
  contact_number VARCHAR(20)  NULL,
  email          VARCHAR(120) NULL,
  address        VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customers_name (full_name)
) ENGINE=InnoDB;

CREATE TABLE sales (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cashier_id    INT UNSIGNED NOT NULL,
  customer_id   INT UNSIGNED NULL,
  total_amount  DECIMAL(10,2) NOT NULL,
  payment       DECIMAL(10,2) NOT NULL,
  change_amount DECIMAL(10,2) NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sales_date (created_at),
  CONSTRAINT fk_sales_cashier  FOREIGN KEY (cashier_id)  REFERENCES users(id)     ON DELETE RESTRICT,
  CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sale_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id      INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  product_name VARCHAR(120) NOT NULL,      -- copied so history survives product deletion
  unit_price   DECIMAL(10,2) NOT NULL,
  quantity     INT NOT NULL,
  subtotal     DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_items_sale    FOREIGN KEY (sale_id)    REFERENCES sales(id)    ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Sample data ----------
-- Passwords: admin / admin123   and   cashier / cashier123  (bcrypt hashes)
INSERT INTO users (username, full_name, password_hash, role) VALUES
('admin',   'Store Administrator', '$2y$10$JICI1q0SvPWcVSrC6viijOK.aChJUkYkgouFPNM9ws0oIkYVRhZja', 'admin'),
('cashier', 'Default Cashier',     '$2y$10$wMCJQQKyADYA7vma9ohvZO/Yg7S46zTWCnk0OIeVeWmtWPoBjM/Vi', 'cashier');

INSERT INTO categories (name, description) VALUES
('Food', 'Snacks, canned goods and instant meals'),
('Drinks', 'Bottled drinks, juices and coffee'),
('School Supplies', 'Notebooks, pens and paper'),
('Personal Care', 'Soap, shampoo and hygiene items'),
('Household Items', 'Cleaning and home essentials');

INSERT INTO products (name, category_id, price, stock_quantity, reorder_level) VALUES
('Instant Noodles Cup',   1,  18.00, 80, 20),
('Canned Sardines',       1,  25.50, 40, 15),
('Biscuit Pack',          1,  12.00,  8, 15),
('Bottled Water 500ml',   2,  15.00, 120, 30),
('Orange Juice 1L',       2,  65.00, 25, 10),
('3-in-1 Coffee Sachet',  2,   8.00,  5, 20),
('Notebook (80 leaves)',  3,  32.00, 60, 20),
('Ballpen Blue',          3,   9.00, 150, 40),
('Bath Soap',             4,  28.00, 45, 15),
('Shampoo Sachet',        4,   7.00, 200, 50),
('Dishwashing Liquid',    5,  45.00, 30, 10),
('Laundry Detergent 1kg', 5,  98.00,  9, 10);

INSERT INTO customers (full_name, contact_number, email, address) VALUES
('Maria Santos',  '09171234567', 'maria@example.com', '12 Rizal St., Davao City'),
('Juan Dela Cruz','09281234567', 'juan@example.com',  '45 Bonifacio Ave., Davao City');
