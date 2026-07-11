<?php

$DB_PATH = __DIR__ . '/../database.sqlite';

function base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    return $dir === '/' || $dir === '\\' ? '' : $dir;
}

function getDB(): PDO {
    global $DB_PATH;
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO("sqlite:$DB_PATH", null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA foreign_keys=ON');
    }
    return $pdo;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function flash_set(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

define('SESSION_TIMEOUT', 3600);

function check_session_timeout(): void {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ($lastActivity > 0 && (time() - $lastActivity) > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: ' . (basename($_SERVER['SCRIPT_NAME']) === 'login.php' ? 'login.php' : dirname($_SERVER['SCRIPT_NAME']) . '/login.php'));
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}
