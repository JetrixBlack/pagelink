<?php
/*
 * Archivo de configuración de base de datos y utilidades generales.
 * Define la conexión PDO a SQLite, funciones de seguridad (CSRF),
 * mensajes flash y control de tiempo de sesión del administrador.
 */

// Ruta absoluta al archivo de base de datos SQLite
$DB_PATH = __DIR__ . '/../database.sqlite';

// Obtiene la URL base del proyecto a partir del nombre del script actual
function base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    return $dir === '/' || $dir === '\\' ? '' : $dir;
}

// Crea y retorna una conexión PDO a SQLite (patrón Singleton).
// Activa WAL para mejor concurrencia y habilita claves foráneas.
function getDB(): PDO {
    global $DB_PATH;
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO("sqlite:$DB_PATH", null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Modo WAL para permitir lecturas concurrentes
        $pdo->exec('PRAGMA journal_mode=WAL');
        // Habilitar restricciones de claves foráneas
        $pdo->exec('PRAGMA foreign_keys=ON');
    }
    return $pdo;
}

// Genera un token CSRF aleatorio de 64 caracteres hexadecimales.
// Lo almacena en la sesión para reutilizarlo en toda la petición.
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Retorna un campo hidden HTML con el token CSRF para incluirlo en formularios.
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Verifica que el token CSRF enviado en POST coincida con el de la sesión.
// Usa hash_equals para prevenir ataques de timing.
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Almacena un mensaje flash en la sesión para mostrarlo tras una redirección.
// $msg: texto del mensaje. $type: tipo CSS (success, error, etc.)
function flash_set(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

// Obtiene y elimina el mensaje flash de la sesión (solo se muestra una vez).
// Retorna un array con 'msg' y 'type', o null si no hay mensaje pendiente.
function flash_get(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// Tiempo máximo de inactividad de sesión del administrador (en segundos)
define('SESSION_TIMEOUT', 3600);

// Verifica si la sesión del administrador ha expirado por inactividad.
// Si expiró, destruye la sesión y redirige al login.
function check_session_timeout(): void {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ($lastActivity > 0 && (time() - $lastActivity) > SESSION_TIMEOUT) {
            // La sesión expiró, destruir y redirigir al login
            session_destroy();
            header('Location: ' . (basename($_SERVER['SCRIPT_NAME']) === 'login.php' ? 'login.php' : dirname($_SERVER['SCRIPT_NAME']) . '/login.php'));
            exit;
        }
        // Actualizar timestamp de última actividad
        $_SESSION['last_activity'] = time();
    }
}
