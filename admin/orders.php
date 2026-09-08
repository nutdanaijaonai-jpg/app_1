<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$admin = require_login();

$statusLabels = [
    'pending' => 'รอชำระ',
    'paid' => 'ชำระแล้ว',
    'shipped' => 'จัดส่งแล้ว',
    'completed' => 'สำเร็จ',
    'cancelled' => 'ยกเลิก'
];

$paymentLabels = [
    'card' => '💳 บัตรเครดิต/เดบิต',
    'transfer' => '🏦 โอนผ่านธนาคาร',
    'promptpay' => '📱 พร้อมเพย์ QR',
    'cod' => '📦 เก็บเงินปลายทาง',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update_status') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    if ($id !== '' && isset($statusLabels[$status])) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }
    header('Location: orders.php?msg=updated' . (isset($_GET['view']) ? '&view=' . urlencode($_GET['view']) : ''));
    exit;
}

$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();

$viewing = null;
$viewingItems = [];
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$_GET['view']]);
    $viewing = $stmt->fetch() ?: null;
    if ($viewing) {
        $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$viewing['id']]);
        $viewingItems = $stmt->fetchAll();
    }
}

render_header('จัดการออเดอร์', 'orders.php', $admin);
?>

<?php if (isset($_GET['msg'])): ?><div class="alert alert-ok">อัปเดตสถานะแล้ว</div><?php endif; ?>

<?php if ($viewing): ?>
<div class="card">
  <h3>ออเดอร์ <span class="mono"><?= h($viewing['id']) ?></span></h3>
  <p style="font-size:13px;color:var(--text-dim);margin-bottom:4px;">ลูกค้า: <b><?= h($viewing['customer_name']) ?></b> · โทร: <?= h($viewing['phone']) ?></p>
  <p style="font-size:13px;color:var(--text-dim);margin-bottom:14px;">
    ที่อยู่: <?= h($viewing['address']) ?> · 
    รูปแบบการชำระเงิน: <b style="color:var(--volt);"><?= h($paymentLabels[$viewing['payment_method']] ?? strtoupper($viewing['payment_method'])) ?></b>
  </p>
  <table>
    <thead><tr><th>สินค้า</th><th>แบรนด์</th><th>ราคา</th><th>จำนวน</th><th>รวม</th></tr></thead>
    <tbody>
      <?php foreach ($viewingItems as $it): ?>
        <tr>
          <td><?= h($it['name']) ?></td>
          <td><?= h(strtoupper($it['brand'])) ?></td>
          <td class="mono"><?= money((float)$it['price']) ?></td>
          <td class="mono"><?= (int)$it['qty'] ?></td>
          <td class="mono"><?= money((float)$it['price'] * (int)$it['qty']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="text-align:right;margin-top:12px;font-weight:700;font-size:16px;">รวมทั้งหมด: <span style="color:var(--volt);"><?= money((float)$viewing['total']) ?></span></p>
  <a href="orders.php" class="btn btn-sm" style="margin-top:10px;">← กลับไปรายการออเดอร์</a>
</div>
<?php endif; ?>

<div class="card">
  <h3>ออเดอร์ทั้งหมด (<?= count($orders) ?>)</h3>
  <?php if (!$orders): ?>
    <div class="empty">ยังไม่มีออเดอร์</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>เลขที่</th>
        <th>ลูกค้า</th>
        <th>วันที่</th>
        <th>รูปแบบการชำระเงิน</th>
        <th>ยอดรวม</th>
        <th>สถานะ</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td class="mono" style="font-weight:700;color:var(--volt);"><?= h($o['id']) ?></td>
        <td>
          <b><?= h($o['customer_name']) ?></b>
          <div style="font-size:11px;color:var(--text-dim);"><?= h($o['phone']) ?></div>
        </td>
        <td style="font-size:12px;color:var(--text-dim);"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
        <td>
          <span style="display:inline-block;padding:3px 8px;background:var(--surface2);border:1px solid var(--line);border-radius:4px;font-size:12px;">
            <?= h($paymentLabels[$o['payment_method']] ?? strtoupper($o['payment_method'])) ?>
          </span>
        </td>
        <td class="mono" style="font-weight:700;"><?= money((float)$o['total']) ?></td>
        <td><span class="status-pill status-<?= h($o['status']) ?>"><?= h($statusLabels[$o['status']] ?? $o['status']) ?></span></td>
        <td style="white-space:nowrap;">
          <form method="post" style="display:inline-flex;gap:6px;align-items:center;">
            <input type="hidden" name="_action" value="update_status">
            <input type="hidden" name="id" value="<?= h($o['id']) ?>">
            <select name="status" onchange="this.form.submit()">
              <?php foreach ($statusLabels as $k => $label): ?>
                <option value="<?= h($k) ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <a href="orders.php?view=<?= urlencode($o['id']) ?>" class="btn btn-sm">ดูรายละเอียด</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
