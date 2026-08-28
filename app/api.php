<?php
/*
 * PageLink - Handlers de API JSON (version front controller).
 * Cada funcion responde a un route `/api/<nombre>` del router.
 */
declare(strict_types=1);

function api_get_profile(): void {
    header('Content-Type: application/json; charset=utf-8');
    global $db;
    $row = $db->query("SELECT name, bio AS description, avatar, cover, footer_brand, footer_text FROM profile WHERE id = 1")->fetch();
    echo json_encode($row ?: []);
}

function api_get_links(): void {
    header('Content-Type: application/json; charset=utf-8');
    global $db;
    $rows = $db->query("SELECT id, label, subtitle, url FROM links ORDER BY sort_order ASC")->fetchAll();
    echo json_encode($rows ?: []);
}

function api_get_testimonials(): void {
    header('Content-Type: application/json; charset=utf-8');
    global $db;
    $rows = $db->query("SELECT text, author FROM testimonials WHERE is_approved = 1 ORDER BY sort_order ASC")->fetchAll();
    echo json_encode($rows ?: []);
}

function api_submit_testimonial(): void {
    header('Content-Type: application/json; charset=utf-8');
    global $db;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        return;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) {
        $body = $_POST;
    }

    // Honeypot anti-bot: si el campo website viene lleno, responder exito falso
    if (!empty($body['website'])) {
        echo json_encode(['success' => true, 'approved' => false]);
        return;
    }

    $author = trim($body['author'] ?? '');
    $text   = trim($body['text'] ?? '');

    if (mb_strlen($author) > 100 || mb_strlen($text) > 500) {
        http_response_code(400);
        echo json_encode(['error' => 'Contenido demasiado largo']);
        return;
    }
    if ($author === '' || $text === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Campos requeridos']);
        return;
    }

    $db->prepare("INSERT INTO testimonials (text, author, is_approved) VALUES (?, ?, 0)")
       ->execute([$text, $author]);
    echo json_encode(['success' => true, 'approved' => false]);
}

function api_track_click(): void {
    global $db;
    $home = base_url() . '/';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header("Location: $home");
        exit;
    }

    $stmt = $db->prepare("SELECT url FROM links WHERE id = ?");
    $stmt->execute([$id]);
    $link = $stmt->fetch();

    if (!$link) {
        header("Location: $home");
        exit;
    }

    $db->prepare("INSERT INTO clicks (link_id, ip_address, user_agent) VALUES (?, ?, ?)")
       ->execute([
           $id,
           $_SERVER['REMOTE_ADDR'] ?? 'unknown',
           $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
       ]);

    header('Location: ' . $link['url']);
    exit;
}

function api_get_clicks(): void {
    header('Content-Type: application/json; charset=utf-8');
    global $db;
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $per   = 50;
    $off   = ($page - 1) * $per;
    $rows  = $db->query("SELECT l.label, c.ip_address, c.created_at, c.user_agent FROM clicks c JOIN links l ON l.id = c.link_id ORDER BY c.created_at DESC LIMIT $per OFFSET $off")->fetchAll();
    echo json_encode($rows ?: []);
}

function api_avatar_fallback(): void {
    // Genera un avatar SVG con las iniciales del nombre.
    // Se usa SVG (texto plano) en lugar de GD para no depender de la
    // extension gd en el runtime serverless de Vercel.
    $name = trim($_GET['name'] ?? '?');
    $parts = explode(' ', $name, 2);
    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (isset($parts[1]) && $parts[1] !== '') {
        $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
    }
    $initials = htmlspecialchars($initials !== '' ? $initials : '?');

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
         . '<rect width="200" height="200" fill="#c47a8a"/>'
         . '<text x="100" y="128" font-family="Arial, Helvetica, sans-serif" font-size="80" '
         . 'font-weight="bold" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">'
         . $initials . '</text>'
         . '</svg>';

    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    echo $svg;
}

// Tabla de despacho de API routes
return [
    'get-profile'      => 'api_get_profile',
    'get-links'        => 'api_get_links',
    'get-testimonials' => 'api_get_testimonials',
    'submit-testimonial' => 'api_submit_testimonial',
    'track-click'      => 'api_track_click',
    'get-clicks'       => 'api_get_clicks',
    'avatar-fallback'  => 'api_avatar_fallback',
];
