<?php
/**
 * SAFEVAULT - Halaman Registrasi Pengguna
 * Versi 0.1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Pengguna yang sudah login tidak boleh mengakses halaman registrasi
require_guest();

$errors = [];
$username_val = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $errors[] = 'Permintaan tidak valid atau token CSRF telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        // Ambil dan bersihkan input pengguna
        $username_val = trim($_POST['username'] ?? '');
        $email_val = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // 2. Validasi Backend
        $err_username = validate_username($username_val);
        if ($err_username) {
            $errors[] = $err_username;
        }

        $err_email = validate_email($email_val);
        if ($err_email) {
            $errors[] = $err_email;
        }

        $err_pass = validate_password($password);
        if ($err_pass) {
            $errors[] = $err_pass;
        }

        if ($password !== $password_confirm) {
            $errors[] = 'Konfirmasi password tidak cocok dengan password yang dimasukkan.';
        }

        // 3. Jika validasi format lolos, periksa duplikasi di database
        if (empty($errors)) {
            try {
                $pdo = get_db();

                // Cek keunikan username
                if (find_user_by_username($pdo, $username_val)) {
                    $errors[] = 'Username sudah digunakan oleh akun lain. Silakan pilih username berbeda.';
                }
                // Cek keunikan email
                elseif (find_user_by_email($pdo, $email_val)) {
                    $errors[] = 'Email sudah terdaftar pada sistem. Silakan gunakan email lain atau login.';
                } else {
                    // Simpan pengguna baru dengan Argon2id hash
                    create_user($pdo, $username_val, $email_val, $password);

                    set_flash('success', 'Registrasi berhasil! Akun Anda telah siap. Silakan masuk.');
                    redirect('login.php');
                }
            } catch (Throwable $e) {
                error_log('[SAFEVAULT Register Error] ' . $e->getMessage());

                // Tangani pelanggaran UNIQUE constraint di level basis data (Defense in Depth)
                if ($e instanceof PDOException && ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false)) {
                    if (strpos($e->getMessage(), 'uk_users_username') !== false || stripos($e->getMessage(), 'username') !== false) {
                        $errors[] = 'Username sudah digunakan oleh akun lain. Silakan pilih username berbeda.';
                    } elseif (strpos($e->getMessage(), 'uk_users_email') !== false || stripos($e->getMessage(), 'email') !== false) {
                        $errors[] = 'Email sudah terdaftar pada sistem. Silakan gunakan email lain atau login.';
                    } else {
                        $errors[] = 'Username atau email yang Anda masukkan sudah terdaftar.';
                    }
                } else {
                    $errors[] = 'Terjadi kendala pada server saat memproses registrasi. Silakan coba beberapa saat lagi.';
                }
            }
        }
    }
}

$page_title = 'Daftar Akun Baru - SAFEVAULT';
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
                <a href="login.php" class="btn btn-outline">Masuk</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="auth-wrapper">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1>Buat Akun SAFEVAULT</h1>
                        <p>Daftarkan akun untuk mulai mengamankan data pribadi Anda.</p>
                    </div>

                    <!-- Tampilkan Pesan Error Validasi -->
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

                    <form action="register.php" method="POST" novalidate>
                        <?= csrf_input() ?>

                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                value="<?= e($username_val) ?>" 
                                maxlength="30"
                                required 
                                autocomplete="username"
                                placeholder="Contoh: user_vault123"
                            >
                            <small class="form-hint">3–30 karakter, hanya huruf, angka, dan underscore (_).</small>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                value="<?= e($email_val) ?>" 
                                maxlength="254"
                                required 
                                autocomplete="email"
                                placeholder="nama@domain.com"
                            >
                            <small class="form-hint">Gunakan alamat email aktif Anda.</small>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                required 
                                autocomplete="new-password"
                                placeholder="••••••••"
                            >
                            <small class="form-hint">Minimal 8 karakter.</small>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm" class="form-label">Konfirmasi Password</label>
                            <input 
                                type="password" 
                                id="password_confirm" 
                                name="password_confirm" 
                                class="form-input" 
                                required 
                                autocomplete="new-password"
                                placeholder="••••••••"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang</button>
                    </form>

                    <div class="auth-footer">
                        Sudah memiliki akun? <a href="login.php">Masuk di sini</a>
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

