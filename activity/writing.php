<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

// FR18: start or resume attempt (mock)
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];

$userId = current_user()['id'];
$activityType = 'writing'; // writing.php'de 'writing', speaking.php'de 'speaking'

$_SESSION['attempts'][$userId][$activityType] = [
  'status' => 'in_progress',
  'started_at' => date('c')
];


include __DIR__ . '/../templates/header.php';
?>

<h2>Writing Activity</h2>
<p>Topic: “Describe your favorite day.” (80–120 words)</p>

<form method="POST" action="<?php echo BASE_URL; ?>/activity/submit.php">
  <input type="hidden" name="activity_type" value="writing">

  <label for="writing_text">Your Answer</label><br/>
  <textarea id="writing_text" name="writing_text" rows="8" style="width:100%;" required></textarea><br/><br/>

  <button type="submit">Submit Writing</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
