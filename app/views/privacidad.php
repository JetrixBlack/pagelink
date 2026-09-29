<?php
/*
 * PageLink - Vista: Política de Privacidad.
 * Se invoca desde el front controller (api/index.php) para el route "privacidad".
 */
declare(strict_types=1);
$title = 'Política de Privacidad';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Política de privacidad de PageLink.">
  <title><?= htmlspecialchars($title) ?> · PageLink</title>
  <link rel="stylesheet" href="<?= base_path() ?>assets/css/style.css?v=<?= asset_version('assets/css/style.css') ?>">
  <style>
    .legal-page {
      max-width: 760px;
      margin: 0 auto;
      padding: 2.5rem 1.25rem 3rem;
      color: var(--fg);
      background: var(--bg);
      min-height: 100vh;
    }
    .legal-page a { color: var(--accent); }
    .legal-page .back a { text-decoration: none; color: var(--accent); font-weight: 600; }
    .legal-page h1 { font-size: 1.8rem; margin: 1.5rem 0 0.25rem; }
    .legal-page .updated { color: var(--fg-soft); font-size: 0.85rem; margin-bottom: 1.5rem; }
    .legal-page h2 { font-size: 1.2rem; margin: 1.6rem 0 0.6rem; color: var(--accent); }
    .legal-page p, .legal-page li { line-height: 1.7; margin-bottom: 0.6rem; color: var(--fg); }
    .legal-page ul { padding-left: 1.25rem; }
    .legal-page strong { color: var(--fg); }
  </style>
</head>
<body>
  <div class="legal-page">
    <div class="back"><a href="<?= base_path() ?>">← Volver al inicio</a></div>

    <h1>Política de Privacidad</h1>
    <p class="updated">Última actualización: 2026-09-29</p>

    <h2>1. Responsable del tratamiento</h2>
    <p>Los datos personales recopilados por <strong>PageLink</strong> son gestionados por:</p>
    <ul>
      <li><strong>Responsable:</strong> Administrador de PageLink.</li>
      <li><strong>Sitio:</strong> aplicación web desplegada en Vercel.</li>
    </ul>

    <h2>2. Datos que recopilamos</h2>
    <p>PageLink recopila únicamente los datos necesarios para su funcionamiento:</p>
    <ul>
      <li><strong>Datos de perfil:</strong> nombre, biografía, avatar, portada y textos de la página pública.</li>
      <li><strong>Datos de cuenta:</strong> usuario admin, contraseña (almacenada cifrada) y pregunta de seguridad con su respuesta cifrada (para recuperación de acceso).</li>
      <li><strong>Datos de visitantes:</strong> testimonios enviados, que incluyen el texto y el nombre del autor; y el registro de cada clic en los enlaces publicados.</li>
      <li><strong>Datos técnicos:</strong> dirección IP y agente del navegador, utilizados para estadísticas de clics y seguridad.</li>
    </ul>
    <p><strong>No se recopilan datos de pago ni información bancaria.</strong></p>

    <h2>3. Finalidad del tratamiento</h2>
    <p>Los datos se utilizan exclusivamente para:</p>
    <ol>
      <li>Mostrar la página de enlaces pública.</li>
      <li>Contar clics y gestionar testimonios.</li>
      <li>Proteger el acceso administrativo.</li>
    </ol>
    <p>No se utilizan datos para publicidad ni se ceden a terceros.</p>

    <h2>4. Base legal</h2>
    <p>El tratamiento se ampara en el <strong>artículo 20 de la Constitución de la República Bolivariana de Venezuela</strong> y la <strong>Ley Orgánica de Protección de Datos Personales (LOPDP)</strong>.</p>

    <h2>5. Conservación de los datos</h2>
    <p>Los datos se conservan mientras la cuenta esté activa y por el tiempo necesario para cumplir obligaciones legales. A solicitud del usuario, los datos se suprimen o anonimizan.</p>

    <h2>6. Derechos del usuario</h2>
    <p>El usuario puede acceder, rectificar, solicitar la eliminación u oponerse al tratamiento de sus datos personales contactando al administrador de PageLink.</p>

    <h2>7. Seguridad de los datos</h2>
    <ul>
      <li>Contraseñas cifradas con hash seguro.</li>
      <li>Sesiones con expiración.</li>
      <li>Conexiones cifradas (HTTPS).</li>
      <li>Límite de intentos de inicio de sesión.</li>
    </ul>

    <h2>8. Cookies y almacenamiento local</h2>
    <p>No se utilizan cookies de terceros ni de seguimiento publicitario. La sesión administrativa se maneja con cookies de sesión propias.</p>

    <h2>9. Terceros</h2>
    <p>La aplicación no comparte datos personales con terceros. La infraestructura (Vercel y Turso) gestiona los datos bajo sus propios términos de servicio.</p>

    <h2>10. Cambios en esta política</h2>
    <p>Cualquier cambio se publicará en esta página con la fecha de actualización.</p>

    <h2>11. Contacto</h2>
    <p>Ante cualquier duda sobre esta política, contacte al administrador de PageLink.</p>
  </div>
</body>
</html>