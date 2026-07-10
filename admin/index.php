<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();
$linkCount = $db->query("SELECT COUNT(*) FROM links")->fetchColumn();
$clickCount = $db->query("SELECT COUNT(*) FROM clicks")->fetchColumn();
$testimonialCount = $db->query("SELECT COUNT(*) FROM testimonials")->fetchColumn();
$lastClick = $db->query("SELECT c.created_at, l.label FROM clicks c JOIN links l ON l.id = c.link_id ORDER BY c.created_at DESC LIMIT 1")->fetch();
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — PageLink Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <?php if ($flash): ?><div class="message<?= $flash['type'] === 'error' ? ' message-error' : '' ?>"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>
        <div class="grid">
            <div class="card"><div class="card-value"><?= $linkCount ?></div><div class="card-label">Enlaces</div></div>
            <div class="card"><div class="card-value"><?= $clickCount ?></div><div class="card-label">Clics totales</div></div>
            <div class="card"><div class="card-value"><?= $testimonialCount ?></div><div class="card-label">Testimonios</div></div>
            <div class="card">
                <div class="card-value"><?= $lastClick ? date('d/m', strtotime($lastClick['created_at'])) : '---' ?></div>
                <div class="card-label">Último clic</div>
                <div class="card-sub"><?= $lastClick ? htmlspecialchars($lastClick['label']) : '---' ?></div>
            </div>
        </div>
        <div class="card">
            <h2>Tus enlaces</h2>
            <ul style="list-style:none">
                <?php foreach ($db->query("SELECT label, sort_order FROM links ORDER BY sort_order ASC")->fetchAll() as $l): ?>
                <li style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:0.9rem">
                    <span><?= htmlspecialchars($l['label']) ?></span>
                    <span style="background:#ede9fe;color:#7c3aed;font-size:0.75rem;padding:2px 10px;border-radius:99px">#<?= $l['sort_order'] + 1 ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
