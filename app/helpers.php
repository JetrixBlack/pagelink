<?php
/*
 * PageLink - Helpers de enrutamiento y rutas.
 *
 * Centraliza:
 *  - ROOT_PATH: ruta absoluta del proyecto en el filesystem (para require/filemtime).
 *  - base_url(): URL base absoluta del sitio (raiz), corregida para serverless.
 *  - asset_url()/version(): cache-busting de estaticos sin depender de filemtime.
 *  - redirect(): redireccion HTTP limpia.
 */
declare(strict_types=1);

// Ruta absoluta del proyecto (un nivel arriba de app/)
define('ROOT_PATH', dirname(__DIR__));

// Devuelve la URL base absoluta del sitio. En Vercel se deriva de las
// cabeceras; en local se apoya en el script. Siempre sin barra final.
function base_url(): string {
    if (IS_VERCEL) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $proto = $https ? 'https' : 'http';
        $host  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . '://' . $host;
    }
    // Modo local: usa el host, sin prefijo de subcarpeta (asume raiz)
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $proto = $https ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $proto . '://' . $host;
}

// Devuelve la ruta raíz del sitio en URLs (siempre '/').
function base_path(): string {
    return '/';
}

// Valor de versión para cache-busting de estaticos. En Vercel usa un hash
// estable del manifiesto; en local usa filemtime.
function asset_version(string $relPath): string {
    $abs = ROOT_PATH . '/' . ltrim($relPath, '/');
    if (!IS_VERCEL && file_exists($abs)) {
        $t = @filemtime($abs);
        return $t !== false ? (string)$t : 'v1';
    }
    return 'v1';
}

// Redirige a una ruta interna (p.ej. '/admin/login') y termina la ejecucion.
function redirect(string $path): void {
    if (!preg_match('#^https?://#', $path)) {
        $path = base_url() . $path;
    }
    header('Location: ' . $path);
    exit;
}
