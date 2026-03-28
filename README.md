# Fleur C Print — Setup Guide
## Local Server (XAMPP / Laragon)

---

## 1. Install a Local Server

**Option A — Laragon (recommended for Windows)**
- Download from https://laragon.org → install → click **Start All**
- PHP 8.1+ and MySQL are included

**Option B — XAMPP**
- Download from https://apachefriends.org → install
- Open XAMPP Control Panel → Start **Apache** and **MySQL**

---

## 2. Copy the Project Files

**Laragon:**
```
C:\laragon\www\fleur-c-print\
```

**XAMPP:**
```
C:\xampp\htdocs\fleur-c-print\
```

Paste the entire `fleur-c-print/` folder there.

---

## 3. Create the Database

1. Open your browser → go to **http://localhost/phpmyadmin**
2. Click **Import** tab
3. Choose the file: `fleur-c-print/database.sql`
4. Click **Go**

This creates the `fleur_c_print` database with all tables and sample data.

---

## 4. Check the Database Config

Open `config/database.php` and confirm:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fleur_c_print');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP default is blank; Laragon default is also blank
```

If you set a MySQL root password, enter it in `DB_PASS`.

---

## 5. Create Required Folders

Make sure these folders exist and are **writable** by PHP:

```
fleur-c-print/uploads/
fleur-c-print/logs/
fleur-c-print/backups/
```

They are included in the zip. On Windows they are writable by default.
On Linux/Mac run:
```bash
chmod -R 750 uploads/ logs/ backups/
```

---

## 6. Open the App

Go to: **http://localhost/fleur-c-print/**

You will be redirected to the login page.

| Field    | Value      |
|----------|------------|
| Username | `admin`    |
| Password | `bulsu2026` |

---

## 7. Change Your Password (Important)

1. Open a browser and go to: **http://localhost/fleur-c-print/change-password.php**
2. Or run this one-liner in any PHP file temporarily:
   ```php
   echo password_hash('your-new-password', PASSWORD_BCRYPT);
   ```
3. Copy the output hash
4. Open `config/auth.php` and replace `AUTH_PASSWORD_HASH` with the new hash
5. Also change `AUTH_USERNAME` if you want

---

## 8. Daily Backup (Optional but Recommended)

**On Windows (Task Scheduler):**
- Use `mysqldump` via XAMPP's shell or set up a `.bat` equivalent manually

**On Linux/cPanel:**
```bash
chmod +x /path/to/fleur-c-print/backup.sh
crontab -e
# Add this line:
30 23 * * * /path/to/fleur-c-print/backup.sh
```

Backups are saved to `fleur-c-print/backups/` and auto-deleted after 30 days.

---

## File Structure

```
fleur-c-print/
├── index.php                  ← redirects to login
├── login.php                  ← login page
├── logout.php
├── database.sql               ← run once in phpMyAdmin
├── backup.sh                  ← daily cron backup script
├── contact.php
├── order.php
├── services.php
├── style.css
│
├── config/
│   ├── auth.php               ← username, password hash, session timeout
│   └── database.php           ← DB credentials
│
├── includes/
│   ├── functions.php          ← shared helpers (e(), csrf, upload, etc.)
│   ├── header.php             ← sidebar + CSS (included on every page)
│   └── footer.php
│
├── admin/
│   ├── auth_check.php         ← session guard (included on every admin page)
│   ├── index.php              ← Dashboard
│   ├── queue.php              ← Order Queue
│   ├── new-order.php          ← Create order + file upload
│   ├── order.php              ← View/edit order, payment, notifications
│   ├── customers.php          ← Customer list + unpaid filter
│   ├── services.php           ← Services & prices editor
│   ├── daily-summary.php      ← End-of-day report
│   └── file.php               ← Secure file download proxy
│
├── public/
│   ├── firebase.js
│   ├── footer.php
│   ├── header.php 
│
├── uploads/
│   ├── .htaccess              ← denies direct browser access
│   └── 2026-03/               ← auto-created per month/order
│
├── logs/
│   └── failed_logins.log      ← auto-created on first failed login
│
└── backups/                   ← DB + uploads archives go here
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Blank white page | Enable PHP error display: add `ini_set('display_errors',1);` to top of `login.php` temporarily |
| "Database connection error" | Check `config/database.php` credentials; confirm MySQL is running |
| Uploads not saving | Check that `uploads/` folder exists and is writable |
| Login always fails | Confirm `AUTH_PASSWORD_HASH` in `config/auth.php` is a valid bcrypt hash |
| "Invalid request" on forms | Session may have expired — log in again |
