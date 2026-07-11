<?php
/*
 * PageLink - Panel de Administración
 * Archivo: _nav.php
 * Descripción: Barra de navegación compartida del panel de administración.
 *              Se incluye en todas las páginas del admin para mostrar
 *              los enlaces de navegación con el estado activo resaltado.
 */

// Obtener el nombre del archivo actual para marcar la página activa
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Definir las páginas del menú de navegación
$pages = [
    'index.php'          => 'Dashboard',
    'links.php'          => 'Enlaces',
    'testimonials.php'   => 'Testimonios',
    'profile.php'        => 'Perfil',
];
?>
<header>
    <h1>Panel PageLink</h1>
    <nav>
        <!-- Generar enlaces de navegación; marcar la página actual con clase "active" -->
        <?php foreach ($pages as $file => $label): ?>
        <a href="<?= $file ?>"<?= $currentPage === $file ? ' class="active"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
        <!-- Enlace para cerrar sesión -->
        <a href="logout.php">Salir</a>
        <!-- Enlace para abrir la página pública en una nueva pestaña -->
        <a href="../index.php" target="_blank" class="nav-accent" title="Abrir pagina publica en nueva pestana">Ver pagina</a>
    </nav>
</header>
