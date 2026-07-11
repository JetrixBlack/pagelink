<?php
/*
 * API: Obtener testimonios aprobados.
 * Retorna un JSON array con los testimonios que tienen is_approved = 1,
 * ordenados por sort_order. Solo se muestran comentarios aprobados por el admin.
 */

require_once __DIR__ . '/../config/database.php';

// Establecer el tipo de respuesta como JSON
header('Content-Type: application/json');

$db = getDB();

// Consultar solo testimonios aprobados, ordenados por su posición
$testimonials = $db->query("SELECT text, author FROM testimonials WHERE is_approved = 1 ORDER BY sort_order ASC")->fetchAll();

// Retornar los testimonios como JSON
echo json_encode($testimonials);
