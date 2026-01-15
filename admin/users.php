<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_admin();

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrf_validate')) {
    function csrf_validate($token) {
        if (!is_string($token) || $token === '') {
            return false;
        }
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}

$errors = [];
$success = isset($_GET['ok']) && $_GET['ok'] === '1';
$currentUserId = (int)(current_user()['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_validate($token)) {
        $errors[] = 'Oturum doğrulama hatası. Lütfen sayfayı yenileyip tekrar deneyin.';
    }
    $targetId = (int)($_POST['user_id'] ?? 0);
    if ($targetId <= 0) {
        $errors[] = 'Geçersiz kullanıcı.';
    }
    if ($targetId === $currentUserId) {
        $errors[] = 'Kendi rolünü değiştiremezsin.';
    }

    if (!$errors) {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = :id');
        $stmt->execute(['id' => $targetId]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            $errors[] = 'Kullanıcı bulunamadı.';
        } else {
            $currentRole = $targetUser['role'] ?? ROLE_STUDENT;
            $newRole = $currentRole === ROLE_ADMIN ? ROLE_STUDENT : ROLE_ADMIN;
            $update = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $update->execute(['role' => $newRole, 'id' => $targetId]);
            redirect('/admin/users.php?ok=1');
        }
    }
}

$pdo = db_connect();
$stmt = $pdo->prepare(
    'SELECT u.id, u.email, u.role, u.created_at,
            (SELECT COUNT(*) FROM evaluations e WHERE e.user_id = u.id) AS evaluation_count
     FROM users u
     ORDER BY u.id ASC'
);
$stmt->execute();
$users = $stmt->fetchAll();

$formatDate = function ($value) {
    $timestamp = strtotime((string)$value);
    if ($timestamp === false) {
        return '-';
    }
    return date('Y-m-d H:i', $timestamp);
};

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Admin - Users</h2>
    <p class="muted">Manage user roles and review evaluation counts.</p>
  </div>
</section>

<?php if ($success): ?>
<section class="section">
  <div class="card">
    <p class="success" role="status" aria-live="polite">Role updated successfully.</p>
  </div>
</section>
<?php endif; ?>

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

<section class="section">
  <div class="card">
    <?php if (!count($users)): ?>
      <p class="muted">No users found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created At</th>
            <th>Evaluations</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $row): ?>
            <?php
              $userId = (int)($row['id'] ?? 0);
              $role = $row['role'] ?? ROLE_STUDENT;
              $nextRole = $role === ROLE_ADMIN ? ROLE_STUDENT : ROLE_ADMIN;
              $actionLabel = $nextRole === ROLE_ADMIN ? 'Make Admin' : 'Make User';
            ?>
            <tr>
              <td><?php echo $userId; ?></td>
              <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($role); ?></td>
              <td><?php echo htmlspecialchars($formatDate($row['created_at'] ?? '')); ?></td>
              <td><?php echo (int)($row['evaluation_count'] ?? 0); ?></td>
              <td>
                <?php if ($userId === $currentUserId): ?>
                  <span class="muted">Current user</span>
                <?php else: ?>
                  <form method="POST" action="<?php echo BASE_URL; ?>/admin/users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                    <button type="submit" class="btn"><?php echo htmlspecialchars($actionLabel); ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
