<?php
/**
 * SESION EXPIRADA — PageLink
 * Se muestra cuando la sesion del admin expira por inactividad.
 * Mismo estilo visual que el login.
 */
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesion Expirada — PageLink</title>
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
            width: 100%; max-width: 360px; text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 16px; }
        h1 { font-size: 1.3rem; margin-bottom: 8px; color: #c47a8a; }
        p { color: #8a8080; font-size: 0.9rem; margin-bottom: 24px; line-height: 1.5; }
        a {
            display: block; width: 100%; padding: 12px; background: #c47a8a; color: #fff;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: 600;
            cursor: pointer; text-decoration: none; transition: background 0.15s;
        }
        a:hover { background: #d48a98; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">&#9200;</div>
        <h1>Sesion expirada</h1>
        <p>Tu sesion ha expirado por inactividad. Por seguridad, debes iniciar sesion nuevamente.</p>
        <a href="login.php">Iniciar sesion</a>
    </div>
</body>
</html>
