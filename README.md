### QR.LECOG.FR 

## Overview
A production-ready QR code management system for LWS shared hosting, using classic PHP file-based routing (no frameworks required). Features a modern Bootstrap 5 UI, user management, and secure admin controls.

## Features
- User authentication (login/logout)
- User management (add, edit password, delete users; only root can delete)
- **Create and generate QR codes (static or dynamic)**
- List all QR codes
- View the last generated QR code
- **Dynamic QR code redirection via `redirect.php`**
- Optional custom dynamic URLs such as `https://qr.lecog.fr/pantin`
- Anonymous total scan count and last-viewed UTC timestamp
- Only root can delete QR codes or users
- Responsive, mobile-friendly interface
- [Custom 404 page](public/404.html)

## File Structure
- `index.php` — Dashboard/homepage (requires login)
- `login.php` — Login form and handler
- `logout.php` — Logout logic
- `create.php` — QR code creation form
- `generate.php` — QR code generation handler
- `renderer.php` — Shows last generated QR code
- `list.php` — QR code list
- `userlist.php` — User management (add/edit/delete users)
- `redirect.php` — Handles dynamic QR code redirection
- `public/` — Static assets (CSS, images, favicon)
- `qrcodes/` — QR code images
- `config/`, `controllers/` — Logic and configuration

## Anonymous scan statistics

Dynamic redirects store only a total counter and the latest scan timestamp. The application does not store visitor IP addresses, cookies, user agents, referrers, fingerprints, or unique visitor identifiers. Statistics and custom URL mappings are JSON files in `../private/qr-data`, outside the public document root; they are intentionally excluded from Git.

The database schema is unchanged. Existing dynamic links continue to work, and their counters begin at zero when this version is installed.

Run the storage tests with:

```bash
php tests/QrDataStoreTest.php
```

## Installation
1. Upload all files to your web server. Create your database. Change your credentials in `config/app.example.php` and rename it to `app.php`.
2. Create the existing `users` and `qrcodes` tables through a protected administration process; no public setup endpoint is included.
3. Create `../private/qr-data` outside the web root and grant the PHP worker read/write access only to that directory.
4. Log in at `/login.php` with an administrator account you created using a strong password.
5. Use the navigation to create, view, and manage QR codes and users.

## Requirements
- PHP 8.0+
- MySQL/MariaDB
- LWS-compatible shared hosting

## Security
- All routes except `login.php` and `redirect.php` require authentication.
- Only the root user can delete QR codes or users.
- Sessions are used for user authentication.

## Assets
- All CSS is in `public/index.css`.
- Logo: `public/COG_Logo_1000.jpg` (used in navbar and login page)
- Favicon:  modern formats in `public/fav/favicon/`

www.lecog.fr

---

