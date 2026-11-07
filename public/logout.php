<?php
/**
 * SAFEVAULT - Penanganan Keluar Sesi (Logout)
 * Versi 0.1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Pastikan hanya metode POST yang diterima untuk mencegah CSRF Logout
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('error', 'Metode permintaan tidak valid untuk proses logout.');
    redirect('index.php');
}

// Validasi Token CSRF
$token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($token)) {
    http_response_code(403);
    set_flash('error', 'Sesi keamanan tidak valid atau telah kedaluwarsa.');
    redirect('index.php');
}

// Kosongkan seluruh data session
$_SESSION = [];

// Hapus cookie session dari browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session
session_destroy();

// Mulai session baru yang bersih untuk menampilkan flash message logout
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
set_flash('success', 'Anda telah berhasil keluar dari akun SAFEVAULT.');
redirect('index.php');

