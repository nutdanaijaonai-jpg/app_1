<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);

$body = read_json_body();
$username = trim($body['username'] ?? '');
$password = (string)($body['password'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
$row = $stmt->fetch();

if (!$row || !password_verify($password, $row['password_hash'])) {
    json_response(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'], 401);
}

$user = ['id' => (int)$row['id'], 'name' => $row['name'], 'email' => $row['email'], 'username' => $row['username'], 'role' => $row['role']];
$token = jwt_encode($user);
json_response(['token' => $token, 'user' => $user]);
