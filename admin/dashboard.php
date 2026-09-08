<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$admin = require_login();

$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$orderCount = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$memberCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$byBrandRows = $pdo->query("
    SELECT oi.brand, SUM(oi.price * oi.qty) AS total
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'cancelled' GROUP BY oi.brand
")->fetchAll();
$byBrand = ['nike' => 0.0, 'adidas' => 0.0];
foreach ($byBrandRows as $r) $byBrand[$r['brand']] = (float)$r['total'];
$brandMax = max($byBrand['nike'], $byBrand['adidas'], 1);

$topProducts = $pdo->query("
    SELECT oi.name, SUM(oi.price * oi.qty) AS total
    FROM order_items oi JOIN orders o ON o.id = oi.order_id
    WHERE o.status != 'cancelled' GROUP BY oi.name ORDER BY total DESC LIMIT 5
")->fetchAll();
$maxP = $topProducts ? (float)$topProducts[0]['total'] : 1;

render_header('ภาพรวม', 'dashboard.php', $admin);
?>

<div class="kpi-row">
  <div class="kpi"><div class="lbl">ยอดขายรวม</div><div class="val"><?= money($totalRevenue) ?></div></div>
  <div class="kpi"><div class="lbl">ออเดอร์ทั้งหมด</div><div class="val"><?= $orderCount ?></div></div>
  <div class="kpi"><div class="lbl">สินค้าทั้งหมด</div><div class="val"><?= $productCount ?></div></div>
  <div class="kpi"><div class="lbl">สมาชิก</div><div class="val"><?= $memberCount ?></div></div>
</div>

<div class="card">
  <h3>ยอดขายตามแบรนด์</h3>
  <div class="bar-row">
    <span>Nike</span>
    <div class="bar-track"><div class="bar-fill" style="width:<?= $byBrand['nike'] / $brandMax * 100 ?>%"></div></div>
    <span class="mono"><?= money($byBrand['nike']) ?></span>
  </div>
  <div class="bar-row">
    <span>Adidas</span>
    <div class="bar-track"><div class="bar-fill" style="width:<?= $byBrand['adidas'] / $brandMax * 100 ?>%"></div></div>
    <span class="mono"><?= money($byBrand['adidas']) ?></span>
  </div>
</div>

<div class="card">
  <h3>สินค้าขายดี Top 5</h3>
  <?php if (!$topProducts): ?>
    <p style="color:var(--text-dim);font-size:13px;">ยังไม่มีข้อมูลการขาย</p>
  <?php else: foreach ($topProducts as $p): ?>
    <div class="bar-row">
      <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= h($p['name']) ?></span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= (float)$p['total'] / $maxP * 100 ?>%"></div></div>
      <span class="mono"><?= money((float)$p['total']) ?></span>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php render_footer(); ?>
