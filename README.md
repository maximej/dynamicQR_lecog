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

## Installation
1. Upload all files to your web server. Create your database. Change your credentials in `config/app.example.php` and rename it to `app.php`.
2. Visit `setup_database_admin.php` to initialize the database and create the root user.
3. Log in at `/login.php` with the root credentials : root / admin.
4. Use the navigation to create, view, and manage QR codes and users. Change root password, change/delete user also.
5. Delete file setup_database_admin.php

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

