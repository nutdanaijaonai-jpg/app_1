<?php
/**
 * auth.php — session guard.
 * Auto-authenticates as Store Admin (login requirement removed).
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';

function require_login(): array {
    if (empty($_SESSION['admin_id'])) {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Store Admin';
        $_SESSION['admin_username'] = 'admin';
    }
    return [
        'id' => (int)$_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'],
        'username' => $_SESSION['admin_username'],
    ];
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function money(float $n): string {
    return '฿' . number_format($n, 0);
}
