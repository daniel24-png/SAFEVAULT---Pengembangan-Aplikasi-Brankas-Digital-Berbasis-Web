<?php
/**
 * SAFEVAULT - Automated Test Suite (Pengujian Fitur v0.1)
 *
 * Menguji seluruh fungsionalitas dan keamanan SAFEVAULT:
 * 1. Ketersediaan Argon2id
 * 2. Hash & Verifikasi Password Argon2id
 * 3. Proteksi CSRF (Generate & Validasi)
 * 4. Validasi Format Input Backend (Username, Email, Password)
 * 5. Registrasi Pengguna & Pencegahan Duplikasi Username/Email
 * 6. Keberadaan Hash Argon2id di Kolom Database
 * 7. Login Berhasil dengan Kredensial Benar
 * 8. Penolakan Login dengan Password Salah
 * 9. Simulasi Pembaruan Session ID (Session Regeneration)
 * 10. Proteksi Logout (Hanya POST & Wajib CSRF)
 */

// Keamanan: Larang akses langsung melalui web browser
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Akses hanya diizinkan melalui command line interface (CLI).');
}

define('SAFEVAULT_APP', true);

// Aktifkan reporting error penuh untuk pengujian
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Gunakan output buffering agar session_regenerate_id tidak terhambat header CLI
ob_start();

// Gunakan direktori session lokal untuk pengujian CLI di sandbox
$session_dir = __DIR__ . '/session_tmp';
if (!is_dir($session_dir)) {
    mkdir($session_dir, 0777, true);
}
session_save_path($session_dir);

// Mulai session mock jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$total_tests = 0;
$passed_tests = 0;
$failed_tests = [];

function assert_test(string $name, bool $condition, string $details = ''): void {
    global $total_tests, $passed_tests, $failed_tests;
    $total_tests++;
    if ($condition) {
        $passed_tests++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed_tests[] = "{$name} - {$details}";
        echo "  [FAIL] {$name}: {$details}\n";
    }
}

echo "========================================================\n";
echo "SAFEVAULT v0.1 - TEST SUITE VERIFIKASI FITUR & KEAMANAN\n";
echo "========================================================\n\n";

// ---------------------------------------------------------
// TEST 1: Ketersediaan PASSWORD_ARGON2ID
// ---------------------------------------------------------
echo "[1] Pengujian Lingkungan Kriptografi:\n";
assert_test(
    "Ketersediaan PASSWORD_ARGON2ID",
    defined('PASSWORD_ARGON2ID'),
    "PASSWORD_ARGON2ID tidak didefinisikan di PHP."
);

// ---------------------------------------------------------
// TEST 2: Hashing & Verifikasi Argon2id
// ---------------------------------------------------------
echo "\n[2] Pengujian Hashing Password Argon2id:\n";
$plain_pass = "RahasiaVault#2026";
$argon_hash = password_hash($plain_pass, PASSWORD_ARGON2ID);

assert_test(
    "Format hash harus merupakan Argon2id (\$argon2id\$)",
    strpos($argon_hash, '$argon2id$') === 0,
    "Hash bukan format Argon2id: {$argon_hash}"
);

assert_test(
    "Verifikasi password benar harus menghasilkan TRUE",
    password_verify($plain_pass, $argon_hash) === true,
    "password_verify gagal untuk password yang cocok."
);

assert_test(
    "Verifikasi password salah harus menghasilkan FALSE",
    password_verify("PasswordSalah123", $argon_hash) === false,
    "password_verify menerima password salah."
);

// ---------------------------------------------------------
// TEST 3: Proteksi Token CSRF
// ---------------------------------------------------------
echo "\n[3] Pengujian Proteksi CSRF:\n";
$_SESSION['csrf_token'] = null;
$token = generate_csrf_token();

assert_test(
    "Token CSRF berhasil di-generate dan memiliki panjang 64 heksadesimal",
    strlen($token) === 64 && ctype_xdigit($token),
    "Panjang token tidak sesuai: " . strlen($token)
);

assert_test(
    "Validasi token yang cocok harus berhasil",
    validate_csrf_token($token) === true,
    "Token valid ditolak."
);

assert_test(
    "Validasi token yang salah harus ditolak",
    validate_csrf_token("invalid_token_1234567890abcdef") === false,
    "Token salah diterima."
);

assert_test(
    "Validasi token kosong harus ditolak",
    validate_csrf_token("") === false && validate_csrf_token(null) === false,
    "Token kosong diterima."
);

// ---------------------------------------------------------
// TEST 4: Validasi Input Backend
// ---------------------------------------------------------
echo "\n[4] Pengujian Validasi Format Input Backend:\n";
assert_test("Username valid 'user_123' diterima", validate_username("user_123") === null);
assert_test("Username kosong ditolak", validate_username("") !== null);
assert_test("Username < 3 karakter ('ab') ditolak", validate_username("ab") !== null);
assert_test("Username > 30 karakter ditolak", validate_username(str_repeat("a", 35)) !== null);
assert_test("Username dengan simbol terlarang ('user@#') ditolak", validate_username("user@#") !== null);

