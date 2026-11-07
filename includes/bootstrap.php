<?php
/**
 * SAFEVAULT - System Bootstrap
 *
 * Menginisialisasi session dengan konfigurasi keamanan ketat,
 * memeriksa kompatibilitas lingkungan (Argon2id),
 * memuat konfigurasi database, dan memuat pustaka helper.
 */

// Konstanta penanda bootstrap aplikasi
if (!defined('SAFEVAULT_APP')) {
    define('SAFEVAULT_APP', true);
}

// 1. Konfigurasi Keamanan Session (HttpOnly & SameSite)
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan parameter cookie session
    $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0,            // Berlaku hingga browser ditutup
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_secure,
        'httponly' => true,         // Mencegah akses cookie via JavaScript (XSS defense)
        'samesite' => 'Lax',        // Mencegah pengiriman cookie lintas domain (CSRF defense)
    ]);

    // Opsi konfigurasi session bawaan PHP
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();
}

// 2. Pemeriksaan Wajib Ketersediaan PASSWORD_ARGON2ID
if (!defined('PASSWORD_ARGON2ID')) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Pemeriksaan Lingkungan Gagal - SAFEVAULT</title>
        <style>
            body { font-family: sans-serif; background: #0f172a; color: #f8fafc; padding: 3rem; }
            .card { max-width: 600px; margin: auto; background: #1e293b; border: 1px solid #ef4444; border-radius: 8px; padding: 2rem; }
            h2 { color: #ef4444; margin-top: 0; }
            p { color: #94a3b8; line-height: 1.6; }
            code { background: #334155; padding: 0.2rem 0.4rem; border-radius: 4px; color: #f8fafc; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Pemeriksaan Lingkungan Gagal</h2>
            <p><strong>PASSWORD_ARGON2ID</strong> tidak tersedia pada instalasi PHP Anda.</p>
            <p><strong>Solusi:</strong></p>
            <ul>
                <li>Pastikan versi PHP yang digunakan minimal PHP 7.3+ dengan ekstensi <code>sodium</code> aktif.</li>
                <li>Periksa file <code>php.ini</code> dan pastikan baris <code>extension=sodium</code> diaktifkan.</li>
                <li>Gunakan PHP resmi dari XAMPP terbaru (PHP 8.x).</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 3. Muat Modul CSRF dan Fungsi Helper
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/functions.php';

// Pastikan token CSRF selalu siap di session
generate_csrf_token();

/**
 * Mendapatkan instance koneksi database PDO (Singleton).
 *
 * @return PDO
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $project_root = dirname(__DIR__);
    $config_file = $project_root . '/config/database.local.php';

    if (!file_exists($config_file)) {
        // Fallback ke template jika file lokal belum dibuat
        $config_file = $project_root . '/config/database.example.php';
    }

    if (!file_exists($config_file)) {
        error_log('[SAFEVAULT] Berkas konfigurasi database tidak ditemukan.');
        die('Konfigurasi database tidak ditemukan. Silakan buat file <code>config/database.local.php</code>.');
    }

    $config = require $config_file;

    $host    = $config['host'] ?? '127.0.0.1';
    $port    = $config['port'] ?? 3306;
    $dbname  = $config['dbname'] ?? 'safevault_db';
    $charset = $config['charset'] ?? 'utf8mb4';
    $user    = $config['username'] ?? 'root';
    $pass    = $config['password'] ?? '';
    $options = $config['options'] ?? [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Catat error teknis ke log internal, jangan tampilkan informasi sensitif ke pengguna
        error_log('[SAFEVAULT Database Error] ' . $e->getMessage());

        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Koneksi Database Gagal - SAFEVAULT</title>
            <style>
                body { font-family: sans-serif; background: #0f172a; color: #f8fafc; padding: 3rem; }
                .card { max-width: 600px; margin: auto; background: #1e293b; border: 1px solid #f59e0b; border-radius: 8px; padding: 2rem; }
                h2 { color: #f59e0b; margin-top: 0; }
                p { color: #94a3b8; line-height: 1.6; }
                code { background: #334155; padding: 0.2rem 0.4rem; border-radius: 4px; color: #f8fafc; }
            </style>
        </head>
        <body>
            <div class="card">
                <h2>Koneksi Database Belum Terhubung</h2>
                <p>Aplikasi SAFEVAULT tidak dapat terhubung ke database MySQL.</p>
                <p><strong>Langkah Penyelesaian:</strong></p>
                <ol>
                    <li>Buka <strong>XAMPP Control Panel</strong> dan pastikan modul <strong>MySQL</strong> dalam status <strong>Start</strong> (hijau).</li>
                    <li>Buka <strong>phpMyAdmin</strong> (<code>http://localhost/phpmyadmin/</code>) dan impor skrip <code>database/schema.sql</code> untuk membuat database <code>safevault_db</code> dan tabel <code>users</code>.</li>
                    <li>Pastikan pengaturan kredensial di <code>config/database.local.php</code> sudah sesuai dengan konfigurasi MySQL XAMPP Anda.</li>
                </ol>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

