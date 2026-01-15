<?php
// auth/_guard.php
require_once __DIR__ . '/../config/auth.php';

function require_login($mode = 'redirect') {
    if (current_user()) {
        return;
    }
    if ($mode === 'json') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'data' => null,
            'error' => 'unauthorized'
        ]);
        exit;
    }
    redirect('/auth/login.php');
}

function require_admin($mode = 'redirect') {
    if (!current_user()) {
        if ($mode === 'json') {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'data' => null,
                'error' => 'unauthorized'
            ]);
            exit;
        }
        redirect('/auth/login.php');
    }
    $user = current_user();
    if (($user['role'] ?? '') !== ROLE_ADMIN) {
        if ($mode === 'json') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'data' => null,
                'error' => 'forbidden'
            ]);
            exit;
        }
        http_response_code(403);
        echo '403 Forbidden: Admin access required.';
        exit;
    }
}
?>
