<?php
// templates/header.php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo APP_NAME; ?></title>

  <!-- New Design Assets -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</head>

<body>

  <a href="#content" class="skip-link">Skip to content</a>

  <header class="site-header">
    <div class="header-inner">

      <!-- Mobile Menu Toggle -->
      <button class="mobile-toggle" aria-label="Menu">☰</button>

      <!-- LOGO -->
      <div class="brand">
        <a href="<?= BASE_URL ?>/dashboard.php" class="brand-link">
          <?= APP_NAME ?>
        </a>
      </div>

      <!-- NAV -->
      <nav class="site-nav">
        <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/reports/weekly.php">Weekly</a>
        <a href="<?= BASE_URL ?>/reports/history.php">History</a>
        <a href="<?= BASE_URL ?>/recommendations">Recommendations</a>

        <!-- Mobile only user links could go here if needed, but handled globally via CSS for now -->
      </nav>

      <!-- USER -->
      <?php if ($user): ?>
        <div class="user-box">
          <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
          <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-link">
            Çıkış
          </a>
        </div>
      <?php endif; ?>

    </div>
  </header>
  <main id="content" class="site-main">