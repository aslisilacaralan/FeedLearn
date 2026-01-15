<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$user = current_user();
$perf = $_SESSION['performance'] ?? [];

$mine = array_values(array_filter($perf, fn($p) => $p['user_id'] == $user['id']));
usort($mine, fn($a,$b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

include __DIR__ . '/../templates/header.php';
?>

<h2>History of Reports</h2>

<?php if (!count($mine)): ?>
  <p>No history found. Complete an activity first.</p>
  <a href="<?php echo BASE_URL; ?>/dashboard.php">Go to Dashboard</a>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">
    <thead>
      <tr>
        <th>Date</th>
        <th>Score</th>
        <th>Weak Topics</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mine as $p): ?>
        <tr>
          <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($p['created_at']))); ?></td>
          <td><?php echo (int)$p['score_percent']; ?>%</td>
          <td>
            <?php 
              $topics = $p['weak_topics'] ?? [];
              echo $topics ? htmlspecialchars(implode(', ', $topics)) : '-';
            ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
