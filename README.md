# Quick Buy Retail System (Version 2.0 - Complete Edition)

A complete Loyverse-inspired retail and Back Office system featuring Point of Sale (POS), Inventory Control, Suppliers, Purchase Orders, Returns & Refunds, Customer History, Discounts, Tax Configuration, Activity Logging, and Python-powered Analytics.

**Stack:** HTML/CSS/JavaScript · PHP 8 (PDO) · Python 3 (Standard Library) · MySQL/MariaDB

---

## 1. Setup & Installation

### Requirements
- **PHP 8.0+** (with `pdo_mysql` and `fileinfo` extensions enabled)
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Python 3.8+** (Standard Library only - no external pip packages required)

### Fresh Installation (3 steps)
1. **Import the base database schema and sample data:**
   ```bash
   mysql -u root -p < database/quick_buy_retail.sql
   ```
2. **Apply the v2.0 upgrade schema:**
   ```bash
   mysql -u root -p quick_buy_retail < database/upgrade_v2.sql
   ```
3. **Configure DB credentials and Python binary** in `backend/php/config/db.php`:
   - Set `DB_USER` and `DB_PASS` to match your MySQL database server.
   - Set `PYTHON_BIN` to `'python'` on Windows or `'python3'` on Mac/Linux.
4. **Run local PHP server:**
   ```bash
   php -S localhost:8000
   ```
5. **Open Browser:** Navigate to <http://localhost:8000/frontend/index.html>

---

## 2. Upgrading an Existing Database

> ⚠️ **IMPORTANT WARNING:** Always back up your database before running any upgrade script!
>
> `mysqldump -u root -p quick_buy_retail > backup_before_v2_upgrade.sql`

To upgrade an existing Quick Buy Retail database without losing any users, products, customers, or sales data:

```bash
mysql -u root -p quick_buy_retail < database/upgrade_v2.sql
```

The `database/upgrade_v2.sql` script uses idempotent `CREATE TABLE IF NOT EXISTS` and `ALTER TABLE ADD COLUMN IF NOT EXISTS` statements. It is completely non-destructive and safe to run multiple times.

---

## 3. Test Accounts

| Role | Username | Password | Default Landing Page | Access Scope |
|---|---|---|---|---|
| **Admin** | `admin` | `admin123` | Dashboard | Full Back Office access (All pages, settings, reports, POs, logs, user management) |
| **Cashier** | `cashier` | `cashier123` | POS Terminal | POS Terminal, Sales History (own receipts only), Returns, Customers |

---

## 4. Financial Calculations & Profit Formula

- **Currency:** All monetary values are calculated on the server using 2 decimal places (`DECIMAL(10,2)`) and rendered as `₱`.
- **Checkout Total:** `Total = MAX(0, Subtotal - Discount) + Tax`
- **Gross Profit Formula:**
  $$\text{Gross Profit} = \text{Net Revenue} - \text{Cost of Goods Sold (COGS)}$$
  Where COGS is calculated based on each product's `cost_price` multiplied by the sold quantity.
- **Stock Valuation:**
  - **Cost Valuation:** $\sum (\text{stock\_quantity} \times \text{cost\_price})$
  - **Retail Valuation:** $\sum (\text{stock\_quantity} \times \text{selling\_price})$

---

## 5. Security & Technical Architecture

- **Session Security:** Cookies configured with `HttpOnly` and `SameSite=Lax`. Session ID regenerated on login.
- **Rate Limiting:** Failed logins track counter in `$_SESSION`. 5 consecutive failures lock the login for 5 minutes (HTTP 429).
- **CSRF Protection:** Secure 32-byte cryptographic token generated per session, sent via `X-CSRF-Token` header / payload on all POST requests, and validated in `helpers.php`.
- **Deadlock Prevention:** Cart items locked in database transactions using `SELECT ... FOR UPDATE` ordered consistently by product ID using `ksort()`.
- **Cart Idempotency:** POS checkout sends a unique `cart_token` per transaction to prevent double-charging on duplicate button clicks.
- **Receipt Access Control:** Non-admin cashiers are strictly limited to viewing receipts where `cashier_id` matches their own logged-in user ID.
- **IPC Python Analytics:** PHP executes Python analytics via standard input/output (`proc_open`). Python has **zero database access** and receives no database credentials.

---

## 6. Test Verification Matrix

| Test Scenario / Requirement | Status | Verification Notes |
|---|---|---|
| Admin & Cashier Logins | **PASSED** | Valid credentials log in; invalid credentials increase rate limiter |
| Rate Limiting (5 failed attempts) | **PASSED** | 5th failure triggers 5-minute lockout (HTTP 429) |
| Cashier Access Control (403 on Admin endpoints) | **PASSED** | Server enforces `require_role('admin')` on Users, POs, Suppliers, Adjustments, Reports, Settings, Activity Logs, and Payment Verification |
| CSRF Token Enforcement | **PASSED** | POST requests without valid `X-CSRF-Token` fail with 403 Forbidden across all endpoints including multipart form uploads |
| POS Checkout & Duplicate Prevention | **PASSED** | `cart_token` prevents duplicate sales; row locking prevents negative stock |
| Returns & Refund Constraints | **PASSED** | Returns validated against `sold_quantity - already_returned`. Restocking updates stock and writes `returned` movement |
| Purchase Order Receiving | **PASSED** | PO receiving tracks outstanding quantities; cannot receive more than ordered; updates product stock and cost price |
| Python Analytics & Exports | **PASSED** | Python standard library script parses JSON from stdin; returns net sales, profit, stock valuation; CSV export generates clean files |
| Database Upgrade Script Safety | **PASSED** | `upgrade_v2.sql` ran cleanly without data loss; safe to execute multiple times |

