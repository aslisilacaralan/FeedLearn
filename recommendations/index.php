<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../templates/header.php';
require_login();
?>

<section class="section">
  <div class="card">
    <h2>Recommendations</h2>
    <p class="muted">
      Personalized learning recommendations will appear here based on your recent activities.
    </p>

    <ul>
      <li>Review grammar topics where you made mistakes</li>
      <li>Practice similar quiz questions</li>
      <li>Try a speaking activity for fluency improvement</li>
    </ul>
  </div>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>