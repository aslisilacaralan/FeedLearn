<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/evaluator.php';
require_login();

$activityType = 'speaking';
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
$activityTitle = $activity['title'] ?? 'Speaking Activity';
$activityDescription = $activity['description'] ?? 'Record with your microphone or upload an audio file.';
$activityEnabled = (int)($activity['is_enabled'] ?? 1) === 1;
$errors = [];

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
    if ($activityId <= 0) {
        $errors[] = 'Aktivite bulunamadı.';
    }

    if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Ses dosyası yüklenemedi.';
    } else {
        $maxBytes = 10 * 1024 * 1024;
        if ((int)$_FILES['audio_file']['size'] > $maxBytes) {
            $errors[] = 'Dosya boyutu 10MB sınırını aşıyor.';
        }
    }

    if (!$errors) {
        $uploadDir = __DIR__ . '/../public/uploads/audio/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['mp3', 'wav', 'webm', 'ogg', 'm4a', 'mp4'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Lütfen mp3 veya wav formatında bir dosya yükleyin.';
        } else {
            $fileName = 'audio_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['audio_file']['tmp_name'], $targetPath)) {
                $errors[] = 'Ses dosyası kaydedilemedi.';
            } else {
                $result = evaluate_speaking($targetPath);
                $evalId = db_create_evaluation(
                    current_user()['id'],
                    $activityId,
                    (int)($result['score_percent'] ?? 0),
                    null,
                    $result['feedback'] ?? null,
                    $result['weak_topics'] ?? [],
                    'audio',
                    $targetPath
                );

                if ($evalId) {
                    db_create_ai_log(
                        current_user()['id'],
                        'speaking',
                        $activityTitle,
                        $result['feedback'] ?? null
                    );
                    if (!isset($_SESSION['performance'])) {
                        $_SESSION['performance'] = [];
                    }
                    $_SESSION['performance'][] = [
                        'user_id' => current_user()['id'],
                        'score_percent' => (int)($result['score_percent'] ?? 0),
                        'weak_topics' => $result['weak_topics'] ?? [],
                        'created_at' => gmdate('c')
                    ];

                    if (!isset($_SESSION['usage_logs'])) {
                        $_SESSION['usage_logs'] = [];
                    }
                    $_SESSION['usage_logs'][] = [
                        'user_id' => current_user()['id'],
                        'action' => 'submit_speaking',
                        'created_at' => gmdate('c')
                    ];

                    if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];
                    $_SESSION['attempts'][current_user()['id']][$activityType]['status'] = 'completed';
                    $_SESSION['attempts'][current_user()['id']][$activityType]['completed_at'] = gmdate('c');

                    $_SESSION['last_result'] = $result;
                    redirect('/results/feedback.php?id=' . $evalId);
                } else {
                    $errors[] = 'Değerlendirme kaydedilemedi. Lütfen tekrar deneyin.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<section class="section">
  <div class="card">
    <h2><?php echo htmlspecialchars($activityTitle); ?></h2>
    <p class="muted"><?php echo htmlspecialchars($activityDescription); ?></p>
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
  <form
    method="POST"
    action="<?php echo htmlspecialchars(BASE_URL . '/activity/speaking.php' . ($activityId ? '?activity_id=' . $activityId : '')); ?>"
    enctype="multipart/form-data"
    class="card"
    aria-label="Speaking form"
  >
    <input type="hidden" name="activity_type" value="speaking">
    <input type="hidden" name="activity_id" value="<?php echo (int)$activityId; ?>">

    <div style="margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #eee;">
      <h3>Record Audio</h3>
      <p class="muted">Use your microphone to capture a short response. The recording will be attached below.</p>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" id="record_start">Start Recording</button>
        <button type="button" class="btn" id="record_stop" disabled>Stop</button>
        <button type="button" class="btn" id="record_reset" disabled>Reset</button>
      </div>

      <p id="record_status" class="muted" aria-live="polite" style="margin-top:10px;">Ready to record.</p>
      <audio id="record_preview" controls style="width:100%; margin-top:10px; display:none;"></audio>
    </div>

    <label for="audio_file">Upload Audio (mp3, wav, webm, ogg, m4a, mp4)</label>
    <input
      id="audio_file"
      type="file"
      name="audio_file"
      accept=".mp3,.wav,.webm,.ogg,.m4a,.mp4"
      required
    >

    <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
      <button type="submit" class="btn btn-primary">Submit Speaking</button>
      <a class="btn" href="<?php echo BASE_URL; ?>/dashboard.php">Back</a>
    </div>
  </form>
</section>

<script>
(function() {
  const startBtn = document.getElementById('record_start');
  const stopBtn = document.getElementById('record_stop');
  const resetBtn = document.getElementById('record_reset');
  const statusEl = document.getElementById('record_status');
  const previewEl = document.getElementById('record_preview');
  const fileInput = document.getElementById('audio_file');

  if (!startBtn || !stopBtn || !resetBtn || !statusEl || !previewEl || !fileInput) {
    return;
  }

  if (!window.MediaRecorder || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    startBtn.disabled = true;
    stopBtn.disabled = true;
    resetBtn.disabled = true;
    statusEl.textContent = 'Recording is not supported in this browser. Please upload a file instead.';
    return;
  }

  let mediaRecorder = null;
  let chunks = [];
  let stream = null;
  let timerId = null;
  let startTime = 0;
  let previewUrl = '';
  let recordedAttached = false;
  let hasRecording = false;

  const setStatus = (text) => {
    statusEl.textContent = text;
  };

  const clearTimer = () => {
    if (timerId) {
      clearInterval(timerId);
      timerId = null;
    }
  };

  const formatTime = (ms) => {
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  };

  const updateTimer = () => {
    const elapsed = Date.now() - startTime;
    setStatus('Recording... ' + formatTime(elapsed));
  };

  const pickMimeType = () => {
    const options = [
      'audio/webm;codecs=opus',
      'audio/webm',
      'audio/ogg;codecs=opus',
      'audio/ogg',
      'audio/mp4'
    ];
    for (const type of options) {
      if (MediaRecorder.isTypeSupported(type)) {
        return type;
      }
    }
    return '';
  };

  const mimeToExt = (mimeType) => {
    if (!mimeType) return 'webm';
    if (mimeType.indexOf('webm') !== -1) return 'webm';
    if (mimeType.indexOf('ogg') !== -1) return 'ogg';
    if (mimeType.indexOf('mp4') !== -1) return 'mp4';
    return 'webm';
  };

  const clearPreview = () => {
    if (previewUrl) {
      URL.revokeObjectURL(previewUrl);
      previewUrl = '';
    }
    previewEl.removeAttribute('src');
    previewEl.style.display = 'none';
    hasRecording = false;
  };

  const attachRecording = (blob, ext) => {
    const safeStamp = new Date().toISOString().replace(/[:.]/g, '-');
    const fileName = 'recording_' + safeStamp + '.' + ext;
    try {
      const file = new File([blob], fileName, { type: blob.type || 'audio/' + ext });
      if (window.DataTransfer) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        recordedAttached = true;
        return true;
      }
    } catch (err) {
      recordedAttached = false;
    }
    return false;
  };

  const setButtons = (state) => {
    if (state === 'recording') {
      startBtn.disabled = true;
      stopBtn.disabled = false;
      resetBtn.disabled = true;
      return;
    }
    if (state === 'ready') {
      startBtn.disabled = false;
      stopBtn.disabled = true;
      resetBtn.disabled = false;
      return;
    }
    startBtn.disabled = false;
    stopBtn.disabled = true;
    resetBtn.disabled = !hasRecording;
  };

  startBtn.addEventListener('click', async () => {
    clearPreview();
    if (recordedAttached) {
      fileInput.value = '';
    }
    recordedAttached = false;
    setStatus('Requesting microphone access...');
    setButtons('recording');

    try {
      stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (err) {
      setStatus('Microphone permission denied or unavailable.');
      setButtons('idle');
      return;
    }

    const mimeType = pickMimeType();
    chunks = [];
    try {
      mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
    } catch (err) {
      setStatus('Recording could not start. Please upload a file instead.');
      setButtons('idle');
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
      return;
    }

    mediaRecorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) {
        chunks.push(event.data);
      }
    };

    mediaRecorder.onstop = () => {
      clearTimer();
      if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
      }
      const blobType = mediaRecorder && mediaRecorder.mimeType ? mediaRecorder.mimeType : (chunks[0] ? chunks[0].type : '');
      const blob = new Blob(chunks, { type: blobType || 'audio/webm' });
      if (!blob.size) {
        setStatus('Recording failed. Please try again or upload a file.');
        setButtons('idle');
        return;
      }
      const ext = mimeToExt(blob.type || blobType);
      previewUrl = URL.createObjectURL(blob);
      previewEl.src = previewUrl;
      previewEl.style.display = 'block';
      hasRecording = true;
      const attached = attachRecording(blob, ext);
      if (attached) {
        setStatus('Recording ready. Submit or record again.');
      } else {
        setStatus('Recording ready, but it could not be attached. Please upload the file manually.');
      }
      setButtons('ready');
    };

    mediaRecorder.start();
    startTime = Date.now();
    updateTimer();
    timerId = setInterval(updateTimer, 500);
  });

  stopBtn.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
      mediaRecorder.stop();
    }
    stopBtn.disabled = true;
  });

  resetBtn.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
      return;
    }
    clearPreview();
    if (recordedAttached) {
      fileInput.value = '';
    }
    recordedAttached = false;
    setStatus('Ready to record.');
    setButtons('idle');
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files && fileInput.files.length > 0) {
      if (recordedAttached) {
        clearPreview();
        recordedAttached = false;
        hasRecording = false;
      }
      resetBtn.disabled = true;
      setStatus('File selected for upload.');
    }
  });

  setButtons('idle');
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
