<?php
/*
 * API: Obtener la lista de enlaces del perfil.
 * Retorna un JSON array con todos los enlaces ordenados por sort_order.
 * Cada enlace incluye: id, label, subtitle y url.
 */

require_once __DIR__ . '/../config/database.php';

// Establecer el tipo de respuesta como JSON
header('Content-Type: application/json');

$db = getDB();

// Consultar todos los enlaces ordenados por su orden de aparición
$links = $db->query("SELECT id, label, subtitle, url FROM links ORDER BY sort_order ASC")->fetchAll();

// Retornar los enlaces como JSON
echo json_encode($links);
