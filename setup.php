<?php
/*
 * Script de inicialización de base de datos para PageLink.
 *
 * ¡EJECUTAR UNA SOLA VEZ después del primer deploy en Vercel!
 * Acceder en: https://tu-proyecto.vercel.app/setup
 *
 * Crea todas las tablas, aplica migraciones y carga datos por defecto.
 * En producción usa Turso, en desarrollo usa SQLite local.
 */

// Desactivar ejecución desde web en producción (seguridad)
// Comentar esta línea solo cuando necesites ejecutar el setup
define('ALLOW_SETUP', true);

if (!ALLOW_SETUP && (IS_VERCEL ?? false)) {
    http_response_code(403);
    die(json_encode(['error' => 'Setup deshabilitado en producción. Actívalo en setup.php.']));
}

require_once __DIR__ . '/config/database.php';

// En Vercel, set header JSON para mayor claridad
if (IS_VERCEL) {
    header('Content-Type: application/json; charset=utf-8');
}

$db  = getDB();
$log = [];

function log_step(string $msg): void {
    global $log;
    $log[] = $msg;
    if (!IS_VERCEL) echo $msg . "\n";
}

try {
    // ── TABLA: profile ────────────────────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS profile (
            id INTEGER PRIMARY KEY DEFAULT 1,
            name TEXT NOT NULL DEFAULT 'Gabriela Lamont',
            bio TEXT DEFAULT '',
            avatar TEXT DEFAULT 'uploads/default.jpg',
            cover TEXT DEFAULT 'https://picsum.photos/seed/pagelink/840/420',
            footer_brand TEXT DEFAULT 'Pagelink',
            footer_text TEXT DEFAULT 'Todos mis enlaces en un solo lugar'
        )
    ");
    log_step('✅ Tabla profile creada/verificada');

    // Migraciones: columnas que pueden faltar en instalaciones antiguas
    if (!IS_VERCEL) {
        // PRAGMA solo funciona en SQLite local
        $cols = $db->query("PRAGMA table_info(profile)")->fetchAll(\PDO::FETCH_COLUMN, 1);
        foreach (['cover', 'footer_brand', 'footer_text'] as $col) {
            if (!in_array($col, $cols)) {
                $db->exec("ALTER TABLE profile ADD COLUMN $col TEXT DEFAULT ''");
                log_step("  [+] Columna '$col' añadida a profile");
            }
        }
    }

    // ── TABLA: links ──────────────────────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            subtitle TEXT DEFAULT '',
            url TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    log_step('✅ Tabla links creada/verificada');

    // ── TABLA: clicks ─────────────────────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS clicks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            link_id INTEGER NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
        )
    ");
    log_step('✅ Tabla clicks creada/verificada');

    // ── TABLA: testimonials ───────────────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            text TEXT NOT NULL,
            author TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_approved INTEGER DEFAULT 0
        )
    ");
    log_step('✅ Tabla testimonials creada/verificada');

    // ── TABLA: admin ──────────────────────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin (
            id INTEGER PRIMARY KEY DEFAULT 1,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL
        )
    ");
    log_step('✅ Tabla admin creada/verificada');

    // ── TABLA: security_question ──────────────────────────────────────────────
    $db->exec("
        CREATE TABLE IF NOT EXISTS security_question (
            id INTEGER PRIMARY KEY DEFAULT 1,
            question TEXT NOT NULL DEFAULT '',
            answer_hash TEXT NOT NULL DEFAULT ''
        )
    ");
    log_step('✅ Tabla security_question creada/verificada');

    // ── DATOS POR DEFECTO ─────────────────────────────────────────────────────

    // Perfil por defecto
    $profileCount = $db->query("SELECT COUNT(*) FROM profile")->fetchColumn();
    if ($profileCount == 0) {
        $db->prepare("INSERT INTO profile (id, name, bio, avatar, cover, footer_brand, footer_text) VALUES (1, ?, ?, ?, ?, ?, ?)")
           ->execute([
               'Gabriela Lamont',
               'Encuentra todos mis enlaces, aquí 💖',
               'uploads/default.jpg',
               'https://picsum.photos/seed/pagelink/840/420',
               'Pagelink',
               'Todos mis enlaces en un solo lugar',
           ]);
        log_step('  [+] Perfil por defecto creado');
    }

    // Enlaces de ejemplo
    $linksCount = $db->query("SELECT COUNT(*) FROM links")->fetchColumn();
    if ($linksCount == 0) {
        $links = [
            ['OnlyFans',      'Lo que estás buscando...',            '#', 0],
            ['Telegram VIP',  'Adelanto de contenido y promos',      '#', 1],
            ['Instagram',     'Mi vida :)',                           '#', 2],
            ['Threads',       'Sígueme',                             '#', 3],
            ['WhatsApp',      'Escríbeme',                           '#', 4],
        ];
        $ins = $db->prepare("INSERT INTO links (label, subtitle, url, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($links as $l) {
            $ins->execute($l);
        }
        log_step('  [+] 5 enlaces de ejemplo creados');
    }

    // Testimonios de ejemplo
    $testimonialsCount = $db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
    if ($testimonialsCount == 0) {
        $testimonials = [
            ['Las fotos quedaron increíbles, capturó cada detalle y emoción.', 'Andrea M.', 0, 1],
            ['Muy profesional, puntual y con un ojo artístico espectacular.',  'Carlos R.', 1, 1],
            ['El resultado fue mejor de lo esperado.',                          'Mariana L.', 2, 1],
        ];
        $ins = $db->prepare("INSERT INTO testimonials (text, author, sort_order, is_approved) VALUES (?, ?, ?, ?)");
        foreach ($testimonials as $t) {
            $ins->execute($t);
        }
        log_step('  [+] 3 testimonios de ejemplo creados');
    }

    // Admin por defecto
    $adminCount = $db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
    if ($adminCount == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO admin (id, username, password_hash) VALUES (1, 'admin', ?)")->execute([$hash]);
        log_step('  [+] Admin por defecto creado (usuario: admin, contraseña: admin123)');
    }

    // Pregunta de seguridad por defecto
    $sqCount = $db->query("SELECT COUNT(*) FROM security_question")->fetchColumn();
    if ($sqCount == 0) {
        $defaultAnswer = password_hash('pagelink', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO security_question (id, question, answer_hash) VALUES (1, ?, ?)")
           ->execute(['Cual es el nombre de tu primera mascota?', $defaultAnswer]);
        log_step('  [+] Pregunta de seguridad por defecto creada');
    }

    log_step('');
    log_step('🎉 Setup completado exitosamente.');
    log_step('   Accede al admin en: /admin/pages/login.php');
    log_step('   Usuario: admin | Contraseña: admin123');
    log_step('   ⚠️  Cambia la contraseña después del primer login.');

    if (IS_VERCEL) {
        echo json_encode(['success' => true, 'log' => $log]);
    }

} catch (\Throwable $e) {
    $errorMsg = '❌ Error durante el setup: ' . $e->getMessage();
    log_step($errorMsg);
    if (IS_VERCEL) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'log' => $log]);
    }
}
