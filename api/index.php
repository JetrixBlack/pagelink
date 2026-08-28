<?php
/*
 * PageLink - Front controller / Router.
 *
 * Este es el punto de entrada de la app en Vercel (runtime vercel-php sobre api/).
 * Despacha cada peticion a la vista o action correspondiente segun la URL.
 *
 * Mapa de rutas:
 *   /                       -> home (publica)
 *   /setup                  -> setup de BD
 *   /api/<nombre>           -> handlers JSON (get-profile, get-links, ...)
 *   /admin                  -> login (o redirect a dashboard)
 *   /admin/login            -> login (GET form / POST auth)
 *   /admin/logout           -> cerrar sesion
 *   /admin/dashboard        -> panel principal
 *   /admin/links            -> CRUD de enlaces
 *   /admin/testimonials     -> gestion de testimonios
 *   /admin/profile          -> perfil + seguridad
 *   /admin/forgot           -> recuperacion de contrasena
 *   /admin/session-expired  -> sesion expirada
 *   /admin/export           -> exportar clics a CSV
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';

$db = getDB();

// -- Con middleware de sesion solo para rutas admin -----------------------
// (los handlers de API publica no inicializan sesion para no sobrecargar)

// Path normalizado (sin query string)
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
// Quitar prefijo si la app vive en una subcarpeta (local) 
$base = base_path(); // '/' en Vercel
if ($base !== '/' && strpos($path, $base) === 0) {
    $path = substr($path, strlen($base) - 1);
}

$route = $path === '/' ? 'home' : ltrim($path, '/');
$segments = explode('/', $route);

// División: api / admin / uploads / setup / home
if ($segments[0] === 'api') {
    $apiName = $segments[1] ?? '';
    $dispatch = require __DIR__ . '/../app/api.php';
    if ($apiName === '' || !isset($dispatch[$apiName])) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'API no encontrada']);
        exit;
    }
    call_user_func($dispatch[$apiName]);
    exit;
}

// Rutas uploads/ (avatar/portada): si el archivo no existe como estatico,
// servimos el avatar por defecto (SVG) en lugar de devolver HTML. Asi,
// uploads/default.jpg y avatares inexistentes muestran una imagen valida.
if ($segments[0] === 'uploads') {
    $file = ROOT_PATH . '/' . implode('/', $segments);
    if (is_file($file)) {
        // Dejar que Vercel/filesystem se sirva el estatico existente (no llega aqui en Vercel)
        http_response_code(200);
        readfile($file);
        exit;
    }
    // No existe: servir avatar generado (SVG) como fallback
    $name = $_GET['name'] ?? '';
    $dispatch = require __DIR__ . '/../app/api.php';
    call_user_func($dispatch['avatar-fallback']);
    exit;
}

if ($route === 'setup') {
    require_once __DIR__ . '/../app/auth.php';
    require_once __DIR__ . '/../app/views/setup.php';
    exit;
}

// -- Rutas admin -----------------------------------------------------------
require_once __DIR__ . '/../app/auth.php';

switch ($segments[0]) {
    case 'admin':
        $sub = $segments[1] ?? '';
        switch ($sub) {
            case '':
            case 'login':
                require_once __DIR__ . '/../app/views/admin_login.php';
                break;
            case 'logout':
                require_once __DIR__ . '/../app/views/admin_logout.php';
                break;
            case 'dashboard':
                require_once __DIR__ . '/../app/views/admin_dashboard.php';
                break;
            case 'links':
                require_once __DIR__ . '/../app/views/admin_links.php';
                break;
            case 'testimonials':
                require_once __DIR__ . '/../app/views/admin_testimonials.php';
                break;
            case 'profile':
                require_once __DIR__ . '/../app/views/admin_profile.php';
                break;
            case 'forgot':
                require_once __DIR__ . '/../app/views/admin_forgot.php';
                break;
            case 'session-expired':
                require_once __DIR__ . '/../app/views/admin_session_expired.php';
                break;
            case 'export':
                require_once __DIR__ . '/../app/views/admin_export.php';
                break;
            default:
                http_response_code(404);
                echo '<h1>404</h1>';
                exit;
        }
        exit;

    default:
        // home (cualquier otra ruta publica va a la pagina principal)
        require_once __DIR__ . '/../app/views/home.php';
        exit;
}
