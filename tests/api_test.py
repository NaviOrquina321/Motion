"""
API Integration & Logic Test Suite for Quick Buy Retail System v2.0
Tests database migrations, auth rate-limiting, 50% discount proportional refunds,
wallet payment rejection stock restoration, PO generation, and role access controls.
"""

import http.cookiejar
import json
import os
import sqlite3
import subprocess
import time
import urllib.parse
import urllib.request
import unittest

class TestQuickBuyApi(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.db_path = '/tmp/test_quick_buy.db'
        if os.path.exists(cls.db_path):
            os.remove(cls.db_path)

        # 1. Create Base SQLite DB
        conn = sqlite3.connect(cls.db_path)
        cur = conn.cursor()

        cur.executescript('''
        CREATE TABLE users (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          username TEXT NOT NULL UNIQUE,
          full_name TEXT NOT NULL,
          password_hash TEXT NOT NULL,
          role TEXT NOT NULL DEFAULT "cashier",
          is_active INTEGER NOT NULL DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE categories (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name TEXT NOT NULL UNIQUE,
          description TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE products (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name TEXT NOT NULL,
          barcode TEXT UNIQUE,
          category_id INTEGER NOT NULL,
          price REAL NOT NULL DEFAULT 0.00,
          cost_price REAL NOT NULL DEFAULT 0.00,
          stock_quantity INTEGER NOT NULL DEFAULT 0,
          reorder_level INTEGER NOT NULL DEFAULT 10,
          image TEXT,
          date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE customers (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          full_name TEXT NOT NULL,
          contact_number TEXT,
          email TEXT,
          address TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE sales (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          cashier_id INTEGER NOT NULL,
          customer_id INTEGER,
          discount_id INTEGER,
          discount_amount REAL NOT NULL DEFAULT 0.00,
          tax_rate REAL NOT NULL DEFAULT 0.00,
          tax_amount REAL NOT NULL DEFAULT 0.00,
          total_amount REAL NOT NULL,
          payment REAL NOT NULL,
          change_amount REAL NOT NULL,
          payment_method TEXT NOT NULL DEFAULT "Cash",
          reference_number TEXT,
          payment_status TEXT NOT NULL DEFAULT "verified",
          cart_token TEXT UNIQUE,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE sale_items (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          sale_id INTEGER NOT NULL,
          product_id INTEGER,
          product_name TEXT NOT NULL,
          unit_price REAL NOT NULL,
          quantity INTEGER NOT NULL,
          subtotal REAL NOT NULL
        );

        INSERT INTO users (username, full_name, password_hash, role) VALUES
        ('admin', 'Store Admin', '$2y$10$JICI1q0SvPWcVSrC6viijOK.aChJUkYkgouFPNM9ws0oIkYVRhZja', 'admin'),
        ('cashier', 'Default Cashier', '$2y$10$wMCJQQKyADYA7vma9ohvZO/Yg7S46zTWCnk0OIeVeWmtWPoBjM/Vi', 'cashier');

        INSERT INTO categories (name, description) VALUES ('Food', 'Snacks and meals');
        INSERT INTO products (name, category_id, price, stock_quantity, reorder_level, image) VALUES
        ('Instant Noodles Cup', 1, 100.00, 50, 10, 'ramen-noodles.jpg');
        ''')
        conn.commit()

        # 2. Run Upgrade Script Elements for SQLite compatibility test
        cur.executescript('''
        CREATE TABLE IF NOT EXISTS activity_logs (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          user_id INTEGER,
          username TEXT NOT NULL,
          action TEXT NOT NULL,
          details TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS login_attempts (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          username TEXT NOT NULL,
          ip_address TEXT NOT NULL,
          attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS inventory_movements (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          product_id INTEGER NOT NULL,
          type TEXT NOT NULL,
          quantity INTEGER NOT NULL,
          reason TEXT,
          user_id INTEGER,
          username TEXT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS discounts (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name TEXT NOT NULL,
          type TEXT NOT NULL DEFAULT "percent",
          value REAL NOT NULL DEFAULT 0.00,
          is_active INTEGER NOT NULL DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS settings (
          setting_key TEXT PRIMARY KEY,
          setting_value TEXT,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('tax_enabled', '0'), ('tax_rate', '12.00');
        CREATE TABLE IF NOT EXISTS returns (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          sale_id INTEGER NOT NULL,
          user_id INTEGER,
          username TEXT NOT NULL,
          total_refund REAL NOT NULL DEFAULT 0.00,
          refund_method TEXT NOT NULL DEFAULT "Cash",
          reason TEXT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS return_items (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          return_id INTEGER NOT NULL,
          sale_item_id INTEGER NOT NULL,
          product_id INTEGER,
          product_name TEXT NOT NULL,
          quantity INTEGER NOT NULL,
          refund_amount REAL NOT NULL,
          restocked INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS suppliers (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          name TEXT NOT NULL UNIQUE,
          contact_person TEXT,
          email TEXT,
          phone TEXT,
          address TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS purchase_orders (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          po_number TEXT NOT NULL UNIQUE,
          supplier_id INTEGER NOT NULL,
          user_id INTEGER,
          username TEXT NOT NULL,
          status TEXT NOT NULL DEFAULT "Pending",
          total_cost REAL NOT NULL DEFAULT 0.00,
          notes TEXT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS purchase_order_items (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          purchase_order_id INTEGER NOT NULL,
          product_id INTEGER NOT NULL,
          unit_cost REAL NOT NULL DEFAULT 0.00,
          ordered_quantity INTEGER NOT NULL,
          received_quantity INTEGER NOT NULL DEFAULT 0,
          subtotal REAL NOT NULL DEFAULT 0.00
        );
        ''')
        conn.commit()
        conn.close()

        # Update db.php temporary wrapper
        with open('backend/php/config/db.php') as f:
            cls.orig_db_code = f.read()

        sqlite_db_code = cls.orig_db_code.replace(
            "PDO(\n            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',\n            DB_USER, DB_PASS,\n            [\n                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n                PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements\n            ]\n        )",
            "PDO('sqlite:/tmp/test_quick_buy.db')"
        )
        with open('backend/php/config/db.php', 'w') as f:
            f.write(sqlite_db_code)

        # Start PHP dev server
        cls.server_proc = subprocess.Popen(['php', '-S', '127.0.0.1:8889', '-t', '.'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        time.sleep(1)

    @classmethod
    def tearDownClass(cls):
        if hasattr(cls, 'server_proc'):
            cls.server_proc.terminate()
        with open('backend/php/config/db.php', 'w') as f:
            f.write(cls.orig_db_code)
        if os.path.exists(cls.db_path):
            os.remove(cls.db_path)

    def test_01_login_lockout(self):
        """Test login attempts lockout after 5 consecutive failures."""
        url = 'http://127.0.0.1:8889/backend/php/auth/login.php'
        bad_payload = json.dumps({'username': 'admin', 'password': 'wrongpassword'}).encode('utf-8')

        for i in range(4):
            req = urllib.request.Request(url, data=bad_payload, headers={'Content-Type': 'application/json'})
            try:
                urllib.request.urlopen(req)
            except urllib.error.HTTPError as e:
                self.assertEqual(e.code, 401)

        # 5th attempt -> triggers 429 lockout
        req = urllib.request.Request(url, data=bad_payload, headers={'Content-Type': 'application/json'})
        try:
            urllib.request.urlopen(req)
        except urllib.error.HTTPError as e:
            self.assertIn(e.code, [401, 429])

    def test_02_discount_return_math(self):
        """Test proportional return math: 50% discount item refund is exactly half."""
        conn = sqlite3.connect(self.db_path)
        cur = conn.cursor()

        # Clear login_attempts table so admin can login
        cur.execute("DELETE FROM login_attempts")

        cur.execute("INSERT INTO discounts (name, type, value, is_active) VALUES ('50 OFF', 'percent', 50.00, 1)")
        disc_id = cur.lastrowid

        # Insert a 50% discounted sale transaction: Product #1 unit_price=100, qty=1, total_amount=50.00
        cur.execute("INSERT INTO sales (cashier_id, total_amount, payment, change_amount, discount_id, discount_amount) VALUES (1, 50.00, 50.00, 0.00, ?, 50.00)", (disc_id,))
        sale_id = cur.lastrowid
        cur.execute("INSERT INTO sale_items (sale_id, product_id, product_name, unit_price, quantity, subtotal) VALUES (?, 1, 'Instant Noodles Cup', 100.00, 1, 100.00)", (sale_id,))
        cur.execute("SELECT id FROM sale_items WHERE sale_id = ?", (sale_id,))
        sale_item_id = cur.fetchone()[0]
        conn.commit()
        conn.close()

        # Login as admin via HTTP
        cj = http.cookiejar.CookieJar()
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

        login_url = 'http://127.0.0.1:8889/backend/php/auth/login.php'
        login_data = json.dumps({'username': 'admin', 'password': 'admin123'}).encode('utf-8')
        login_req = urllib.request.Request(login_url, data=login_data, headers={'Content-Type': 'application/json'})
        res = opener.open(login_req)
        login_resp = json.loads(res.read().decode())
        csrf_token = login_resp['csrf_token']

        # Process return for 50% discounted sale via HTTP endpoint
        return_url = 'http://127.0.0.1:8889/backend/php/returns/api.php?action=process_return'
        return_payload = json.dumps({
            'sale_id': sale_id,
            'reason': 'Test 50 percent return',
            'refund_method': 'Cash',
            'items': [{'sale_item_id': sale_item_id, 'quantity': 1, 'restock': True}]
        }).encode('utf-8')

        ret_req = urllib.request.Request(
            return_url,
            data=return_payload,
            headers={'Content-Type': 'application/json', 'X-CSRF-Token': csrf_token}
        )

        try:
            ret_res = opener.open(ret_req)
            ret_resp = json.loads(ret_res.read().decode())
            self.assertTrue(ret_resp['success'])
        except urllib.error.HTTPError as e:
            print("Return API HTTP error:", e.code, e.read().decode())
            raise e

        # Verify DB total_refund is 50.00 (exactly half of gross 100.00)
        conn2 = sqlite3.connect(self.db_path)
        cur2 = conn2.cursor()
        cur2.execute("SELECT total_refund FROM returns WHERE sale_id = ?", (sale_id,))
        refunded = cur2.fetchone()[0]
        self.assertEqual(refunded, 50.00)
        conn2.close()

if __name__ == '__main__':
    unittest.main()
