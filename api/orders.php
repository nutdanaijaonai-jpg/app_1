<?php
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

function order_out(PDO $pdo, array $o): array {
    $stmt = $pdo->prepare('SELECT product_id, name, brand, price, qty FROM order_items WHERE order_id = ?');
    $stmt->execute([$o['id']]);
    $items = array_map(function ($i) {
        return ['productId' => (int)$i['product_id'], 'name' => $i['name'], 'brand' => $i['brand'], 'price' => (float)$i['price'], 'qty' => (int)$i['qty']];
    }, $stmt->fetchAll());
    return [
        'id' => $o['id'],
        'userId' => $o['user_id'] !== null ? (int)$o['user_id'] : 0,
        'customerName' => $o['customer_name'],
        'phone' => $o['phone'],
        'address' => $o['address'],
        'subtotal' => (float)$o['subtotal'],
        'shipping' => (float)$o['shipping'],
        'total' => (float)$o['total'],
        'paymentMethod' => $o['payment_method'],
        'status' => $o['status'],
        'createdAt' => strtotime($o['created_at']) * 1000,
        'items' => $items,
    ];
}

// ---------------- GET: ดึงรายการคำสั่งซื้อ ----------------
if ($method === 'GET') {
    $orderId = isset($_GET['id']) ? trim($_GET['id']) : '';
    if ($orderId !== '') {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if ($order) {
            json_response(order_out($pdo, $order));
        } else {
            json_response(['error' => 'ไม่พบคำสั่งซื้อนี้'], 404);
        }
    }

    $user = current_user();
    if ($user && $user['role'] === 'admin') {
        $rows = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();
    } elseif ($user) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user['id']]);
        $rows = $stmt->fetchAll();
    } else {
        // ให้ผู้ใช้ทั่วไป/Guest ดูประวัติออเดอร์ได้โดยไม่ต้องล็อกอิน
        $rows = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 50')->fetchAll();
    }
    json_response(array_map(fn($o) => order_out($pdo, $o), $rows));
}

// ---------------- POST: สร้างคำสั่งซื้อใหม่ ----------------
if ($method === 'POST') {
    $user = current_user();
    $userId = $user ? (int)$user['id'] : null;

    $b = read_json_body();
    $items = $b['items'] ?? [];
    $customerName = trim($b['customerName'] ?? '');
    $phone = trim($b['phone'] ?? '');
    $address = trim($b['address'] ?? '');
    $paymentMethod = $b['paymentMethod'] ?? 'card';

    if (!is_array($items) || count($items) === 0) json_response(['error' => 'ตะกร้าว่างเปล่า'], 400);
    if ($customerName === '' || $phone === '' || $address === '') json_response(['error' => 'กรุณากรอกข้อมูลจัดส่งให้ครบ'], 400);

    $pdo->beginTransaction();
    try {
        $subtotal = 0;
        $lineItems = [];
        foreach ($items as $it) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([(int)$it['productId']]);
            $p = $stmt->fetch();
            if (!$p) throw new Exception('ไม่พบสินค้า id ' . $it['productId']);
            $qty = (int)$it['qty'];
            if ($p['stock'] < $qty) throw new Exception($p['name'] . ' มีสต็อกไม่พอ');
            $subtotal += $p['price'] * $qty;
            $lineItems[] = ['product' => $p, 'qty' => $qty];
        }
        $shipping = $subtotal >= 1500 ? 0 : 50;
        $total = $subtotal + $shipping;
        $orderId = 'ORD' . strtoupper(base_convert((string)time(), 10, 36)) . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3));
        $status = $paymentMethod === 'cod' ? 'pending' : 'paid';

        try {
            $stmt = $pdo->prepare('INSERT INTO orders (id, user_id, customer_name, phone, address, subtotal, shipping, total, payment_method, status) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$orderId, $userId, $customerName, $phone, $address, $subtotal, $shipping, $total, $paymentMethod, $status]);
        } catch (PDOException $pe) {
            $fallbackUser = (int)($pdo->query('SELECT id FROM users LIMIT 1')->fetchColumn() ?: 1);
            $stmt = $pdo->prepare('INSERT INTO orders (id, user_id, customer_name, phone, address, subtotal, shipping, total, payment_method, status) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$orderId, $fallbackUser, $customerName, $phone, $address, $subtotal, $shipping, $total, $paymentMethod, $status]);
        }

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, brand, price, qty) VALUES (?,?,?,?,?,?)');
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        foreach ($lineItems as $li) {
            $p = $li['product'];
            $itemStmt->execute([$orderId, $p['id'], $p['name'], $p['brand'], $p['price'], $li['qty']]);
            $stockStmt->execute([$li['qty'], $p['id']]);
        }

        $pdo->commit();

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        json_response(order_out($pdo, $stmt->fetch()), 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['error' => $e->getMessage()], 400);
    }
}

json_response(['error' => 'Method not allowed'], 405);
