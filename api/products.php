<?php
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

function product_row_out(array $r): array {
    return [
        'id' => (int)$r['id'],
        'name' => $r['name'],
        'brand' => $r['brand'],
        'category' => $r['category'] ?? '',
        'price' => (float)$r['price'],
        'stock' => (int)$r['stock'],
        'desc' => $r['description'] ?? '',
        'imageUrl' => $r['image_url'] ?? '',
    ];
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) json_response(['error' => 'ไม่พบสินค้า'], 404);
        json_response(product_row_out($row));
    }
    $rows = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
    json_response(array_map('product_row_out', $rows));
}

if ($method === 'POST') {
    require_admin();
    $b = read_json_body();
    $name = trim($b['name'] ?? '');
    $brand = $b['brand'] ?? '';
    $price = $b['price'] ?? null;
    $stock = $b['stock'] ?? null;
    $imageUrl = trim($b['imageUrl'] ?? ($b['image_url'] ?? ''));
    if ($name === '' || !in_array($brand, ['nike', 'adidas'], true) || $price === null || $stock === null) {
        json_response(['error' => 'ข้อมูลสินค้าไม่ครบ'], 400);
    }
    $stmt = $pdo->prepare('INSERT INTO products (name, brand, category, price, stock, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $brand, $b['category'] ?? '', (float)$price, (int)$stock, $b['desc'] ?? '', $imageUrl]);
    $newId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$newId]);
    json_response(product_row_out($stmt->fetch()), 201);
}

if ($method === 'PUT') {
    require_admin();
    if (!$id) json_response(['error' => 'ต้องระบุ id สินค้า'], 400);
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) json_response(['error' => 'ไม่พบสินค้า'], 404);

    $b = read_json_body();
    $name = trim($b['name'] ?? $existing['name']);
    $brand = $b['brand'] ?? $existing['brand'];
    $category = $b['category'] ?? $existing['category'];
    $price = $b['price'] ?? $existing['price'];
    $stock = $b['stock'] ?? $existing['stock'];
    $desc = $b['desc'] ?? $existing['description'];
    $imageUrl = trim($b['imageUrl'] ?? ($b['image_url'] ?? ($existing['image_url'] ?? '')));

    $stmt = $pdo->prepare('UPDATE products SET name=?, brand=?, category=?, price=?, stock=?, description=?, image_url=? WHERE id=?');
    $stmt->execute([$name, $brand, $category, (float)$price, (int)$stock, $desc, $imageUrl, $id]);

    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    json_response(product_row_out($stmt->fetch()));
}

if ($method === 'DELETE') {
    require_admin();
    if (!$id) json_response(['error' => 'ต้องระบุ id สินค้า'], 400);
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    http_response_code(204);
    exit;
}

json_response(['error' => 'Method not allowed'], 405);
