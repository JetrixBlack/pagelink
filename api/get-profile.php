<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = getDB();
$profile = $db->query("SELECT name, bio, avatar, cover, footer_brand, footer_text FROM profile WHERE id = 1")->fetch();

echo json_encode($profile);
