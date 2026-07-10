<?php

require_once __DIR__ . '/../config/database.php';

$base = base_url();
$home = $base === '' ? '/' : $base . '/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: $home");
    exit;
}

$db = getDB();

$stmt = $db->prepare("SELECT url FROM links WHERE id = ?");
$stmt->execute([$id]);
$link = $stmt->fetch();

if (!$link) {
    header("Location: $home");
    exit;
}

$stmt = $db->prepare("INSERT INTO clicks (link_id, ip_address, user_agent) VALUES (?, ?, ?)");
$stmt->execute([
    $id,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
]);

header('Location: ' . $link['url']);
exit;
