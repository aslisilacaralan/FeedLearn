<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    // Basit validasyon (FR19 erişilebilir hata mesajı için net yaz)
    if ($name === '') $errors[] = 'Ad Soyad zorunludur.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (strlen($pass) < 6) $errors[] = 'Şifre en az 6 karakter olmalıdır.';

    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        // DB kayıt (mock)
        db_create_user($name, $email, $hash, ROLE_STUDENT);

        $success = "Kayıt başarılı. Giriş yapabilirsiniz.";
    }
}
?>

<?php include __DIR__ . '/../templates/header.php'; ?>

<h2>Kayıt Ol</h2>

<?php if ($success): ?>
  <p style="color:green;"><?php echo $success; ?></p>
  <a href="<?php echo BASE_URL; ?>/auth/login.php">Girişe git</a>
<?php endif; ?>

<?php if ($errors): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $e): ?>
      <li><?php echo htmlspecialchars($e); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="POST" aria-label="Kayıt formu">
  <label>Ad Soyad</label><br/>
  <input name="name" type="text" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" /><br/><br/>

  <label>E-posta</label><br/>
  <input name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" /><br/><br/>

  <label>Şifre</label><br/>
  <input name="password" type="password" /><br/><br/>

  <button type="submit">Kayıt Ol</button>
</form>

<p>Hesabın var mı? <a href="<?php echo BASE_URL; ?>/auth/login.php">Giriş Yap</a></p>

<?php include __DIR__ . '/../templates/footer.php'; ?>
