# CampusConnect Pro — Installation (XAMPP)

## 1. Copy the project
Copy the `CampusConnect-Pro` folder to `C:\xampp\htdocs\`. Keep the folder name, or change `BASE_URL` in `config/app.php`.

## 2. Create the database
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open http://localhost/phpmyadmin → **Import** → choose `database/campusconnect.sql` → **Go**.
3. If your MySQL has a password, edit `config/database.php`.

## 3. Create the first Admin (one time)
1. Open http://localhost/CampusConnect-Pro/setup-admin.php
2. Enter the admin email and a password (10+ characters, letters and numbers).
3. **Delete `setup-admin.php`** afterwards. It refuses to run again once an admin exists, but delete it anyway.
No admin credentials are stored in the source code.

## 4. First use
1. Log in at `/auth/admin-login.php`.
2. **Clubs → Register a new club** (club name, official email, temporary password).
3. Log in as the club at `/auth/club-login.php`. Add members, then open **Executives** and mark at least one person "can create posts/events".
4. Students register at `/auth/student-register.php`.

## 5. Email (PHPMailer) — optional
Email is **off by default** and the app never claims an email was sent unless it really was.
1. Download PHPMailer and place its `src` folder at `CampusConnect-Pro/lib/PHPMailer/src/` (or run `composer require phpmailer/phpmailer` in the project folder).
2. Edit `config/mail.php`: set `MAIL_ENABLED` to `true` and fill SMTP host, port, user and an **app password** (for Gmail: enable 2-step verification, then create an App Password).
3. Test by registering a student. If the mail fails you will still see the verification link while `APP_DEBUG` is `true`.
Note: club "official email verification" here means the admin registers the address; OAuth mailbox verification is **not** implemented.

## 6. Production checklist
- Set `APP_DEBUG` to `false` in `config/app.php`.
- Use HTTPS (needed for camera QR scanning outside localhost).
- Change the MySQL root password and use a dedicated DB user.
- Delete `setup-admin.php`.
- Make sure `assets/uploads/` is writable but cannot execute PHP (an `.htaccess` is included).

## Requirements
PHP 8.0+ (uses `str_ends_with`, arrow functions), MySQL/MariaDB with InnoDB, `fileinfo` and `pdo_mysql` extensions (enabled in XAMPP by default). Bootstrap, Chart.js, QR libraries load from CDNs, so an internet connection is required in the browser.


## v2 additions
- Existing database: import `database/upgrade_v2.sql` (adds `event_feedback`). Fresh install: `campusconnect.sql` already includes it.
- Demo data (optional, for screenshots): open `database/seed-demo.php` once on localhost, then delete it. Logins: `demo.club@campus.test`, `student1@campus.test` to `student4@campus.test`, password `Demo@1234`.
