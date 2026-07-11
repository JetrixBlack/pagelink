<?php
/*
 * PageLink - Panel de Administración
 * Archivo: export-csv.php
 * Descripción: Exporta todos los registros de clics como un archivo CSV descargable.
 *              El archivo incluye: nombre del enlace, dirección IP, fecha y navegador.
 *              Se sirve directamente como descarga sin mostrar HTML.
 */

session_start();

// Verificar que el admin esté autenticado; si no, redirigir al login
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../config/database.php';

// Verificar si la sesión ha expirado por inactividad
check_session_timeout();

$db = getDB();

// ─── Configurar las cabeceras HTTP para descargar como CSV ────────
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=clicks-pagelink-' . date('Y-m-d') . '.csv');

// ─── Generar el contenido del archivo CSV ─────────────────────────
// Abrir el stream de salida directa (sin archivo temporal)
$output = fopen('php://output', 'w');

// Escribir el BOM (Byte Order Mark) UTF-8 para que Excel reconozca la codificación
fprintf($output, "\xEF\xBB\xBF");

// Escribir la fila de encabezados del CSV
fputcsv($output, ['Enlace', 'IP', 'Fecha', 'Navegador']);

// ─── Consultar todos los clics con el nombre del enlace asociado ──
$clicks = $db->query("
    SELECT l.label, c.ip_address, c.created_at, c.user_agent
    FROM clicks c
    JOIN links l ON l.id = c.link_id
    ORDER BY c.created_at DESC
")->fetchAll();

// Escribir cada registro de clic como una fila del CSV
foreach ($clicks as $c) {
    fputcsv($output, [
        $c['label'],
        $c['ip_address'],
        $c['created_at'],
        $c['user_agent'],
    ]);
}

// Cerrar el stream de salida y finalizar la ejecución
fclose($output);
exit;
