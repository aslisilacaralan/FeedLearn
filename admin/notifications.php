<?php
require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../services/notifier.php';

$defaults = $_SESSION['notification_defaults'] ?? [
  'email_enabled' => 1,
  'weekly_report_enabled' => 1,
  'reminder_enabled' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $defaults = [
      'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
      'weekly_report_enabled' => isset($_POST['weekly_report_enabled']) ? 1 : 0,
      'reminder_enabled' => isset($_POST['reminder_enabled']) ? 1 : 0
    ];
    $_SESSION['notification_defaults'] = $defaults;
}

require_once __DIR__ . '/../templates/header.php';
?>

<h2>Admin – Notification Preferences</h2>
<p>Set default notification settings (mock).</p>

<form method="POST">
  <label><input type="checkbox" name="email_enabled" <?php echo $defaults['email_enabled'] ? 'checked' : ''; ?>> Email Enabled</label><br/>
  <label><input type="checkbox" name="weekly_report_enabled" <?php echo $defaults['weekly_report_enabled'] ? 'checked' : ''; ?>> Weekly Report</label><br/>
  <label><input type="checkbox" name="reminder_enabled" <?php echo $defaults['reminder_enabled'] ? 'checked' : ''; ?>> Reminders</label><br/><br/>
  <button type="submit">Save Defaults</button>
</form>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
