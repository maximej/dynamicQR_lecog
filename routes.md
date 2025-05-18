### QR.LECOG.FR Application Overview

QR.LECOG.FR is a web application for generating and managing both dynamic and static QR codes. It features a clean, responsive, and user-friendly interface built with Bootstrap 5 and classic PHP file-based routing. The application supports secure authentication, QR code generation, record management (CRUD), and an admin panel for managing QR code data and user sessions.

---

### Route Reference (File-based Routing)

| Path           | File             | Auth Required | Description                         | Root Only? |
|----------------|------------------|--------------|-------------------------------------|------------|
| /              | index.php        | Yes          | Dashboard/homepage                  | No         |
| /login.php     | login.php        | No           | Login form and handler              | No         |
| /logout.php    | logout.php       | Yes          | Logout logic                        | No         |
| /create.php    | create.php       | Yes          | QR code creation form               | No         |
| /generate.php  | generate.php     | Yes          | QR code generation handler          | No         |
| /renderer.php  | renderer.php     | Yes          | Shows last generated QR code        | No         |
| /list.php      | list.php         | Yes          | QR code list                        | No         |
| /userlist.php  | userlist.php     | Yes          | User management (add/edit/delete)   | Delete: Yes|
| /delete_qr.php | delete_qr.php    | Yes          | Delete QR code (POST)               | Yes        |
| /delete_user.php| delete_user.php | Yes          | Delete user (POST)                  | Yes        |
| /edit_user_password.php | edit_user_password.php | Yes | Change user password (POST) | No |
| /edit_redirect.php | edit_redirect.php | Yes      | Edit QR code redirect (POST)        | No         |
| /redirect.php  | redirect.php     | No           | Dynamic QR code redirection         | No         |

## Notes
- All files except `login.php` and `redirect.php` require the user to be logged in (session-based auth).
- Only the root user can delete QR codes or users.
- Static assets (CSS, images, QR code images) are in `public/` and `qrcodes/`.
- All CSS is in `public/index.css`.
- Logo and favicon are fully integrated for all platforms.

---

### Application Features

1. **QR Code Generation**:
   - Users can generate static or dynamic QR codes by providing input parameters such as size, margin, colors, and content (URL or text).
   - The generated QR codes are saved as PNG files in the `qrcodes/` directory.

2. **Dynamic QR Codes**:
   - Dynamic QR codes allow redirection based on QR code ID. This feature is useful for tracking and updating QR code destinations.

3. **User Management**:
   - The users can add, and edit users. Only root can delete users or change root password.
   - All users can change all passwords.

4. **Session Management**:
   - The application uses PHP sessions to store temporary data such as success/error messages and QR code paths.
   - Session data is cleared after being displayed to ensure a clean user experience.

5. **Responsive Design**:
   - The application is built with Bootstrap 5, ensuring a responsive and mobile-friendly interface.
