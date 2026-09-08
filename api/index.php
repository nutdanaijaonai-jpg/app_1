<?php
require __DIR__ . '/config.php';

$productCount = 0;
$dbStatus = 'connected';
try {
    $productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
} catch (Exception $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

json_response([
    'name' => 'LANE 3 — REST API',
    'status' => 'online',
    'database' => [
        'name' => DB_NAME,
        'status' => $dbStatus,
        'productCount' => $productCount,
    ],
    'endpoints' => [
        'GET  /products.php' => 'ดึงรายการสินค้าทั้งหมด',
        'POST /orders.php' => 'สร้างคำสั่งซื้อใหม่',
        'POST /login.php' => 'เข้าสู่ระบบ',
        'POST /register.php' => 'สมัครสมาชิกใหม่',
        'GET  /sales_summary.php' => 'สรุปยอดขาย',
    ],
    'adminUrl' => 'http://localhost/backend-php/admin/',
]);
