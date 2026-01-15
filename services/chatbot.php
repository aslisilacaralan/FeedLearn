<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$reply = '';
$userMsg = '';

$faq = [
  'how to use' => "Go to Dashboard → select an activity → submit your response → view feedback and reports.",
  'quiz' => "Quiz activities measure grammar/vocabulary with multiple-choice questions.",
  'writing' => "Writing activities evaluate your text and provide feedback about grammar and structure.",
  'speaking' => "Speaking activities let you record with your microphone or upload an audio file. In MVP, we confirm upload and give basic feedback.",
  'report' => "Weekly report shows your average score and top weak topics for this week.",
  'feedback' => "Feedback explains your score and suggests what to improve next.",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMsg = trim($_POST['message'] ?? '');

    if ($userMsg === '') {
        $reply = "Please type a question.";
    } else {
        $msg = strtolower($userMsg);
        $reply = "I couldn't understand. Try keywords like: quiz, writing, speaking, report, feedback.";

        foreach ($faq as $key => $ans) {
            if (strpos($msg, $key) !== false) {
                $reply = $ans;
                break;
            }
        }
    }

    if (!isset($_SESSION['usage_logs'])) $_SESSION['usage_logs'] = [];
    $_SESSION['usage_logs'][] = [
      'user_id' => current_user()['id'],
      'action' => 'chatbot_message',
      'created_at' => date('c')
    ];
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2>Help Chatbot</h2>
    <p class="muted">Ask something like: <strong>quiz</strong>, <strong>writing</strong>, <strong>speaking</strong>, <strong>report</strong>, <strong>feedback</strong>.</p>
  </div>
</section>

<section class="section">
  <div class="card">
    <form method="POST" aria-label="Chatbot form">
      <label for="message">Your Question</label>
      <input
        id="message"
        name="message"
        type="text"
        placeholder="Type your question..."
        value="<?php echo htmlspecialchars($userMsg); ?>"
        required
      />

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Ask</button>
        <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back</a>
      </div>
    </form>
  </div>
</section>

<?php if ($reply): ?>
<section class="section">
  <div class="card">
    <h3>Bot Reply</h3>
    <p><?php echo htmlspecialchars($reply); ?></p>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
