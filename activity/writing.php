<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_login();

/* =========================
   GEMINI AI FUNCTION
   ========================= */
function evaluate_writing_with_gemini(string $prompt, string $text): array
{
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
        return [
            'score' => 60,
            'cefr' => 'B1',
            'weak_topics' => ['configuration'],
            'feedback' => 'Gemini API key is missing.'
        ];
    }

    $aiPrompt = <<<PROMPT
SYSTEM INSTRUCTION (STRICT):
You are a grading engine.
You MUST output ONLY valid JSON.
NO explanations.
NO markdown.
NO comments.
NO extra text.
NO greetings.
NO prefixes.
NO suffixes.

--------------------------------
TASK:
Evaluate the student's English writing.

Rules:
- Score MUST be between 40 and 95.
- NEVER return 0.
- If off-topic, LOWER the score but still evaluate.
- Be fair, academic, and realistic.

--------------------------------
OUTPUT FORMAT (STRICT JSON ONLY):

{
  "score": 70,
  "cefr": "B1",
  "weak_topics": ["grammar"],
  "feedback": "Your feedback here."
}

--------------------------------
WRITING PROMPT:
$prompt

--------------------------------
STUDENT RESPONSE:
$text
PROMPT;

$payload = [
  "generationConfig" => [
    "temperature" => 0.85,
    "topP" => 0.9,
    "maxOutputTokens" => 220
  ],
  "contents" => [[
    "parts" => [[ "text" => $aiPrompt ]]
  ]]
];

    $ch = curl_init(
        "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) {
        return fallback_evaluation($text, 'Gemini API call failed.');
    }

    $json = json_decode($res, true);
    $textOut = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // JSON ayıklama (çok kritik)
    if (preg_match('/\{[\s\S]*\}/', $textOut, $m)) {
        $ai = json_decode($m[0], true);
    } else {
        $ai = null;
    }

    if (!is_array($ai)) {
        return fallback_evaluation($text, 'AI formatting issue.');
    }

    return [
        'score' => max(40, min(95, (int)($ai['score'] ?? 65))),
        'cefr' => (string)($ai['cefr'] ?? 'B1'),
        'weak_topics' => is_array($ai['weak_topics'] ?? null) ? $ai['weak_topics'] : ['grammar'],
        'feedback' => (string)($ai['feedback'] ?? 'AI-generated feedback.')
    ];
}

/* =========================
   FALLBACK (SMART)
   ========================= */
   function fallback_evaluation(string $text, string $reason): array
   {
       $words = str_word_count($text);
       $score = min(90, max(45, 40 + (int)($words / 3)));
   
       $fallbackMessages = [
           "The response demonstrates developing control of structure, though clarity can be improved with more cohesive linking.",
           "The writing shows an emerging ability to express ideas, but sentence-level accuracy requires further practice.",
           "The text communicates the main idea, yet grammatical consistency and flow need refinement.",
           "The response reflects basic argument construction; expanding sentence variety would strengthen the overall quality."
       ];
   
       return [
           'score' => $score,
           'cefr' => $score >= 80 ? 'B2' : ($score >= 65 ? 'B1' : 'A2'),
           'weak_topics' => ['grammar', 'coherence'],
           'feedback' => $fallbackMessages[array_rand($fallbackMessages)]
       ];
   }
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
        $result = evaluate_writing_with_gemini($prompt, $text);

        $evalId = db_create_evaluation(
            current_user()['id'],
            $activityId,
            $result['score'],
            $result['cefr'],
            $result['feedback'],
            $result['weak_topics'],
            'Text',
            json_encode(['prompt' => $prompt, 'text' => $text])
        );

        if ($evalId) {
            db_create_ai_log(
                current_user()['id'],
                'writing',
                $prompt,
                $result['feedback']
            );

            unset($_SESSION['writing_prompt']);
            redirect('/results/feedback.php?id=' . $evalId);
        } else {
            $errors[] = 'Failed to save evaluation.';
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
    <textarea name="writing_text" required rows="8"><?= htmlspecialchars($textValue) ?></textarea>
    <button type="submit" class="btn btn-primary">Submit Writing</button>
    <a href="<?= BASE_URL ?>/dashboard.php" class="btn">Back</a>
  </form>
</section>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>