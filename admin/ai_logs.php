<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_admin();

$pdo = db_connect();
$stmt = $pdo->prepare(
    'SELECT id, created_at, user_id, module, prompt_summary, response_summary
     FROM ai_logs
     ORDER BY datetime(created_at) DESC, id DESC
     LIMIT 100'
);
$stmt->execute();
$logs = $stmt->fetchAll();

$formatDate = function ($value) {
    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return '-';
    }
    return date('Y-m-d H:i', $timestamp);
};

$normalizeText = function ($value) {
    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }
    return preg_replace('/\s+/', ' ', $text);
};

$truncateText = function ($text, $limit = 120) {
    if ($text === '') {
        return '';
    }
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit - 3) . '...';
};

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Admin - AI Logs</h2>
    <p class="muted">Latest AI-related summaries for evaluations and modules.</p>
    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
      <a class="btn" href="<?php echo BASE_URL; ?>/admin/users.php">Manage Users</a>
      <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back to Dashboard</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="card">
    <?php if (!count($logs)): ?>
      <p class="muted">No logs yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Created At</th>
            <th>User ID</th>
            <th>Module</th>
            <th>Prompt (truncated)</th>
            <th>Response (truncated)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $row): ?>
            <?php
              $promptFull = $normalizeText($row['prompt_summary'] ?? '');
              $promptDisplay = $truncateText($promptFull);
              $responseFull = $normalizeText($row['response_summary'] ?? '');
              $responseDisplay = $truncateText($responseFull);
              $promptTitle = $promptFull !== '' ? ' title="' . htmlspecialchars($promptFull) . '"' : '';
              $responseTitle = $responseFull !== '' ? ' title="' . htmlspecialchars($responseFull) . '"' : '';
            ?>
            <tr>
              <td><?php echo (int) ($row['id'] ?? 0); ?></td>
              <td><?php echo htmlspecialchars($formatDate($row['created_at'] ?? '')); ?></td>
              <td><?php echo $row['user_id'] !== null ? (int) $row['user_id'] : '-'; ?></td>
              <td><?php echo htmlspecialchars($row['module'] ?? ''); ?></td>
              <td<?php echo $promptTitle; ?>><?php echo htmlspecialchars($promptDisplay !== '' ? $promptDisplay : '-'); ?></td>
              <td<?php echo $responseTitle; ?>><?php echo htmlspecialchars($responseDisplay !== '' ? $responseDisplay : '-'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
