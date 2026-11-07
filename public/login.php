<?php
/**
 * SAFEVAULT - Halaman Masuk (Login)
 * Versi 0.1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Pengguna yang sudah login diarahkan ke dashboard
require_guest();

$errors = [];
$identifier_val = '';
$flashes = get_flashes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Permintaan tidak valid atau sesi CSRF telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $identifier_val = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier_val === '' || $password === '') {
            $errors[] = 'Silakan isi username/email dan password.';
        } else {
            try {
                $pdo = get_db();
                $client_ip = get_client_ip();

                // 2. Pemeriksaan Rate Limiting (Maks 5 gagal dalam 15 menit)
                $throttle_seconds = check_login_rate_limit($pdo, $identifier_val, $client_ip);
                if ($throttle_seconds !== null) {
                    $minutes = (int) ceil($throttle_seconds / 60);
                    $errors[] = "Terlalu banyak percobaan login yang gagal. Akses dibatasi sementara demi keamanan. Silakan coba kembali dalam {$minutes} menit.";
                } else {
                    // Cari pengguna berdasarkan username ATAU email dengan Prepared Statement
                    $user = find_user_by_identifier($pdo, $identifier_val);

                    // Verifikasi keberadaan pengguna dan kecocokan password_hash
                    if ($user && password_verify($password, $user['password_hash'])) {
                        // Bersihkan riwayat percobaan gagal setelah login berhasil
                        clear_login_attempts($pdo, $identifier_val, $client_ip);

                        // KEAMANAN WAJIB: Regenerasi ID Sesi untuk mencegah Session Fixation
                        session_regenerate_id(true);

                        // Simpan data identitas pengguna ke dalam sesi
                        $_SESSION['user_id']       = (int) $user['id'];
                        $_SESSION['username']      = (string) $user['username'];
                        $_SESSION['email']         = (string) $user['email'];
                        $_SESSION['login_time']    = time(); // Untuk Absolute Session Timeout
                        $_SESSION['last_activity'] = time(); // Untuk Idle Session Timeout

                        // Buat token CSRF baru untuk sesi yang baru
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        set_flash('success', 'Selamat datang kembali, ' . $user['username'] . '!');
                        redirect('dashboard.php');
                    } else {
                        // Catat percobaan login yang gagal untuk rate limiting
                        record_failed_login($pdo, $identifier_val, $client_ip);

                        // Pesan kesalahan umum yang aman (mencegah enumerasi akun)
                        $errors[] = 'Username/email atau password yang Anda masukkan salah.';
                    }
                }
            } catch (Throwable $e) {
                error_log('[SAFEVAULT Login Error] ' . $e->getMessage());
                $errors[] = 'Terjadi kendala pada server saat memproses login. Silakan coba beberapa saat lagi.';
            }
        }
    }
}

$page_title = 'Masuk ke SAFEVAULT';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="brand">
                <span class="brand-icon">🛡️</span>
                <span>SAFEVAULT</span>
            </a>
            <nav class="nav-links">
                <a href="index.php" class="nav-link">Beranda</a>
                <a href="register.php" class="btn btn-outline">Daftar Akun</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="auth-wrapper">

                <!-- Flash Messages (misal: pesan sukses setelah registrasi) -->
                <?php foreach ($flashes as $flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>">
                        <span><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
                        <div><?= e($flash['message']) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="auth-card">
                    <div class="auth-header">
                        <h1>Masuk ke Akun</h1>
                        <p>Akses brankas digital pribadi Anda dengan aman.</p>
                    </div>

                    <!-- Tampilkan Pesan Error Login -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <span>⚠️</span>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <div><?= e($error) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" novalidate>
                        <?= csrf_input() ?>

                        <div class="form-group">
                            <label for="identifier" class="form-label">Username atau Email</label>
                            <input 
                                type="text" 
                                id="identifier" 
                                name="identifier" 
                                class="form-input" 
                                value="<?= e($identifier_val) ?>" 
                                required 
                                autocomplete="username"
                                placeholder="Masukkan username atau email"
                            >
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                required 
                                autocomplete="current-password"
                                placeholder="••••••••"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Masuk ke Vault</button>
                    </form>

                    <div class="auth-footer">
                        Belum memiliki akun? <a href="register.php">Daftar sekarang</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SAFEVAULT. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

</body>
</html>

