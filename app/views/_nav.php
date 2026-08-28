<?php
/*
 * PageLink - Barra de navegacion del admin (front controller).
 * Uso: admin_nav('dashboard' | 'links' | 'testimonials' | 'profile')
 */
declare(strict_types=1);

function admin_nav(string $active): void {
    $items = [
        'dashboard'    => ['/admin/dashboard', 'Dashboard'],
        'links'        => ['/admin/links', 'Enlaces'],
        'testimonials' => ['/admin/testimonials', 'Testimonios'],
        'profile'      => ['/admin/profile', 'Perfil'],
    ];
    $base = base_path();
?>
<header>
    <h1>Panel PageLink</h1>
    <nav>
        <?php foreach ($items as $key => [$href, $label]): ?>
        <a href="<?= $base . ltrim($href, '/') ?>"<?= $active === $key ? ' class="active"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
        <a href="<?= $base ?>admin/logout">Salir</a>
        <a href="<?= $base ?>" target="_blank" class="nav-accent" title="Abrir pagina publica en nueva pestana">Ver pagina</a>
    </nav>
</header>
<?php
}
