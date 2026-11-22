<?php
define('APP_NAME', 'CampusConnect Pro');
define('BASE_URL', '/CampusConnect-Pro');   // change if you rename the folder in htdocs
define('APP_DEBUG', true);                  // set false in production (hides verification links and errors)
define('ADMIN_SIGNUP_CODE', 'campus-admin');   // admins must type this code to register. Change it! Use '' to remove the code.
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_MINUTES', 15);
date_default_timezone_set('Asia/Dhaka');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
