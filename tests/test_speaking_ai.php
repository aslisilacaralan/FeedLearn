<?php
require_once __DIR__ . '/../services/evaluator.php';

echo "Testing evaluate_speaking function...\n";

if (!function_exists('evaluate_speaking')) {
    echo "ERROR: evaluate_speaking function does not exist!\n";
    exit(1);
}

echo "Function exists.\n";

// Test with REAL file
$realFile = __DIR__ . '/../public/uploads/audio/audio_1768513246_4864.mp3';
echo "Testing with file: $realFile\n";
$result = evaluate_speaking($realFile);
print_r($result);


if ($result['feedback'] === 'Ses dosyası bulunamadı.') {
    echo "SUCCESS: Handled missing file correctly.\n";
} else {
    echo "FAILURE: Did not handle missing file correctly.\n";
}
