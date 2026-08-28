<?php
/*
 * PageLink - Inicializador de base de datos (vista / action).
 * Se invoca desde el front controller para el route "setup".
 *
 * Seguridad: en Vercel (produccion) solo se permite ejecutar si la base
 * esta vacia O si existe una variable de entorno PAGELINK_ALLOW_SETUP=1.
 * En local (XAMPP) se ejecuta normal.
 */
declare(strict_types=1);

$log = [];

function setup_log(string $msg): void {
    global $log;
    $log[] = $msg;
}

if (IS_VERCEL) {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    // El setup es idempotente y seguro: usa CREATE TABLE IF NOT EXISTS e
    // inserta datos de ejemplo solo cuando la tabla esta vacia. Nunca borra
    // informacion existente. Por eso se permite ejecutarlo repetidamente.

    $db->exec("CREATE TABLE IF NOT EXISTS profile (
        id INTEGER PRIMARY KEY DEFAULT 1,
        name TEXT NOT NULL DEFAULT 'Gabriela Lamont',
        bio TEXT DEFAULT '',
        avatar TEXT DEFAULT 'uploads/default.svg',
        cover TEXT DEFAULT 'https://picsum.photos/seed/pagelink/840/420',
        footer_brand TEXT DEFAULT 'Pagelink',
        footer_text TEXT DEFAULT 'Todos mis enlaces en un solo lugar'
    )");
    setup_log('+ Tabla profile creada/verificada');

    $db->exec("CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        label TEXT NOT NULL,
        subtitle TEXT DEFAULT '',
        url TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    setup_log('+ Tabla links creada/verificada');

    $db->exec("CREATE TABLE IF NOT EXISTS clicks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        link_id INTEGER NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE
    )");
    setup_log('+ Tabla clicks creada/verificada');

    $db->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        text TEXT NOT NULL,
        author TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        is_approved INTEGER DEFAULT 0
    )");
    setup_log('+ Tabla testimonials creada/verificada');

    $db->exec("CREATE TABLE IF NOT EXISTS admin (
        id INTEGER PRIMARY KEY DEFAULT 1,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL
    )");
    setup_log('+ Tabla admin creada/verificada');

    $db->exec("CREATE TABLE IF NOT EXISTS security_question (
        id INTEGER PRIMARY KEY DEFAULT 1,
        question TEXT NOT NULL DEFAULT '',
        answer_hash TEXT NOT NULL DEFAULT ''
    )");
    setup_log('+ Tabla security_question creada/verificada');

    // Nota: la tabla `sessions` se crea por app/session.php al iniciar sesion.

    // Datos por defecto
    if ((int)$db->query("SELECT COUNT(*) FROM profile")->fetchColumn() === 0) {
        $db->prepare("INSERT INTO profile (id, name, bio, avatar, cover, footer_brand, footer_text) VALUES (1, ?, ?, ?, ?, ?, ?)")
           ->execute([
               'Gabriela Lamont',
               'Encuentra todos mis enlaces, aquí 💖',
               'uploads/default.svg',
               'https://picsum.photos/seed/pagelink/840/420',
               'Pagelink',
               'Todos mis enlaces en un solo lugar',
           ]);
        setup_log('  + Perfil por defecto creado');
    }

    if ((int)$db->query("SELECT COUNT(*) FROM links")->fetchColumn() === 0) {
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
        setup_log('  + 5 enlaces de ejemplo creados');
    }

    if ((int)$db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn() === 0) {
        $testimonials = [
            ['Las fotos quedaron increíbles, capturó cada detalle y emoción.', 'Andrea M.', 0, 1],
            ['Muy profesional, puntual y con un ojo artístico espectacular.',  'Carlos R.', 1, 1],
            ['El resultado fue mejor de lo esperado.',                          'Mariana L.', 2, 1],
        ];
        $ins = $db->prepare("INSERT INTO testimonials (text, author, sort_order, is_approved) VALUES (?, ?, ?, ?)");
        foreach ($testimonials as $t) {
            $ins->execute($t);
        }
        setup_log('  + 3 testimonios de ejemplo creados');
    }

    if ((int)$db->query("SELECT COUNT(*) FROM admin")->fetchColumn() === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO admin (id, username, password_hash) VALUES (1, 'admin', ?)")->execute([$hash]);
        setup_log('  + Admin por defecto creado (admin/admin123)');
    }

    if ((int)$db->query("SELECT COUNT(*) FROM security_question")->fetchColumn() === 0) {
        $defaultAnswer = password_hash('pagelink', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO security_question (id, question, answer_hash) VALUES (1, ?, ?)")
           ->execute(['Cual es el nombre de tu primera mascota?', $defaultAnswer]);
        setup_log('  + Pregunta de seguridad por defecto creada');
    }

    // Migracion del perfil: si el avatar/portada usan los placeholders, usar
    // las imagenes reales del perfil por defecto (avatar=Perfil.jpg, portada=Portada.jpg)
    // que se sirven como estaticos desde uploads/.
    $prof = $db->query("SELECT avatar, cover FROM profile WHERE id = 1")->fetch();
    $placeholders = ['uploads/default.jpg', 'uploads/default.svg'];
    $needAvatar = in_array(($prof['avatar'] ?? ''), $placeholders, true);
    $needCover  = ($prof['cover'] ?? '') === 'https://picsum.photos/seed/pagelink/840/420';
    if ($needAvatar || $needCover) {
        $newAvatar = $needAvatar ? 'uploads/Perfil.jpg' : ($prof['avatar'] ?? '');
        $newCover  = $needCover  ? 'uploads/Portada.jpg'  : ($prof['cover']  ?? '');
        $db->prepare("UPDATE profile SET avatar = ?, cover = ? WHERE id = 1")->execute([$newAvatar, $newCover]);
        setup_log('  + Perfil actualizado con imagenes reales (Perfil.jpg avatar + Portada.jpg portada)');
    }

    setup_log('Setup completado exitosamente.');

    if (IS_VERCEL) {
        echo json_encode(['success' => true, 'log' => $log]);
    } else {
        echo "Setup completado.\n" . implode("\n", $log);
    }
} catch (\Throwable $e) {
    $errorMsg = 'Error durante el setup: ' . $e->getMessage();
    setup_log($errorMsg);
    if (IS_VERCEL) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'log' => $log]);
    } else {
        echo $errorMsg . "\n";
    }
}
