# AtharLink (أثر لينك) 🔗⚡
### High-Performance Self-Hosted URL Shortener & Real-Time Click Analytics Engine
**محرك تتبع وتقصير الروابط الذاتي عالي السرعة وتحليلات النقرات المباشرة**

---

## 🌟 Features / المميزات

- **⚡ Sub-Millisecond Redirection (سرعة توجيه فائقة):** Micro-core routing architecture via `r.php` with zero framework bloat.
- **🛠️ Zero-Friction Web Installer (مثبت ذاتي فوري):** First-time setup wizard initializes SQLite 3 database and admin credentials in seconds.
- **📊 Real-Time Analytics Dashboard (لوحة تحليلات تفاعلية):**
  - KPI overview: Total Clicks, Unique Clicks, Conversion Rate, Active Links, Peak Hours.
  - Interactive charts (Timeline Growth, Referrers, Devices, Browsers, Top Countries).
  - Live visitor activity feed stream with GDPR-compliant IP anonymization.
  - Top Performing Links Leaderboard with percentage distribution.
- **🎨 Premium UI System (تصميم احترافي فائق الدقة):**
  - Built on the **Tamoza UI** design standard: strict 8pt grid, soft glassmorphism, responsive centered layout.
  - Seamless Dark & Light themes with persistent state.
  - Pixel-perfect Lucide Vector SVG icons.
- **📸 Infographic Report Card Export (تصدير التقارير كصور):** Generates high-resolution Retina 2x graphical report cards downloadable as PNG or copied directly to clipboard.
- **🔒 Advanced Link Protection (حماية متقدمة للروابط):**
  - Custom password-protected short links with brute-force lockout protection.
  - Expiration dates and maximum click limits.
  - Automatic UTM parameter forwarding.
  - Instant dynamic QR Code generation with one-click PNG download.
- **🌐 Dual Language Support (دعم ثنائي كامل للغات):** Native Arabic (RTL) and English (LTR) with instant on-the-fly toggling.
- **🔑 Developer REST API (واجهة برمجية متكاملة):** Complete API (`/api/v1`) with Bearer token authentication to programmatically create, manage, and fetch link analytics.
- **🛡️ Emergency Recovery Key (استرداد الطوارئ):** Built-in cryptographic recovery system to safely reset the admin password without requiring SMTP mail servers.
- **💾 Database Backup & Restore (نسخ احتياطي واستعادة):** Download or restore instant `.sqlite` snapshots directly from the dashboard.

---

## 📋 System Requirements / متطلبات التشغيل

- **PHP:** 8.2 or higher (PHP 8.2+).
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx / Caddy.
- **PHP Extensions:**
  - `pdo` & `pdo_sqlite`
  - `curl`
  - `mbstring`
  - `openssl`
  - `json`
  - `filter`
- **File Permissions:** Write permissions on the `storage/` directory (`chmod 775 storage` or `chmod 777 storage` on Linux).

---

## 🚀 Quick Installation / خطوات التثبيت السريع

### 1. Upload Files / رفع الملفات
Clone this repository or upload the contents directly to your web server's document root (e.g., `public_html/` or `/var/www/html/`):

```bash
git clone https://github.com/your-username/AtharLink.git .
```

### 2. Set Storage Permissions / ضبط صلاحيات التخزين
Ensure the `storage/` folder is writable by the web server:

```bash
chmod -R 775 storage
# or for shared hosting environments:
chmod -R 777 storage
```

### 3. Launch Web Setup Wizard / بدء التثبيت الذاتي
1. Open your browser and navigate to your domain (e.g. `https://yourdomain.com`).
2. The automatic installer will detect the fresh installation and display the environment check.
3. Enter your desired **Admin Username** and **Password** (minimum 8 characters).
4. Click **"Start Setup & Launch Platform" (بدء التهيئة والتشغيل الفوري)**.
5. Your platform is immediately initialized, secured, and ready for production!

---

## 🔄 Updating AtharLink / كيفية تحديث المنصة

### Option A: Using Git (الأسهل والأسرع عبر Git)
If you installed AtharLink via `git clone`, run the automated update script:

```bash
bash update.sh
```
*This command automatically pulls the latest changes from Git, creates an automatic backup of your database, applies any new schema updates, and verifies system integrity.*

### Option B: Manual File Update (التحديث اليدوي)
If you update files manually via FTP, cPanel, or ZIP:
1. Upload and overwrite all files **EXCEPT** the `storage/` directory (your database and keys are inside `storage/` and must never be deleted).
2. Run the updater CLI tool to update the database schema:
```bash
php bin/update.php
```

---

## 📁 Directory Structure / هيكلية المجلدات

```text
AtharLink/
├── .htaccess              # Apache URL rewriting & security rules
├── .gitignore             # Git ignore rules for runtime files
├── README.md              # Project documentation
├── LICENSE                # Open-source license (MIT)
├── embed.js               # Public lightweight click counter embed script
├── index.php              # Application front controller & dashboard router
├── r.php                  # Ultra-fast redirection endpoint
├── api/
│   └── v1/
│       └── index.php      # RESTful API router (JSON endpoints)
├── assets/
│   ├── css/
│   │   ├── app.css        # Core Tamoza UI styling & responsive theme variables
│   │   └── bootstrap.min.css
│   └── js/
│       ├── app.js         # Interactive application behaviors & live checks
│       ├── report-card.js # Retina 2x Canvas infographic generator
│       ├── chart.umd.min.js
│       └── qrcode.min.js
├── bin/
│   └── reset_password.php # CLI emergency password reset tool
├── config/
│   └── config.php         # Application constants & environment settings
├── src/
│   ├── Auth.php           # Authentication, brute-force rate limiter & sessions
│   ├── Database.php       # SQLite connection manager, schema & WAL mode
│   ├── Helpers.php        # Utility functions, encryption, XSS & CSRF filters
│   ├── I18n.php           # Dual-language localization dictionary (AR/EN)
│   ├── Icon.php           # Lightweight Lucide Vector SVG icon engine
│   ├── LinkManager.php    # CRUD operations & analytics calculation engine
│   └── Tracker.php        # Redirection handler, bot filter & click logger
├── storage/
│   ├── .htaccess          # Strict web access block for database & secrets
│   └── .gitkeep           # Preserves empty storage folder in git
└── views/
    ├── auth/              # Sign-in & emergency recovery modals
    ├── dashboard/         # Overview, Links, Stats, and Settings views
    ├── install/           # Zero-friction installation view
    └── layout/            # Floating navigation header & footer
```

---

## 🔒 Security Best Practices / معايير الأمان المطبقة

- **SQLite WAL Mode:** Write-Ahead Logging is enabled by default to prevent database locks under concurrent traffic.
- **Direct Database Shield:** Direct HTTP access to `storage/` and `.sqlite` files is completely forbidden via Apache `.htaccess` directives.
- **CSRF Tokens:** All destructive and configuration operations require validated CSRF tokens.
- **Rate Limiting:** IP and account brute-force protection locks accounts for 15 minutes after 5 consecutive failed attempts.
- **Encrypted Storage:** Sensitive link passwords and API secrets are encrypted using authenticated `AES-256-GCM`.
- **GDPR Anonymization:** Raw visitor IP addresses are never saved; they are hashed via SHA-256 with a rolling daily salt.

---

## 📄 License / الترخيص

This project is open-source software licensed under the [MIT License](LICENSE).
Developed with ❤️ by **Tamoza**.
