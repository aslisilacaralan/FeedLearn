<?php
// config/auth.php
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function login_user(array $user) {
    // Session fixation koruması (iyi görünür raporda da)
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $user['id'] ?? null,
        'name' => $user['name'] ?? '',
        'email' => $user['email'] ?? '',
        'role' => $user['role'] ?? ROLE_STUDENT
    ];
}

function logout_user() {
    // Session verisini temizle
    $_SESSION = [];

    // Cookie de temizle (bazı hataları çözer)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

function require_login() {
    if (!current_user()) {
        redirect('/auth/login.php');
    }
}
?>
