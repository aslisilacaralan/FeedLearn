<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/evaluator.php';
require_login();

$activityType = 'writing';
$activityId = isset($_GET['activity_id']) ? (int)$_GET['activity_id'] : 0;
$activity = null;
if ($activityId > 0) {
    $activity = db_get_activity_by_id($activityId);
    if ($activity && ($activity['activity_type'] ?? '') !== $activityType) {
        $activity = null;
    }
}
if (!$activity) {
    $activity = db_get_activity_by_type($activityType);
}
$activityId = (int)($activity['id'] ?? $activityId);
$activityTitle = $activity['title'] ?? 'Writing Activity';
$activityDescription = $activity['description'] ?? 'Write a short text and get automated feedback.';
$activityEnabled = (int)($activity['is_enabled'] ?? 1) === 1;
$errors = [];
$textValue = $_POST['writing_text'] ?? '';
$prompts = [
    'Some people think university education should be free for everyone. Do you agree or disagree?',
    'The best way to improve public health is by encouraging a healthy diet. To what extent do you agree?',
    'Many people now work from home. Discuss the advantages and disadvantages.',
    'Do the benefits of studying abroad outweigh the drawbacks?',
    'Some believe technology makes life more stressful. Do you agree or disagree?',
    'What are the causes of traffic congestion in cities, and how can it be solved?',
    'In your opinion, should governments invest more in public transport than roads?',
    'Some people think children should start school at a younger age. Discuss both views and give your opinion.',
    'To what extent does advertising influence the choices people make?',
    'Many believe that reading books is becoming less important. Do you agree or disagree?',
    'Some argue that history is the most important subject in school. Do you agree or disagree?',
    'Discuss the advantages and disadvantages of using social media for education.'
];
$prompt = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = $_SESSION['writing_prompt'] ?? '';
    if ($prompt === '') {
        $prompt = $prompts[array_rand($prompts)];
        $_SESSION['writing_prompt'] = $prompt;
    }
} else {
    $prompt = $prompts[array_rand($prompts)];
    $_SESSION['writing_prompt'] = $prompt;
}

// FR18: start or resume attempt (mock)
if ($activityEnabled) {
    if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];
    $userId = current_user()['id'];
    $_SESSION['attempts'][$userId][$activityType] = [
      'status' => 'in_progress',
      'started_at' => date('c')
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$activityEnabled) {
        $errors[] = 'Bu aktivite şu anda kullanılamıyor.';
    }
    if (function_exists('csrf_validate')) {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!csrf_validate($csrf)) {
            $errors[] = 'Oturum doğrulama hatası. Lütfen sayfayı yenileyip tekrar deneyin.';
        }
    }
    $text = trim((string)($textValue ?? ''));
    if (strlen($text) < 50) {
        $errors[] = 'Yanıt en az 50 karakter olmalıdır.';
    }
    if ($activityId <= 0) {
        $errors[] = 'Aktivite bulunamadı.';
    }

    if (!$errors) {
        $result = evaluate_writing($text);
        $score = (int)($result['score_percent'] ?? 0);
        if ($score < 40) {
            $cefrLevel = 'A1';
        } elseif ($score < 50) {
            $cefrLevel = 'A2';
        } elseif ($score < 65) {
            $cefrLevel = 'B1';
        } elseif ($score < 80) {
            $cefrLevel = 'B2';
        } elseif ($score < 90) {
            $cefrLevel = 'C1';
        } else {
            $cefrLevel = 'C2';
        }

        $weakTopics = [];
        if (str_word_count($text) < 80) {
            $weakTopics[] = 'coherence';
        }
        if (!preg_match('/[.!?]/', $text)) {
            $weakTopics[] = 'grammar';
        }
        if (!$weakTopics) {
            $weakTopics = ['grammar', 'coherence'];
        }

        $feedback = $prompt !== ''
            ? 'You responded to the prompt: "' . $prompt . '". Focus on grammar and coherence.'
            : 'Your response was recorded. Focus on grammar and coherence.';
        $inputRef = json_encode(['prompt' => $prompt, 'text' => $text]);

        $evalId = db_create_evaluation(
            current_user()['id'],
            $activityId,
            $score,
            $cefrLevel,
            $feedback,
            $weakTopics,
            'Text',
            $inputRef
        );

        if ($evalId) {
            db_create_ai_log(
                current_user()['id'],
                'writing',
                $prompt,
                $feedback
            );
            if (!isset($_SESSION['performance'])) {
                $_SESSION['performance'] = [];
            }
            $_SESSION['performance'][] = [
                'user_id' => current_user()['id'],
                'score_percent' => $score,
                'weak_topics' => $weakTopics,
                'created_at' => gmdate('c')
            ];

            if (!isset($_SESSION['usage_logs'])) {
                $_SESSION['usage_logs'] = [];
            }
            $_SESSION['usage_logs'][] = [
                'user_id' => current_user()['id'],
                'action' => 'submit_writing',
                'created_at' => gmdate('c')
            ];

            if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];
            $_SESSION['attempts'][current_user()['id']][$activityType]['status'] = 'completed';
            $_SESSION['attempts'][current_user()['id']][$activityType]['completed_at'] = gmdate('c');

            $_SESSION['last_result'] = [
                'score_percent' => $score,
                'weak_topics' => $weakTopics,
                'feedback' => $feedback
            ];
            redirect('/results/feedback.php?id=' . $evalId);
        } else {
            $errors[] = 'Değerlendirme kaydedilemedi. Lütfen tekrar deneyin.';
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2><?php echo htmlspecialchars($activityTitle); ?></h2>
    <p class="muted"><?php echo htmlspecialchars($activityDescription); ?></p>
    <p class="muted"><strong>Writing Prompt:</strong> <?php echo htmlspecialchars($prompt); ?></p>
  </div>
</section>

<?php if ($errors): ?>
<section class="section">
  <div class="card">
    <div class="error" role="alert" aria-live="polite">
      <ul class="list">
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$activityEnabled): ?>
<section class="section">
  <div class="card">
    <p class="muted">Bu aktivite yakında aktif olacak.</p>
    <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back</a>
  </div>
