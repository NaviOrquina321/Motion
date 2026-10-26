# Deploying CloudVault on InfinityFree (InfinityFree.com)

This guide provides step-by-step instructions to deploy **CloudVault** on [InfinityFree](https://www.infinityfree.com/) free PHP & MySQL hosting.

---

## 📋 Prerequisites & Requirements
- An active account on **InfinityFree**.
- A registered domain or free subdomain (e.g., `yourproject.infinityfreeapp.com` or `yourproject.epizy.com`).
- FTP credentials or access to InfinityFree Online File Manager.
- phpMyAdmin access on InfinityFree cPanel.

---

## 🚀 Step 1: Create MySQL Database on InfinityFree
1. Log in to your **InfinityFree Control Panel (cPanel)**.
2. Go to **MySQL Databases**.
3. Create a new database name (e.g., `cloudvault`). InfinityFree will generate a full database name like `if0_38000000_cloudvault`.
4. Note down your MySQL details provided on the page:
   - **MySQL Hostname**: e.g., `sql123.infinityfree.com`
   - **MySQL Database Name**: e.g., `if0_38000000_cloudvault`
   - **MySQL Username**: e.g., `if0_38000000`
   - **MySQL Password**: Your vPanel password (visible under *Account Details*).

---

## 🗄️ Step 2: Import Database Schema
1. In InfinityFree Control Panel, click **phpMyAdmin** next to your created database.
2. Select your newly created database in the left sidebar.
3. Click the **Import** tab at the top.
4. Choose the `database/cloudvault_db.sql` file from this project repository.
5. Click **Go** at the bottom to execute and import all database tables.

---

## ⚙️ Step 3: Configure Database Connection in `config/database.php`
1. Open `config/database.php` in a text editor or InfinityFree File Manager.
2. Update the credentials with your InfinityFree MySQL details:

```php
define('DB_HOST', 'sql123.infinityfree.com'); // Replace with your MySQL Hostname
define('DB_PORT', '3306');
define('DB_NAME', 'if0_38000000_cloudvault');  // Replace with your MySQL Database Name
define('DB_USER', 'if0_38000000');             // Replace with your MySQL Username
define('DB_PASS', 'YourVpanelPassword');        // Replace with your MySQL Password
```

---

## 📁 Step 4: Upload CloudVault Files
1. Open the InfinityFree **Online File Manager** or connect via FTP using **FileZilla**:
   - Host: `ftpupload.net`
   - Username: Your FTP username (e.g., `if0_38000000`)
   - Password: Your vPanel password
2. Navigate to your website root directory: `htdocs/`.
3. Upload all CloudVault project files and folders into `htdocs/`:
   ```text
   htdocs/
   ├── .htaccess
   ├── public/
   │   ├── index.php
   │   ├── login.php
   │   ├── register.php
   │   ├── install.php
   │   ├── dashboard.php
   │   ├── upload.php
   │   ├── projects.php
   │   └── assets/
   ├── app/
   ├── config/
   │   └── database.php
   ├── database/
   └── storage/
   ```

---

## 🔒 Step 5: Directory Security & Route Protection
The repository includes pre-configured `.htaccess` files:
- **Root `.htaccess`**: Automatically routes web traffic into `public/`.
- **Private `.htaccess` (`storage/`, `config/`, `app/`, `database/`)**: Blocks direct public browser access (`Deny from all`) so project uploads and secrets cannot be downloaded directly via URL.

---

## 🛡️ Step 6: Complete First-Run Administrator Setup
1. Open your web browser and visit your InfinityFree domain:
   `http://yourdomain.infinityfreeapp.com` or `http://yourdomain.epizy.com`
2. You will be automatically redirected to the **First-Run Administrator Setup Page** (`install.php`).
3. Fill out the administrator account creation form (Full Name, Username, Email, Password).
4. Click **Complete Installation**.
5. Log in with your new administrator credentials.

---

## ✅ Deployment Completed!
Your CloudVault instance is now live on InfinityFree! You can upload project folders/archives, pair database backups, generate setup-assisted installation packages, and manage your development repositories.
