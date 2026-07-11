<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$pages = [
    'index.php'          => 'Dashboard',
    'links.php'          => 'Enlaces',
    'testimonials.php'   => 'Testimonios',
    'stats.php'          => 'Estadísticas',
    'profile.php'        => 'Perfil',
    'change-password.php'=> 'Seguridad',
];
?>
<header>
    <h1>Panel PageLink</h1>
    <nav>
        <?php foreach ($pages as $file => $label): ?>
        <a href="<?= $file ?>"<?= $currentPage === $file ? ' class="active"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
        <a href="logout.php">Salir</a>
        <a href="../index.php" target="_blank" style="background:#7c3aed;color:#fff" title="Abrir página pública en nueva pestaña">Ver página</a>
    </nav>
</header>
