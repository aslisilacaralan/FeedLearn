<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/constants.php';
require_login();

include __DIR__ . '/templates/header.php';
?>

<div class="section card">
  <h2>Dashboard</h2>
  <p class="muted">Welcome, <?php echo htmlspecialchars(current_user()['name']); ?> 👋</p>
</div>

<section class="section">
  <h3>Activity Selection</h3>
  <p class="muted">Choose an activity to start.</p>

  <div class="grid">
    <div class="card">
      <div class="card-title">Quiz</div>
      <div class="card-desc">Measure grammar & vocabulary with multiple-choice questions.</div>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/activity/quiz.php">Start Quiz</a>
    </div>

    <div class="card">
      <div class="card-title">Writing</div>
      <div class="card-desc">Write a short text and get automated feedback.</div>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/activity/writing.php">Start Writing</a>
    </div>

    <div class="card">
      <div class="card-title">Speaking</div>
      <div class="card-desc">Upload an audio file and receive basic speaking feedback.</div>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/activity/speaking.php">Start Speaking</a>
    </div>
  </div>
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

<?php include __DIR__ . '/templates/footer.php'; ?>
