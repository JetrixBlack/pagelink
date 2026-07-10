<?php

$name = trim($_GET['name'] ?? '?');

$parts = explode(' ', $name, 2);
$initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
if (isset($parts[1])) {
    $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
}

$size = 200;
$img = imagecreatetruecolor($size, $size);

$bg = imagecolorallocate($img, 124, 58, 237);
imagefill($img, 0, 0, $bg);

$white = imagecolorallocate($img, 255, 255, 255);

$fontSize = 64;
$font = 'C:\Windows\Fonts\Arial.ttf';
if (!file_exists($font)) {
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

$bbox = imagettfbbox($fontSize, 0, $font, $initials);
$textW = $bbox[2] - $bbox[0];
$textH = $bbox[1] - $bbox[7];
$x = ($size - $textW) / 2;
$y = ($size + $textH) / 2;

imagettftext($img, $fontSize, 0, (int)$x, (int)$y, $white, $font, $initials);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
imagepng($img);
imagedestroy($img);
