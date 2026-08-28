<?php
/*
 * PageLink - Session Handler para Turso (serverless).
 *
 * En Vercel el filesystem es efimero entre invocaciones, por lo que las
 * sesiones nativas de PHP (archivos) no persisten. Este handler guarda
 * cada sesion en la tabla `sessions` de Turso usando el mismo PDO/TursoPDO
 * de la aplicacion, manteniendo el modelo $_SESSION intacto.
 *
 * USO: require_once app/session.php  (antes de cualquier session_start)
 */
declare(strict_types=1);

// Solo se registra el handler una vez
if (!function_exists('pagelink_session_registered')) {
    function pagelink_session_registered(): bool { return false; }
}

function pagelink_register_session_handler(): void {
    static $registered = false;
    if ($registered) {
        return;
    }

    $db = getDB();

    // Crear la tabla de sesiones si no existe
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS sessions (
                id      TEXT PRIMARY KEY,
                data    TEXT NOT NULL DEFAULT '',
                ts      INTEGER NOT NULL DEFAULT 0
            )
        ");
    } catch (\Throwable $e) {
        error_log('PageLink session: no se pudo crear tabla sessions: ' . $e->getMessage());
    }

    $open    = function (string $savePath, string $sessionName): bool { return true; };
    $close   = function (): bool { return true; };
    $read    = function (string $id) use ($db): string {
        try {
            $stmt = $db->prepare("SELECT data FROM sessions WHERE id = ?");
            $stmt->execute([$id]);
            return (string)($stmt->fetchColumn() ?: '');
        } catch (\Throwable $e) {
            return '';
        }
    };
    $write   = function (string $id, string $data) use ($db): bool {
        try {
            $stmt = $db->prepare(
                "INSERT INTO sessions (id, data, ts) VALUES (?, ?, ?)
                 ON CONFLICT(id) DO UPDATE SET data = excluded.data, ts = excluded.ts"
            );
            $stmt->execute([$id, $data, time()]);
            return true;
        } catch (\Throwable $e) {
            error_log('PageLink session write: ' . $e->getMessage());
            return false;
        }
    };
    $destroy = function (string $id) use ($db): bool {
        try {
            $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([$id]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    };
    $gc     = function (int $maxLifetime) use ($db): bool {
        try {
            $expire = time() - $maxLifetime;
            $stmt = $db->prepare("DELETE FROM sessions WHERE ts < ?");
            $stmt->execute([$expire]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    };

    session_set_save_handler($open, $close, $read, $write, $destroy, $gc);
    $registered = true;
}

// Registrar el handler inmediatamente si estamos en Vercel.
if (IS_VERCEL) {
    pagelink_register_session_handler();
}
