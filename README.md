# AtharLink

A lightweight, self-hosted URL shortener and click tracker built with PHP and SQLite. Fast, dependency-free, and easy to set up on any shared hosting or VPS.

---

## Why AtharLink?

Most URL shorteners out there are either bloated with Node/Docker dependencies or locked behind monthly subscriptions. AtharLink is built to be simple: drop the files onto a server with PHP and SQLite, run the installer, and you have your own private tracking system running in under two minutes.

---

## Features

- **Fast Redirections:** A tiny routing micro-core (`r.php`) handles redirects directly with minimal overhead.
- **Detailed Click Analytics:** Track total clicks, unique visitors, referrers, device types, browsers, and country origins in real time.
- **Web-Based Installer:** Sets up the SQLite database and admin account on the first visit. No manual SQL imports or config editing needed.
- **Clean Dashboard:** Responsive dashboard with dark/light mode toggle and bilingual UI support (English & Arabic).
- **Embeddable Click Badges:** Includes an `embed.js` script so you can display live click counts on your own websites or blogs.
- **Link Controls:**
  - Custom slugs and optional titles.
  - Password-protected links.
  - Expiration dates and click limits.
  - Configurable redirect types (302 temporary, 301 permanent).
  - Automatic UTM parameter forwarding.
  - Built-in QR code generator.
- **Data Export:** Export your links and click logs to CSV or JSON anytime.
- **REST API:** Simple `/api/v1` endpoints with Bearer token authentication to manage links programmatically.
- **SQLite Database:** Zero database setup required. Runs on a single `.sqlite` file with WAL mode enabled for smooth concurrent access.

---

## Requirements

- **PHP 8.2** or higher
- **Web Server:** Apache (with `mod_rewrite` enabled), Nginx, or Caddy
- **PHP Extensions:**
  - `pdo_sqlite`
  - `curl`
  - `mbstring`
  - `openssl`
  - `json`
  - `filter`
- Write permissions on the `storage/` directory

---

## Installation

### 1. Upload files
Upload the repository files to your web root (such as `public_html/` or `/var/www/html/`):

```bash
git clone https://github.com/Tamoza4/AtharLink.git .
```

### 2. Set permissions
Make sure PHP can write to the `storage/` folder:

```bash
chmod -R 775 storage
# or on shared hosting if needed:
chmod -R 777 storage
```

### 3. Open in your browser
Navigate to your domain (e.g. `https://example.com/`). The setup wizard will automatically launch, check your server environment, and guide you through creating your admin account.

Once completed, you are redirected straight to your dashboard.

---

## Updating

### Via Git
If you installed using `git clone`, pull updates and run the migration script:

```bash
bash update.sh
```

### Manual update (FTP / ZIP)
1. Replace all files and folders **except** the `storage/` directory (your database lives there).
2. Run database migrations from the command line:
```bash
php bin/update.php
```

---

## Security

- Direct web access to the `storage/` folder and SQLite files is blocked via `.htaccess`.
- CSRF tokens are enforced on all form actions.
- Login rate-limiting locks accounts after repeated failed attempts to block brute-force attacks.
- Link passwords and sensitive tokens are encrypted using `AES-256-GCM`.
- Emergency password reset tool included via CLI (`bin/reset_password.php`).

---

## License

Released under the [MIT License](LICENSE).
Created by **Tamoza**.
