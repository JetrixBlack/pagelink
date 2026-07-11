<?php
/*
 * PageLink - Panel de Administración
 * Archivo: forgot-password.php
 * Descripción: Flujo de recuperación de contraseña en dos pasos:
 *              Paso 1 — El usuario ingresa su nombre de usuario.
 *              Paso 2 — Responde la pregunta de seguridad y establece
 *                       una nueva contraseña.
 *              Incluye limitación de intentos (máx. 5 por hora).
 */

session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// Determinar en qué paso del flujo nos encontramos (1 o 2)
$step = (int)($_GET['step'] ?? 1);
$error = '';
$message = '';

// ─── Limitación de intentos (rate limiting) ──────────────────────
// Máximo 5 intentos por hora para prevenir fuerza bruta.
// Se almacenan en la sesión: contador y timestamp del primer intento.
$attempts_key = 'forgot_attempts';
if (!isset($_SESSION[$attempts_key])) {
    $_SESSION[$attempts_key] = ['count' => 0, 'first_attempt' => time()];
}
$attempts = $_SESSION[$attempts_key];

// Reiniciar el contador si ya pasó más de 1 hora desde el primer intento
if (time() - $attempts['first_attempt'] > 3600) {
    $_SESSION[$attempts_key] = ['count' => 0, 'first_attempt' => time()];
    $attempts = $_SESSION[$attempts_key];
}

// Si se alcanzó el límite de 5 intentos, bloquear el proceso
if ($attempts['count'] >= 5) {
    $error = 'Demasiados intentos. Intenta de nuevo en 1 hora.';
    $step = 1;
}

// ─── Procesamiento del formulario POST ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $attempts['count'] < 5) {
    // Verificar token CSRF antes de procesar
    if (!verify_csrf()) {
        $error = 'Token de seguridad invalido.';
    } else {
        // ── Paso 1: Verificar que el usuario exista ──────────────
        if ($step === 1) {
            $username = trim($_POST['username'] ?? '');

            // Buscar el administrador por nombre de usuario
            $admin = $db->prepare("SELECT * FROM admin WHERE username = ?");
            $admin->execute([$username]);
            $admin = $admin->fetch();

            if (!$admin) {
                $error = 'Usuario no encontrado.';
            } else {
                // Verificar que exista una pregunta de seguridad configurada
                $sq = $db->query("SELECT * FROM security_question WHERE id = 1")->fetch();
                if (empty($sq['question']) || empty($sq['answer_hash'])) {
                    $error = 'No hay pregunta de seguridad configurada. Contacta al administrador.';
                } else {
                    // Guardar el usuario en sesión y avanzar al paso 2
                    $_SESSION['forgot_user'] = $username;
                    $_SESSION['forgot_step'] = 2;
                    header('Location: forgot-password.php?step=2');
                    exit;
                }
            }
        }

        // ── Paso 2: Verificar respuesta + establecer nueva contraseña ──
        if ($step === 2) {
            $answer = trim($_POST['answer'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            // Obtener la respuesta hasheada de la pregunta de seguridad
            $sq = $db->query("SELECT * FROM security_question WHERE id = 1")->fetch();

            // Verificar que la respuesta sea correcta (comparar en minúsculas)
            if (!password_verify(mb_strtolower($answer), $sq['answer_hash'])) {
                $error = 'La respuesta es incorrecta.';
                $_SESSION[$attempts_key]['count']++;
            } elseif (strlen($new_password) < 8) {
                // Validar longitud mínima de la contraseña
                $error = 'La contrasena debe tener al menos 8 caracteres.';
            } elseif (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{}|;:\'",.<>?\/]+$/', $new_password)) {
                // Solo permite: mayusculas, minusculas, numeros y caracteres especiales
                $error = 'La contrasena solo puede contener letras, numeros y caracteres especiales (!@#$%^&*).';
            } elseif ($new_password !== $confirm) {
                // Verificar que ambas contraseñas coincidan
                $error = 'Las contrasenas no coinciden.';
            } else {
                // Todo correcto: actualizar la contraseña en la base de datos
                $username = $_SESSION['forgot_user'] ?? '';
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $db->prepare("UPDATE admin SET password_hash = ? WHERE username = ?")->execute([$hash, $username]);

                // Limpiar variables de sesión del flujo de recuperación
                unset($_SESSION['forgot_user'], $_SESSION['forgot_step'], $_SESSION[$attempts_key]);

                // Guardar mensaje de éxito y redirigir al login
                flash_set('Contrasena actualizada. Ahora puedes iniciar sesion.');
                header('Location: login.php');
                exit;
            }
        }
    }
}

// Si se intenta acceder al paso 2 sin haber completado el paso 1, redirigir
if ($step === 2 && empty($_SESSION['forgot_user'])) {
    header('Location: forgot-password.php?step=1');
    exit;
}

