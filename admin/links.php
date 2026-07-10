<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        $message = 'Token de seguridad inválido.';
    } else {
        $label    = trim($_POST['label'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $url      = trim($_POST['url'] ?? '');
        $sort     = (int)($_POST['sort_order'] ?? 0);

        if ($label === '' || $url === '') {
            $message = 'Label y URL son obligatorios.';
        } elseif ($_POST['action'] === 'create') {
            $db->prepare("INSERT INTO links (label, subtitle, url, sort_order) VALUES (?, ?, ?, ?)")->execute([$label, $subtitle, $url, $sort]);
            $message = 'Enlace creado.';
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE links SET label=?, subtitle=?, url=?, sort_order=? WHERE id=?")->execute([$label, $subtitle, $url, $sort, $id]);
            $message = 'Enlace actualizado.';
        }
    }
}

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM clicks WHERE link_id = ?")->execute([(int)$_GET['delete']]);
    $db->prepare("DELETE FROM links WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = 'Enlace eliminado.';
}

$links = $db->query("SELECT * FROM links ORDER BY sort_order ASC")->fetchAll();
$nextSort = $links ? max(array_column($links, 'sort_order')) + 1 : 0;

$brandColors = [
    'telegram' => '#26A5E4', 'github' => '#181717', 'instagram' => '#E4405F',
    'facebook' => '#1877F2', 'twitter' => '#000000', 'x' => '#000000',
    'onlyfans' => '#00AFF0', 'tiktok' => '#000000', 'threads' => '#000000',
    'youtube' => '#FF0000', 'whatsapp' => '#25D366', 'link' => '#7c3aed',
];

$editLink = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editLink = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlaces — PageLink Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="card">
            <h2><?= $editLink ? 'Editar enlace' : 'Nuevo enlace' ?></h2>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editLink ? 'update' : 'create' ?>">
                <?php if ($editLink): ?><input type="hidden" name="id" value="<?= $editLink['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <input type="text" name="label" placeholder="Label (ej: Instagram)" required value="<?= $editLink ? htmlspecialchars($editLink['label']) : '' ?>">
                    <input type="text" name="subtitle" placeholder="Subtítulo (ej: Sígueme)" value="<?= $editLink ? htmlspecialchars($editLink['subtitle']) : '' ?>">
                </div>
                <div class="form-row">
                    <input type="text" name="url" placeholder="URL (ej: https://instagram.com/tuuser)" required value="<?= $editLink ? htmlspecialchars($editLink['url']) : '' ?>">
                    <input type="number" name="sort_order" placeholder="Orden" style="min-width:80px" value="<?= $editLink ? $editLink['sort_order'] : $nextSort ?>">
                </div>
                <button type="submit"><?= $editLink ? 'Actualizar' : 'Crear enlace' ?></button>
                <?php if ($editLink): ?><a href="links.php" class="btn btn-cancel">Cancelar</a><?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Enlaces actuales</h2>
            <?php if (empty($links)): ?>
                <p class="empty">No hay enlaces todavía.</p>
            <?php else: ?>
            <div class="table-scroll">
            <table>
                <thead><tr><th>#</th><th>Icono</th><th>Label</th><th>Subtítulo</th><th>URL</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($links as $l):
                        $labelLower = mb_strtolower($l['label']);
                        $matchedColor = '#7c3aed';
                        foreach ($brandColors as $key => $color) {
                            if (str_contains($labelLower, $key)) { $matchedColor = $color; break; }
                        }
                    ?>
                    <tr>
                        <td><?= $l['sort_order'] + 1 ?></td>
                        <td><span style="display:inline-block;width:28px;height:28px;border-radius:8px;background:<?= $matchedColor ?>;color:#fff;text-align:center;line-height:28px;font-size:0.75rem;font-weight:700"><?= mb_strtoupper(mb_substr($l['label'], 0, 1)) ?></span></td>
                        <td><?= htmlspecialchars($l['label']) ?></td>
                        <td><?= htmlspecialchars($l['subtitle']) ?></td>
                        <td class="url-cell"><a href="<?= htmlspecialchars($l['url']) ?>" target="_blank"><?= htmlspecialchars($l['url']) ?></a></td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?= $l['id'] ?>" class="btn btn-sm">Editar</a>
                                <a href="?delete=<?= $l['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este enlace?')">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
