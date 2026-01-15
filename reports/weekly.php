<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$user = current_user();
$perf = $_SESSION['performance'] ?? [];

// Bu haftanın başlangıcı (Pazartesi) - basit yaklaşım
$weekStart = strtotime('monday this week');
$weekEnd = strtotime('sunday this week 23:59:59');

$weekly = array_filter($perf, function($p) use ($user, $weekStart, $weekEnd) {
    $t = strtotime($p['created_at']);
    return $p['user_id'] == $user['id'] && $t >= $weekStart && $t <= $weekEnd;
});

$scores = array_map(fn($p) => $p['score_percent'], $weekly);
$avg = count($scores) ? round(array_sum($scores) / count($scores)) : 0;

// weak topic sayımı
$topicCount = [];
foreach ($weekly as $p) {
    foreach (($p['weak_topics'] ?? []) as $topic) {
        $topicCount[$topic] = ($topicCount[$topic] ?? 0) + 1;
    }
}
arsort($topicCount);
$topWeak = array_slice(array_keys($topicCount), 0, 3);

include __DIR__ . '/../templates/header.php';
?>

<h2>Weekly Progress Report</h2>
<p><strong>User:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
<p><strong>Week:</strong> <?php echo date('Y-m-d', $weekStart) . ' to ' . date('Y-m-d', $weekEnd); ?></p>

<?php if (!count($weekly)): ?>
  <p>This week you have no completed activities yet.</p>
  <a href="<?php echo BASE_URL; ?>/dashboard.php">Start an activity</a>
<?php else: ?>
  <p><strong>Completed Attempts:</strong> <?php echo count($weekly); ?></p>
  <p><strong>Average Score:</strong> <?php echo $avg; ?>%</p>

  <h3>Top Weak Topics</h3>
  <?php if (count($topWeak)): ?>
    <ul>
      <?php foreach ($topWeak as $t): ?>
        <li><?php echo htmlspecialchars($t); ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No repeated weak topics detected this week.</p>
  <?php endif; ?>

  <h3>Recommendation</h3>
  <p>
    <?php echo $avg >= 80 
      ? "Keep your momentum! Try a higher difficulty activity next."
      : "Focus on your weak topics and retry similar tasks."; ?>
  </p>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
