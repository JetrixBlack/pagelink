<?php
/*
 * PageLink - Panel de Administración
 * Archivo: login.php
 * Descripción: Formulario de inicio de sesión para el administrador.
 *              Permite autenticar al admin con usuario y contraseña,
 *              verifica el token CSRF y maneja la sesión del usuario.
 */

// Iniciar sesión para manejar el estado de autenticación
session_start();

// Cargar la configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// ─── Manejo del envío del formulario (POST) ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el token CSRF sea válido para prevenir ataques de falsificación
    if (!verify_csrf()) {
        $error = 'Token de seguridad invalido.';
    } else {
        // Obtener los datos enviados por el formulario
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        // Buscar el administrador en la base de datos por nombre de usuario
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // Verificar que el usuario exista y que la contraseña coincida con el hash almacenado
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Regenerar el ID de sesión para prevenir secuestro de sesión
            session_regenerate_id(true);

            // Guardar variables de sesión del administrador
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_activity'] = time();

            // Redirigir al panel de administración
            header('Location: index.php');
            exit;
        }

        // Si las credenciales son incorrectas, mostrar mensaje de error
        $error = 'Usuario o contrasena incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — PageLink</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f0f0f;
            color: #e0d8d0;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 14px;
            border: 1px solid #2a2a2a;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 360px;
        }
        h1 { font-size: 1.5rem; margin-bottom: 24px; text-align: center; color: #c47a8a; }
        label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 4px; margin-top: 16px; color: #e0d8d0; }
        .pw-wrap {
            position: relative;
        }
        .pw-wrap input {
            width: 100%;
            padding: 10px 44px 10px 12px;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            font-size: 0.95rem;
            margin-bottom: 0;
            background: #0f0f0f;
            color: #e0d8d0;
            transition: border-color 0.2s;
        }
        .pw-wrap input:focus {
            outline: none;
            border-color: #c47a8a;
        }
        .pw-toggle {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            color: #8a8080; display: flex; align-items: center; justify-content: center;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: #e0d8d0; }
        .pw-toggle svg { width: 20px; height: 20px; }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #c47a8a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.15s;
        }
        button[type="submit"]:hover { background: #d48a98; }
        .error { color: #ca6a6a; font-size: 0.85rem; text-align: center; margin-bottom: 16px; }
        .forgot-link {
            display: block; text-align: center; margin-top: 16px;
            font-size: 0.85rem; color: #8a8080; text-decoration: none;
            transition: color 0.15s;
        }
        .forgot-link:hover { color: #c47a8a; }
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

    <!-- ═══════════════════════════════════════
         FORMULARIO DE LOGIN
         Envía usuario y contraseña al mismo archivo vía POST.
    ═══════════════════════════════════════ -->
    <form class="login-box" method="POST">
        <h1>PageLink Admin</h1>
        <!-- Mostrar mensaje de error si existe -->
        <?php if (isset($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Campo oculto con el token CSRF para protección -->
        <?= csrf_field() ?>
        <label for="username">Usuario</label>
        <input type="text" name="username" id="username" required autofocus style="width:100%;padding:10px 12px;border:1px solid #2a2a2a;border-radius:8px;font-size:0.95rem;background:#0f0f0f;color:#e0d8d0">
        <label for="password">Contrasena</label>
        <!-- Contenedor del campo contraseña con botón de mostrar/ocultar -->
        <div class="pw-wrap">
            <input type="password" name="password" id="password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('password', this)" tabindex="-1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-open"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-closed" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>
        <button type="submit">Ingresar</button>
        <!-- Enlace a la página de recuperación de contraseña -->
        <a href="forgot-password.php" class="forgot-link">Olvidaste tu contrasena?</a>
    </form>
    <script>
    // Alternar visibilidad de la contraseña entre texto y puntos
    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        const eyeOpen = btn.querySelector('.eye-open');
        const eyeClosed = btn.querySelector('.eye-closed');
        if (input.type === 'password') {
            input.type = 'text';
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = 'block';
        } else {
            input.type = 'password';
            eyeOpen.style.display = 'block';
            eyeClosed.style.display = 'none';
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
