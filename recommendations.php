<?php
require_once __DIR__ . '/auth/_guard.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';
require_login();

// 1. Get recent evaluations
$userId = current_user()['id'];
$recent = db_get_recent_evaluations_for_user($userId, 20);

// 2. Calculate Averages
$scores = ['quiz' => [], 'speaking' => [], 'writing' => []];
$counts = ['quiz' => 0, 'speaking' => 0, 'writing' => 0];

foreach ($recent as $r) {
    $type = $r['activity_type'] ?? 'unknown';
    if (isset($scores[$type])) {
        $scores[$type][] = (float)$r['score'];
        $counts[$type]++;
    }
}

$averages = [];
foreach ($scores as $type => $vals) {
    if (count($vals) > 0) {
        $averages[$type] = array_sum($vals) / count($vals);
    } else {
        $averages[$type] = null; // No data
    }
}

// 3. Determine Recommendation
// Logic:
// - If no data at all -> Quiz (Start basic)
// - If data exists, pick the LOWEST average score.
// - If all scores are high (>85), pick the one with fewest attempts (Balance).

$recType = 'quiz';
$recReason = "Start your journey with a quick quiz to test your grammar.";
$recLink = BASE_URL . '/activity/quiz.php';

if (empty($recent)) {
    // Default
} else {
    // Find lowest score
    $minScore = 101;
    $lowestType = null;
    $hasData = false;

    foreach ($averages as $type => $avg) {
        if ($avg !== null) {
            $hasData = true;
            if ($avg < $minScore) {
                $minScore = $avg;
                $lowestType = $type;
            }
        }
    }

    if ($lowestType) {
        // If the lowest score is actually quite good (>80), maybe suggest something else?
        // For simplicity, always suggest the weakest link.
        $recType = $lowestType;
        $recReason = "Your recent performance in " . ucfirst($lowestType) . " averages " . round($minScore) . "%. Focusing here will improve your overall level.";
    } else {
        // Has data but somehow averages are null? Fallback.
        $recType = 'writing';
        $recReason = "Try writing a short essay to practice sentence structure.";
    }
}

// Map type to link
switch ($recType) {
    case 'quiz': 
        $recLink = BASE_URL . '/activity/quiz.php'; 
        $icon = '⚡';
        break;
    case 'speaking': 
        $recLink = BASE_URL . '/activity/speaking.php'; 
        $icon = '🎤';
        break;
    case 'writing': 
        $recLink = BASE_URL . '/activity/writing.php'; 
        $icon = '✍️';
        break;
}

require_once __DIR__ . '/templates/header.php';
?>

<section class="section">
    <div class="card" style="text-align: center; padding: 40px;">
        <div style="font-size: 3rem; margin-bottom: 20px;"><?= $icon ?></div>
        <h2 style="font-size: 2rem; margin-bottom: 10px;">Recommended: <?= ucfirst($recType) ?></h2>
        <p class="muted" style="font-size: 1.1rem; max-width: 600px; margin: 0 auto 30px;">
            <?= htmlspecialchars($recReason) ?>
        </p>
        
        <a href="<?= $recLink ?>" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
            Start <?= ucfirst($recType) ?>
        </a>
        
        <div style="margin-top: 30px;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
