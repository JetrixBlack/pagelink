<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $message = 'Token de seguridad inválido.';
    } else {
        $current     = $_POST['current_password'] ?? '';
        $new         = $_POST['new_password'] ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';

        $admin = $db->query("SELECT * FROM admin WHERE id = 1")->fetch();

        if (!password_verify($current, $admin['password_hash'])) {
            $message = 'La contraseña actual no es correcta.';
        } elseif (strlen($new) < 8) {
            $message = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($new !== $confirm) {
            $message = 'Las contraseñas no coinciden.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare("UPDATE admin SET password_hash = ? WHERE id = 1")->execute([$hash]);
            flash_set('Contraseña actualizada correctamente.');
            header('Location: change-password.php');
            exit;
        }
    }
}
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguridad — PageLink Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>.container .card { max-width: 500px; margin-left: auto; margin-right: auto; }</style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <?php if ($flash): ?><div class="message"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>
        <?php if ($message): ?><div class="message message-error"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="card">
            <h2>Cambiar contraseña</h2>
            <form method="POST">
                <?= csrf_field() ?>
                <label for="current_password">Contraseña actual</label>
                <input type="password" name="current_password" id="current_password" required>
                <label for="new_password">Nueva contraseña (mín. 8 caracteres)</label>
                <input type="password" name="new_password" id="new_password" required minlength="8">
                <label for="confirm_password">Confirmar nueva contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                <button type="submit">Cambiar contraseña</button>
            </form>
        </div>
    </div>
</body>
</html>
