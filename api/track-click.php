<?php
/*
 * API: Registrar clics en enlaces y redirigir al visitante.
 * Recibe el ID del enlace como parámetro GET, valida que exista,
 * registra el clic en la tabla clicks (IP, User-Agent, fecha)
 * y redirige al visitante a la URL original del enlace.
 */

require_once __DIR__ . '/../config/database.php';

// Construir la URL base del sitio para usar en redirecciones de error
$base = base_url();
$home = $base === '' ? '/' : $base . '/';

// Obtener y sanitizar el ID del enlace desde los parámetros de la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si el ID no es válido, redirigir a la página principal
if ($id <= 0) {
    header("Location: $home");
    exit;
}

$db = getDB();

// Buscar la URL del enlace en la base de datos
$stmt = $db->prepare("SELECT url FROM links WHERE id = ?");
$stmt->execute([$id]);
$link = $stmt->fetch();

// Si el enlace no existe, redirigir a la página principal
if (!$link) {
    header("Location: $home");
    exit;
}

// Registrar el clic en la base de datos con IP y User-Agent del visitante
$stmt = $db->prepare("INSERT INTO clicks (link_id, ip_address, user_agent) VALUES (?, ?, ?)");
$stmt->execute([
    $id,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
]);

// Redirigir al visitante a la URL original del enlace
header('Location: ' . $link['url']);
exit;
