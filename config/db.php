<?php
// config/db.php
// SQLite persistence layer (PDO).

require_once __DIR__ . '/config.php';

function db_connect(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbDir = dirname(DB_PATH);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    db_init_schema($pdo);

    return $pdo;
}

function db_apply_schema_file(PDO $pdo): void {
    $schemaPath = __DIR__ . '/schema.sql';
    if (!is_file($schemaPath)) {
        return;
    }
    $sql = trim((string) file_get_contents($schemaPath));
    if ($sql === '') {
        return;
    }
    $pdo->exec($sql);
}

function db_ai_logs_columns(PDO $pdo): array {
    $stmt = $pdo->query("PRAGMA table_info(ai_logs)");
    if (!$stmt) {
        return [];
    }
    $columns = [];
    foreach ($stmt->fetchAll() as $row) {
        $name = $row['name'] ?? '';
        if ($name !== '') {
            $columns[$name] = true;
        }
    }
    return $columns;
}

function db_ensure_ai_logs_columns(PDO $pdo): void {
    $columns = db_ai_logs_columns($pdo);
    if (!$columns) {
        return;
    }
    $missing = ['module', 'prompt_summary', 'response_summary'];
    foreach ($missing as $column) {
        if (!isset($columns[$column])) {
            $pdo->exec('ALTER TABLE ai_logs ADD COLUMN ' . $column . ' TEXT');
        }
    }

    if (isset($columns['action'])) {
        $pdo->exec("UPDATE ai_logs SET module = action WHERE (module IS NULL OR module = '')");
    }
}

function db_init_schema(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS activities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            activity_type TEXT NOT NULL,
            is_enabled INTEGER NOT NULL DEFAULT 1
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS evaluations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            activity_id INTEGER NOT NULL,
            score REAL,
            cefr_level TEXT,
            feedback TEXT,
            weak_topics_json TEXT,
            input_type TEXT,
            input_ref TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            notifications_enabled INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS ai_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            module TEXT NOT NULL,
            prompt_summary TEXT,
            response_summary TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    );

    db_apply_schema_file($pdo);
    db_ensure_ai_logs_columns($pdo);

    $activityCount = (int) $pdo->query('SELECT COUNT(*) FROM activities')->fetchColumn();
    if ($activityCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO activities (title, description, activity_type, is_enabled)
             VALUES (:title, :description, :activity_type, :is_enabled)'
        );
        $seed = [
            [
                'title' => 'Speaking',
                'description' => 'Record or upload audio and receive speaking feedback.',
                'activity_type' => 'speaking',
                'is_enabled' => 1
            ],
            [
                'title' => 'Writing',
                'description' => 'Write a short text and get automated feedback.',
                'activity_type' => 'writing',
                'is_enabled' => 1
            ],
            [
                'title' => 'Quiz',
                'description' => 'Multiple-choice grammar and vocabulary practice.',
                'activity_type' => 'quiz',
                'is_enabled' => 1
            ],
        ];
        foreach ($seed as $row) {
            $stmt->execute($row);
        }
    }

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role, created_at)
             VALUES (:email, :password_hash, :role, :created_at)'
        );
        $stmt->execute([
            'email' => 'admin@feedlearn.local',
            'password_hash' => password_hash('Admin123!', PASSWORD_DEFAULT),
            'role' => 'admin',
            'created_at' => date('c')
        ]);
    }
}

// Kullaniciyi veritabanina kaydetme.
function db_create_user($email, $passwordHash, $role = 'user') {
    $pdo = db_connect();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role, created_at)
             VALUES (:email, :password_hash, :role, :created_at)'
        );

        return $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'created_at' => date('c')
        ]);

    } catch (PDOException $e) {
        die('DB ERROR: ' . $e->getMessage());
    }
}
// Kullaniciyi bulma.
function db_find_user_by_email($email) {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, email, password_hash, role, created_at FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }
    $user['name'] = db_display_name_from_email($user['email']);
    return $user;
}

