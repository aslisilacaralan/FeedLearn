<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$user = current_user();
$pdo = db_connect();
$formatUtc = function ($value, $format) {
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }
    return gmdate($format, $timestamp);
};
$stmt = $pdo->prepare(
    'SELECT e.id, e.score, e.cefr_level, e.weak_topics_json, e.created_at,
            a.title, a.activity_type
     FROM evaluations e
     LEFT JOIN activities a ON a.id = e.activity_id
     WHERE e.user_id = :user_id
     ORDER BY datetime(e.created_at) DESC, e.id DESC'
);
$stmt->execute(['user_id' => (int)$user['id']]);
$evaluations = $stmt->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>History of Reports</h2>
    <p class="muted">All your past activity results are listed here.</p>
  </div>
</section>

<section class="section">
  <div class="card">
    <?php if (!count($evaluations)): ?>
      <p class="muted">No history found. Complete an activity first.</p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Go to Dashboard</a>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Activity</th>
            <th>Score</th>
            <th>CEFR</th>
            <th>Weak Topics</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evaluations as $row): ?>
            <?php
              $topics = json_decode($row['weak_topics_json'] ?? '[]', true);
              if (!is_array($topics)) {
                  $topics = [];
              }
              $activityLabel = $row['title'] ?: ($row['activity_type'] ?: 'Activity');
              $cefr = $row['cefr_level'] ?: 'Unknown';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($formatUtc($row['created_at'] ?? '', 'Y-m-d H:i')); ?></td>
              <td><?php echo htmlspecialchars($activityLabel); ?></td>
              <td><?php echo (int)round((float)($row['score'] ?? 0)); ?>%</td>
              <td><?php echo htmlspecialchars($cefr); ?></td>
              <td><?php echo !empty($topics) ? htmlspecialchars(implode(', ', $topics)) : '-'; ?></td>
              <td>
                <a class="btn" href="<?php echo BASE_URL; ?>/results/feedback.php?id=<?php echo (int)$row['id']; ?>">
                  View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?php echo BASE_URL; ?>/reports/weekly.php">Weekly Report</a>
        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
