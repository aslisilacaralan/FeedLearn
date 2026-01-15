<?php
function handle_writing_api()
{
    $uploadsDir = __DIR__ . '/../public/uploads/writing';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Accept file upload or JSON body with 'text' and optional 'user_id'
        if (!empty($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $orig = basename($_FILES['file']['name']);
            $target = $uploadsDir . '/' . uniqid('writing_', true) . '_' . $orig;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                echo json_encode(['success' => true, 'path' => str_replace(__DIR__ . '/..', '', $target)]);
                return;
            }
            echo json_encode(['success' => false, 'error' => 'file_move_failed']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if (!empty($body['text'])) {
            $filename = $uploadsDir . '/' . uniqid('writing_', true) . '.txt';
            if (file_put_contents($filename, $body['text']) !== false) {
                echo json_encode(['success' => true, 'path' => str_replace(__DIR__ . '/..', '', $filename)]);
                return;
            }
            echo json_encode(['success' => false, 'error' => 'save_failed']);
            return;
        }

        echo json_encode(['success' => false, 'error' => 'no_input_provided']);
        return;
    }

    // GET -> list recent submissions
    $files = glob($uploadsDir . '/*');
    rsort($files);
    $out = [];
    foreach (array_slice($files, 0, 50) as $f) {
        $out[] = ['file' => str_replace(__DIR__ . '/..', '', $f), 'mtime' => filemtime($f)];
    }
    echo json_encode(['success' => true, 'submissions' => $out]);
}
