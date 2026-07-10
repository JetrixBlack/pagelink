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
        $name        = trim($_POST['name'] ?? '');
        $bio         = trim($_POST['bio'] ?? '');
        $cover       = trim($_POST['cover'] ?? '');
        $footerBrand = trim($_POST['footer_brand'] ?? '');
        $footerText  = trim($_POST['footer_text'] ?? '');

        $avatarChanged = false;

        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 5 * 1024 * 1024;
            if ($_FILES['cover_file']['size'] <= $maxSize) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detected = finfo_file($finfo, $_FILES['cover_file']['tmp_name']);
                finfo_close($finfo);
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (in_array($detected, $allowed)) {
                    $ext = match ($detected) { 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg' };
                    $filename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['cover_file']['tmp_name'], __DIR__ . '/../uploads/' . $filename);
                    $cover = 'uploads/' . $filename;
                    $avatarChanged = true;
                }
            }
        }

        if ($name === '') {
            $message = 'El nombre es obligatorio.';
        } else {
            $db->prepare("UPDATE profile SET name=?, bio=?, cover=?, footer_brand=?, footer_text=? WHERE id=1")
               ->execute([$name, $bio, $cover, $footerBrand, $footerText]);
            $message = $avatarChanged ? 'Perfil y portada actualizados.' : 'Perfil actualizado.';
        }

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $maxSize = 5 * 1024 * 1024;
            if ($_FILES['avatar']['size'] > $maxSize) {
                $message = 'La imagen no puede superar los 5 MB.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedType = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
                finfo_close($finfo);
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($detectedType, $allowed)) {
                    $message = 'Formato no permitido (solo JPG, PNG, WebP, GIF).';
                } else {
                    $ext = match ($detectedType) { 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg' };
                    $filename = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../uploads/' . $filename);
                    $db->prepare("UPDATE profile SET avatar = ? WHERE id = 1")->execute(['uploads/' . $filename]);
                    $message = 'Perfil y avatar actualizados.';
                }
            }
        }
    }
}

if (isset($_GET['reset_avatar'])) {
    $db->prepare("UPDATE profile SET avatar = 'uploads/default.jpg' WHERE id = 1")->execute();
    flash_set('Avatar restaurado al predeterminado.');
    header('Location: profile.php');
    exit;
}

$profile = $db->query("SELECT * FROM profile WHERE id = 1")->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil — PageLink Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>.container .card { max-width: 600px; margin-left: auto; margin-right: auto; }</style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="card">
            <div class="avatar-preview">
                <img src="<?= $profile['avatar'] ?>" alt="Avatar" onerror="this.src='../api/avatar-fallback.php?name=<?= urlencode($profile['name']) ?>'">
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label for="name">Nombre</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                <label for="bio">Biografía</label>
                <textarea name="bio" id="bio"><?= htmlspecialchars($profile['bio']) ?></textarea>
                <label for="cover">URL de portada (cover image)</label>
                <input type="url" name="cover" id="cover" value="<?= htmlspecialchars($profile['cover']) ?>">
                <p class="hint">Deja vacío para usar el gradiente violeta por defecto.</p>
                <label for="cover_file">O sube una imagen (JPG, PNG, WebP, GIF — máx 5 MB)</label>
                <input type="file" name="cover_file" id="cover_file" accept="image/jpeg,image/png,image/webp,image/gif">

                <label for="footer_brand">Texto de marca (footer)</label>
                <input type="text" name="footer_brand" id="footer_brand" value="<?= htmlspecialchars($profile['footer_brand']) ?>">

                <label for="footer_text">Texto descriptivo (footer)</label>
                <input type="text" name="footer_text" id="footer_text" value="<?= htmlspecialchars($profile['footer_text']) ?>">

                <label for="avatar">Foto de perfil (JPG, PNG, WebP, GIF — máx 5 MB)</label>
                <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
                <p class="hint">El nombre del archivo se genera automáticamente para evitar conflictos.</p>
                <div style="display:flex;gap:12px;margin-top:20px">
                    <button type="submit" style="flex:1">Guardar cambios</button>
                    <a href="?reset_avatar=1" class="btn btn-cancel" style="flex:0.5;text-align:center" onclick="return confirm('¿Restaurar avatar por defecto?')">Eliminar foto</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
