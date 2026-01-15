<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../services/notifier.php';
require_login();

$user = current_user();
$prefs = get_notification_prefs($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prefs = [
      'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
      'weekly_report_enabled' => isset($_POST['weekly_report_enabled']) ? 1 : 0,
      'reminder_enabled' => isset($_POST['reminder_enabled']) ? 1 : 0
    ];
    set_notification_prefs($user['id'], $prefs);
}

include __DIR__ . '/../templates/header.php';
?>

<h2>My Notification Preferences</h2>

<form method="POST">
  <label><input type="checkbox" name="email_enabled" <?php echo $prefs['email_enabled'] ? 'checked' : ''; ?>> Email notifications</label><br/>
  <label><input type="checkbox" name="weekly_report_enabled" <?php echo $prefs['weekly_report_enabled'] ? 'checked' : ''; ?>> Weekly report notifications</label><br/>
  <label><input type="checkbox" name="reminder_enabled" <?php echo $prefs['reminder_enabled'] ? 'checked' : ''; ?>> Activity reminders</label><br/><br/>
  <button type="submit">Save</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
