<?php
require __DIR__ . '/config.php';

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['error' => 'Method not allowed'], 405);

$rows = $pdo->query('SELECT id, name, email, username, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$rows = array_map(function ($r) {
    return [
        'id' => (int)$r['id'], 'name' => $r['name'], 'email' => $r['email'],
        'username' => $r['username'], 'role' => $r['role'],
        'createdAt' => strtotime($r['created_at']) * 1000,
    ];
}, $rows);
json_response($rows);
