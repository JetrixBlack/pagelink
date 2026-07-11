<?php
/**
 * PERFIL UNIFICADO — PageLink Admin
 * 
 * Este archivo maneja dos secciones principales en pestañas:
 * 1. PERFIL: Avatar, nombre, biografía, portada (cover), footer
 * 2. SEGURIDAD: Cambiar contraseña + configurar pregunta de seguridad
 * 
 * Incluye vista previa de imágenes antes de subir al servidor.
 */

session_start();

// Verificar que el admin esté autenticado
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
check_session_timeout();
$db = getDB();
$message = '';

// Cargar datos PRIMERO (necesarios para el handler POST y la vista)
$profile = $db->query("SELECT * FROM profile WHERE id = 1")->fetch();
$sq = $db->query("SELECT * FROM security_question WHERE id = 1")->fetch();
$admin = $db->query("SELECT * FROM admin WHERE id = 1")->fetch();

// Determinar qué pestaña está activa (perfil o seguridad)
$activeTab = $_GET['tab'] ?? 'perfil';

// ═══════════════════════════════════════════════════════════
// MANEJO DE FORMULARIOS POST
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verificar token CSRF antes de procesar
    if (!verify_csrf()) {
        $message = 'Token de seguridad invalido.';
    } else {

        // ─── ACTUALIZAR PERFIL ───
        if ($_POST['action'] === 'update_profile') {
            // Obtener datos del formulario
            $name        = trim($_POST['name'] ?? '');
            $bio         = trim($_POST['bio'] ?? '');
            $cover       = $profile['cover']; // Mantener cover actual por defecto
            $footerBrand = trim($_POST['footer_brand'] ?? '');
            $footerText  = trim($_POST['footer_text'] ?? '');

            // Procesar subida de portada (cover) si se seleccionó archivo
            if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
                $maxSize = 5 * 1024 * 1024; // 5 MB máximo
                if ($_FILES['cover_file']['size'] <= $maxSize) {
                    // Detectar tipo MIME real del archivo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detected = finfo_file($finfo, $_FILES['cover_file']['tmp_name']);
                    finfo_close($finfo);
                    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    
                    if (in_array($detected, $allowed)) {
                        // Generar nombre único para evitar conflictos
                        $ext = match ($detected) {
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp',
                            'image/gif'  => 'gif',
                            default      => 'jpg'
                        };
                        $filename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        move_uploaded_file($_FILES['cover_file']['tmp_name'], __DIR__ . '/../uploads/' . $filename);
                        $cover = 'uploads/' . $filename;
                    }
                }
            }

            // Validar que el nombre no esté vacío
            if ($name === '') {
                $message = 'El nombre es obligatorio.';
            } else {
                // Guardar cambios en la base de datos
                $db->prepare("UPDATE profile SET name=?, bio=?, cover=?, footer_brand=?, footer_text=? WHERE id=1")
                   ->execute([$name, $bio, $cover, $footerBrand, $footerText]);
                $message = 'Perfil actualizado.';
            }

            // Procesar subida de avatar si se seleccionó archivo
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $maxSize = 5 * 1024 * 1024; // 5 MB máximo
                if ($_FILES['avatar']['size'] > $maxSize) {
                    $message = 'La imagen no puede superar los 5 MB.';
                } else {
                    // Detectar tipo MIME real
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detectedType = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
                    finfo_close($finfo);
                    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    
                    if (!in_array($detectedType, $allowed)) {
                        $message = 'Formato no permitido (solo JPG, PNG, WebP, GIF).';
                    } else {
                        // Generar nombre único y guardar
                        $ext = match ($detectedType) {
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp',
                            'image/gif'  => 'gif',
                            default      => 'jpg'
                        };
                        $filename = 'avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../uploads/' . $filename);
                        $db->prepare("UPDATE profile SET avatar = ? WHERE id = 1")->execute(['uploads/' . $filename]);
                        $message = 'Perfil y avatar actualizados.';
                    }
                }
            }
            $activeTab = 'perfil';
        }

        // ─── CAMBIAR CONTRASEÑA ───
        if ($_POST['action'] === 'change_password') {
            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            // Obtener el hash actual de la contraseña
            $admin = $db->query("SELECT * FROM admin WHERE id = 1")->fetch();

            // Verificar que la contraseña actual sea correcta
            if (!password_verify($current, $admin['password_hash'])) {
                $message = 'La contrasena actual no es correcta.';
            } elseif (strlen($new) < 8) {
                // Validar longitud mínima
                $message = 'La nueva contrasena debe tener al menos 8 caracteres.';
            } elseif (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{}|;:\'",.<>?\/]+$/', $new)) {
                // Solo permite: mayusculas, minusculas, numeros y caracteres especiales
                $message = 'La contrasena solo puede contener letras, numeros y caracteres especiales (!@#$%^&*).';
            } elseif ($new !== $confirm) {
                // Verificar que las contraseñas coincidan
                $message = 'Las contrasenas no coinciden.';
            } else {
                // Hashear y guardar la nueva contraseña
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $db->prepare("UPDATE admin SET password_hash = ? WHERE id = 1")->execute([$hash]);
                $message = 'Contrasena actualizada correctamente.';
            }
            $activeTab = 'seguridad';
        }

        // ─── CONFIGURAR PREGUNTA DE SEGURIDAD ───
        if ($_POST['action'] === 'set_security_question') {
            $questionSel = trim($_POST['question'] ?? '');
            $questionCustom = trim($_POST['question_custom'] ?? '');
            $answer   = trim($_POST['answer'] ?? '');

            // Si selecciono "Otra", usar el campo custom
            $question = $questionSel === '__custom' ? $questionCustom : $questionSel;

            if ($question === '' || $answer === '') {
                $message = 'La pregunta y la respuesta son obligatorias.';
            } else {
                // Hashear la respuesta en minúsculas para consistencia
                $answerHash = password_hash(mb_strtolower($answer), PASSWORD_DEFAULT);
                $existing = $db->query("SELECT COUNT(*) FROM security_question WHERE id = 1")->fetchColumn();
                
                if ($existing > 0) {
                    // Actualizar pregunta existente
                    $db->prepare("UPDATE security_question SET question=?, answer_hash=? WHERE id=1")
                       ->execute([$question, $answerHash]);
                } else {
                    // Insertar nueva pregunta
                    $db->prepare("INSERT INTO security_question (id, question, answer_hash) VALUES (1, ?, ?)")
                       ->execute([$question, $answerHash]);
                }
                $message = 'Pregunta de seguridad configurada.';
            }
            $activeTab = 'seguridad';
        }
    }
}

