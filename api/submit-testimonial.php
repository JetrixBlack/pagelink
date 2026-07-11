<?php
/*
 * API: Enviar un nuevo testimonio/comentario.
 * Recibe nombre y texto del comentario, valida los datos,
 * verifica el honeypot anti-spam e inserta el testimonio
 * con is_approved = 0 (pendiente de moderación por el admin).
 */

require_once __DIR__ . '/../config/database.php';

// Establecer el tipo de respuesta como JSON
header('Content-Type: application/json');

// Solo permitir peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$db = getDB();

// Obtener datos del cuerpo JSON o formulario (admite ambos formatos)
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Extraer campos del formulario
$text = trim($input['text'] ?? '');
$author = trim($input['author'] ?? '');
$honeypot = trim($input['website'] ?? ''); // Campo honeypot (debe estar vacío)

// Verificación anti-spam: si el honeypot tiene contenido, es un bot.
// Retornamos éxito simulado para no revelar la detección.
if ($honeypot !== '') {
    echo json_encode(['success' => true]);
    exit;
}

// Validar que el comentario y el nombre no estén vacíos
if ($text === '' || $author === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El comentario y el nombre son obligatorios.']);
    exit;
}

// Validar longitud máxima del comentario (500 caracteres)
if (mb_strlen($text) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El comentario es demasiado largo.']);
    exit;
}

// Validar longitud máxima del nombre del autor (100 caracteres)
if (mb_strlen($author) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El nombre es demasiado largo.']);
    exit;
}

try {
    // Insertar el testimonio con is_approved = 0 (pendiente de moderación)
    $stmt = $db->prepare("INSERT INTO testimonials (text, author, is_approved) VALUES (?, ?, 0)");
    $stmt->execute([$text, $author]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Error inesperado al insertar en la base de datos
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
