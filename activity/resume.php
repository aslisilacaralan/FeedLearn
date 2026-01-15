<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$userId = current_user()['id'];
$lastEval = db_get_last_evaluation_for_user($userId);
$lastActivity = null;
if ($lastEval) {
    $lastActivity = db_get_activity_by_id((int)($lastEval['activity_id'] ?? 0));
    if ($lastActivity && (int)($lastActivity['is_enabled'] ?? 0) === 1) {
        $type = $lastActivity['activity_type'] ?? '';
        $id = (int)($lastActivity['id'] ?? 0);
        if ($type !== '' && $id > 0) {
            redirect('/activity/' . $type . '.php?activity_id=' . $id);
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Resume Activity</h2>
    <p class="muted">Continue your most recent activity based on evaluation history.</p>
  </div>
</section>

<section class="section">
  <div class="card">
    <?php if (!$lastEval): ?>
      <p class="muted">No activity history found yet.</p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>
    <?php elseif (!$lastActivity): ?>
      <p class="muted">Last activity could not be loaded.</p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>
    <?php else: ?>
      <p class="muted">Your last activity is currently unavailable.</p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
