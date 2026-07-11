<?php

require_once __DIR__ . '/config/database.php';

$db = getDB();

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

$cols = $db->query("PRAGMA table_info(profile)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('cover', $cols)) {
    $db->exec("ALTER TABLE profile ADD COLUMN cover TEXT DEFAULT 'https://picsum.photos/seed/pagelink/840/420'");
}
if (!in_array('footer_brand', $cols)) {
    $db->exec("ALTER TABLE profile ADD COLUMN footer_brand TEXT DEFAULT 'Pagelink'");
}
if (!in_array('footer_text', $cols)) {
    $db->exec("ALTER TABLE profile ADD COLUMN footer_text TEXT DEFAULT 'Todos mis enlaces en un solo lugar'");
}

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

$db->exec("
    CREATE TABLE IF NOT EXISTS testimonials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        text TEXT NOT NULL,
        author TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS admin (
        id INTEGER PRIMARY KEY DEFAULT 1,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL
    )
");

$stmt = $db->query("SELECT COUNT(*) as c FROM profile");
if ($stmt->fetch()['c'] == 0) {
    $db->exec("INSERT INTO profile (id, name, bio, avatar, cover, footer_brand, footer_text) VALUES (1, 'Gabriela Lamont', 'Encuentra todos mis enlaces, aquí 💖', 'uploads/default.jpg', 'https://picsum.photos/seed/pagelink/840/420', 'Pagelink', 'Todos mis enlaces en un solo lugar')");
}

$stmt = $db->query("SELECT COUNT(*) as c FROM links");
if ($stmt->fetch()['c'] == 0) {
    $links = [
        ['OnlyFans', 'Lo que estás buscando..', '#', 0],
        ['Telegram VIP', 'Adelanto de contenido y promos', '#', 1],
        ['Instagram', 'Mi vida :)', '#', 2],
        ['Threads', 'Sígueme', '#', 3],
        ['WhatsApp', 'Escríbeme', '#', 4],
    ];
    $ins = $db->prepare("INSERT INTO links (label, subtitle, url, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($links as $l) {
        $ins->execute($l);
    }
}

$stmt = $db->query("SELECT COUNT(*) as c FROM testimonials");
if ($stmt->fetch()['c'] == 0) {
    $testimonials = [
        ['Las fotos quedaron increíbles, capturó cada detalle y emoción. Totalmente recomendada.', 'Andrea M.', 0],
        ['Muy profesional, puntual y con un ojo artístico espectacular. Repetiría sin dudar.', 'Carlos R.', 1],
        ['Hizo que la sesión fuera súper cómoda y natural. El resultado fue mejor de lo esperado.', 'Mariana L.', 2],
    ];
    $ins = $db->prepare("INSERT INTO testimonials (text, author, sort_order) VALUES (?, ?, ?)");
    foreach ($testimonials as $t) {
        $ins->execute($t);
    }
}

$stmt = $db->query("SELECT COUNT(*) as c FROM admin");
if ($stmt->fetch()['c'] == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO admin (id, username, password_hash) VALUES (1, 'admin', ?)")->execute([$hash]);
}

$defaultPath = __DIR__ . '/uploads/default.jpg';
if (!file_exists($defaultPath)) {
    $img = imagecreatetruecolor(200, 200);
    $bg = imagecolorallocate($img, 124, 58, 237);
    imagefill($img, 0, 0, $bg);
    $white = imagecolorallocate($img, 255, 255, 255);
    $font = 'C:\Windows\Fonts\Arial.ttf';
    if (file_exists($font)) {
        imagettftext($img, 60, 0, 55, 130, $white, $font, 'P');
    }
    imagepng($img, $defaultPath);
    imagedestroy($img);
    echo "  [i] Avatar por defecto creado.\n";
}

$alreadySetup = ($db->query("SELECT COUNT(*) FROM profile")->fetchColumn() > 0);

if ($alreadySetup) {
    echo "ℹ️  La base de datos ya estaba inicializada. No se hicieron cambios.\n";
} else {
    echo "✅ Base de datos inicializada correctamente.\n";
    echo "   Admin: admin / admin123\n";
}
