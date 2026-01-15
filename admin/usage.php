<?php
require_once __DIR__ . '/_admin_guard.php';

$logs = $_SESSION['usage_logs'] ?? [];
usort($logs, fn($a,$b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

require_once __DIR__ . '/../templates/header.php';
?>

<h2>Admin – Usage Report</h2>
<p>This page summarizes how users interact with the system (mock logs).</p>

<?php if (!count($logs)): ?>
  <p>No usage data yet.</p>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">
    <thead>
      <tr>
        <th>Date</th>
        <th>User ID</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($l['created_at']))); ?></td>
          <td><?php echo (int)$l['user_id']; ?></td>
          <td><?php echo htmlspecialchars($l['action']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
