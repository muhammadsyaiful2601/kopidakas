<?php
/**
 * Admin session guard (PRD Bagian 3B: Autentikasi Login/Logout)
 * Menggunakan PHP $_SESSION untuk mengunci rute admin dari akses publik.
 */
session_start();

require_once __DIR__ . '/../../includes/db.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['admin_id'] ?? null);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        header('Location: ../login.php');
        exit;
    }
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}