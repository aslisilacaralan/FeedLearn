<?php
require_once __DIR__ . '/auth/_guard.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';
require_login();

$activities = db_get_activities();

require_once __DIR__ . '/templates/header.php';
?>

<div class="section card">
  <h2>Dashboard</h2>
  <p class="muted">Welcome, <?php echo htmlspecialchars(current_user()['name']); ?> 👋</p>
</div>

<section class="section">
  <h3>Activity Selection</h3>
  <p class="muted">Choose an activity to start.</p>

  <?php if (!count($activities)): ?>
    <div class="card">
      <p class="muted">No activities available yet.</p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($activities as $activity): ?>
        <?php
          $type = $activity['activity_type'] ?? '';
          $title = $activity['title'] ?? '';
          $description = $activity['description'] ?? '';
          $enabled = (int)($activity['is_enabled'] ?? 0) === 1;
          $activityId = (int)($activity['id'] ?? 0);
          $link = BASE_URL . '/activity/' . $type . '.php?activity_id=' . $activityId;
          $label = ucfirst($type);
        ?>
        <div class="card">
          <div class="card-title"><?php echo htmlspecialchars($title); ?></div>
          <div class="card-desc"><?php echo htmlspecialchars($description); ?></div>
          <?php if ($enabled): ?>
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($link); ?>">Start <?php echo htmlspecialchars($label); ?></a>
          <?php else: ?>
            <span class="btn" aria-disabled="true" style="opacity:0.6; cursor:not-allowed;">Yakında</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <h3>Reports</h3>
  <div class="card">
    <ul class="list">
      <li><a class="btn" href="<?php echo BASE_URL; ?>/reports/weekly.php">Weekly Report</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/reports/history.php">History</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/results/feedback.php">Detailed Report (Last Result)</a></li>
    </ul>
  </div>
</section>

<section class="section">
  <h3>Tools</h3>
  <div class="card">
    <ul class="list">
      <li><a class="btn" href="<?php echo BASE_URL; ?>/recommendations.php">Recommended Next Activity</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/help/chatbot.php">Help Chatbot</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/activity/resume.php">Resume Unfinished Activity</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/reports/preferences.php">Notification Preferences</a></li>
    </ul>
  </div>
</section>

<?php if ((current_user()['role'] ?? '') === ROLE_ADMIN): ?>
<section class="section">
  <h3>Admin Panel</h3>
  <div class="card">
    <ul class="list">
      <li><a class="btn" href="<?php echo BASE_URL; ?>/admin/users.php">Manage Users</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/admin/usage.php">Usage Report</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/admin/ai_logs.php">AI Feedback Logs</a></li>
      <li><a class="btn" href="<?php echo BASE_URL; ?>/admin/notifications.php">Notification Defaults</a></li>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
