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
    // Session fixation protection.
    session_regenerate_id(true);

    $email = $user['email'] ?? '';
    $name = $user['name'] ?? '';
    if ($name === '' && $email !== '') {
        $name = explode('@', $email)[0] ?? $email;
    }

    $_SESSION['user'] = [
        'id' => $user['id'] ?? null,
        'user_id' => $user['id'] ?? null,
        'name' => $name,
        'email' => $email,
        'role' => $user['role'] ?? ROLE_STUDENT
    ];
}

function logout_user() {
    // Clear session data.
    $_SESSION = [];

    // Clear session cookie too.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate($token): bool {
    if (!is_string($token) || $token === '') {
        return false;
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}
?>