// ─── Obtener la pregunta de seguridad para mostrar en el paso 2 ──
$question = '';
if ($step === 2) {
    $sq = $db->query("SELECT question FROM security_question WHERE id = 1")->fetch();
    $question = $sq['question'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contrasena — PageLink</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f0f0f; color: #e0d8d0;
            min-height: 100dvh; display: flex; align-items: center;
            justify-content: center; padding: 20px;
        }
        .box {
            background: #1a1a1a; padding: 40px; border-radius: 14px;
            border: 1px solid #2a2a2a; box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 100%; max-width: 380px;
        }
        h1 { font-size: 1.3rem; margin-bottom: 8px; text-align: center; color: #c47a8a; }
        .subtitle { font-size: 0.85rem; color: #8a8080; text-align: center; margin-bottom: 24px; }
        label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 4px; margin-top: 16px; color: #e0d8d0; }
        input {
            width: 100%; padding: 10px 12px; border: 1px solid #2a2a2a;
            border-radius: 8px; font-size: 0.95rem; background: #0f0f0f;
            color: #e0d8d0; transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: #c47a8a; }
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 44px; }
        .pw-toggle {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            color: #8a8080; display: flex; align-items: center; justify-content: center;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: #e0d8d0; }
        .pw-toggle svg { width: 20px; height: 20px; }
        button[type="submit"] {
            width: 100%; padding: 12px; background: #c47a8a; color: #fff;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
            cursor: pointer; margin-top: 20px; transition: background 0.15s;
        }
        button[type="submit"]:hover { background: #d48a98; }
        .error { color: #ca6a6a; font-size: 0.85rem; text-align: center; margin-bottom: 16px; }
        .back-link {
            display: block; text-align: center; margin-top: 16px;
            font-size: 0.85rem; color: #8a8080; text-decoration: none;
        }
        .back-link:hover { color: #c47a8a; }
        .question-box {
            background: #0f0f0f; border: 1px solid #2a2a2a; border-radius: 8px;
            padding: 14px; margin-bottom: 8px; font-size: 0.95rem; color: #c47a8a;
            font-weight: 500; text-align: center;
        }
        .step-indicator {
            display: flex; gap: 8px; justify-content: center; margin-bottom: 20px;
        }
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%; background: #2a2a2a;
        }
        .step-dot.active { background: #c47a8a; }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="preloader-spinner"></div>
        <div class="preloader-text">PageLink</div>
    </div>
    <style>
    .preloader { position:fixed; inset:0; z-index:10000; background:#0f0f0f; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:16px; transition:opacity 0.4s; }
    .preloader.fade-out { opacity:0; pointer-events:none; }
    .preloader-spinner { width:36px; height:36px; border:3px solid #2a2a2a; border-top-color:#c47a8a; border-radius:50%; animation:spin 0.8s linear infinite; }
    .preloader-text { color:#8a8080; font-size:0.9rem; letter-spacing:0.05em; }
    @keyframes spin { to { transform:rotate(360deg); } }
    </style>
    <form class="box" method="POST">
        <?= csrf_field() ?>
        <!-- Indicador visual del paso actual (puntos) -->
        <div class="step-indicator">
            <div class="step-dot<?= $step === 1 ? ' active' : '' ?>"></div>
            <div class="step-dot<?= $step === 2 ? ' active' : '' ?>"></div>
        </div>

        <?php if ($step === 1): ?>
            <!-- ── PASO 1: Ingreso de usuario ─────────────────────── -->
            <h1>Recuperar contrasena</h1>
            <p class="subtitle">Ingresa tu usuario para comenzar</p>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <label for="username">Usuario</label>
            <input type="text" name="username" id="username" required autofocus>
            <button type="submit">Siguiente</button>
        <?php else: ?>
            <!-- ── PASO 2: Verificar identidad y establecer nueva contraseña ── -->
            <h1>Verificar identidad</h1>
            <p class="subtitle">Responde tu pregunta de seguridad</p>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <!-- Mostrar la pregunta de seguridad configurada -->
            <label>Pregunta</label>
            <div class="question-box"><?= htmlspecialchars($question) ?></div>
            <label for="answer">Tu respuesta</label>
            <input type="text" name="answer" id="answer" required autofocus autocomplete="off">
            <label for="new_password">Nueva contrasena (min. 8 caracteres)</label>
            <p style="font-size:0.75rem;color:#8a8080;margin-top:-4px;margin-bottom:4px">Solo letras, numeros y caracteres especiales (!@#$%^&*).</p>
            <!-- Campo de nueva contraseña con botón de mostrar/ocultar -->
            <div class="pw-wrap">
                <input type="password" name="new_password" id="new_password" required minlength="8">
                <button type="button" class="pw-toggle" onclick="togglePw('new_password', this)" tabindex="-1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-open"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-closed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <label for="confirm_password">Confirmar contrasena</label>
            <!-- Campo de confirmación de contraseña con botón de mostrar/ocultar -->
            <div class="pw-wrap">
                <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                <button type="button" class="pw-toggle" onclick="togglePw('confirm_password', this)" tabindex="-1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-open"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-closed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
            <button type="submit">Restablecer contrasena</button>
        <?php endif; ?>

        <!-- Enlace para volver al formulario de login -->
        <a href="login.php" class="back-link">&larr; Volver al login</a>
    </form>
    <script>
    // Alternar visibilidad de la contraseña entre texto y puntos
    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        const eyeOpen = btn.querySelector('.eye-open');
        const eyeClosed = btn.querySelector('.eye-closed');
        if (input.type === 'password') {
            input.type = 'text'; eyeOpen.style.display = 'none'; eyeClosed.style.display = 'block';
        } else {
            input.type = 'password'; eyeOpen.style.display = 'block'; eyeClosed.style.display = 'none';
        }
    }
    // Ocultar preloader cuando la pagina este lista
    window.addEventListener('load', function() {
        var p = document.getElementById('preloader');
        if (p) { p.classList.add('fade-out'); setTimeout(function() { p.remove(); }, 400); }
    });
    </script>
</body>
</html>
