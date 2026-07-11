<?php
/*
 * API: Generar avatar con iniciales del nombre.
 * Crea dinámicamente una imagen PNG de 200x200 píxeles con fondo rosa
 * y las iniciales del nombre recibido como parámetro GET 'name'.
 * Se usa como imagen de perfil alternativa cuando no hay avatar subido.
 */

// Obtener el nombre del parámetro GET (por defecto "?")
$name = trim($_GET['name'] ?? '?');

// =====================================================
// Extraer las iniciales del nombre (máximo 2 letras)
// =====================================================
// Dividir el nombre por espacios (máximo 2 partes: nombre y apellido)
$parts = explode(' ', $name, 2);
// Primera letra del nombre en mayúscula
$initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
// Si hay apellido, agregar la primera letra en mayúscula
if (isset($parts[1])) {
    $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
}

// =====================================================
// Crear la imagen con GD (librería de generación de imágenes)
// =====================================================
$size = 200; // Tamaño del lienzo en píxeles (cuadrado)

// Crear un lienzo de imagen de color verdadero de 200x200
$img = imagecreatetruecolor($size, $size);

// Color de fondo rosa (RGB: 196, 122, 138)
$bg = imagecolorallocate($img, 196, 122, 138);
// Rellenar todo el lienzo con el color de fondo
imagefill($img, 0, 0, $bg);

// Color del texto (blanco puro)
$white = imagecolorallocate($img, 255, 255, 255);

// =====================================================
// Renderizar las iniciales centradas en la imagen
// =====================================================
$fontSize = 64; // Tamaño de fuente en puntos
// Ruta a la fuente Arial del sistema Windows
$font = 'C:\Windows\Fonts\Arial.ttf';

// Si la fuente no existe, retornar solo el fondo sin texto
if (!file_exists($font)) {
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Calcular dimensiones del texto para centrarlo en la imagen
$bbox = imagettfbbox($fontSize, 0, $font, $initials);
// Ancho y alto del bounding box del texto
$textW = $bbox[2] - $bbox[0];
$textH = $bbox[1] - $bbox[7];
// Posición X e Y para centrar el texto horizontal y verticalmente
$x = ($size - $textW) / 2;
$y = ($size + $textH) / 2;

// Dibujar las iniciales en la imagen
imagettftext($img, $fontSize, 0, (int)$x, (int)$y, $white, $font, $initials);

// =====================================================
// Enviar la imagen PNG al navegador
// =====================================================
header('Content-Type: image/png');
// Cache de 24 horas (86400 segundos) para evitar regeneraciones
header('Cache-Control: public, max-age=86400');
// Generar y enviar la imagen al navegador
imagepng($img);
// Liberar la memoria de la imagen generada
imagedestroy($img);
