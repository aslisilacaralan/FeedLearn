<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!csrf_validate($csrf)) {
        $errors[] = 'Oturum doğrulama hatası. Lütfen sayfayı yenileyip tekrar deneyin.';
    }

    if ($email === '' || $pass === '') {
        $errors[] = 'E-posta ve şifre zorunludur.';
    } elseif (!$errors) {
        $user = db_find_user_by_email($email);

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $errors[] = 'E-posta veya şifre hatalı.';
        } else {
            login_user($user);
            redirect('/dashboard.php');
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card" style="max-width:520px; margin:0 auto;">
    <h2>Giriş Yap</h2>
    <p class="muted">Hesabınla giriş yap ve aktiviteleri başlat.</p>

    <?php if ($errors): ?>
      <div class="error" role="alert" aria-live="polite" style="margin:10px 0;">
        <ul class="list">
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" aria-label="Giriş formu">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <label for="email">E-posta</label>
      <input
        id="email"
        name="email"
        type="email"
        autocomplete="email"
        required
        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
      />

      <label for="password">Şifre</label>
      <input
        id="password"
        name="password"
        type="password"
        autocomplete="current-password"
        required
      />

      <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Giriş</button>
        <a class="btn" href="<?php echo BASE_URL; ?>/auth/register.php">Kayıt Ol</a>
      </div>
    </form>

    <hr style="border:none; border-top:1px solid #eee; margin:14px 0;">

    <p class="muted" style="margin:0;">
      Admin kullanıcı: <strong>admin@feedlearn.local</strong> / <strong>Admin123!</strong>
    </p>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