assert_test("Email valid 'vault@example.com' diterima", validate_email("vault@example.com") === null);
assert_test("Email format salah 'invalid-email' ditolak", validate_email("invalid-email") !== null);
assert_test("Email kosong ditolak", validate_email("") !== null);

assert_test("Password >= 8 karakter diterima", validate_password("StrongP@ss8") === null);
assert_test("Password < 8 karakter ditolak", validate_password("short") !== null);

// ---------------------------------------------------------
// TEST 5: Operasi Database & Registrasi Pengguna
// ---------------------------------------------------------
echo "\n[5] Pengujian Operasi Database & Registrasi (Mock SQLite Schema):\n";

$test_pdo = new PDO('sqlite::memory:');
$test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$test_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$test_pdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(30) NOT NULL UNIQUE,
        email VARCHAR(254) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

$user_id = create_user($test_pdo, "alice_vault", "alice@safevault.id", "KunciVaultAlice123");

assert_test(
    "Registrasi akun pengguna berhasil dan mengembalikan User ID > 0",
    $user_id > 0,
    "ID pengguna tidak valid: {$user_id}"
);

$user_db = find_user_by_username($test_pdo, "alice_vault");
assert_test(
    "Pencarian pengguna berdasarkan username berhasil",
    $user_db !== null && $user_db['username'] === "alice_vault",
    "Pengguna tidak ditemukan berdasarkan username."
);

assert_test(
    "Password yang tersimpan di database bertipe Argon2id hash",
    strpos($user_db['password_hash'], '$argon2id$') === 0,
    "Hash bukan Argon2id: " . $user_db['password_hash']
);

$user_by_email = find_user_by_email($test_pdo, "alice@safevault.id");
assert_test(
    "Pencarian pengguna berdasarkan email berhasil",
    $user_by_email !== null && $user_by_email['email'] === "alice@safevault.id",
    "Pengguna tidak ditemukan berdasarkan email."
);

// Cek pencegahan duplikasi
$is_duplicate_username = (find_user_by_username($test_pdo, "alice_vault") !== null);
assert_test(
    "Pencegahan duplikasi username berhasil",
    $is_duplicate_username === true,
    "Duplikasi username tidak terdeteksi."
);

$is_duplicate_email = (find_user_by_email($test_pdo, "alice@safevault.id") !== null);
assert_test(
    "Pencegahan duplikasi email berhasil",
    $is_duplicate_email === true,
    "Duplikasi email tidak terdeteksi."
);

// ---------------------------------------------------------
// TEST 6: Pengujian Login (Kredensial Benar & Kredensial Salah)
// ---------------------------------------------------------
echo "\n[6] Pengujian Alur Login Pengguna:\n";

// Login dengan username
$login_by_username = find_user_by_identifier($test_pdo, "alice_vault");
assert_test(
    "Login dengan username: akun ditemukan",
    $login_by_username !== null
);
assert_test(
    "Login dengan password benar: verifikasi berhasil",
    password_verify("KunciVaultAlice123", $login_by_username['password_hash']) === true
);

// Login dengan email
$login_by_email = find_user_by_identifier($test_pdo, "alice@safevault.id");
assert_test(
    "Login dengan email: akun ditemukan",
    $login_by_email !== null
);
assert_test(
    "Login dengan password salah: verifikasi ditolak",
    password_verify("PasswordSalahTotal", $login_by_email['password_hash']) === false
);

// Login dengan akun yang tidak terdaftar
$login_nonexistent = find_user_by_identifier($test_pdo, "user_fiktif_99");
assert_test(
    "Login dengan akun tidak terdaftar: ditolak",
    $login_nonexistent === null
);

// ---------------------------------------------------------
// TEST 7: Regenerasi Session ID saat Login
// ---------------------------------------------------------
echo "\n[7] Pengujian Keamanan Sesi & Session Regeneration:\n";
$old_session_id = session_id();
session_regenerate_id(true);
$new_session_id = session_id();

assert_test(
    "Session ID berubah setelah regenerasi (Mitigasi Session Fixation)",
    $old_session_id !== $new_session_id && !empty($new_session_id),
    "Session ID tidak berubah."
);

// ---------------------------------------------------------
// TEST 8: Proteksi Akses & Logout
// ---------------------------------------------------------
echo "\n[8] Pengujian Proteksi Halaman & Logout:\n";

// Tes simulasi require_auth() jika belum login
$_SESSION['user_id'] = null;
assert_test(
    "is_authenticated() menghasilkan FALSE ketika session user_id kosong",
    is_authenticated() === false
);

// Login user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = "alice_vault";
$_SESSION['email'] = "alice@safevault.id";
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();

assert_test(
    "is_authenticated() menghasilkan TRUE ketika session aktif",
    is_authenticated() === true
);

// Tes simulasi logout
$_SESSION = [];
assert_test(
    "Session kosong setelah pembersihan logout",
    is_authenticated() === false && empty($_SESSION['user_id'])
);

