<?php
// .env loader (simple)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
// config/constants.php
// Proje genel sabitleri

// ================================
// APP
// ================================
define('APP_NAME', 'FEEDLEARN');

// Base URL (htdocs altındaki klasör adı)
// http://localhost/feedlearn
define('BASE_URL', '/feedlearn');

// ================================
// ROLES
// ================================
define('ROLE_STUDENT', 'user');
define('ROLE_ADMIN', 'admin');

// ================================
// DEBUG
// ================================
$appDebugEnv = getenv('APP_DEBUG') ?: '';
define(
    'APP_DEBUG',
    getenv('APP_DEBUG') === 'true'
);
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');




// ================================
// HELPERS
// ================================
function redirect(string $path): void
{
    if ($path && $path[0] !== '/') {
        $path = '/' . $path;
    }

    header('Location: ' . BASE_URL . $path);
    exit;
}