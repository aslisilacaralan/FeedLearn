<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$user = current_user();
$result = $_SESSION['last_result'] ?? null;
$evalError = '';
$evaluationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$inputRefData = null;
$inputType = '';
if ($evaluationId > 0) {
    $evaluation = db_get_evaluation_by_id($evaluationId);
    if (!$evaluation) {
        $evalError = 'Evaluation not found.';
        $result = null;
    } else {
        $isAdmin = ($user['role'] ?? '') === ROLE_ADMIN;
        if (!$isAdmin && (int)$evaluation['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo '403 Forbidden: You do not have access to this evaluation.';
            exit;
        }
        $weakTopics = json_decode($evaluation['weak_topics_json'] ?? '[]', true);
        if (!is_array($weakTopics)) {
            $weakTopics = [];
        }
        $inputType = strtolower((string)($evaluation['input_type'] ?? ''));
        $inputRefRaw = (string)($evaluation['input_ref'] ?? '');
        if ($inputRefRaw !== '') {
            $decoded = json_decode($inputRefRaw, true);
            if (is_array($decoded)) {
                $inputRefData = $decoded;
            }
        }
        $result = [
            'score_percent' => (int)round((float)($evaluation['score'] ?? 0)),
            'weak_topics' => $weakTopics,
            'feedback' => $evaluation['feedback'] ?? ''
        ];
    }
}

// FR11 data (session)
$perf = $_SESSION['performance'] ?? [];
$mine = array_values(array_filter($perf, fn($p) => $p['user_id'] == $user['id']));
usort($mine, fn($a,$b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

$last5 = array_slice($mine, 0, 5);

// Weekly avg
$weekStart = strtotime('monday this week');
$weekEnd = strtotime('sunday this week 23:59:59');
$weekly = array_filter($mine, function($p) use ($weekStart,$weekEnd) {
    $t = strtotime($p['created_at']);
    return $t >= $weekStart && $t <= $weekEnd;
});
$scores = array_map(fn($p) => $p['score_percent'], $weekly);
$weeklyAvg = count($scores) ? round(array_sum($scores) / count($scores)) : 0;

// Weak topics overall
$topicCount = [];
foreach ($mine as $p) {
    foreach (($p['weak_topics'] ?? []) as $t) {
        $topicCount[$t] = ($topicCount[$t] ?? 0) + 1;
    }
}
arsort($topicCount);
$topWeakAllTime = array_slice(array_keys($topicCount), 0, 5);

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Detailed Performance Report</h2>
    <p class="muted">Latest result + weekly summary + trend + recommendations</p>
  </div>
</section>

<?php if (!$result): ?>
  <section class="section">
    <div class="card">
      <p class="error"><?php echo htmlspecialchars($evalError ?: 'No result found. Please submit an activity first.'); ?></p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/dashboard.php">Go to Dashboard</a>
    </div>
  </section>
<?php else: ?>

  <!-- Latest -->
  <section class="section">
    <div class="card">
      <h3>Latest Assessment</h3>
      <p><strong>Score:</strong> <span style="padding:6px 10px; border:1px solid #e6e6e6; border-radius:999px; background:#fff;"><?php echo (int)$result['score_percent']; ?>%</span></p>

      <div style="margin-top:10px;">
        <h4>Feedback</h4>
        <p><?php echo htmlspecialchars($result['feedback'] ?? ''); ?></p>
      </div>

      <div style="margin-top:10px;">
        <h4>Weak Topics (Latest)</h4>
        <?php if (!empty($result['weak_topics'])): ?>
          <ul class="list">
            <?php foreach ($result['weak_topics'] as $t): ?>
              <li><?php echo htmlspecialchars($t); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="muted">No weak topics detected in the latest attempt.</p>
        <?php endif; ?>
      </div>

      <?php if ($inputType === 'text' && is_array($inputRefData)): ?>
        <div style="margin-top:10px;">
          <?php if (!empty($inputRefData['prompt'])): ?>
            <h4>Writing Prompt</h4>
            <p><?php echo htmlspecialchars($inputRefData['prompt']); ?></p>
          <?php endif; ?>
          <?php if (!empty($inputRefData['text'])): ?>
            <h4>Your Response</h4>
            <p><?php echo nl2br(htmlspecialchars($inputRefData['text'])); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Weekly Summary -->
  <section class="section">
    <div class="card">
      <h3>This Week Summary</h3>
      <p><strong>Weekly Average:</strong> <?php echo $weeklyAvg; ?>%</p>
      <p><strong>Attempts this week:</strong> <?php echo count($weekly); ?></p>
      <a class="btn" href="<?php echo BASE_URL; ?>/reports/weekly.php">Open Weekly Report</a>
    </div>
  </section>

  <!-- Trend -->
  <section class="section">
    <div class="card">
      <h3>Trend (Last 5 Attempts)</h3>

      <?php if (!count($last5)): ?>
        <p class="muted">No history available yet.</p>
      <?php else: ?>
        <table>
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
        <div style="margin-top:12px;">
          <a class="btn" href="<?php echo BASE_URL; ?>/reports/history.php">View Full History</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Weak topics overall -->
  <section class="section">
    <div class="card">
      <h3>Most Frequent Weak Topics (All Time)</h3>
      <?php if (!count($topWeakAllTime)): ?>
        <p class="muted">No weak topics recorded yet.</p>
      <?php else: ?>
        <ol class="list">
          <?php foreach ($topWeakAllTime as $t): ?>
            <li><?php echo htmlspecialchars($t); ?> (<?php echo (int)$topicCount[$t]; ?> times)</li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
  </section>

  <!-- Next Step -->
  <section class="section">
    <div class="card">
      <h3>Next Step Recommendation</h3>

      <?php
        $scoreNow = (int)($result['score_percent'] ?? 0);
        if ($scoreNow < 60) {
            $msg = "Your score is below 60%. We recommend doing a Quiz to practice core grammar topics.";
            $link = BASE_URL . "/activity/quiz.php";
            $btn = "Start Quiz";
        } elseif (!empty($result['weak_topics'])) {
            $msg = "You have specific weak topics. Writing practice can help reinforce accuracy and coherence.";
            $link = BASE_URL . "/activity/writing.php";
            $btn = "Start Writing";
        } else {
            $msg = "Great performance! Try Speaking to improve fluency and confidence.";
            $link = BASE_URL . "/activity/speaking.php";
            $btn = "Start Speaking";
        }
      ?>

      <p><?php echo htmlspecialchars($msg); ?></p>
      <a class="btn btn-primary" href="<?php echo htmlspecialchars($link); ?>"><?php echo htmlspecialchars($btn); ?></a>
      <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php" style="margin-left:8px;">Back to Dashboard</a>
    </div>
  </section>

<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
