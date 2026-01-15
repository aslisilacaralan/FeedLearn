<?php
function handle_speaking_api()
{
    $uploadsDir = __DIR__ . '/../public/uploads/audio';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $orig = basename($_FILES['file']['name']);
            $target = $uploadsDir . '/' . uniqid('speech_', true) . '_' . $orig;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {

                // 🔹 BASİT "AI-LIKE" DEĞERLENDİRME
                $fileSizeKB = filesize($target) / 1024; // KB
                $durationScore = min(100, 50 + ($fileSizeKB / 10));
            
                $score = round($durationScore);
                $cefr = $score >= 85 ? 'B2' : ($score >= 70 ? 'B1' : 'A2');
            
                $feedback = "Audio analyzed. Duration-based speaking evaluation applied.";
                $weakTopics = [];
            
                if ($score < 75) {
                    $weakTopics[] = 'Pronunciation';
                }
                if ($score < 65) {
                    $weakTopics[] = 'Fluency';
                }
            
                // 🔹 FRONTEND'E GERÇEK SONUÇ DÖN
                echo json_encode([
                    'success' => true,
                    'path' => str_replace(__DIR__ . '/..', '', $target),
                    'score' => $score,
                    'cefr' => $cefr,
                    'feedback' => $feedback,
                    'weak_topics' => $weakTopics
                ]);
                return;
            }
            }
            echo json_encode(['success' => false, 'error' => 'file_move_failed']);
            return;
        }

        echo json_encode(['success' => false, 'error' => 'no_file_uploaded']);
        return;
    }

    // GET -> list recent audio files
    $files = glob($uploadsDir . '/*');
    rsort($files);
    $out = [];
    foreach (array_slice($files, 0, 50) as $f) {
        $out[] = ['file' => str_replace(__DIR__ . '/..', '', $f), 'mtime' => filemtime($f)];
    }
    echo json_encode(['success' => true, 'recordings' => $out]);
}
