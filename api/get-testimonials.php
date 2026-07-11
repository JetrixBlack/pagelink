<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = getDB();
$testimonials = $db->query("SELECT text, author FROM testimonials ORDER BY sort_order ASC")->fetchAll();

echo json_encode($testimonials);
