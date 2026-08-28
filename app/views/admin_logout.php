<?php
/*
 * PageLink - Vista: Cerrar sesion.
 */
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

admin_session_start();
admin_session_destroy_all();
redirect('/admin/login');