// ═══════════════════════════════════════════════════════════
// RESTABLECER AVATAR POR DEFECTO
// ═══════════════════════════════════════════════════════════
if (isset($_GET['reset_avatar'])) {
    $db->prepare("UPDATE profile SET avatar = 'uploads/default.jpg' WHERE id = 1")->execute();
    flash_set('Avatar restaurado al predeterminado.');
    header('Location: profile.php?tab=perfil');
    exit;
}

// ═══════════════════════════════════════════════════════════
// CARGAR FLASH MESSAGES
// ═══════════════════════════════════════════════════════════
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil — PageLink Admin</title>
    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
    <style>
        /* Contenedor centrado para el formulario de perfil */
        .container .card { max-width: 650px; margin-left: auto; margin-right: auto; }
        
        /* Vista previa de portada (cover) */
        .cover-preview {
            width: 100%;
            height: 160px;
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 16px;
            border: 1px solid var(--border);
            position: relative;
        }
        .cover-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .cover-preview-fallback {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #2a1a1e 0%, #3a2028 50%, #2a1a1e 100%);
        }
        .cover-preview-label {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        /* Vista previa de avatar con superposición */
        .avatar-preview {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        .avatar-preview img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--surface);
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/_nav.php'; ?>
        
        <!-- Toast notifications (flotantes) -->
        <div class="toast-container" id="toastContainer"></div>

        <!-- Indicadores de estado de seguridad -->
        <div class="security-status">
            <?php
            $passConfigured = ($admin['password_hash'] ?? '') !== '' && $admin['password_hash'] !== password_hash('admin123', PASSWORD_DEFAULT);
            $sqConfigured = !empty($sq['question']) && !empty($sq['answer_hash']);
            ?>
            <div class="status-chip <?= $passConfigured ? 'configured' : 'not-configured' ?>">
                <span class="status-chip-dot"></span>
                Contrasena <?= $passConfigured ? 'cambiada' : 'por defecto' ?>
            </div>
            <div class="status-chip <?= $sqConfigured ? 'configured' : 'not-configured' ?>">
                <span class="status-chip-dot"></span>
                Pregunta <?= $sqConfigured ? 'configurada' : 'sin configurar' ?>
            </div>
        </div>

        <div class="card">
            <!-- ═══ PESTAÑAS DE NAVEGACIÓN ═══ -->
            <div class="tabs">
                <button type="button" class="tab-btn<?= $activeTab === 'perfil' ? ' active' : '' ?>" onclick="switchTab('perfil')">Perfil</button>
                <button type="button" class="tab-btn<?= $activeTab === 'seguridad' ? ' active' : '' ?>" onclick="switchTab('seguridad')">Seguridad</button>
            </div>

            <!-- ═══ PESTAÑA: PERFIL ═══ -->
            <div class="tab-content<?= $activeTab === 'perfil' ? ' active' : '' ?>" id="tab-perfil">
                
                <!-- Vista previa de portada -->
                <div class="cover-preview" id="coverPreview">
                    <?php if (!empty($profile['cover'])): ?>
                        <img src="../<?= htmlspecialchars($profile['cover']) ?>" alt="Portada" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                    <?php endif; ?>
                    <div class="cover-preview-fallback"<?= !empty($profile['cover']) ? ' style="display:none"' : '' ?>></div>
                    <span class="cover-preview-label">Portada actual</span>
                </div>

                <!-- Vista previa de avatar -->
                <div class="avatar-preview">
                    <img id="avatarPreviewImg" src="../<?= $profile['avatar'] ?>" alt="Avatar" onerror="this.src='../api/avatar-fallback.php?name=<?= urlencode($profile['name']) ?>'">
                </div>

                <!-- Formulario de perfil -->
                <form method="POST" enctype="multipart/form-data" id="profileForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <label for="name">Nombre</label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                    
                    <label for="bio">Biografia</label>
                    <textarea name="bio" id="bio" rows="3"><?= htmlspecialchars($profile['bio']) ?></textarea>
                    
                    <label for="cover_file">Portada (JPG, PNG, WebP, GIF - max 5 MB)</label>
                    <input type="file" name="cover_file" id="cover_file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <p class="hint">Formato recomendado: 840x420px.</p>
                    
                    <label for="footer_brand">Texto de marca (footer)</label>
                    <input type="text" name="footer_brand" id="footer_brand" value="<?= htmlspecialchars($profile['footer_brand']) ?>">
                    
                    <label for="footer_text">Texto descriptivo (footer)</label>
                    <input type="text" name="footer_text" id="footer_text" value="<?= htmlspecialchars($profile['footer_text']) ?>">
                    
                    <label for="avatar">Foto de perfil (JPG, PNG, WebP, GIF - max 5 MB)</label>
                    <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
                    <p class="hint">El nombre del archivo se genera automaticamente para evitar conflictos.</p>
                    
                    <div style="display:flex;gap:12px;margin-top:20px">
                        <button type="submit" style="flex:1">Guardar cambios</button>
                        <a href="?reset_avatar=1&tab=perfil" class="btn btn-cancel" style="flex:0.5;text-align:center" onclick="return confirm('Restaurar avatar por defecto?')">Eliminar foto</a>
                    </div>
                </form>
            </div>

            <!-- ═══ PESTAÑA: SEGURIDAD ═══ -->
            <div class="tab-content<?= $activeTab === 'seguridad' ? ' active' : '' ?>" id="tab-seguridad">

                <!-- Formulario para cambiar contraseña -->
                <div style="margin-bottom: 28px;">
                    <h2 style="font-size:0.95rem;margin-bottom:16px;color:var(--fg)">Cambiar contrasena</h2>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">
                        
                        <label for="current_password">Contrasena actual</label>
                        <div class="password-wrapper">
                            <input type="password" name="current_password" id="current_password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-open"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-closed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        
                        <label for="new_password">Nueva contrasena (min. 8 caracteres)</label>
                        <p class="hint" style="margin-top:-8px;margin-bottom:4px">Solo letras, numeros y caracteres especiales (!@#$%^&*).</p>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" id="new_password" required minlength="8">
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-open"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-closed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        
                        <label for="confirm_password">Confirmar nueva contrasena</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)" tabindex="-1">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-open"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-closed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        
                        <button type="submit" style="margin-top:16px">Cambiar contrasena</button>
                    </form>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:24px 0">

                <!-- Formulario de pregunta de seguridad -->
                <div>
                    <h2 style="font-size:0.95rem;margin-bottom:6px;color:var(--fg)">Pregunta de seguridad</h2>
                    <p class="hint" style="margin-bottom:16px">Se usa para recuperar tu contrasena si la olvidas. Se guarda solo 1 pregunta.</p>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="set_security_question">
                        
                        <label for="question">Pregunta</label>
                        <select name="question" id="question" required>
                            <option value="">Selecciona una pregunta...</option>
                            <?php
                            $questions = [
                                'Cual es el nombre de tu primera mascota?',
                                'En que ciudad naciste?',
                                'Cual es el nombre de tu mejor amigo de la infancia?',
                                'Cual fue tu primer apellido de soltera de tu madre?',
                                'Cual es tu color favorito?',
                                'Cual es el nombre de tu escuela primaria?',
                                'Cual es tu comida favorita?',
                                'Cual es tu pelicula favorita?',
                            ];
                            $currentQ = $sq['question'] ?? '';
                            foreach ($questions as $q): ?>
                            <option value="<?= htmlspecialchars($q) ?>"<?= $currentQ === $q ? ' selected' : '' ?>><?= htmlspecialchars($q) ?></option>
                            <?php endforeach; ?>
                            <option value="__custom"<?php if ($currentQ && !in_array($currentQ, $questions)): ?> selected<?php endif; ?>>Otra pregunta (escribir)...</option>
                        </select>
                        <input type="text" name="question_custom" id="question_custom" value="<?= (!empty($currentQ) && !in_array($currentQ, $questions)) ? htmlspecialchars($currentQ) : '' ?>" placeholder="Escribe tu pregunta personalizada" style="display:none;margin-top:8px">
                        
                        <label for="answer">Respuesta</label>
                        <input type="text" name="answer" id="answer" value="" placeholder="<?= ($sq['answer_hash'] ?? '') ? '(ya configurada - escribe para cambiar)' : 'Escribe tu respuesta' ?>" required autocomplete="off">
                        <p class="hint">La respuesta se guarda encriptada. No podras verla despues.</p>
                        
                        <button type="submit" style="margin-top:16px">Guardar pregunta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de utilidades del panel admin (toggle contrasena) -->
    <script src="admin.js?v=<?= filemtime(__DIR__ . '/admin.js') ?>"></script>
    <script>
    /**
     * Cambiar entre pestañas (Perfil / Seguridad)
     */
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        // Activar el boton correcto
        document.querySelectorAll('.tab-btn').forEach(b => {
            if (b.textContent.toLowerCase().includes(tab)) b.classList.add('active');
        });
        history.replaceState(null, '', '?tab=' + tab);
    }

    /**
     * VISTA PREVIA DE IMÁGENES
     * Se activa cuando el usuario selecciona un archivo
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Preview del avatar
        var avatarInput = document.getElementById('avatar');
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(ev) {
                        var img = document.getElementById('avatarPreviewImg');
                        if (img) img.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Preview de la portada
        var coverInput = document.getElementById('cover_file');
        if (coverInput) {
            coverInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(ev) {
                        var preview = document.getElementById('coverPreview');
                        if (!preview) return;
                        var img = preview.querySelector('img:not(.cover-preview-fallback)');
                        if (!img) {
                            img = document.createElement('img');
                            img.alt = 'Vista previa de portada';
                            preview.insertBefore(img, preview.firstChild);
                        }
                        img.src = ev.target.result;
                        img.style.display = 'block';
                        var fallback = preview.querySelector('.cover-preview-fallback');
                        if (fallback) fallback.style.display = 'none';
                        var label = preview.querySelector('.cover-preview-label');
                        if (label) label.textContent = 'Nueva portada';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // ═══ SELECTOR DE PREGUNTA DE SEGURIDAD ═══
    var questionSelect = document.getElementById('question');
    var customInput = document.getElementById('question_custom');
    if (questionSelect && customInput) {
        questionSelect.addEventListener('change', function() {
            customInput.style.display = this.value === '__custom' ? 'block' : 'none';
            if (this.value !== '__custom') customInput.value = '';
        });
        // Disparar change al cargar por si ya hay valor seleccionado
        if (questionSelect.value === '__custom') customInput.style.display = 'block';
    }

    // ═══ TOAST NOTIFICATIONS ═══
    <?php if ($message): ?>
    (function() {
        var isError = <?= (str_starts_with($message, 'Token') || str_starts_with($message, 'La ') || str_starts_with($message, 'Las ') || str_starts_with($message, 'No')) ? 'true' : 'false' ?>;
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success');
        toast.innerHTML = '<span class="toast-icon">' + (isError ? '&#10060;' : '&#9989;') + '</span><?= addslashes(htmlspecialchars($message)) ?>';
        container.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
    })();
    <?php endif; ?>
    <?php if ($flash): ?>
    (function() {
        var isError = <?= $flash['type'] === 'error' ? 'true' : 'false' ?>;
        var container = document.getElementById('toastContainer');
        var toast = document.createElement('div');
        toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success');
        toast.innerHTML = '<span class="toast-icon">' + (isError ? '&#10060;' : '&#9989;') + '</span><?= addslashes(htmlspecialchars($flash['msg'])) ?>';
        container.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 4000);
    })();
    <?php endif; ?>
    </script>
</body>
</html>
