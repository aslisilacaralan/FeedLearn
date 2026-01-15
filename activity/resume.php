<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$userId = current_user()['id'];
$attempts = $_SESSION['attempts'][$userId] ?? [];

$inProgress = [];
foreach ($attempts as $type => $a) {
    if (($a['status'] ?? '') === 'in_progress') {
        $inProgress[$type] = $a;
    }
}

include __DIR__ . '/../templates/header.php';
?>

<h2>Resume Activity</h2>

<?php if (!count($inProgress)): ?>
  <p>No unfinished activities.</p>
  <a href="<?php echo BASE_URL; ?>/dashboard.php">Go Dashboard</a>
<?php else: ?>
  <p>Choose an unfinished activity to continue:</p>
  <ul>
    <?php foreach ($inProgress as $type => $a): ?>
      <li>
        <strong><?php echo htmlspecialchars(strtoupper($type)); ?></strong>
        (started: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($a['started_at']))); ?>)
        →
        <a href="<?php echo BASE_URL; ?>/activity/<?php echo htmlspecialchars($type); ?>.php">Resume</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
