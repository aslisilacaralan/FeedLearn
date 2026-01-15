<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

// FR18: start or resume attempt (mock)
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];

$userId = current_user()['id'];
$activityType = 'speaking'; // writing.php'de 'writing', speaking.php'de 'speaking'

$_SESSION['attempts'][$userId][$activityType] = [
  'status' => 'in_progress',
  'started_at' => date('c')
];


include __DIR__ . '/../templates/header.php';
?>

<h2>Speaking Activity</h2>
<p>Task: “Introduce yourself in 30–60 seconds.”</p>

<form method="POST" action="<?php echo BASE_URL; ?>/activity/submit.php" enctype="multipart/form-data">
  <input type="hidden" name="activity_type" value="speaking">

  <label for="audio_file">Upload Audio (mp3/wav)</label><br/>
  <input id="audio_file" type="file" name="audio_file" accept=".mp3,.wav" required><br/><br/>

  <button type="submit">Submit Speaking</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
