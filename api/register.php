<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);

$body = read_json_body();
$name = trim($body['name'] ?? '');
$email = trim($body['email'] ?? '');
$username = trim($body['username'] ?? '');
$password = (string)($body['password'] ?? '');

if ($name === '' || $email === '' || $username === '' || strlen($password) < 6) {
    json_response(['error' => 'กรุณากรอกข้อมูลให้ครบ และรหัสผ่านอย่างน้อย 6 ตัวอักษร'], 400);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    json_response(['error' => 'ชื่อผู้ใช้นี้ถูกใช้แล้ว'], 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, username, password_hash, role) VALUES (?, ?, ?, ?, "customer")');
$stmt->execute([$name, $email, $username, $hash]);
$id = (int)$pdo->lastInsertId();

$user = ['id' => $id, 'name' => $name, 'email' => $email, 'username' => $username, 'role' => 'customer'];
$token = jwt_encode($user);
json_response(['token' => $token, 'user' => $user], 201);
