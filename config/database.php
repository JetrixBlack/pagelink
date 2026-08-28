<?php
/*
 * Configuración de base de datos y utilidades generales de PageLink.
 *
 * En producción (Vercel): usa Turso (libSQL/SQLite en la nube) vía HTTP.
 * En desarrollo local (XAMPP): usa SQLite local con PDO.
 *
 * El resto del código llama a getDB() sin saber qué motor está detrás.
 */

// Detectar si estamos en Vercel (tienen la env var VERCEL=1)
define('IS_VERCEL', !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']));

// Ruta al SQLite local (solo se usa en desarrollo)
$DB_PATH = __DIR__ . '/../database.sqlite';

// ─── Obtiene la URL base del proyecto ────────────────────────────────────────
function base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    return $dir === '/' || $dir === '\\' ? '' : $dir;
}

// ─── Conexión a base de datos (modo automático) ───────────────────────────────
/**
 * Retorna la conexión activa a la base de datos.
 * - En Vercel: instancia de TursoPDO (SQLite en la nube)
 * - En local: instancia de PDO conectada al archivo SQLite local
 *
 * Ambas implementan la misma interfaz (query, exec, prepare, etc.)
 */
function getDB(): PDO|object {
    static $connection = null;

    if ($connection === null) {
        if (IS_VERCEL) {
            // Modo producción: usar Turso vía HTTP
            require_once __DIR__ . '/turso.php';
            $connection = getTursoDB();
        } else {
            // Modo desarrollo: usar SQLite local con PDO estándar
            global $DB_PATH;
            $connection = new PDO("sqlite:$DB_PATH", null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // WAL para concurrencia y claves foráneas habilitadas
            $connection->exec('PRAGMA journal_mode=WAL');
            $connection->exec('PRAGMA foreign_keys=ON');
        }
    }

    return $connection;
}

// ─── Seguridad CSRF ───────────────────────────────────────────────────────────

/** Genera (o recupera de sesión) un token CSRF de 64 caracteres hex */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Retorna un campo hidden HTML con el token CSRF para formularios */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Verifica el token CSRF del POST contra el de sesión (timing-safe) */
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ─── Mensajes Flash ───────────────────────────────────────────────────────────

/** Guarda un mensaje flash en sesión para mostrarlo tras una redirección */
function flash_set(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

/** Recupera y elimina el mensaje flash de la sesión (single-use) */
function flash_get(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ─── Sesión de administrador ──────────────────────────────────────────────────

/** Tiempo máximo de inactividad del admin antes de cerrar sesión (1 hora) */
define('SESSION_TIMEOUT', 3600);

/**
 * Verifica si la sesión del administrador ha expirado.
 * Si expiró, destruye la sesión y redirige al login.
 */
function check_session_timeout(): void {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ($lastActivity > 0 && (time() - $lastActivity) > SESSION_TIMEOUT) {
            session_destroy();
            $currentDir = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $currentDir . '/session-expired.php');
            exit;
        }
        // Actualizar timestamp de última actividad
        $_SESSION['last_activity'] = time();
    }
}
