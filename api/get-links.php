<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = getDB();
$links = $db->query("SELECT id, label, subtitle, url FROM links ORDER BY sort_order ASC")->fetchAll();

echo json_encode($links);
