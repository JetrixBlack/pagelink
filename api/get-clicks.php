<?php
/*
 * API: Obtener el historial de clics registrados (panel de administración).
 * Retorna un JSON paginado con los clics más recientes, incluyendo
 * la IP del visitante, User-Agent, fecha y etiqueta del enlace clickeado.
 * Soporta paginación mediante el parámetro GET 'page'.
 */

require_once __DIR__ . '/../config/database.php';

// Establecer el tipo de respuesta como JSON
header('Content-Type: application/json');

// Calcular paginación: página actual, elementos por página y offset
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 50;
$offset   = ($page - 1) * $perPage;

$db = getDB();

// Obtener el total de clics registrados
$total = $db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();

// Consultar los clics más recientes con un JOIN para obtener el nombre del enlace
$clicks = $db->prepare("
    SELECT c.ip_address, c.user_agent, c.created_at, l.label
    FROM clicks c
    JOIN links l ON l.id = c.link_id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$clicks->execute([$perPage, $offset]);
$data = $clicks->fetchAll();

// Retornar los datos con información de paginación
echo json_encode([
    'data'       => $data,
    'total'      => (int)$total,
    'page'       => $page,
    'perPage'    => $perPage,
    'hasMore'    => ($offset + $perPage) < $total,
]);
