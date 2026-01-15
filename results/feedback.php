<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$user = current_user();
$result = $_SESSION['last_result'] ?? null;

// FR11 verileri (session performance)
$perf = $_SESSION['performance'] ?? [];
$mine = array_values(array_filter($perf, fn($p) => $p['user_id'] == $user['id']));
usort($mine, fn($a,$b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

// Son 5 attempt
$last5 = array_slice($mine, 0, 5);

// Bu hafta ortalama
$weekStart = strtotime('monday this week');
$weekEnd = strtotime('sunday this week 23:59:59');
$weekly = array_filter($mine, function($p) use ($weekStart,$weekEnd) {
    $t = strtotime($p['created_at']);
    return $t >= $weekStart && $t <= $weekEnd;
});
$scores = array_map(fn($p) => $p['score_percent'], $weekly);
$weeklyAvg = count($scores) ? round(array_sum($scores) / count($scores)) : 0;

// Weak topic toplam sayımı (tüm geçmiş)
$topicCount = [];
foreach ($mine as $p) {
    foreach (($p['weak_topics'] ?? []) as $t) {
        $topicCount[$t] = ($topicCount[$t] ?? 0) + 1;
    }
}
arsort($topicCount);
$topWeakAllTime = array_slice(array_keys($topicCount), 0, 5);

include __DIR__ . '/../templates/header.php';
?>

<h2>Detailed Performance Report</h2>

<?php if (!$result): ?>
  <p class="error">No result found. Please submit an activity first.</p>
  <a href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>

<?php else: ?>
  <!-- 1) Latest Result -->
  <section style="border:1px solid #eee; padding:12px; margin-bottom:16px;">
    <h3>Latest Assessment</h3>
    <p><strong>Score:</strong> <?php echo (int)$result['score_percent']; ?>%</p>

    <h4>Feedback</h4>
    <p><?php echo htmlspecialchars($result['feedback'] ?? ''); ?></p>

    <h4>Weak Topics (Latest)</h4>
    <?php if (!empty($result['weak_topics'])): ?>
      <ul>
        <?php foreach ($result['weak_topics'] as $t): ?>
          <li><?php echo htmlspecialchars($t); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No weak topics detected in the latest attempt.</p>
    <?php endif; ?>
  </section>

  <!-- 2) Weekly Summary -->
  <section style="border:1px solid #eee; padding:12px; margin-bottom:16px;">
    <h3>This Week Summary</h3>
    <p><strong>Weekly Average:</strong> <?php echo $weeklyAvg; ?>%</p>
    <p><strong>Attempts this week:</strong> <?php echo count($weekly); ?></p>
  </section>

  <!-- 3) Trend (Last 5 attempts) -->
  <section style="border:1px solid #eee; padding:12px; margin-bottom:16px;">
    <h3>Trend (Last 5 Attempts)</h3>
    <?php if (!count($last5)): ?>
      <p>No history available yet.</p>
    <?php else: ?>
      <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">
        <thead>
          <tr>
            <th>Date</th>
            <th>Score</th>
            <th>Weak Topics</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($last5 as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($p['created_at']))); ?></td>
              <td><?php echo (int)$p['score_percent']; ?>%</td>
              <td><?php echo !empty($p['weak_topics']) ? htmlspecialchars(implode(', ', $p['weak_topics'])) : '-'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <!-- 4) Weak topics overall -->
  <section style="border:1px solid #eee; padding:12px; margin-bottom:16px;">
    <h3>Most Frequent Weak Topics (All Time)</h3>
    <?php if (!count($topWeakAllTime)): ?>
      <p>No weak topics recorded yet.</p>
    <?php else: ?>
      <ol>
        <?php foreach ($topWeakAllTime as $t): ?>
          <li><?php echo htmlspecialchars($t); ?> (<?php echo (int)$topicCount[$t]; ?> times)</li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <!-- 5) Next Step Recommendation -->
  <section style="border:1px solid #eee; padding:12px;">
    <h3>Next Step</h3>
    <?php
      $scoreNow = (int)($result['score_percent'] ?? 0);
      if ($scoreNow < 60) {
          $msg = "Your score is below 60%. We recommend doing a Quiz to practice core grammar topics.";
          $link = BASE_URL . "/activity/quiz.php";
          $btn = "Start Quiz";
      } elseif (!empty($result['weak_topics'])) {
          $msg = "You have specific weak topics. Writing practice can help you reinforce accuracy and coherence.";
          $link = BASE_URL . "/activity/writing.php";
          $btn = "Start Writing";
      } else {
          $msg = "Great performance! Try Speaking to improve fluency and confidence.";
          $link = BASE_URL . "/activity/speaking.php";
          $btn = "Start Speaking";
      }
    ?>
    <p><?php echo htmlspecialchars($msg); ?></p>
    <a href="<?php echo htmlspecialchars($link); ?>"><?php echo htmlspecialchars($btn); ?></a>
  </section>

  <br/>
  <a href="<?php echo BASE_URL; ?>/reports/history.php">View Full History</a>
  |
  <a href="<?php echo BASE_URL; ?>/reports/weekly.php">View Weekly Report</a>
  |
  <a href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>

<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
