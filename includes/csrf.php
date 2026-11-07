<?php
/**
 * SAFEVAULT - Modul Proteksi CSRF (Cross-Site Request Forgery)
 *
 * Menggunakan token acak kriptografis yang disimpan dalam session
 * dan divalidasi menggunakan hash_equals untuk mencegah timing attack.
 */

if (!defined('SAFEVAULT_APP')) {
    exit('Akses langsung tidak diizinkan.');
}

/**
 * Menghasilkan token CSRF unik jika belum ada di session.
 *
 * @return string Token CSRF 64 karakter heksadesimal
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Mengambil token CSRF aktif saat ini.
 *
 * @return string
 */
function get_csrf_token(): string
{
    return generate_csrf_token();
}

/**
 * Menghasilkan elemen input HTML hidden berisi token CSRF.
 *
 * @return string
 */
function csrf_input(): string
{
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Memvalidasi token CSRF yang dikirim dari form terhadap token di session.
 *
 * @param string|null $token
 * @return bool True jika token cocok dan valid
 */
function validate_csrf_token(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

