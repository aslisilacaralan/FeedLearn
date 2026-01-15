<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/evaluator.php';
require_login();

// FR4: kullanıcı cevap gönderir
// FR5: cevap sistemde saklanır (şimdilik session/mock)
// FR7: otomatik değerlendirme tetiklenir

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard.php');
}

$activityType = $_POST['activity_type'] ?? '';
$activityId = (int)($_POST['activity_id'] ?? 0);
$activity = null;
$inputType = null;
$inputRef = null;
$result = null;
$evalId = null;

if ($activityId <= 0 && $activityType !== '') {
    $activity = db_get_activity_by_type($activityType);
    $activityId = (int)($activity['id'] ?? 0);
}

// Basit "storage" (FR5) -> session içine kaydedelim (mock DB yerine)
if (!isset($_SESSION['submissions'])) {
    $_SESSION['submissions'] = [];
}

if ($activityType === 'quiz') {
    $_SESSION['submissions'][] = ['type' => 'quiz', 'data' => $_POST, 'created_at' => date('c')];
    $result = evaluate_quiz($_POST);
    $inputType = 'quiz';

} elseif ($activityType === 'writing') {
    $text = trim($_POST['writing_text'] ?? '');
    $_SESSION['submissions'][] = ['type' => 'writing', 'data' => $text, 'created_at' => date('c')];
    $result = evaluate_writing($text);
    $inputType = 'text';
    $inputRef = $text;

} elseif ($activityType === 'speaking') {
    // FR6: ses kaydı yükleme
    $uploadDir = __DIR__ . '/../public/uploads/audio/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        $result = ['score_percent' => 0, 'weak_topics' => ['Upload error'], 'feedback' => 'Audio upload failed.'];
        $inputType = 'audio';
    } else {
        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['mp3', 'wav', 'webm', 'ogg', 'm4a', 'mp4'];
        if (!in_array($ext, $allowed, true)) {
            $result = ['score_percent' => 0, 'weak_topics' => ['Invalid format'], 'feedback' => 'Please upload mp3, wav, webm, ogg, m4a, or mp4.'];
            $inputType = 'audio';
        } else {
            $fileName = 'audio_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            move_uploaded_file($_FILES['audio_file']['tmp_name'], $targetPath);

            $_SESSION['submissions'][] = ['type' => 'speaking', 'data' => $targetPath, 'created_at' => date('c')];
            $result = evaluate_speaking($targetPath);
            $inputType = 'audio';
            $inputRef = $targetPath;
        }
    }

} else {
    $result = ['score_percent' => 0, 'weak_topics' => ['Unknown activity'], 'feedback' => 'Unknown activity type.'];
}

$userId = current_user()['id'];
if ($activityId > 0 && $result) {
    $evalId = db_create_evaluation(
        $userId,
        $activityId,
        (int)($result['score_percent'] ?? 0),
        null,
        $result['feedback'] ?? null,
        $result['weak_topics'] ?? [],
        $inputType,
        $inputRef
    );
}
// FR11: performance data store (mock DB -> session)
if (!isset($_SESSION['performance'])) {
    $_SESSION['performance'] = []; 
}
// her sonuç bir performance kaydıdır
$_SESSION['performance'][] = [
    'user_id' => $userId,
    'score_percent' => (int)($result['score_percent'] ?? 0),
    'weak_topics' => $result['weak_topics'] ?? [],
    'created_at' => gmdate('c')
];

// FR14 için usage log (mock)
if (!isset($_SESSION['usage_logs'])) {
    $_SESSION['usage_logs'] = [];
}
$_SESSION['usage_logs'][] = [
    'user_id' => $userId,
    'action' => 'submit_' . $activityType,
    'created_at' => gmdate('c')
];
// FR18: mark attempt completed
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = [];
$_SESSION['attempts'][$userId][$activityType]['status'] = 'completed';
$_SESSION['attempts'][$userId][$activityType]['completed_at'] = gmdate('c');


// sonucu session’a koyup feedback sayfasına gönderelim
$_SESSION['last_result'] = $result;
if ($evalId) {
    redirect('/results/feedback.php?id=' . $evalId);
}
redirect('/results/feedback.php');
