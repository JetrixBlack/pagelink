<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$p = $db->query("SELECT name, bio, avatar, cover, footer_brand, footer_text FROM profile WHERE id = 1")->fetch();
$desc = strip_tags(html_entity_decode($p['bio'] ?? ''));
$desc = mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc;
$avatarUrl = $p['avatar'] ?? 'uploads/default.jpg';
$coverUrl  = $p['cover'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:title" content="<?= htmlspecialchars($p['name'] ?? 'Pagelink') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($avatarUrl) ?>">
  <meta property="og:type" content="profile">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="<?= htmlspecialchars($p['name'] ?? 'Pagelink') ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($avatarUrl) ?>">
  <title><?= htmlspecialchars($p['name'] ?? 'Pagelink') ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">

    <div class="hero">
      <?php if ($coverUrl): ?>
      <img class="hero-cover" src="<?= htmlspecialchars($coverUrl) ?>" alt="Cover" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <?php endif; ?>
      <div class="hero-cover-fallback"<?= $coverUrl ? ' style="display:none"' : '' ?>></div>
      <div class="hero-avatar-wrapper">
        <img class="hero-avatar" id="heroAvatar" alt="<?= htmlspecialchars($p['name'] ?? '') ?>">
      </div>
      <div class="hero-name" id="displayName"><?= htmlspecialchars($p['name'] ?? '') ?></div>
    </div>

    <p class="bio" id="displayBio"><?= $p['bio'] ?? '' ?></p>

    <div class="links" id="linksContainer"></div>

    <div class="testimonials" id="testimonialsContainer"></div>

    <footer class="footer">
      <div class="footer-divider"></div>
      <span class="footer-brand"><?= htmlspecialchars($p['footer_brand'] ?? 'Pagelink') ?></span>
      <span class="footer-text"><?= htmlspecialchars($p['footer_text'] ?? '') ?></span>
      <span class="footer-year">&copy; <?= date('Y') ?></span>
    </footer>

  </div>

  <script src="assets/js/script.js"></script>
</body>
</html>