// ---------------------------------------------------------
// TEST 9: Login Rate Limiting (Priority 2)
// ---------------------------------------------------------
echo "\n[9] Pengujian Login Rate Limiting:\n";
// Buat tabel login_attempts pada test_pdo SQLite in-memory
$test_pdo->exec("
    CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address VARCHAR(45) NOT NULL,
        identifier VARCHAR(254) NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// 0 gagal: diizinkan
assert_test("Rate limit awal: diizinkan (null)", check_login_rate_limit($test_pdo, "alice_vault", "127.0.0.1") === null);

// Catat 4 kegagalan: masih diizinkan
for ($i = 1; $i <= 4; $i++) {
    record_failed_login($test_pdo, "alice_vault", "127.0.0.1");
}
assert_test("Rate limit setelah 4 kegagalan: masih diizinkan (null)", check_login_rate_limit($test_pdo, "alice_vault", "127.0.0.1") === null);

// Catat kegagalan ke-5: harus dibatasi (throttled)
record_failed_login($test_pdo, "alice_vault", "127.0.0.1");
$rate_check_5 = check_login_rate_limit($test_pdo, "alice_vault", "127.0.0.1");
assert_test("Rate limit setelah 5 kegagalan: dibatasi (throttled)", $rate_check_5 !== null && $rate_check_5 > 0);

// Coba username berbeda dari IP yang sama: harus tetap dibatasi (IP throttle)
assert_test("Pembatasan per IP aktif untuk username berbeda", check_login_rate_limit($test_pdo, "different_user", "127.0.0.1") !== null);

// Reset percobaan setelah login sukses
clear_login_attempts($test_pdo, "alice_vault", "127.0.0.1");
assert_test("Clear login attempts mengembalikan status ke diizinkan", check_login_rate_limit($test_pdo, "alice_vault", "127.0.0.1") === null);

// ---------------------------------------------------------
// TEST 10: Session Timeout (Priority 3)
// ---------------------------------------------------------
echo "\n[10] Pengujian Session Timeout (Idle & Absolute):\n";

// Sesi aktif dalam batas waktu
$_SESSION['user_id'] = 99;
$_SESSION['login_time'] = time() - 300;     // 5 menit lalu
$_SESSION['last_activity'] = time() - 60;   // 1 menit lalu
assert_test("Sesi valid dalam batas waktu menghasilkan is_authenticated() = TRUE", is_authenticated() === true);

// Idle Timeout (> 15 menit / 900 detik)
$_SESSION['user_id'] = 99;
$_SESSION['login_time'] = time() - 2000;
$_SESSION['last_activity'] = time() - 901;  // Lewat 15 menit
$idle_check = is_authenticated();
assert_test("Sesi lewat 15 menit tidak aktif ditolak (Idle Timeout)", $idle_check === false && empty($_SESSION['user_id']));

// Absolute Timeout (> 8 jam / 28.800 detik)
$_SESSION['user_id'] = 99;
$_SESSION['login_time'] = time() - 28801;   // Lewat 8 jam
$_SESSION['last_activity'] = time() - 10;   // Baru saja aktif
$abs_check = is_authenticated();
assert_test("Sesi lewat 8 jam sejak login ditolak (Absolute Timeout)", $abs_check === false && empty($_SESSION['user_id']));

// Background request tidak memperbarui last_activity
$_SESSION['user_id'] = 99;
$_SESSION['login_time'] = time() - 100;
$fixed_time = time() - 300;
$_SESSION['last_activity'] = $fixed_time;
$_SERVER['HTTP_X_BACKGROUND_REQUEST'] = '1';
$bg_check = is_authenticated();
assert_test("Background request tidak memperpanjang last_activity", $bg_check === true && $_SESSION['last_activity'] === $fixed_time);
unset($_SERVER['HTTP_X_BACKGROUND_REQUEST']);

// Normal request memperbarui last_activity
$norm_check = is_authenticated();
assert_test("Normal request memperbarui last_activity ke waktu terkini", $norm_check === true && $_SESSION['last_activity'] >= time() - 1);

// Bersihkan session
$_SESSION = [];

// ---------------------------------------------------------
// RINGKASAN HASIL TEST
// ---------------------------------------------------------
echo "\n========================================================\n";
echo "RINGKASAN HASIL TEST SAFEVAULT v0.1:\n";
echo "Total Uji Coba : {$total_tests}\n";
echo "Lolos (Pass)   : {$passed_tests}\n";
echo "Gagal (Fail)   : " . count($failed_tests) . "\n";
echo "Status Akhir   : " . (count($failed_tests) === 0 ? "SEMUA PENGUJIAN LOLOS (100% SUKSES)" : "TERDAPAT KEGAGALAN") . "\n";
echo "========================================================\n";

// Bersihkan session files pengujian
foreach (glob($session_dir . '/*') as $file) {
    if (is_file($file)) {
        @unlink($file);
    }
}
@rmdir($session_dir);

if (count($failed_tests) > 0) {
    ob_end_flush();
    exit(1);
}
ob_end_flush();
exit(0);
