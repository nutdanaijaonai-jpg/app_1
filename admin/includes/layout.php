<?php
/** layout.php — render_header()/render_footer() wrap every protected page. */

function render_header(string $title, string $active, array $admin): void {
    $nav = [
        'dashboard.php' => '📊 ภาพรวม',
        'products.php' => '👕 สินค้า',
        'orders.php' => '📦 ออเดอร์',
        'members.php' => '👥 สมาชิก',
    ];
    ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?> — LANE 3 Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot"></span>LANE 3</div>
    <nav>
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= h($href) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= h($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <main class="content">
    <div class="topbar">
      <h1><?= h($title) ?></h1>
      <div class="who">👤 <b><?= h($admin['name']) ?></b> <span style="color:var(--text-dim);font-size:12px;">(Admin Mode)</span></div>
    </div>
<?php
}

function render_footer(): void {
    ?>
  </main>
</div>
</body>
</html>
<?php
}
