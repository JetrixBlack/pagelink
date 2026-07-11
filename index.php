<?php
/*
 * Página pública principal de PageLink.
 * Muestra el perfil del usuario (foto, nombre, biografía), sus enlaces,
 * testimonios aprobados, un formulario de comentarios y el pie de página.
 * Es la página que ven los visitantes al acceder al perfil.
 */

require_once __DIR__ . '/config/database.php';
$db = getDB();

// Consultar los datos del perfil del usuario (siempre el registro id=1)
$p = $db->query("SELECT name, bio, avatar, cover, footer_brand, footer_text FROM profile WHERE id = 1")->fetch();

// Preparar la descripción para los meta tags (sin HTML, máximo 120 caracteres)
$desc = strip_tags(html_entity_decode($p['bio'] ?? ''));
$desc = mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc;

// URL del avatar y portada del perfil
$avatarUrl = $p['avatar'] ?? 'uploads/default.jpg';
$coverUrl  = $p['cover'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Meta tags SEO y redes sociales para compartir en redes -->
  <meta name="description" content="<?= htmlspecialchars($desc) ?>">
  <!-- Open Graph (Facebook, LinkedIn, etc.) -->
  <meta property="og:title" content="<?= htmlspecialchars($p['name'] ?? 'Pagelink') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($avatarUrl) ?>">
  <meta property="og:type" content="profile">
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?= htmlspecialchars($p['name'] ?? 'Pagelink') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($avatarUrl) ?>">

  <title><?= htmlspecialchars($p['name'] ?? 'Pagelink') ?></title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>
<body>
  <div class="container">

    <!-- Sección hero: portada, avatar y nombre del perfil -->
    <div class="hero">
      <?php if ($coverUrl): ?>
      <!-- Imagen de portada con fallback si falla la carga -->
      <img class="hero-cover" src="<?= htmlspecialchars($coverUrl) ?>" alt="Cover" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <?php endif; ?>
      <!-- Portada alternativa cuando no hay imagen de portada -->
      <div class="hero-cover-fallback"<?= $coverUrl ? ' style="display:none"' : '' ?>></div>
      <!-- Avatar circular del perfil -->
      <div class="hero-avatar-wrapper">
        <img class="hero-avatar" id="heroAvatar" alt="<?= htmlspecialchars($p['name'] ?? '') ?>">
      </div>
      <!-- Nombre del perfil -->
      <div class="hero-name" id="displayName"><?= htmlspecialchars($p['name'] ?? '') ?></div>
    </div>

    <!-- Biografía del perfil -->
    <p class="bio" id="displayBio"><?= $p['bio'] ?? '' ?></p>

    <!-- Contenedor donde se cargan los enlaces vía JavaScript -->
    <div class="links" id="linksContainer"></div>

    <!-- Contenedor donde se cargan los testimonios aprobados vía JavaScript -->
    <div class="testimonials" id="testimonialsContainer"></div>
    
    <!-- Botón para abrir el modal de comentarios -->
    <div style="text-align: center; margin-bottom: 30px;">
      <button class="btn-comment" onclick="openCommentModal()">Dejar un comentario</button>
    </div>

    <!-- Modal para dejar un comentario/testimonio -->
    <div class="public-modal-overlay" id="commentModal">
      <div class="public-modal">
        <div class="public-modal-header">
          <h3>Dejar un comentario</h3>
        </div>
        <div class="public-modal-body">
          <form id="commentForm">
            <!-- Campo honeypot oculto para detectar bots automáticos -->
            <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
            
            <!-- Campo: nombre del autor del comentario -->
            <div class="form-group">
              <label for="commentAuthor">Tu nombre</label>
              <input type="text" id="commentAuthor" name="author" placeholder="Ej: Juan Pérez" required>
            </div>
            <!-- Campo: texto del comentario -->
            <div class="form-group">
              <label for="commentText">Tu comentario</label>
              <textarea id="commentText" name="text" placeholder="Escribe tu testimonio aquí..." required></textarea>
            </div>
            <!-- Mensaje de alerta para errores/éxito -->
            <div id="commentAlert" class="comment-alert" style="display:none;"></div>
            <button type="submit" class="btn-submit-comment">Enviar comentario</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Pie de página con marca y copyright -->
    <footer class="footer">
      <div class="footer-divider"></div>
      <span class="footer-brand"><?= htmlspecialchars($p['footer_brand'] ?? 'Pagelink') ?></span>
      <span class="footer-text"><?= htmlspecialchars($p['footer_text'] ?? '') ?></span>
      <span class="footer-year">&copy; <?= date('Y') ?></span>
    </footer>

  </div>

  <script src="assets/js/script.js?v=<?= filemtime(__DIR__ . '/assets/js/script.js') ?>"></script>
</body>
</html>
