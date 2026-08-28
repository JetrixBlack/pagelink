<?php
/*
 * PageLink - Middleware de autenticacion del admin.
 *
 * Reemplaza el patron replicado en cada pagina admin:
 *   session_start(); if (!$_SESSION['admin_logged_in']) { redirect login; }
 *   check_session_timeout();
 *
 *  - admin_session_start(): inicia sesion (usando handler Turso en Vercel).
 *  - require_admin(): exige sesion valida; si no, redirige a /admin/login.
 *  - admin_redirect_home(): si ya hay sesion, redirige a /admin/dashboard.
 */
declare(strict_types=1);

function admin_session_start(): void {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/session.php';
    require_once __DIR__ . '/helpers.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_name('pagelink_session');
        session_start();
    }
}

function admin_session_destroy_all(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Exige admin autenticado; si no, redirige al login.
function require_admin(): void {
    admin_session_start();
    if (empty($_SESSION['admin_logged_in'])) {
        redirect('/admin/login');
    }
    check_session_timeout();
}

// Si ya hay sesion activa, redirige al dashboard (para login/forgot).
function admin_redirect_home(): void {
    admin_session_start();
    if (!empty($_SESSION['admin_logged_in'])) {
        redirect('/admin/dashboard');
    }
}
