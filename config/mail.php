<?php
// SMTP settings for PHPMailer. Keep MAIL_ENABLED false until you have real credentials and PHPMailer installed.
// The app never claims an email was sent unless MAIL_ENABLED is true and PHPMailer actually succeeded.
define('MAIL_ENABLED', false);
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'your-address@gmail.com');
define('MAIL_PASS', 'your-app-password');
define('MAIL_FROM', 'no-reply@campusconnect.local');
define('MAIL_FROM_NAME', 'CampusConnect Pro');
