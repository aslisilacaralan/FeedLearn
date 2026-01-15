<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf  = $_POST['csrf_token'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!csrf_validate($csrf)) $errors[] = 'Oturum doğrulama hatası. Lütfen sayfayı yenileyip tekrar deneyin.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (strlen($pass) < 6) $errors[] = 'Şifre en az 6 karakter olmalıdır.';

    if (!$errors) {
        if (db_find_user_by_email($email)) {
            $errors[] = 'Bu e-posta zaten kayıtlı.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            if (!db_create_user($email, $hash, ROLE_STUDENT)) {
                $errors[] = 'Kayıt oluşturulamadı. Lütfen tekrar deneyin.';
            } else {
                $success = "Kayıt başarılı. Şimdi giriş yapabilirsin.";
            }
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card" style="max-width:520px; margin:0 auto;">
    <h2>Kayıt Ol</h2>
    <p class="muted">Yeni bir hesap oluştur.</p>

    <?php if ($success): ?>
      <p class="success" role="status" aria-live="polite"><?php echo htmlspecialchars($success); ?></p>
      <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/auth/login.php">Girişe Git</a>
      <hr style="border:none; border-top:1px solid #eee; margin:14px 0;">
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="error" role="alert" aria-live="polite" style="margin:10px 0;">
        <ul class="list">
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" aria-label="Kayıt formu">
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
        autocomplete="new-password"
        required
      />

      <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Kayıt Ol</button>
        <a class="btn" href="<?php echo BASE_URL; ?>/auth/login.php">Giriş Yap</a>
      </div>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
