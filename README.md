# daloRADIUS Guest Wi-Fi User Portal

This is a lightweight PHP-based portal for creating guest Wi-Fi users in a FreeRADIUS + daloRADIUS environment. It allows authenticated admins to create users with a phone number as a username, set session limits, and send login credentials via email.

## 🌐 Features

- Admin-protected panel
- Global phone number support (E.164 format: e.g., +905XXXXXXXXX)
- Auto-generated random passwords
- Session-Timeout control (via `radreply`)
- User info saved in `userinfo` table for daloRADIUS visibility
- Email delivery of login credentials
- MySQLi support (PDO optional)

## 🏗 Requirements

- PHP 7.2+
- FreeRADIUS + daloRADIUS installed
- MySQL/MariaDB
- A working email server (sendmail/postfix or SMTP setup)
- Composer (optional, if expanding to packages)

## ⚙️ Configuration

Edit the following values in `index.php`:

```php
define('ADMIN_PASSWORD', 'YourStrongPassword');  // Admin login
define('FROM_EMAIL', 'wifi@yourdomain.com');     // Outgoing email address
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'radius');
define('DB_PASSWORD', 'your-db-password');
define('DB_NAME', 'radius');
```

## ✅ Usage

1. Place `index.php` on a web server with PHP support.
2. Protect the directory if needed (basic auth, VPN, etc.).
3. Admin logs in with the configured password.
4. Fill out the guest creation form:
   - Phone number (e.g., +905XXXXXXXXX)
   - First name, last name
   - Email
   - Session duration (in days)
5. The portal:
   - Adds `radcheck` with `Cleartext-Password`
   - Adds `radreply` with `Session-Timeout`
   - Adds metadata to `userinfo` table
   - Sends email with credentials

## 🛡 Security Notes

- Always restrict admin access (VPN / HTTPS / IP Whitelist).
- Replace `FROM_EMAIL` with a real, SPF/DKIM-configured domain.
- Use a secure password for `ADMIN_PASSWORD`.