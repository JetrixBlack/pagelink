<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        $message = 'Token de seguridad inválido.';
    } else {
        $text   = trim($_POST['text'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $sort   = (int)($_POST['sort_order'] ?? 0);

        if ($text === '' || $author === '') {
            $message = 'El testimonio y el autor son obligatorios.';
        } elseif ($_POST['action'] === 'create') {
            $db->prepare("INSERT INTO testimonials (text, author, sort_order) VALUES (?, ?, ?)")->execute([$text, $author, $sort]);
            $message = 'Testimonio creado.';
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE testimonials SET text=?, author=?, sort_order=? WHERE id=?")->execute([$text, $author, $sort, $id]);
            $message = 'Testimonio actualizado.';
        }
    }
}

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM testimonials WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = 'Testimonio eliminado.';
}

$testimonials = $db->query("SELECT * FROM testimonials ORDER BY sort_order ASC")->fetchAll();
$nextSort = $testimonials ? max(array_column($testimonials, 'sort_order')) + 1 : 0;

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonios — PageLink Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <?php if (isset($message)): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="card">
            <h2><?= $editItem ? 'Editar testimonio' : 'Nuevo testimonio' ?></h2>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
                <?php if ($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <textarea name="text" placeholder="Texto del testimonio..." required style="min-height:80px"><?= $editItem ? htmlspecialchars($editItem['text']) : '' ?></textarea>
                </div>
                <div class="form-row">
                    <input type="text" name="author" placeholder="Autor (ej: Andrea M.)" required value="<?= $editItem ? htmlspecialchars($editItem['author']) : '' ?>">
                    <input type="number" name="sort_order" placeholder="Orden" style="min-width:80px" value="<?= $editItem ? $editItem['sort_order'] : $nextSort ?>">
                </div>
                <button type="submit"><?= $editItem ? 'Actualizar' : 'Crear testimonio' ?></button>
                <?php if ($editItem): ?><a href="testimonials.php" class="btn btn-cancel">Cancelar</a><?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>Testimonios actuales</h2>
            <?php if (empty($testimonials)): ?>
                <p class="empty">No hay testimonios todavía.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>#</th><th>Testimonio</th><th>Autor</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($testimonials as $t): ?>
                    <tr>
                        <td><?= $t['sort_order'] + 1 ?></td>
                        <td style="max-width:300px"><?= htmlspecialchars(mb_substr($t['text'], 0, 80)) ?><?= mb_strlen($t['text']) > 80 ? '...' : '' ?></td>
                        <td><?= htmlspecialchars($t['author']) ?></td>
                        <td>
                            <div class="actions">
                                <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm">Editar</a>
                                <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este testimonio?')">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
