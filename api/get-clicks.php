<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 50;
$offset   = ($page - 1) * $perPage;

$db = getDB();

$total = $db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();

$clicks = $db->prepare("
    SELECT c.ip_address, c.user_agent, c.created_at, l.label
    FROM clicks c
    JOIN links l ON l.id = c.link_id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$clicks->execute([$perPage, $offset]);
$data = $clicks->fetchAll();

echo json_encode([
    'data'       => $data,
    'total'      => (int)$total,
    'page'       => $page,
    'perPage'    => $perPage,
    'hasMore'    => ($offset + $perPage) < $total,
]);
