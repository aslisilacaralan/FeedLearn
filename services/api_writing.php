<?php
// services/api_writing.php

require_once __DIR__ . '/../config/constants.php';
// require_once __DIR__ . '/../config/auth.php'; // Eğer giriş kontrolü varsa aç
require_once __DIR__ . '/evaluator.php';

header('Content-Type: application/json; charset=utf-8');

// Sadece POST isteği kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Sadece POST isteği atılabilir.']);
    exit;
}

// Gelen veriyi al
$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? $_POST['text'] ?? '';

if (trim($text) === '') {
    echo json_encode(['ok' => false, 'error' => 'Lütfen bir metin yazın.']);
    exit;
}

// Değerlendir
$sonuc = evaluate_writing($text);

// Cevabı Frontend'e gönder
echo json_encode([
    'ok' => true,
    'data' => $sonuc
]);