<?php
/**
 * db.php — DB connection for the server-rendered admin panel.
 * Kept separate from api/config.php on purpose: the admin panel uses PHP
 * sessions (cookie-based login) while api/config.php is for the JWT REST
 * API the Flutter app talks to. Same database, two different front doors.
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'lane3');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('เชื่อมต่อฐานข้อมูลไม่ได้ — ตรวจสอบค่าตั้งค่าใน admin/includes/db.php: ' . htmlspecialchars($e->getMessage()));
}
