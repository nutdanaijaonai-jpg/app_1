<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$admin = require_login();

$categories = ['เสื้อยืด', 'เสื้อฮู้ด/แจ็คเก็ต', 'กางเกง', 'ถุงเท้า/แอคเซสซอรี่'];
$error = '';
$success = '';

// ---- handle form submissions (POST → redirect, so refresh never resubmits) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([(int)$_POST['id']]);
        header('Location: products.php?msg=deleted');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $brand = $_POST['brand'] ?? '';
    $category = $_POST['category'] ?? $categories[0];
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $desc = trim($_POST['desc'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($name === '' || !in_array($brand, ['nike', 'adidas'], true) || !is_numeric($price) || $price < 0 || !ctype_digit((string)$stock)) {
        $error = 'กรุณากรอกข้อมูลให้ครบและถูกต้อง';
    } else {
        if ($action === 'update') {
            $stmt = $pdo->prepare('UPDATE products SET name=?, brand=?, category=?, price=?, stock=?, description=?, image_url=? WHERE id=?');
            $stmt->execute([$name, $brand, $category, (float)$price, (int)$stock, $desc, $imageUrl, (int)$_POST['id']]);
            header('Location: products.php?msg=updated');
            exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO products (name, brand, category, price, stock, description, image_url) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$name, $brand, $category, (float)$price, (int)$stock, $desc, $imageUrl]);
            header('Location: products.php?msg=created');
            exit;
        }
    }
}

// ---- editing state ----
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();

$messages = ['created' => 'เพิ่มสินค้าแล้ว', 'updated' => 'อัปเดตสินค้าแล้ว', 'deleted' => 'ลบสินค้าแล้ว'];
$flash = $messages[$_GET['msg'] ?? ''] ?? null;

render_header('จัดการสินค้า', 'products.php', $admin);
?>

<?php if ($flash): ?><div class="alert alert-ok"><?= h($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="card">
  <h3><?= $editing ? 'แก้ไขสินค้า: ' . h($editing['name']) : 'เพิ่มสินค้าใหม่' ?></h3>
  <form method="post">
    <input type="hidden" name="_action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px;">
      <div><label>ชื่อสินค้า</label><input type="text" name="name" value="<?= h($editing['name'] ?? '') ?>" required></div>
      <div>
        <label>แบรนด์</label>
        <select name="brand">
          <option value="nike" <?= ($editing['brand'] ?? '') === 'nike' ? 'selected' : '' ?>>Nike</option>
          <option value="adidas" <?= ($editing['brand'] ?? '') === 'adidas' ? 'selected' : '' ?>>Adidas</option>
        </select>
      </div>
      <div>
        <label>หมวดหมู่</label>
        <select name="category">
          <?php foreach ($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= ($editing['category'] ?? '') === $c ? 'selected' : '' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:14px;">
      <div><label>ราคา (บาท)</label><input type="number" step="0.01" min="0" name="price" value="<?= h((string)($editing['price'] ?? '')) ?>" required></div>
      <div><label>สต็อก</label><input type="number" min="0" name="stock" value="<?= h((string)($editing['stock'] ?? '')) ?>" required></div>
      <div><label>URL รูปภาพ (ถ้ามี)</label><input type="text" name="image_url" placeholder="https://example.com/image.jpg" value="<?= h($editing['image_url'] ?? '') ?>"></div>
    </div>
    <div style="margin-top:14px;">
      <label>รายละเอียด</label>
      <input type="text" name="desc" value="<?= h($editing['description'] ?? '') ?>">
    </div>
    <div style="margin-top:16px;">
      <button type="submit" class="btn btn-solid"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้า' ?></button>
      <?php if ($editing): ?><a href="products.php" class="btn btn-sm" style="margin-left:8px;">ยกเลิก</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h3>สินค้าทั้งหมด (<?= count($products) ?>)</h3>
  <?php if (!$products): ?>
    <div class="empty">ยังไม่มีสินค้า</div>
  <?php else: ?>
  <table>
    <thead><tr><th></th><th>ชื่อสินค้า</th><th>แบรนด์</th><th>หมวดหมู่</th><th>ราคา</th><th>สต็อก</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr>
        <td>
          <?php if (!empty($p['image_url'])): ?>
            <img src="<?= h($p['image_url']) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:2px;" onerror="this.style.display='none'">
          <?php else: ?>
            <span class="swatch" style="background:<?= $p['brand'] === 'nike' ? '#1a1a1a' : '#0f3d2e' ?>"><?= $p['brand'] === 'nike' ? 'N' : 'A' ?></span>
          <?php endif; ?>
        </td>
        <td><?= h($p['name']) ?></td>
        <td><?= $p['brand'] === 'nike' ? 'NIKE' : 'ADIDAS' ?></td>
        <td><?= h($p['category']) ?></td>
        <td class="mono"><?= money((float)$p['price']) ?></td>
        <td class="mono" style="<?= (int)$p['stock'] === 0 ? 'color:var(--danger)' : '' ?>"><?= (int)$p['stock'] ?></td>
        <td style="white-space:nowrap;">
          <a href="products.php?edit=<?= (int)$p['id'] ?>" class="btn btn-sm">แก้ไข</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('ยืนยันลบสินค้านี้?');">
            <input type="hidden" name="_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">ลบ</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
