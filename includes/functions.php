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
 * Menghapus dan menghancurkan sesi serta cookie sesi secara bersih.
 *
 * @return void
 */
function destroy_session_and_cookies(): void
{
    $_SESSION = [];
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
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Memeriksa apakah pengguna saat ini sudah terautentikasi (login)
 * serta memvalidasi masa berlaku sesi (Idle Timeout & Absolute Timeout).
 *
 * @return bool True jika sesi aktif dan belum kedaluwarsa
 */
function is_authenticated(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $now = time();
    $idle_timeout = 900;        // 15 menit
    $absolute_timeout = 28800;  // 8 jam

    // 1. Validasi Absolute Timeout (8 Jam sejak waktu login awal)
    $login_time = $_SESSION['login_time'] ?? 0;
    if ($login_time > 0 && ($now - $login_time) > $absolute_timeout) {
        destroy_session_and_cookies();
        return false;
    }

    // 2. Validasi Idle Timeout (15 Menit sejak aktivitas terakhir)
    $last_activity = $_SESSION['last_activity'] ?? 0;
    if ($last_activity > 0 && ($now - $last_activity) > $idle_timeout) {
        destroy_session_and_cookies();
        return false;
    }

    // Perpanjang idle timeout HANYA jika bukan background request
    $is_background = !empty($_SERVER['HTTP_X_BACKGROUND_REQUEST']) 
                     || (!empty($_GET['background']) && $_GET['background'] === '1');
    if (!$is_background) {
        $_SESSION['last_activity'] = $now;
    }

    return true;
}

/**
 * Memastikan hanya pengguna yang sudah login yang dapat mengakses halaman.
 * Jika sesi kedaluwarsa atau belum login, diarahkan ke halaman login dengan pesan yang sesuai.
 *
 * @return void
 */
function require_auth(): void
{
    $was_logged_in = !empty($_SESSION['user_id']);
    $last_activity = $_SESSION['last_activity'] ?? 0;
    $login_time = $_SESSION['login_time'] ?? 0;
    $now = time();

    // Deteksi alasan kedaluwarsa sebelum sesi dihancurkan
    $is_idle_expired = ($was_logged_in && $last_activity > 0 && ($now - $last_activity) > 900);
    $is_absolute_expired = ($was_logged_in && $login_time > 0 && ($now - $login_time) > 28800);

    if (!is_authenticated()) {
        // Mulai sesi baru yang bersih untuk menyimpan flash message
        if (session_status() === PHP_SESSION_NONE) {
            $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                         || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $is_secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        if ($is_absolute_expired) {
            set_flash('warning', 'Sesi Anda telah kedaluwarsa setelah 8 jam demi keamanan. Silakan login kembali.');
        } elseif ($is_idle_expired) {
            set_flash('warning', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 15 menit. Silakan login kembali.');
        } else {
            set_flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
        }

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

/**
 * Mendapatkan IP klien secara aman.
 *
 * @return string
 */
function get_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Memeriksa pembatasan laju percobaan login (Rate Limiting).
 * Maksimal 5 kegagalan login dalam jendela waktu 15 menit per Akun atau per IP.
 *
 * @param PDO $pdo
 * @param string $identifier Username atau email
 * @param string $ip Alamat IP klien
 * @return int|null Sisa waktu tunggu (detik) jika dibatasi, null jika diizinkan
 */
function check_login_rate_limit(PDO $pdo, string $identifier, string $ip): ?int
{
    $window_seconds = 900; // 15 menit
    $max_attempts = 5;
    $normalized_ident = strtolower(trim($identifier));
    $cutoff = date('Y-m-d H:i:s', time() - $window_seconds);

    // Hitung percobaan gagal per IP dalam 15 menit terakhir
    $stmtIp = $pdo->prepare('
        SELECT COUNT(*) as total, MAX(attempted_at) as newest 
        FROM login_attempts 
        WHERE ip_address = :ip AND attempted_at > :cutoff
    ');
    $stmtIp->execute([':ip' => $ip, ':cutoff' => $cutoff]);
    $ipData = $stmtIp->fetch();
    $ipCount = (int) ($ipData['total'] ?? 0);

    // Hitung percobaan gagal per Akun (identifier) dalam 15 menit terakhir
    $stmtAcc = $pdo->prepare('
        SELECT COUNT(*) as total, MAX(attempted_at) as newest 
        FROM login_attempts 
        WHERE identifier = :ident AND attempted_at > :cutoff
    ');
    $stmtAcc->execute([':ident' => $normalized_ident, ':cutoff' => $cutoff]);
    $accData = $stmtAcc->fetch();
    $accCount = (int) ($accData['total'] ?? 0);

    if ($ipCount >= $max_attempts || $accCount >= $max_attempts) {
        $lastTimeStr = ($ipCount >= $max_attempts) ? ($ipData['newest'] ?? 'now') : ($accData['newest'] ?? 'now');
        $lastTimestamp = strtotime($lastTimeStr);
        $elapsed = time() - $lastTimestamp;
        $remaining = max(1, $window_seconds - $elapsed);
        return (int) $remaining;
    }

    return null;
}

/**
 * Mencatat kegagalan login ke tabel login_attempts.
 *
 * @param PDO $pdo
 * @param string $identifier Username atau email
 * @param string $ip Alamat IP klien
 * @return void
 */
function record_failed_login(PDO $pdo, string $identifier, string $ip): void
{
    $normalized_ident = strtolower(trim($identifier));
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, identifier, attempted_at) VALUES (:ip, :ident, :attempted_at)');
    $stmt->execute([
        ':ip'           => $ip,
        ':ident'        => $normalized_ident,
        ':attempted_at' => $now,
    ]);

    // Pembersihan probabilistik berkala untuk data usang (> 24 jam)
    if (mt_rand(1, 50) === 1) {
        $cutoff24 = date('Y-m-d H:i:s', time() - 86400);
        $stmtClean = $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < :cutoff');
        $stmtClean->execute([':cutoff' => $cutoff24]);
    }
}

/**
 * Menghapus catatan percobaan gagal setelah login berhasil.
 *
 * @param PDO $pdo
 * @param string $identifier Username atau email
 * @param string $ip Alamat IP klien
 * @return void
 */
function clear_login_attempts(PDO $pdo, string $identifier, string $ip): void
{
    $normalized_ident = strtolower(trim($identifier));
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE identifier = :ident OR ip_address = :ip');
    $stmt->execute([
        ':ident' => $normalized_ident,
        ':ip'    => $ip,
    ]);
}

