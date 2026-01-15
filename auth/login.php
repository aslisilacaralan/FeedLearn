<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

/**
 * 🔴 KRİTİK:
 * Login sayfası kendi kendini redirect ETMEZ
 * Sadece kullanıcı ZATEN giriş yaptıysa dashboard'a yollar
 */
if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $errors[] = 'E-posta ve şifre zorunludur.';
    } else {
        $user = db_find_user_by_email($email);

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $errors[] = 'E-posta veya şifre hatalı.';
        } else {
            login_user($user);   // $_SESSION['user'] set edilir
            redirect('/dashboard.php');
        }
    }
}
?>

<?php include __DIR__ . '/../templates/header.php'; ?>

<h2>Giriş Yap</h2>

<?php if ($errors): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $e): ?>
      <li><?php echo htmlspecialchars($e); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="POST" aria-label="Giriş formu">
  <label>E-posta</label><br/>
  <input
    name="email"
    type="email"
    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
    required
  /><br/><br/>

  <label>Şifre</label><br/>
  <input name="password" type="password" required /><br/><br/>

  <button type="submit">Giriş</button>
</form>

<p>
  Hesabın yok mu?
  <a href="<?php echo BASE_URL; ?>/auth/register.php">Kayıt Ol</a>
</p>

<p style="font-size:12px; color:#666;">
  Demo: student@test.com / 123456
</p>

<?php include __DIR__ . '/../templates/footer.php'; ?>
