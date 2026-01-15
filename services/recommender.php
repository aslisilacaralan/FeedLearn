<?php
// services/recommender.php
// FR16: Personalized recommendation of next activity

function recommend_next_activity(array $performanceHistory) {
    if (!count($performanceHistory)) {
        return [
          'type' => 'quiz',
          'reason' => 'No history found. Start with a quiz to determine your level.'
        ];
    }

    // son sonuç
    $last = $performanceHistory[count($performanceHistory)-1];
    $score = (int)($last['score_percent'] ?? 0);
    $weak = $last['weak_topics'] ?? [];

    if ($score < 60) {
        return [
          'type' => 'quiz',
          'reason' => 'Your score is below 60%. A quiz can help you practice weak grammar topics.'
        ];
    }

    if (!empty($weak)) {
        return [
          'type' => 'writing',
          'reason' => 'You have weak topics. Writing practice helps reinforce grammar and coherence.'
        ];
    }

    return [
      'type' => 'speaking',
      'reason' => 'Your recent results are strong. Try speaking to improve fluency.'
    ];
    
}
?>
<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/services/recommender.php';
require_login();

$user = current_user();
$perf = $_SESSION['performance'] ?? [];
$mine = array_values(array_filter($perf, fn($p) => $p['user_id'] == $user['id']));

$rec = recommend_next_activity($mine);

$linkMap = [
  'quiz' => BASE_URL . '/activity/quiz.php',
  'writing' => BASE_URL . '/activity/writing.php',
  'speaking' => BASE_URL . '/activity/speaking.php'
];

include __DIR__ . '/templates/header.php';
?>

<h2>Recommended Next Activity</h2>
<p><strong>Recommendation:</strong> <?php echo htmlspecialchars(strtoupper($rec['type'])); ?></p>
<p><strong>Reason:</strong> <?php echo htmlspecialchars($rec['reason']); ?></p>

<a href="<?php echo htmlspecialchars($linkMap[$rec['type']] ?? (BASE_URL.'/dashboard.php')); ?>">
  Start Recommended Activity
</a>

<?php include __DIR__ . '/templates/footer.php'; ?>