---

## 7. Known Limitations & Design Notes

1. **GCash & Maya Payment Gateway:** Digital payments (GCash and Maya) record customer reference numbers and store payment status as `pending`. Store administrators manually verify payment receipts in **Sales History**. There is no live third-party payment gateway integration included. Comments in `backend/php/sales/api.php` indicate where live API webhooks should be connected.
2. **Barcode Scanner Compatibility:** The POS search input automatically captures standard USB or HID Bluetooth barcode scanner input (which emulates keyboard typing followed by an `Enter` keypress).

---

## 8. Complete File Guide

- `README.md` - Setup, upgrade instructions, test matrix, security notes, and project documentation.
- `.gitignore` - Build and runtime artifact exclusion rules.
- `database/quick_buy_retail.sql` - Base database schema and seed data.
- `database/upgrade_v2.sql` - Safe non-destructive v2.0 upgrade script.
- `backend/php/config/db.php` - Database credentials and Python binary configuration.
- `backend/php/config/helpers.php` - Shared helpers, session management, CSRF validation, and activity logging.
- `backend/php/auth/login.php` - Login authentication and rate limiting.
- `backend/php/auth/session.php` - Active user session and CSRF token endpoint.
- `backend/php/auth/logout.php` - Session destruction.
- `backend/php/activity/api.php` - Admin activity logging API.
- `backend/php/categories/api.php` - Category management API.
- `backend/php/customers/api.php` - Customer management and purchase history API.
- `backend/php/dashboard/stats.php` - Dashboard metrics and top/recent/low stock alerts API.
- `backend/php/discounts/api.php` - Promotional discount configuration API.
- `backend/php/inventory/api.php` - Inventory stock overview, adjustments, and movements API.
- `backend/php/products/api.php` - Product catalog, barcode, cost price, and image upload API.
- `backend/php/purchase_orders/api.php` - Purchase orders creation, receiving, and restocking needs API.
- `backend/php/reports/summary.php` - Report aggregator executing Python analytics service.
- `backend/php/reports/export.php` - Admin CSV report export endpoint.
- `backend/php/returns/api.php` - Order return lookup and refund processing API.
- `backend/php/sales/api.php` - POS checkout, transaction history, and payment verification API.
- `backend/php/settings/api.php` - Store tax and receipt header/footer settings API.
- `backend/php/suppliers/api.php` - Supplier management API.
- `backend/php/users/api.php` - User accounts management API.
- `backend/python/reports/sales_summary.py` - Python analytics entry point reading JSON stdin.
- `backend/python/analytics/stats.py` - Pure Python analytics calculation engine.
- `frontend/css/style.css` - Responsive Loyverse-inspired blue theme stylesheet.
- `frontend/js/common.js` - Shared frontend utilities, grouped Loyverse sidebar, modal/toast/receipt handlers.
- `frontend/index.html` + `frontend/js/login.js` - Sign in page.
- `frontend/dashboard.html` + `frontend/js/dashboard.js` - Back Office dashboard.
- `frontend/pos.html` + `frontend/js/pos.js` - Fast cashier POS terminal with barcode scanning.
- `frontend/products.html` + `frontend/js/products.js` - Product catalog management.
- `frontend/categories.html` + `frontend/js/categories.js` - Category management.
- `frontend/discounts.html` + `frontend/js/discounts.js` - Discount configuration.
- `frontend/stock_overview.html` + `frontend/js/stock_overview.js` - Inventory stock level overview & valuation.
- `frontend/stock_adjustments.html` + `frontend/js/stock_adjustments.js` - Stock adjustment form.
- `frontend/movements.html` + `frontend/js/movements.js` - Inventory movement audit trail.
- `frontend/suppliers.html` + `frontend/js/suppliers.js` - Supplier management.
- `frontend/purchase_orders.html` + `frontend/js/purchase_orders.js` - Purchase order workflow & stock receiving.
- `frontend/customers.html` + `frontend/js/customers.js` - Customer directory & purchase history.
- `frontend/sales.html` + `frontend/js/sales.js` - Sales transaction history.
- `frontend/returns.html` + `frontend/js/returns.js` - Returns lookup and refund processing.
- `frontend/activity.html` + `frontend/js/activity.js` - System activity audit log.
- `frontend/reports.html` + `frontend/js/reports.js` - Sales, profit, valuation, and analytics reports.
- `frontend/settings.html` + `frontend/js/settings.js` - Store tax and receipt settings.
- `frontend/users.html` + `frontend/js/users.js` - User management.
