<?php
/**
 * SAFEVAULT - Halaman Utama (Homepage)
 * Versi 0.1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$page_title = 'SAFEVAULT - Brankas Digital Pribadi';
$user = get_logged_in_user();
$flashes = get_flashes();
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
                <?php if ($user): ?>
                    <a href="dashboard.php" class="nav-link">Hai, <?= e($user['username']) ?></a>
                    <a href="dashboard.php" class="btn btn-primary">Buka Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Masuk</a>
                    <a href="register.php" class="btn btn-primary">Daftar Akun</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">

            <!-- Flash Messages -->
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <span><?= $flash['type'] === 'success' ? '✅' : '⚠️' ?></span>
                    <div><?= e($flash['message']) ?></div>
                </div>
            <?php endforeach; ?>

            <!-- Hero Section -->
            <section class="hero">
                <span class="hero-tag">Aplikasi Brankas Digital Pribadi Berbasis Web</span>
                <h1 class="hero-title">Keamanan Maksimal untuk Privasi Digital Anda</h1>
                <p class="hero-desc">
                    SAFEVAULT dirancang untuk melindungi rahasia dan berkas sensitif Anda dengan fondasi keamanan modern: hashing Argon2id, perlindungan session ketat, dan pertahanan CSRF penuh.
                </p>
                <div class="hero-actions">
                    <?php if ($user): ?>
                        <a href="dashboard.php" class="btn btn-primary">Masuk ke Dashboard Anda</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Buat Akun Gratis</a>
                        <a href="login.php" class="btn btn-outline">Sudah Punya Akun? Masuk</a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Fitur Utama -->
            <section class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h2 class="feature-title">Argon2id Hashing</h2>
                    <p class="feature-desc">
                        Kata sandi diamankan menggunakan standar industri terkini (Argon2id), tahan terhadap serangan brute-force dan ASIC/GPU modern.
                    </p>
                    <span class="badge badge-active">Aktif di v0.1</span>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h2 class="feature-title">Perlindungan Sesi Ketat</h2>
                    <p class="feature-desc">
                        Cookie sesi dilindungi dengan flag HttpOnly dan SameSite=Lax, serta pembaruan ID sesi otomatis saat login untuk mencegah session fixation.
                    </p>
                    <span class="badge badge-active">Aktif di v0.1</span>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h2 class="feature-title">Anti-CSRF & Prepared Statements</h2>
                    <p class="feature-desc">
                        Seluruh formulir divalidasi dengan token kriptografis anti-CSRF, dan interaksi basis data menggunakan PDO Prepared Statements bebas SQL Injection.
                    </p>
                    <span class="badge badge-active">Aktif di v0.1</span>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SAFEVAULT. Dibangun dengan PHP Native & MySQL via XAMPP.</p>
        </div>
    </footer>

</body>
</html>
