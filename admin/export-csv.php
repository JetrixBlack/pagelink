<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();

$db = getDB();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=clicks-pagelink-' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, "\xEF\xBB\xBF");
fputcsv($output, ['Enlace', 'IP', 'Fecha', 'Navegador']);

$clicks = $db->query("
    SELECT l.label, c.ip_address, c.created_at, c.user_agent
    FROM clicks c
    JOIN links l ON l.id = c.link_id
    ORDER BY c.created_at DESC
")->fetchAll();

foreach ($clicks as $c) {
    fputcsv($output, [
        $c['label'],
        $c['ip_address'],
        $c['created_at'],
        $c['user_agent'],
    ]);
}

fclose($output);
exit;