</section>
<?php else: ?>
<section class="section">
  <form method="POST" action="<?php echo htmlspecialchars(BASE_URL . '/activity/writing.php' . ($activityId ? '?activity_id=' . $activityId : '')); ?>" class="card" aria-label="Writing form" id="writing_form">
    <?php if (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <?php endif; ?>
    <input type="hidden" name="activity_type" value="writing">
    <input type="hidden" name="activity_id" value="<?php echo (int)$activityId; ?>">
    <input type="hidden" name="prompt_text" id="prompt_text" value="<?php echo htmlspecialchars($prompt); ?>">

    <label for="writing_text">Your Answer</label>
    <textarea
      id="writing_text"
      name="writing_text"
      required
      placeholder="Write your paragraph here..."
    ><?php echo htmlspecialchars($textValue); ?></textarea>

    <p class="muted" style="margin-top:10px;">Minimum 10 words recommended.</p>

    <div id="writing_client_error" class="error" role="alert" aria-live="polite" style="display:none; margin-top:12px;"></div>
    <div id="writing_status" class="muted" role="status" aria-live="polite" style="margin-top:12px; display:none;"></div>

    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
      <button type="submit" class="btn btn-primary" id="writing_submit">Submit Writing</button>
      <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back</a>
    </div>
  </form>
</section>
<?php endif; ?>

<script>
(function() {
  const form = document.getElementById('writing_form');
  const textEl = document.getElementById('writing_text');
  const promptEl = document.getElementById('prompt_text');
  const errorEl = document.getElementById('writing_client_error');
  const statusEl = document.getElementById('writing_status');
  const submitBtn = document.getElementById('writing_submit');

  if (!form || !textEl || !promptEl || !errorEl || !statusEl || !submitBtn) {
    return;
  }

  const minWords = 10;
  const apiUrl = '<?php echo htmlspecialchars(BASE_URL . '/services/api.php?module=evaluate_writing'); ?>';

  const setError = (msg) => {
    if (!msg) {
      errorEl.textContent = '';
      errorEl.style.display = 'none';
      return;
    }
    errorEl.textContent = msg;
    errorEl.style.display = 'block';
  };

  const setStatus = (msg) => {
    if (!msg) {
      statusEl.textContent = '';
      statusEl.style.display = 'none';
      return;
    }
    statusEl.textContent = msg;
    statusEl.style.display = 'block';
  };

  const countWords = (text) => {
    const words = text.trim().split(/\s+/).filter(Boolean);
    return words.length;
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setError('');

    const promptText = promptEl.value || '';
    const userText = textEl.value || '';
    const wordCount = countWords(userText);
    if (wordCount < minWords) {
      setError('Please write at least ' + minWords + ' words before submitting.');
      return;
    }

    submitBtn.disabled = true;
    setStatus('Evaluating your writing...');

    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          prompt_text: promptText,
          user_text: userText
        })
      });

      const payload = await response.json();
      if (!response.ok || !payload || payload.ok !== true) {
        const message = payload && payload.error ? payload.error : 'Evaluation failed.';
        setError(message);
        if (payload && payload.debug) {
          console.error('Writing evaluation debug:', payload.debug);
        }
        return;
      }

      const evalId = payload.data && payload.data.evaluation_id ? payload.data.evaluation_id : null;
      if (!evalId) {
        setError('Evaluation completed but no id returned.');
        return;
      }

      window.location.href = '<?php echo htmlspecialchars(BASE_URL); ?>/results/feedback.php?id=' + encodeURIComponent(evalId);
    } catch (err) {
      setError('Network error. Please try again.');
    } finally {
      submitBtn.disabled = false;
      setStatus('');
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