function db_display_name_from_email($email): string {
    $email = trim((string) $email);
    if ($email === '') {
        return '';
    }
    $parts = explode('@', $email);
    return $parts[0] !== '' ? $parts[0] : $email;
}

function db_get_activities(): array {
    $pdo = db_connect();
    $stmt = $pdo->query(
        'SELECT id, title, description, activity_type, is_enabled FROM activities ORDER BY id ASC'
    );
    return $stmt->fetchAll();
}

function db_get_activity_by_id($activityId) {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, title, description, activity_type, is_enabled FROM activities WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => (int) $activityId]);
    $activity = $stmt->fetch();
    return $activity ?: null;
}

function db_get_activity_by_type($activityType) {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, title, description, activity_type, is_enabled
         FROM activities
         WHERE activity_type = :activity_type
         ORDER BY id ASC
         LIMIT 1'
    );
    $stmt->execute(['activity_type' => $activityType]);
    $activity = $stmt->fetch();
    return $activity ?: null;
}

function db_create_evaluation(
    $userId,
    $activityId,
    $score,
    $cefrLevel,
    $feedback,
    array $weakTopics,
    $inputType,
    $inputRef
) {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'INSERT INTO evaluations
            (user_id, activity_id, score, cefr_level, feedback, weak_topics_json, input_type, input_ref, created_at)
         VALUES
            (:user_id, :activity_id, :score, :cefr_level, :feedback, :weak_topics_json, :input_type, :input_ref, :created_at)'
    );
    $ok = $stmt->execute([
        'user_id' => (int) $userId,
        'activity_id' => (int) $activityId,
        'score' => $score,
        'cefr_level' => $cefrLevel,
        'feedback' => $feedback,
        'weak_topics_json' => json_encode(array_values($weakTopics)),
        'input_type' => $inputType,
        'input_ref' => $inputRef,
        'created_at' => gmdate('c')
    ]);
    if (!$ok) {
        return null;
    }
    return (int) $pdo->lastInsertId();
}

function db_get_last_evaluation_for_user($userId) {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, user_id, activity_id, score, cefr_level, feedback, weak_topics_json, input_type, input_ref, created_at
         FROM evaluations
         WHERE user_id = :user_id
         ORDER BY datetime(created_at) DESC, id DESC
         LIMIT 1'
    );
    $stmt->execute(['user_id' => (int) $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_get_evaluation_by_id($evaluationId) {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT id, user_id, activity_id, score, cefr_level, feedback, weak_topics_json, input_type, input_ref, created_at
         FROM evaluations
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $evaluationId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_truncate_summary($value, $limit = 120) {
    $text = trim((string) $value);
    if ($text === '') {
        return null;
    }
    $text = preg_replace('/\s+/', ' ', $text);
    $length = strlen($text);
    if ($length <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit);
}

function db_create_ai_log($userId, $module, $promptSummary = null, $responseSummary = null) {
    $pdo = db_connect();
    try {
        $columns = db_ai_logs_columns($pdo);
        $fields = ['user_id', 'prompt_summary', 'response_summary', 'created_at'];
        $params = [
            'user_id' => $userId !== null ? (int) $userId : null,
            'prompt_summary' => db_truncate_summary($promptSummary),
            'response_summary' => db_truncate_summary($responseSummary),
            'created_at' => gmdate('Y-m-d H:i:s')
        ];

        if (isset($columns['module'])) {
            $fields[] = 'module';
            $params['module'] = (string) $module;
        }
        if (isset($columns['action'])) {
            $fields[] = 'action';
            $params['action'] = (string) $module;
        }

        if (!isset($params['module']) && !isset($params['action'])) {
            return false;
        }

        $placeholders = array_map(function ($field) {
            return ':' . $field;
        }, $fields);
        $stmt = $pdo->prepare(
            'INSERT INTO ai_logs (' . implode(', ', $fields) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );
        return $stmt->execute($params);
    } catch (PDOException $e) {
        return false;
    }
}
?>
