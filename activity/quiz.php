<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/evaluator.php';
require_login();

$activityType = 'quiz';
$activityId = isset($_GET['activity_id']) ? (int)$_GET['activity_id'] : 0;
$activity = null;
if ($activityId > 0) {
    $activity = db_get_activity_by_id($activityId);
    if ($activity && ($activity['activity_type'] ?? '') !== $activityType) {
        $activity = null;
    }
}
if (!$activity) {
    $activity = db_get_activity_by_type($activityType);
}
$activityId = (int)($activity['id'] ?? $activityId);
$activityTitle = $activity['title'] ?? 'Quiz Activity';
$activityDescription = $activity['description'] ?? 'Answer the questions and submit to get automated feedback.';
$activityEnabled = (int)($activity['is_enabled'] ?? 1) === 1;
$errors = [];

// FR18: start or resume attempt (mock)
if ($activityEnabled) {
    if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];
    $userId = current_user()['id'];
    $_SESSION['attempts'][$userId][$activityType] = [
      'status' => 'in_progress',
      'started_at' => date('c')
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$activityEnabled) {
        $errors[] = 'Bu aktivite şu anda kullanılamıyor.';
    }
    if ($activityId <= 0) {
        $errors[] = 'Aktivite bulunamadı.';
    }

    $answers = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'q_') === 0) {
            $qid = substr($key, 2);
            $answers[$qid] = $value;
        }
    }
    $topics = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'topic_') === 0) {
            $topic = trim((string) $value);
            if ($topic !== '') {
                $topics[] = $topic;
            }
        }
    }
    $topics = array_values(array_unique($topics));
    $topicSummary = $topics ? implode(', ', $topics) : 'Quiz attempt';
    if (!$answers) {
        $errors[] = 'Lütfen tüm soruları yanıtlayın.';
    }

    if (!$errors) {
        $result = evaluate_quiz($_POST);
        $evalId = db_create_evaluation(
            current_user()['id'],
            $activityId,
            (int)($result['score_percent'] ?? 0),
            null,
            $result['feedback'] ?? null,
            $result['weak_topics'] ?? [],
            'quiz',
            json_encode($answers)
        );

        if ($evalId) {
            db_create_ai_log(
                current_user()['id'],
                'quiz',
                $topicSummary,
                $result['feedback'] ?? null
            );
            if (!isset($_SESSION['performance'])) {
                $_SESSION['performance'] = [];
            }
            $_SESSION['performance'][] = [
                'user_id' => current_user()['id'],
                'score_percent' => (int)($result['score_percent'] ?? 0),
                'weak_topics' => $result['weak_topics'] ?? [],
                'created_at' => gmdate('c')
            ];

            if (!isset($_SESSION['usage_logs'])) {
                $_SESSION['usage_logs'] = [];
            }
            $_SESSION['usage_logs'][] = [
                'user_id' => current_user()['id'],
                'action' => 'submit_quiz',
                'created_at' => gmdate('c')
            ];

            if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];
            $_SESSION['attempts'][current_user()['id']][$activityType]['status'] = 'completed';
            $_SESSION['attempts'][current_user()['id']][$activityType]['completed_at'] = gmdate('c');

            $_SESSION['last_result'] = $result;
            redirect('/results/feedback.php?id=' . $evalId);
        } else {
            $errors[] = 'Değerlendirme kaydedilemedi. Lütfen tekrar deneyin.';
        }
    }
}

// Demo questions (mock)
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
    'q' => 'Select the correct preposition for time:',
    'options' => ['in', 'on', 'at'],
    'correct' => 2,
    'topic' => 'Prepositions - time'
  ],
];

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2><?php echo htmlspecialchars($activityTitle); ?></h2>
    <p class="muted"><?php echo htmlspecialchars($activityDescription); ?></p>
  </div>
</section>

<?php if ($errors): ?>
<section class="section">
  <div class="card">
    <div class="error" role="alert" aria-live="polite">
      <ul class="list">
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$activityEnabled): ?>
<section class="section">
  <div class="card">
    <p class="muted">Bu aktivite yakında aktif olacak.</p>
    <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back</a>
  </div>
</section>
<?php else: ?>
<section class="section">
  <form method="POST" action="<?php echo htmlspecialchars(BASE_URL . '/activity/quiz.php' . ($activityId ? '?activity_id=' . $activityId : '')); ?>" class="card" aria-label="Quiz form">
    <input type="hidden" name="activity_type" value="quiz">
    <input type="hidden" name="activity_id" value="<?php echo (int)$activityId; ?>">

    <?php foreach ($questions as $i => $qq): ?>
      <div class="card" style="box-shadow:none; border-style:dashed; margin-bottom:12px;">
        <div class="card-title"><?php echo ($i+1) . ') ' . htmlspecialchars($qq['q']); ?></div>

        <?php foreach ($qq['options'] as $idx => $opt): ?>
          <label style="font-weight:600; display:flex; gap:8px; align-items:center; margin:8px 0;">
            <input
              type="radio"
              name="q_<?php echo $qq['id']; ?>"
              value="<?php echo $idx; ?>"
              required
              style="width:auto;"
            >
            <span><?php echo htmlspecialchars($opt); ?></span>
          </label>
        <?php endforeach; ?>

        <input type="hidden" name="topic_<?php echo $qq['id']; ?>" value="<?php echo htmlspecialchars($qq['topic']); ?>">
        <input type="hidden" name="correct_<?php echo $qq['id']; ?>" value="<?php echo $qq['correct']; ?>">
      </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Submit Quiz</button>
    <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php" style="margin-left:8px;">Back</a>
  </form>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
