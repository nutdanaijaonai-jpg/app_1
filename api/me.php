<?php
require __DIR__ . '/config.php';

$auth = require_auth();
$stmt = $pdo->prepare('SELECT id, name, email, username, role, created_at FROM users WHERE id = ?');
$stmt->execute([$auth['id']]);
$row = $stmt->fetch();
if (!$row) json_response(['error' => 'ไม่พบผู้ใช้'], 404);
json_response(['user' => $row]);
