<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$user = current_user();

$endAt = gmdate('Y-m-d H:i:s');
$startAt = gmdate('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60);
$formatUtc = function ($value, $format) {
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }
    return gmdate($format, $timestamp);
};

$pdo = db_connect();
$stmt = $pdo->prepare(
    'SELECT score, cefr_level, weak_topics_json, created_at
     FROM evaluations
     WHERE user_id = :user_id
       AND datetime(created_at) >= datetime(:start_at)
       AND datetime(created_at) <= datetime(:end_at)
     ORDER BY datetime(created_at) DESC, id DESC'
);
$stmt->execute([
    'user_id' => (int)$user['id'],
    'start_at' => $startAt,
    'end_at' => $endAt
]);
$weekly = $stmt->fetchAll();

$scores = array_map(fn($p) => (float)($p['score'] ?? 0), $weekly);
$avg = count($scores) ? round(array_sum($scores) / count($scores)) : 0;

// weak topics count
$topicCount = [];
foreach ($weekly as $p) {
    $topics = json_decode($p['weak_topics_json'] ?? '[]', true);
    if (!is_array($topics)) {
        $topics = [];
    }
    foreach ($topics as $topic) {
        if ($topic === '') {
            continue;
        }
        $topicCount[$topic] = ($topicCount[$topic] ?? 0) + 1;
    }
}
arsort($topicCount);
$topWeak = array_slice(array_keys($topicCount), 0, 3);

// CEFR distribution
$cefrCounts = [];
foreach ($weekly as $p) {
    $level = $p['cefr_level'] ?: 'Unknown';
    $cefrCounts[$level] = ($cefrCounts[$level] ?? 0) + 1;
}
$cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'Unknown'];
$cefrOrderMap = array_flip($cefrOrder);
uksort($cefrCounts, function ($a, $b) use ($cefrOrderMap) {
    $aIndex = $cefrOrderMap[$a] ?? 999;
    $bIndex = $cefrOrderMap[$b] ?? 999;
    if ($aIndex === $bIndex) {
        return strcmp($a, $b);
    }
    return $aIndex <=> $bIndex;
});

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Weekly Progress Report</h2>
    <p class="muted">
      Last 7 days (UTC): <?php echo htmlspecialchars($formatUtc($startAt, 'Y-m-d') . ' -> ' . $formatUtc($endAt, 'Y-m-d')); ?>
    </p>
  </div>
</section>

<section class="section">
  <div class="card">
    <?php if (!count($weekly)): ?>
      <p class="muted">No activity found in the last 7 days.</p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Start an Activity</a>
    <?php else: ?>
      <p><strong>Completed Attempts:</strong> <?php echo count($weekly); ?></p>
      <p><strong>Average Score:</strong> <?php echo $avg; ?>%</p>

      <div style="margin-top:12px;">
        <h3>CEFR Distribution</h3>
        <?php if (!count($cefrCounts)): ?>
          <p class="muted">No CEFR data available.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Level</th>
                <th>Count</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cefrCounts as $level => $count): ?>
                <tr>
                  <td><?php echo htmlspecialchars($level); ?></td>
                  <td><?php echo (int)$count; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div style="margin-top:12px;">
        <h3>Top Weak Topics (This Week)</h3>
        <?php if (count($topWeak)): ?>
          <ul class="list">
            <?php foreach ($topWeak as $t): ?>
              <li><?php echo htmlspecialchars($t); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="muted">No repeated weak topics detected this week.</p>
        <?php endif; ?>
      </div>

      <div style="margin-top:12px;">
        <h3>Recommendation</h3>
        <p class="muted">
          <?php echo $avg >= 80
            ? "You're doing great! Try speaking or a higher difficulty task next."
            : "Focus on weak topics and practice again with quiz/writing tasks."; ?>
        </p>
      </div>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?php echo BASE_URL; ?>/reports/history.php">View History</a>
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
