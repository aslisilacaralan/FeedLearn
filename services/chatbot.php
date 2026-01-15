<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$reply = '';
$userMsg = '';

$faq = [
  'how to use' => "Go to Dashboard → select an activity → submit your response → view feedback and reports.",
  'quiz' => "Quiz activities measure your grammar/vocabulary with multiple-choice questions.",
  'writing' => "Writing activities evaluate your text and provide feedback about grammar and structure.",
  'speaking' => "Speaking activities accept an audio file. In MVP, we confirm upload and give basic feedback.",
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

    // FR5 benzeri log (opsiyonel)
    $_SESSION['usage_logs'][] = [
      'user_id' => current_user()['id'],
      'action' => 'chatbot_message',
      'created_at' => date('c')
    ];
}

include __DIR__ . '/../templates/header.php';
?>

<h2>Help Chatbot</h2>
<p>Ask something like: “quiz”, “writing”, “speaking”, “report”, “feedback”.</p>

<form method="POST">
  <input type="text" name="message" style="width:100%;" placeholder="Type your question..." value="<?php echo htmlspecialchars($userMsg); ?>" />
  <br/><br/>
  <button type="submit">Ask</button>
</form>

<?php if ($reply): ?>
  <div style="margin-top:16px; padding:12px; border:1px solid #eee;">
    <strong>Bot:</strong> <?php echo htmlspecialchars($reply); ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
