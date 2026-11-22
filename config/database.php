<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'campusconnect');
define('DB_USER', 'root');
define('DB_PASS', '');   // XAMPP default is empty

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '+06:00'");
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Database connection failed. Check config/database.php and import database/campusconnect.sql.');
        }
    }
    return $pdo;
}
