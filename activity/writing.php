<?php
// activity/writing.php

require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';

// DİKKAT: Eski 'grammar_evaluator.php' DEĞİL, yeni yazdığımız evaluator'ı çağırıyoruz.
require_once __DIR__ . '/../services/evaluator.php'; 

require_login();

/* =========================
   PAGE LOGIC
   ========================= */

$activity = db_get_activity_by_type('writing');
$activityId = (int)($activity['id'] ?? 0);

$prompts = [
    'Some people think university education should be free for everyone. Do you agree or disagree?',
    'Many people now work from home. Discuss the advantages and disadvantages.',
    'To what extent does advertising influence the choices people make?',
    'Discuss the advantages and disadvantages of using social media for education.'
];

if (!isset($_SESSION['writing_prompt'])) {
    $_SESSION['writing_prompt'] = $prompts[array_rand($prompts)];
}
$prompt = $_SESSION['writing_prompt'];

$errors = [];
$textValue = $_POST['writing_text'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($textValue);

    if (strlen($text) < 50) {
        $errors[] = 'Response must be at least 50 characters.';
    }

    if (!$errors) {
        try {
            // YENİ SİSTEM: evaluate_writing() fonksiyonunu çağırıyoruz.
            // Bu fonksiyon arkada GeminiClient ve gemini-2.5-flash kullanıyor.
            $result = evaluate_writing($text);

            // Gelen sonucu veritabanına uygun hale getiriyoruz
            $feedbackStr = $result['feedback']; 
            $weakTopics = $result['weak_topics'] ?? [];
            
            // Veritabanına kaydet
            $evalId = db_create_evaluation(
                current_user()['id'],
                $activityId,
                $result['score_percent'], // Gemini'den gelen yüzdelik puan
                $result['cefr'],          // Seviye (A2, B1 vs)
                $feedbackStr,             // Öğretmen yorumu
                $weakTopics,
                'Text',
                json_encode(['prompt' => $prompt, 'text' => $text])
            );

            if ($evalId) {
                // Log kaydı
                db_create_ai_log(
                    current_user()['id'],
                    'writing',
                    $prompt,
                    $feedbackStr
                );

                unset($_SESSION['writing_prompt']);
                redirect('/results/feedback.php?id=' . $evalId);
            } else {
                $errors[] = 'Failed to save evaluation.';
            }

        } catch (Exception $e) {
            $errors[] = "Evaluation Failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Writing Activity</h2>
    <p class="muted"><strong>Prompt:</strong> <?= htmlspecialchars($prompt) ?></p>
  </div>
</section>

<?php if ($errors): ?>
<section class="section">
  <div class="card error">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <form method="POST" class="card">
    <label>Your Answer</label>
    <textarea name="writing_text" required rows="8" placeholder="Write your essay here..."><?= htmlspecialchars($textValue) ?></textarea>
    <button type="submit" class="btn btn-primary">Submit Writing</button>
    <a href="<?= BASE_URL ?>/dashboard.php" class="btn">Back</a>
  </form>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>