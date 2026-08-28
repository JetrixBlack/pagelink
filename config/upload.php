<?php
/*
 * Helper de uploads para PageLink.
 *
 * En producción (Vercel): sube la imagen a Vercel Blob Storage vía API REST.
 *   - Requiere la variable de entorno BLOB_READ_WRITE_TOKEN
 *   - Retorna la URL pública del blob (https://...)
 *
 * En desarrollo local: guarda en la carpeta /uploads/ local.
 *   - Retorna la ruta relativa (uploads/nombre_archivo.ext)
 */

/**
 * Sube un archivo de imagen y retorna la URL/ruta donde quedó almacenado.
 *
 * @param  array  $file     Entrada de $_FILES para el archivo a subir
 * @param  string $prefix   Prefijo para el nombre del archivo ('avatar' o 'cover')
 * @return string|null      URL o ruta del archivo subido, o null si hubo error
 */
function uploadImage(array $file, string $prefix = 'file'): ?string {
    // Validar que el archivo no tenga error de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Límite de tamaño: 5 MB
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return null;
    }

    // Detectar tipo MIME real del archivo (no confiar en la extensión)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $detected = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($detected, $allowed)) {
        return null;
    }

    // Determinar extensión según el MIME detectado
    $ext = match ($detected) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };

    // Nombre de archivo único
    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (IS_VERCEL) {
        // ── Vercel Blob: subir vía API REST ──────────────────────────────────
        return _uploadToVercelBlob($file['tmp_name'], $filename, $detected);
    } else {
        // ── Local: guardar en /uploads/ ───────────────────────────────────────
        $dest = __DIR__ . '/../uploads/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }
        return 'uploads/' . $filename;
    }
}

/**
 * Sube un archivo a Vercel Blob Storage usando la API REST.
 *
 * @param  string $tmpPath   Ruta temporal del archivo en el servidor
 * @param  string $filename  Nombre deseado del blob
 * @param  string $mimeType  Tipo MIME del archivo
 * @return string|null       URL pública del blob, o null si falla
 */
function _uploadToVercelBlob(string $tmpPath, string $filename, string $mimeType): ?string {
    $token = getenv('BLOB_READ_WRITE_TOKEN') ?: ($_ENV['BLOB_READ_WRITE_TOKEN'] ?? '');
    if (!$token) {
        error_log('BLOB_READ_WRITE_TOKEN no configurado');
        return null;
    }

    $fileContents = file_get_contents($tmpPath);
    if ($fileContents === false) {
        return null;
    }

    // Endpoint de la API de Vercel Blob
    $url = 'https://blob.vercel-storage.com/' . urlencode($filename);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $fileContents,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: ' . $mimeType,
            'x-api-version: 7',
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log("Vercel Blob error $httpCode: $response");
        return null;
    }

    $data = json_decode($response, true);
    // La API retorna { "url": "https://..." }
    return $data['url'] ?? null;
}
