<?php
/*
 * Script de instalación y configuración inicial de PageLink.
 * Crea las tablas de la base de datos SQLite (si no existen),
 * aplica migraciones de columnas faltantes, inserta datos por defecto
 * (perfil, enlaces, testimonios, admin) y genera el avatar por defecto.
 */

require_once __DIR__ . '/config/database.php';

$db = getDB();

// =====================================================
// TABLA: profile - Datos del perfil del usuario
// Almacena nombre, biografía, avatar, portada y texto de pie de página.
// Solo debe contener un registro (id=1).
// =====================================================
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

// Migraciones: agregar columnas faltantes en la tabla profile
// (para bases de datos creadas antes de añadir estos campos)
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

// =====================================================
// TABLA: links - Enlaces del usuario
// Cada enlace tiene etiqueta, subtítulo, URL y orden de aparición.
// =====================================================
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

// =====================================================
// TABLA: clicks - Registro de clics en enlaces
// Almacena cada clic con la IP del visitante y su User-Agent.
// Tiene relación foránea con links (se elimina en cascada).
// =====================================================
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

// =====================================================
// TABLA: testimonials - Testimonios/comentarios de visitantes
// Solo se muestran los aprobados (is_approved = 1) en la página pública.
// =====================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS testimonials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        text TEXT NOT NULL,
        author TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        is_approved INTEGER DEFAULT 0
    )
");

// Migración: agregar columna is_approved si falta en testimonios
// y aprobar todos los testimonios existentes
$cols = $db->query("PRAGMA table_info(testimonials)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('is_approved', $cols)) {
    $db->exec("ALTER TABLE testimonials ADD COLUMN is_approved INTEGER DEFAULT 0");
    // Aprobar los testimonios que ya existían antes de esta migración
    $db->exec("UPDATE testimonials SET is_approved = 1");
}

// =====================================================
// TABLA: admin - Credenciales del administrador
// Solo almacena un usuario administrador (id=1).
// =====================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS admin (
        id INTEGER PRIMARY KEY DEFAULT 1,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL
    )
");

// =====================================================
// Inserción de datos por defecto (solo si las tablas están vacías)
// =====================================================

// Insertar perfil por defecto si no existe ninguno
$stmt = $db->query("SELECT COUNT(*) as c FROM profile");
if ($stmt->fetch()['c'] == 0) {
    $db->exec("INSERT INTO profile (id, name, bio, avatar, cover, footer_brand, footer_text) VALUES (1, 'Gabriela Lamont', 'Encuentra todos mis enlaces, aquí 💖', 'uploads/default.jpg', 'https://picsum.photos/seed/pagelink/840/420', 'Pagelink', 'Todos mis enlaces en un solo lugar')");
}

// Insertar enlaces de ejemplo si no existen ninguno
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

// Insertar testimonios de ejemplo si no existen ninguno
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

// =====================================================
// TABLA: security_question - Pregunta de seguridad
// Se usa para recuperar la contraseña del administrador.
// =====================================================
$db->exec("
    CREATE TABLE IF NOT EXISTS security_question (
        id INTEGER PRIMARY KEY DEFAULT 1,
        question TEXT NOT NULL DEFAULT '',
        answer_hash TEXT NOT NULL DEFAULT ''
    )
");

// Migraciones: agregar columnas faltantes en la tabla security_question
$cols = $db->query("PRAGMA table_info(security_question)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('question', $cols)) {
    $db->exec("ALTER TABLE security_question ADD COLUMN question TEXT NOT NULL DEFAULT ''");
}
if (!in_array('answer_hash', $cols)) {
    $db->exec("ALTER TABLE security_question ADD COLUMN answer_hash TEXT NOT NULL DEFAULT ''");
}

// Insertar pregunta de seguridad por defecto si no existe
$sqCount = $db->query("SELECT COUNT(*) FROM security_question")->fetchColumn();
if ($sqCount == 0) {
    $defaultAnswer = password_hash('pagelink', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO security_question (id, question, answer_hash) VALUES (1, ?, ?)")
       ->execute(['Cual es el nombre de tu primera mascota?', $defaultAnswer]);
}

// Crear usuario admin por defecto si no existe ninguno
$stmt = $db->query("SELECT COUNT(*) as c FROM admin");
if ($stmt->fetch()['c'] == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO admin (id, username, password_hash) VALUES (1, 'admin', ?)")->execute([$hash]);
}

// =====================================================
// Generación del avatar por defecto (imagen PNG con GD)
// Crea una imagen de 200x200px color rosa con la letra "P" centrada.
// Solo se genera si el archivo no existe aún.
// =====================================================
$defaultPath = __DIR__ . '/uploads/default.jpg';
if (!file_exists($defaultPath)) {
    // Crear lienzo de 200x200 píxeles
    $img = imagecreatetruecolor(200, 200);
    // Color de fondo rosa (RGB: 196, 122, 138)
    $bg = imagecolorallocate($img, 196, 122, 138);
    // Rellenar todo el lienzo con el color de fondo
    imagefill($img, 0, 0, $bg);
    // Color del texto (blanco)
    $white = imagecolorallocate($img, 255, 255, 255);
    // Ruta a la fuente Arial del sistema Windows
    $font = 'C:\Windows\Fonts\Arial.ttf';
    if (file_exists($font)) {
        // Dibujar la letra "P" centrada con tamaño 60
        imagettftext($img, 60, 0, 55, 130, $white, $font, 'P');
    }
    // Guardar como archivo PNG y liberar memoria
    imagepng($img, $defaultPath);
    imagedestroy($img);
    echo "  [i] Avatar por defecto creado.\n";
}

// Verificar si la base de datos ya estaba configurada previamente
$alreadySetup = ($db->query("SELECT COUNT(*) FROM profile")->fetchColumn() > 0);

if ($alreadySetup) {
    echo "ℹ️  La base de datos ya estaba inicializada. No se hicieron cambios.\n";
} else {
    echo "✅ Base de datos inicializada correctamente.\n";
    echo "   Admin: admin / admin123\n";
}
