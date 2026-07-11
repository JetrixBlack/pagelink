<?php
/*
 * API: Obtener datos del perfil del usuario.
 * Retorna un JSON con el nombre, biografía, avatar, portada,
 * marca de pie de página y texto de pie de página.
 * Consulta siempre el registro id=1 de la tabla profile.
 */

require_once __DIR__ . '/../config/database.php';

// Establecer el tipo de respuesta como JSON
header('Content-Type: application/json');

$db = getDB();

// Consultar los campos del perfil (solo el registro principal)
$profile = $db->query("SELECT name, bio, avatar, cover, footer_brand, footer_text FROM profile WHERE id = 1")->fetch();

// Retornar el perfil como JSON
echo json_encode($profile);
