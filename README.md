# Quick Buy Retail System (Version 1 – about 50%)

A small retail system: login, dashboard, products, categories, point of sale, customers, sales history, low-stock alert, and a Python-powered sales report.

**Stack:** HTML/CSS/JavaScript · PHP 8 (PDO) · Python 3 · MySQL/MariaDB

---

## 1. Setup (5 steps)

**Requirements:** PHP 8.0+ (with `pdo_mysql` and `fileinfo`), MySQL or MariaDB, Python 3.8+. (XAMPP/Laragon include PHP + MySQL.)

1. **Import the database** (creates `quick_buy_retail` plus sample data):
   ```
   mysql -u root -p < database/quick_buy_retail.sql
   ```
   Or in phpMyAdmin: *Import* → choose `database/quick_buy_retail.sql`.
2. **Set DB login** in `backend/php/config/db.php` (`DB_USER`, `DB_PASS`).
   Set `PYTHON_BIN` to `python` on Windows, `python3` on Mac/Linux.
3. **Make sure** `backend/php/uploads/` is writable (product images are saved there).
4. **Run the PHP server** from the project root (the `quick-buy-retail` folder):
   ```
   php -S localhost:8000
   ```
5. **Open** <http://localhost:8000/frontend/index.html>

> Using XAMPP/Laragon instead? Put the whole `quick-buy-retail` folder in `htdocs`/`www` and open
> `http://localhost/quick-buy-retail/frontend/index.html`.

### Test accounts
| Role    | Username  | Password     | Lands on  |
|---------|-----------|--------------|-----------|
| Admin   | `admin`   | `admin123`   | Dashboard |
| Cashier | `cashier` | `cashier123` | POS       |

Change these passwords in **Users** after the first login.

### Running the Python component
PHP starts it for you when an admin opens **Reports**. To run it by hand:
```
echo '{"days":7,"rows":[]}' | python3 backend/python/reports/sales_summary.py
```
No extra Python packages are needed (standard library only).

---

## 2. How everything communicates

```
Browser (HTML/CSS/JS)
   │  fetch() – JSON or form data, session cookie sent automatically
   ▼
PHP API files (backend/php/*/api.php) ── PDO prepared statements ──► MySQL (quick_buy_retail)
   │
   │  Reports only: PHP reads sales rows, sends them as JSON to Python's stdin
   ▼
Python (backend/python/reports/sales_summary.py)
   │  calculates totals, top products, daily revenue → prints JSON to stdout
   ▼
PHP returns that JSON to the browser → Reports page draws it
```

* **Frontend → PHP:** each page calls `api()` (in `js/common.js`), e.g. `../backend/php/products/api.php?action=list`. PHP answers with JSON: `{success: true, ...}` or `{success: false, message: "..."}`.
* **PHP → MySQL:** only through `config/db.php` (PDO + prepared statements).
* **PHP ↔ Python:** loosely coupled. PHP runs `sales_summary.py` with `proc_open`, writes JSON to its stdin, reads JSON from stdout. Python has **no database access** and knows nothing about PHP, so it can be tested alone and extended later (forecasting, exports).
* **Login state:** a PHP session cookie. Every API file calls `require_login()` / `require_role('admin')`, so permissions are enforced on the server even if the JavaScript is edited.

---

## 3. File guide (name · purpose · where it goes)

### database/
| File | Purpose |
|---|---|
| `database/quick_buy_retail.sql` | Creates the DB and tables `users`, `categories`, `products`, `customers`, `sales`, `sale_items` (primary + foreign keys), sample data, test users. |

### backend/php/
| File | Purpose |
|---|---|
| `config/db.php` | DB credentials, `PYTHON_BIN`, PDO connection `db()`. |
| `config/helpers.php` | Session start, JSON helpers, `require_login()`, `require_role()`, input cleaning, error handler. |
| `auth/login.php` | Validates credentials, starts session, returns redirect by role. |
| `auth/session.php` | Returns current user (pages call it to confirm login). |
| `auth/logout.php` | Destroys the session. |
| `dashboard/stats.php` | Card numbers + Low Stock Alert (stock ≤ reorder level). |
| `products/api.php` | List/search/filter (all); add/edit/delete + image upload (admin). |
| `categories/api.php` | List (all); add/edit/delete (admin). Blocks deleting a category that still has products. |
| `customers/api.php` | List/search, add, edit, delete. |
| `sales/api.php` | `checkout` (transaction: checks stock, saves sale, deducts inventory), `history`, `receipt`. |
| `reports/summary.php` | Bridge: fetches sales rows, runs Python, returns the result. |
| `users/api.php` | User accounts (admin). |
| `uploads/` | Product images (`.htaccess` blocks script execution on Apache). |

### backend/python/
| File | Purpose |
|---|---|
| `reports/sales_summary.py` | Entry point: JSON in (stdin or file argument), summary JSON out. |
| `analytics/stats.py` | The calculations: revenue, transactions, average sale, items sold, top 5 products, revenue by category, daily series. |

### frontend/
| File | Purpose |
|---|---|
| `index.html` + `js/login.js` | Login: validation, show/hide password, error message. |
| `dashboard.html` + `js/dashboard.js` | Stat cards and Low Stock Alert. |
| `products.html` + `js/products.js` | Product table, search, category filter, add/edit/delete, image upload. |
| `categories.html` + `js/categories.js` | Category management. |
| `pos.html` + `js/pos.js` | Product grid, cart, quantity buttons, totals, payment, change, receipt. |
| `customers.html` + `js/customers.js` | Customer management with search. |
| `sales.html` + `js/sales.js` | Sales history: search, date filter, receipt view. |
| `reports.html` + `js/reports.js` | Python-generated summary (7/30/90 days). |
| `users.html` + `js/users.js` | Account management (admin). |
| `js/common.js` | Shared: `api()` helper, role-based sidebar, toasts, modals, receipt. |
| `css/style.css` | All styling; responsive (sidebar becomes a ☰ menu under 800px). |

---

## 4. Security included

* `password_hash()` / `password_verify()` (bcrypt).
* PHP sessions (HttpOnly, SameSite=Lax, ID regenerated on login).
* Role-based access enforced server-side; cashiers see only their own sales history.
* Prepared statements everywhere (injection test string returns nothing).
* Server-side validation on all forms plus basic browser-side checks.
* Prices and totals are calculated on the server from the database.
* Stock rows are locked during checkout so two cashiers can't oversell.
* Uploads: type checked by content, size-limited, random file names.
* Output escaped in the browser to prevent XSS.

**Before going live:** use HTTPS, a real DB password and non-root DB user, change default passwords, add CSRF tokens.

---

## 5. Not included yet (the other ~50%)

Inventory reports, advanced analytics, supplier management, purchase orders, barcode scanning, discounts/tax, refunds, customer purchase history, activity logs, password reset, CSV/PDF export.
