<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/layout.php';
$admin = require_login();

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

render_header('สมาชิก', 'members.php', $admin);
?>

<div class="card">
  <h3>สมาชิกทั้งหมด (<?= count($users) ?>)</h3>
  <?php if (!$users): ?>
    <div class="empty">ยังไม่มีสมาชิก</div>
  <?php else: ?>
  <table>
    <thead><tr><th>ชื่อ</th><th>ชื่อผู้ใช้</th><th>อีเมล</th><th>บทบาท</th><th>สมัครเมื่อ</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= h($u['name']) ?></td>
        <td class="mono">@<?= h($u['username']) ?></td>
        <td><?= h($u['email']) ?></td>
        <td><?= $u['role'] === 'admin' ? 'แอดมิน' : 'ลูกค้า' ?></td>
        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
