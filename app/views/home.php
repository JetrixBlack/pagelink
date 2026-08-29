<?php
/*
 * PageLink - Pagina publica (vista).
 * Se invoca desde el front controller (api/index.php) para el route "home".
 * Conserva el HTML original; solo cambia como se resuelven las rutas de
 * assets usando helpers (ROOT_PATH / asset_version).
 */
declare(strict_types=1);

$p = $db->query("SELECT name, bio, avatar, cover, footer_brand, footer_text FROM profile WHERE id = 1")->fetch();

$desc = strip_tags(html_entity_decode($p['bio'] ?? ''));
$desc = mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc;

$avatarUrl = $p['avatar'] ?? 'uploads/default.svg';
$coverUrl  = $p['cover'] ?? '';

// En Vercel los meta tags necesitan URL absoluta si el valor es relativo.
if (!preg_match('#^https?://#', $avatarUrl)) {
    $metaAvatar = base_url() . '/' . ltrim($avatarUrl, '/');
} else {
    $metaAvatar = $avatarUrl;
}
$metaCover = $coverUrl; // se usa solo como og:image si existe
if ($metaCover !== '' && !preg_match('#^https?://#', $metaCover)) {
    $metaCover = base_url() . '/' . ltrim($metaCover, '/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta name="description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($p['name'] ?? 'Pagelink') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($metaAvatar) ?>">
  <meta property="og:type" content="profile">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?= htmlspecialchars($p['name'] ?? 'Pagelink') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($metaAvatar) ?>">

  <title><?= htmlspecialchars($p['name'] ?? 'Pagelink') ?></title>
  <link rel="stylesheet" href="<?= base_path() ?>assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>">
</head>
<body>
  <div class="container">

    <div class="hero">
      <?php if ($coverUrl): ?>
      <img class="hero-cover" src="<?= htmlspecialchars($coverUrl) ?>" alt="Cover" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <?php endif; ?>
      <div class="hero-cover-fallback"<?= $coverUrl ? ' style="display:none"' : '' ?>></div>
      <div class="hero-avatar-wrapper">
        <img class="hero-avatar" id="heroAvatar" src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($p['name'] ?? '') ?>">
      </div>
      <div class="hero-name" id="displayName"><?= htmlspecialchars($p['name'] ?? '') ?></div>
    </div>

    <p class="bio" id="displayBio"><?= $p['bio'] ?? '' ?></p>

    <div class="links" id="linksContainer"></div>
    <div class="testimonials" id="testimonialsContainer"></div>

    <div style="text-align: center; margin-bottom: 30px;">
      <button class="btn-comment" onclick="openCommentModal()">Dejar un comentario</button>
    </div>

    <div class="public-modal-overlay" id="commentModal">
      <div class="public-modal">
        <div class="public-modal-header">
          <h3>Dejar un comentario</h3>
        </div>
        <div class="public-modal-body">
          <form id="commentForm">
            <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
            <div class="form-group">
              <label for="commentAuthor">Tu nombre</label>
              <input type="text" id="commentAuthor" name="author" placeholder="Ej: Juan Pérez" required>
            </div>
            <div class="form-group">
              <label for="commentText">Tu comentario</label>
              <textarea id="commentText" name="text" placeholder="Escribe tu testimonio aquí..." required></textarea>
            </div>
            <div id="commentAlert" class="comment-alert" style="display:none;"></div>
            <button type="submit" class="btn-submit-comment">Enviar comentario</button>
          </form>
        </div>
      </div>
    </div>

    <footer class="footer">
      <div class="footer-divider"></div>
      <span class="footer-brand"><?= htmlspecialchars($p['footer_brand'] ?? 'Pagelink') ?></span>
      <span class="footer-text"><?= htmlspecialchars($p['footer_text'] ?? '') ?></span>
      <span class="footer-year">&copy; <?= date('Y') ?></span>
    </footer>

  </div>

  <script src="<?= base_path() ?>assets/js/script.js?v=<?= asset_version('assets/js/script.js') ?>"></script>
</body>
</html>
