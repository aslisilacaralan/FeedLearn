<?php
// services/notifier.php
// FR17: Notification preferences + notification generation (mock)

function get_notification_prefs($userId) {
    if (!isset($_SESSION['notification_prefs'])) {
        $_SESSION['notification_prefs'] = [];
    }
    return $_SESSION['notification_prefs'][$userId] ?? [
        'email_enabled' => 1,
        'weekly_report_enabled' => 1,
        'reminder_enabled' => 0
    ];
}

function set_notification_prefs($userId, $prefs) {
    if (!isset($_SESSION['notification_prefs'])) {
        $_SESSION['notification_prefs'] = [];
    }
    $_SESSION['notification_prefs'][$userId] = $prefs;
}

function push_notification($userId, $message) {
    if (!isset($_SESSION['notifications'])) {
        $_SESSION['notifications'] = [];
    }
    $_SESSION['notifications'][] = [
        'user_id' => $userId,
        'message' => $message,
        'created_at' => date('c')
    ];
}
?>
