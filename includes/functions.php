<?php
/**
 * SAFEVAULT - Kumpulan Fungsi Pembantu (Helper Functions)
 *
 * Berisi fungsi sanitasi input/output, validasi data, manajemen autentikasi,
 * pesan kilat (flash messages), dan operasi database dengan Prepared Statements.
 */

if (!defined('SAFEVAULT_APP')) {
    exit('Akses langsung tidak diizinkan.');
}

/**
 * Sanitasi string untuk output HTML guna mencegah Cross-Site Scripting (XSS).
 *
 * @param mixed $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Mengarahkan peramban ke URL tertentu dan menghentikan eksekusi script.
 *
 * @param string $path
 * @return void
 */
function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

/**
 * Memeriksa apakah pengguna saat ini sudah terautentikasi (login).
 *
 * @return bool
 */
function is_authenticated(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Memastikan hanya pengguna yang sudah login yang dapat mengakses halaman.
 * Jika belum login, diarahkan ke halaman login.
 *
 * @return void
 */
function require_auth(): void
{
    if (!is_authenticated()) {
        set_flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        redirect('login.php');
    }
}

/**
 * Memastikan pengguna yang sudah login tidak dapat mengakses halaman guest (register/login).
 * Jika sudah login, diarahkan langsung ke dashboard.
 *
 * @return void
 */
function require_guest(): void
{
    if (is_authenticated()) {
        redirect('dashboard.php');
    }
}

/**
 * Mengambil informasi pengguna yang sedang login dari session.
 *
 * @return array{id: int, username: string, email: string}|null
 */
function get_logged_in_user(): ?array
{
    if (!is_authenticated()) {
        return null;
    }

    return [
        'id'       => (int) $_SESSION['user_id'],
        'username' => (string) ($_SESSION['username'] ?? ''),
        'email'    => (string) ($_SESSION['email'] ?? ''),
    ];
}

/**
 * Menyimpan pesan kilat (flash message) dalam session untuk ditampilkan di halaman berikutnya.
 *
 * @param string $type Jenis pesan: 'success', 'error', 'info', 'warning'
 * @param string $message Teks pesan
 * @return void
 */
function set_flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Mengambil dan menghapus seluruh pesan kilat dari session.
 *
 * @return array<int, array{type: string, message: string}>
 */
function get_flashes(): array
{
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Validasi format username.
 * Harus 3-30 karakter, hanya huruf, angka, dan underscore (_).
 *
 * @param string $username
 * @return string|null Pesan error jika tidak valid, null jika valid
 */
function validate_username(string $username): ?string
{
    $username = trim($username);
    if ($username === '') {
        return 'Username wajib diisi.';
    }
    if (strlen($username) < 3 || strlen($username) > 30) {
        return 'Username harus memiliki panjang antara 3 hingga 30 karakter.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return 'Username hanya boleh memuat huruf, angka, dan garis bawah (_).';
    }
    return null;
}

/**
 * Validasi format email.
 *
 * @param string $email
 * @return string|null Pesan error jika tidak valid, null jika valid
 */
function validate_email(string $email): ?string
{
    $email = trim($email);
    if ($email === '') {
        return 'Email wajib diisi.';
    }
    if (strlen($email) > 254) {
        return 'Email tidak boleh lebih dari 254 karakter.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Format email yang dimasukkan tidak valid.';
    }
    return null;
}

/**
 * Validasi password pengguna.
 * Minimal 8 karakter.
 *
 * @param string $password
 * @return string|null Pesan error jika tidak valid, null jika valid
 */
function validate_password(string $password): ?string
{
    if ($password === '') {
        return 'Password wajib diisi.';
    }
    if (strlen($password) < 8) {
        return 'Password minimal harus terdiri dari 8 karakter.';
    }
    return null;
}

/**
 * Mencari data pengguna berdasarkan username.
 * Menggunakan Prepared Statement untuk keamanan terhadap SQL Injection.
 *
 * @param PDO $pdo
 * @param string $username
 * @return array|null
 */
function find_user_by_username(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, created_at, updated_at FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Mencari data pengguna berdasarkan email.
 * Menggunakan Prepared Statement untuk keamanan terhadap SQL Injection.
 *
 * @param PDO $pdo
 * @param string $email
 * @return array|null
 */
function find_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, created_at, updated_at FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Mencari data pengguna berdasarkan username ATAU email (untuk form login).
 * Menggunakan Prepared Statement untuk keamanan terhadap SQL Injection.
 *
 * @param PDO $pdo
 * @param string $identifier
 * @return array|null
 */
function find_user_by_identifier(PDO $pdo, string $identifier): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, created_at, updated_at FROM users WHERE username = :username OR email = :email LIMIT 1');
    $stmt->execute([
        ':username' => $identifier,
        ':email'    => $identifier,
    ]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Mendaftarkan pengguna baru dengan hash password Argon2id.
 * Menggunakan Prepared Statement.
 *
 * @param PDO $pdo
 * @param string $username
 * @param string $email
 * @param string $password
 * @return int ID pengguna yang baru dibuat
 * @throws RuntimeException Jika hashing gagal atau query gagal
 */
function create_user(PDO $pdo, string $username, string $email, string $password): int
{
    // Verifikasi ketersediaan PASSWORD_ARGON2ID
    if (!defined('PASSWORD_ARGON2ID')) {
        throw new RuntimeException('PASSWORD_ARGON2ID tidak tersedia di lingkungan server PHP Anda.');
    }

    $hash = password_hash($password, PASSWORD_ARGON2ID);
    if ($hash === false) {
        throw new RuntimeException('Gagal menghasilkan hash password.');
    }

    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
    $stmt->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => $hash,
    ]);

    return (int) $pdo->lastInsertId();
}

