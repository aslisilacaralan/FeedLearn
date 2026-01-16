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

  <style>
    
  /* =========================
     THEME VARIABLES
     ========================= */
  :root {
    --bg-main: #f6f7fb;
    --bg-card: #ffffff;

    --primary: #4f46e5;
    --primary-dark: #4338ca;

    --text-main: #111827;
    --text-muted: #6b7280;

    --border-soft: #e5e7eb;
    --bg-soft: #f1f5f9;
  }

  /* =========================
     RESET
     ========================= */
  * {
    box-sizing: border-box;
  }

  body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                 Roboto, Helvetica, Arial, sans-serif;
    background: var(--bg-main);
    color: var(--text-main);
    line-height: 1.6;
  }

  /* =========================
     LAYOUT
     ========================= */
  .section {
    max-width: 880px;
    margin: 36px auto;
    padding: 0 18px;
  }

  .card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 26px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
  }

  h2 {
    margin-top: 0;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 18px;
  }

  .muted {
    color: var(--text-muted);
    font-size: 14px;
  }

  /* =========================
     BUTTONS
     ========================= */
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 20px;
    border-radius: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-decoration: none;
    background: var(--bg-soft);
    color: var(--text-main);
    transition: all 0.25s ease;
  }

  .btn-primary {
    background: var(--primary);
    color: #fff;
  }

  .btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
  }

  /* =========================
     QUIZ DESIGN (FINAL)
     ========================= */
  .quiz-question {
    margin-bottom: 28px;
  }

  .quiz-title {
    font-weight: 600;
    margin-bottom: 12px;
  }

  .quiz-option {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 14px 16px;
    margin-bottom: 10px;

    border: 1px solid var(--border-soft);
    border-radius: 14px;
    background: #fff;

    cursor: pointer;
    transition: all 0.25s ease;

    text-align: left;
  }

  .quiz-option:hover {
    background: var(--bg-soft);
    border-color: var(--primary);
  }

  .quiz-option input {
    margin: 0;
    flex-shrink: 0;
  }

  .quiz-option span {
    display: block;
  }

  .quiz-option input:checked + span {
    font-weight: 600;
    color: var(--primary);
  }

  /* =========================
     FEEDBACK
     ========================= */
  .result-score {
    font-size: 34px;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 10px;
  }

  .feedback-box {
    background: #eef2ff;
    border-left: 5px solid var(--primary);
    padding: 18px;
    border-radius: 14px;
  }
  /* ===== HEADER FIX ===== */
.site-header {
  position: relative;
  z-index: 100;
}

.header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand-link {
  font-weight: 800;
  font-size: 20px;
  color: #4f46e5;
  text-decoration: none;
}

.brand-link:hover {
  text-decoration: underline;
}

.user-box {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logout-link {
  padding: 6px 12px;
  border-radius: 8px;
  background: #ef4444;
  color: #fff;
  font-weight: 600;
  text-decoration: none;
}

.logout-link:hover {
  background: #dc2626;
}
/* ===== HEADER FIX ===== */

.site-header {
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
  padding: 14px 32px;
  position: sticky;
  top: 0;
  z-index: 1000;
}

.header-inner {
  max-width: 1100px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.brand-link {
  font-size: 20px;
  font-weight: 800;
  color: #4f46e5;
  text-decoration: none;
}

.site-nav {
  display: flex;
  gap: 20px;
}

.site-nav a {
  text-decoration: none;
  color: #374151;
  font-weight: 600;
}

.site-nav a:hover {
  color: #4f46e5;
}

.user-box {
  display: flex;
  align-items: center;
  gap: 14px;
}

.user-name {
  font-weight: 600;
  color: #111827;
}

.logout-link {
  padding: 6px 14px;
  background: #ef4444;
  color: #ffffff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
}

.logout-link:hover {
  background: #dc2626;
}
  </style>
</head>

<body>

<a href="#content" class="skip-link">

<header class="site-header">
  <div class="header-inner">

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