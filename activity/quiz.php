<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();
// FR18: start or resume attempt (mock)
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];

$userId = current_user()['id'];
$activityType = 'quiz'; // writing.php'de 'writing', speaking.php'de 'speaking'

$_SESSION['attempts'][$userId][$activityType] = [
  'status' => 'in_progress',
  'started_at' => date('c')
];


$questions = [
  [
    'id' => 1,
    'q' => 'Choose the correct sentence:',
    'options' => ['He go to school.', 'He goes to school.', 'He going to school.'],
    'correct' => 1,
    'topic' => 'Present Simple - 3rd person'
  ],
  [
    'id' => 2,
    'q' => 'Select the correct preposition:',
    'options' => ['in', 'on', 'at'],
    'correct' => 2,
    'topic' => 'Prepositions - time'
  ],
];

include __DIR__ . '/../templates/header.php';
?>

<h2>Quiz Activity</h2>
<form method="POST" action="<?php echo BASE_URL; ?>/activity/submit.php">
  <input type="hidden" name="activity_type" value="quiz">

  <?php foreach ($questions as $i => $qq): ?>
    <div style="margin-bottom:16px; padding:12px; border:1px solid #eee;">
      <strong><?php echo ($i+1) . ') ' . htmlspecialchars($qq['q']); ?></strong><br/><br/>
      <?php foreach ($qq['options'] as $idx => $opt): ?>
        <label>
          <input type="radio" name="q_<?php echo $qq['id']; ?>" value="<?php echo $idx; ?>" required>
          <?php echo htmlspecialchars($opt); ?>
        </label><br/>
      <?php endforeach; ?>
      <input type="hidden" name="topic_<?php echo $qq['id']; ?>" value="<?php echo htmlspecialchars($qq['topic']); ?>">
      <input type="hidden" name="correct_<?php echo $qq['id']; ?>" value="<?php echo $qq['correct']; ?>">
    </div>
  <?php endforeach; ?>

  <button type="submit">Submit Quiz</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
