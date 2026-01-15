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
                // Optionally: call evaluator service here
                echo json_encode(['success' => true, 'path' => str_replace(__DIR__ . '/..', '', $target)]);
                return;
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
