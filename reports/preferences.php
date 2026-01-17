<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../services/notifier.php';
require_login();

$user = current_user();
$prefs = get_notification_prefs($user['id']);

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefs = [
      'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
      'weekly_report_enabled' => isset($_POST['weekly_report_enabled']) ? 1 : 0,
      'reminder_enabled' => isset($_POST['reminder_enabled']) ? 1 : 0
    ];
    set_notification_prefs($user['id'], $prefs);
    $success = "Preferences saved successfully.";
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Notification Preferences</h2>
    <p class="muted">Choose what type of notifications you want to receive (mock settings).</p>
  </div>
</section>

<section class="section">
  <div class="card" style="max-width:620px;">
    <?php if ($success): ?>
      <p class="success" role="status" aria-live="polite"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="POST" aria-label="Notification preferences form">
      <label style="display:flex; gap:10px; align-items:center; font-weight:700; margin:10px 0;">
        <input type="checkbox" name="email_enabled" <?php echo $prefs['email_enabled'] ? 'checked' : ''; ?> style="width:auto;">
        Email notifications
      </label>

      <label style="display:flex; gap:10px; align-items:center; font-weight:700; margin:10px 0;">
        <input type="checkbox" name="weekly_report_enabled" <?php echo $prefs['weekly_report_enabled'] ? 'checked' : ''; ?> style="width:auto;">
        Weekly report notifications
      </label>

      <label style="display:flex; gap:10px; align-items:center; font-weight:700; margin:10px 0;">
        <input type="checkbox" name="reminder_enabled" <?php echo $prefs['reminder_enabled'] ? 'checked' : ''; ?> style="width:auto;">
        Activity reminders
      </label>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save</button>
        <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back</a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
