<?php
require __DIR__ . '/config.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['error' => 'Method not allowed'], 405);

$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$orderCount = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$memberCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$byBrandRows = $pdo->query("
    SELECT oi.brand, SUM(oi.price * oi.qty) AS total
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'cancelled'
    GROUP BY oi.brand
")->fetchAll();
$byBrand = ['nike' => 0, 'adidas' => 0];
foreach ($byBrandRows as $r) $byBrand[$r['brand']] = (float)$r['total'];

$byProductRows = $pdo->query("
    SELECT oi.name, SUM(oi.price * oi.qty) AS total
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'cancelled'
    GROUP BY oi.name
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();
$byProduct = [];
foreach ($byProductRows as $r) $byProduct[$r['name']] = (float)$r['total'];

json_response([
    'totalRevenue' => $totalRevenue,
    'orderCount' => $orderCount,
    'memberCount' => $memberCount,
    'productCount' => $productCount,
    'byBrand' => $byBrand,
    'byProduct' => $byProduct,
]);
