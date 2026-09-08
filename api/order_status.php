<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}
require_admin();

$id = $_GET['id'] ?? '';
$b = read_json_body();
$status = $b['status'] ?? '';
$allowed = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

if ($id === '' || !in_array($status, $allowed, true)) {
    json_response(['error' => 'ข้อมูลไม่ถูกต้อง'], 400);
}

$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$status, $id]);
if ($stmt->rowCount() === 0) json_response(['error' => 'ไม่พบออเดอร์'], 404);

json_response(['id' => $id, 'status' => $status]);
