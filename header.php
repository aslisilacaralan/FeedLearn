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

  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css" />
</head>
<body>

<!-- FR19: Accessibility - Skip to content link -->
<a href="#content" class="skip-link">
  İçeriğe atla
</a>

<header class="site-header" role="banner" aria-label="Site header">
  <div class="container">
    <div class="header-inner">
      <div class="brand">
        <a class="brand-link" href="<?php echo BASE_URL; ?>/dashboard.php" aria-label="<?php echo APP_NAME; ?> dashboard">
          <?php echo APP_NAME; ?>
        </a>
      </div>

      <nav class="site-nav" role="navigation" aria-label="Main navigation">
        <ul class="nav-list">
          <?php if ($user): ?>
            <li><a href="<?php echo BASE_URL; ?>/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/reports/weekly.php">Weekly Report</a></li>
            <li><a href="<?php echo BASE_URL; ?>/reports/history.php">History</a></li>
            <li><a href="<?php echo BASE_URL; ?>/recommendations.php">Recommendations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/help/chatbot.php">Chatbot</a></li>
            <li><a href="<?php echo BASE_URL; ?>/activity/resume.php">Resume</a></li>
          <?php else: ?>
            <li><a href="<?php echo BASE_URL; ?>/auth/login.php">Login</a></li>
            <li><a href="<?php echo BASE_URL; ?>/auth/register.php">Register</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <div class="user-box" aria-label="User info">
        <?php if ($user): ?>
          <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
          <a class="logout-link" href="<?php echo BASE_URL; ?>/auth/logout.php">Çıkış</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<main id="content" class="site-main" role="main" tabindex="-1">
  <div class="container">
