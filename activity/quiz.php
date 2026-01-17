<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();

/* =====================================================
   QUIZ QUESTIONS (10 ADET – TEKRARSIZ)
   ===================================================== */
$questions = [
  ['id'=>1,'q'=>'Choose the correct sentence:','o'=>['He go to school.','He goes to school.','He going to school.'],'c'=>1,'t'=>'grammar'],
  ['id'=>2,'q'=>'Choose the correct sentence:','o'=>['They is happy.','They are happy.','They be happy.'],'c'=>1,'t'=>'grammar'],
  ['id'=>3,'q'=>'Select the correct preposition:','o'=>['in','on','at'],'c'=>2,'t'=>'prepositions'],
  ['id'=>4,'q'=>'Choose the correct article:','o'=>['a apple','an apple','the apple'],'c'=>1,'t'=>'articles'],
  ['id'=>5,'q'=>'Choose the correct tense:','o'=>['I have see it.','I saw it.','I have saw it.'],'c'=>1,'t'=>'tenses'],
  ['id'=>6,'q'=>'Choose the correct word:','o'=>['much people','many people','little people'],'c'=>1,'t'=>'vocabulary'],
  ['id'=>7,'q'=>'Choose the correct sentence:','o'=>['She don’t like tea.','She doesn’t like tea.','She didn’t likes tea.'],'c'=>1,'t'=>'grammar'],
  ['id'=>8,'q'=>'Choose the correct form:','o'=>['There is many cars.','There are many cars.','There be many cars.'],'c'=>1,'t'=>'grammar'],
  ['id'=>9,'q'=>'Choose the correct article:','o'=>['a university','an university','the university'],'c'=>0,'t'=>'articles'],
  ['id'=>10,'q'=>'Choose the correct tense:','o'=>['He was go home.','He went home.','He has go home.'],'c'=>1,'t'=>'tenses'],
];

/* =====================================================
   ACTIVITY
   ===================================================== */
$activity = db_get_activity_by_type('quiz');
$activityId = (int)($activity['id'] ?? 0);
$errors = [];

/* =====================================================
   SCORING (10 soru × 10 puan = 100)
   ===================================================== */
function evaluate_quiz(array $questions, array $answers): array {
    $correct = 0;
    $weak = [];

    foreach ($questions as $q) {
        if ((int)$answers[$q['id']] === (int)$q['c']) {
            $correct++;
        } else {
            $weak[] = $q['t'];
        }
    }

    $score = $correct * 10;

    if ($score < 40)      $cefr = 'A1';
    elseif ($score < 55)  $cefr = 'A2';
    elseif ($score < 70)  $cefr = 'B1';
    elseif ($score < 85)  $cefr = 'B2';
    else                  $cefr = 'C1';

    return [
        'score' => $score,
        'cefr' => $cefr,
        'weak_topics' => array_values(array_unique($weak)),
        'correct' => $correct,
        'total' => count($questions)
    ];
}

/* =====================================================
   🧠 AI-ASSISTED FEEDBACK (STABLE FINAL)
   ===================================================== */
function quiz_feedback_ai_assisted(
    int $score,
    string $cefr,
    array $weak,
    int $correct,
    int $total
): string {

    /* ---------- PERFORMANCE BAND ---------- */
    if ($score >= 85) {
        $band = 'high';
    } elseif ($score >= 60) {
        $band = 'medium';
    } else {
        $band = 'low';
    }

    $topics = $weak ? implode(', ', $weak) : 'overall accuracy';

    /* ---------- TRY GEMINI (IF AVAILABLE) ---------- */
    require_once __DIR__ . '/../services/gemini_client.php';
    
    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {

        $prompt = <<<PROMPT
You are an English instructor.

A student completed a grammar quiz.

Performance level: $band
CEFR level: $cefr
Score: $score
Correct answers: $correct out of $total
Weak areas: $topics

Write a short academic feedback (2–3 sentences).
Adapt tone to performance level.
Do not use bullet points.
Return plain text only.
PROMPT;

        try {
            $client = new GeminiClient();
            $text = $client->generateResponse($prompt, false); // False = expect plain text
            
            // Basic sanitation to prevent encoding errors
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $text = trim($text);

            if ($text) {
                return $text;
            }
        } catch (Exception $e) {
            error_log("Quiz Activity AI Error: " . $e->getMessage());
            // Fallback to rules if AI fails
        }
    }

    /* ---------- SMART FALLBACK (AI-ASSISTED) ---------- */
    if ($band === 'high') {
        return "Your performance demonstrates strong grammatical awareness and consistent accuracy. To progress further, focus on refining tense usage and expanding sentence variety.";
    }

    if ($band === 'medium') {
        return "Your results show a developing understanding of core grammar structures. Reviewing the identified weak areas and practicing similar question types will help improve consistency.";
    }

    return "This attempt indicates gaps in foundational grammar patterns. Concentrating on basic sentence structures and practicing targeted exercises will support steady improvement.";
}

/* =====================================================
   POST
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $answers = [];
    foreach ($questions as $q) {
        if (!isset($_POST['q_'.$q['id']])) {
            $errors[] = 'Please answer all questions.';
            break;
        }
        $answers[$q['id']] = (int)$_POST['q_'.$q['id']];
    }

    if (!$errors) {
        $base = evaluate_quiz($questions, $answers);

        $feedback = quiz_feedback_ai_assisted(
            $base['score'],
            $base['cefr'],
            $base['weak_topics'],
            $base['correct'],
            $base['total']
        );

        $evalId = db_create_evaluation(
            current_user()['id'],
            $activityId,
            $base['score'],
            $base['cefr'],
            $feedback,
            $base['weak_topics'],
            'quiz',
            json_encode($answers)
        );

        redirect('/results/feedback.php?id=' . $evalId);
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
<form method="POST" class="card">
<h2>Quiz Activity</h2>

<?php foreach ($questions as $i => $q): ?>
  <div class="quiz-question">

    <p class="quiz-title">
      <?= ($i+1) ?>. <?= htmlspecialchars($q['q']) ?>
    </p>

    <?php foreach ($q['o'] as $idx => $opt): ?>
      <label class="quiz-option">
        <input
          type="radio"
          name="q_<?= $q['id'] ?>"
          value="<?= $idx ?>"
          required
        >
        <span><?= htmlspecialchars($opt) ?></span>
      </label>
    <?php endforeach; ?>

  </div>
  <hr>
<?php endforeach; ?>

<button class="btn btn-primary">Submit Quiz</button>
<a class="btn" href="<?= BASE_URL ?>/dashboard.php">Back</a>
</form>
</section>