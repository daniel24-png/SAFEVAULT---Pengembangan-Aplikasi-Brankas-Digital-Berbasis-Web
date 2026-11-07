<?php
/**
 * SAFEVAULT - Halaman Dashboard Pribadi
 * Versi 0.1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Halaman dashboard hanya dapat diakses setelah login
require_auth();

$user = get_logged_in_user();
$flashes = get_flashes();

// Ambil data lengkap pengguna dari database untuk menampilkan profil
$user_data = null;
try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, username, email, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $user['id']]);
    $user_data = $stmt->fetch();
} catch (Throwable $e) {
    error_log('[SAFEVAULT Dashboard Error] ' . $e->getMessage());
}

$page_title = 'Dashboard Pribadi - SAFEVAULT';
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
            <a href="dashboard.php" class="brand">
                <span class="brand-icon">🛡️</span>
                <span>SAFEVAULT</span>
            </a>
            <nav class="nav-links">
                <span class="nav-link">Hai, <strong><?= e($user['username']) ?></strong></span>
                
                <!-- Logout Form (Metode POST + CSRF) -->
                <form action="logout.php" method="POST" style="display: inline-block;">
                    <?= csrf_input() ?>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin keluar?')">Keluar (Logout)</button>
                </form>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">

            <!-- Flash Messages -->
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <span><?= $flash['type'] === 'success' ? '✅' : 'ℹ️' ?></span>
                    <div><?= e($flash['message']) ?></div>
                </div>
            <?php endforeach; ?>

            <!-- Dashboard Welcome Banner -->
            <section class="dashboard-header">
                <div class="dashboard-user">
                    <h1>Selamat Datang di Brankas Pribadi, <?= e($user['username']) ?>!</h1>
                    <p>Sesi Anda terlindungi dengan standar keamanan Argon2id dan cookie HttpOnly.</p>
                </div>
                <div>
                    <form action="logout.php" method="POST">
                        <?= csrf_input() ?>
                        <button type="submit" class="btn btn-outline">Logout Sesi</button>
                    </form>
                </div>
            </section>

            <!-- Informasi Pengguna -->
            <section class="info-section">
                <h2 class="card-title">👤 Informasi Akun Anda</h2>
                <table class="info-table">
                    <tbody>
                        <tr>
                            <th>User ID</th>
                            <td>#<?= e($user['id']) ?></td>
                        </tr>
                        <tr>
                            <th>Username</th>
                            <td><?= e($user['username']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= e($user['email']) ?></td>
                        </tr>
                        <tr>
                            <th>Waktu Pendaftaran</th>
                            <td><?= e($user_data['created_at'] ?? 'Baru saja') ?></td>
                        </tr>
                        <tr>
                            <th>Status Akun</th>
                            <td><span class="badge badge-active">Terverifikasi & Aktif</span></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Empat Kartu Fitur (Status: Belum Tersedia) -->
            <section>
                <div style="margin-bottom: 1.5rem;">
                    <h2 class="hero-title" style="font-size: 1.75rem; text-align: left; margin-bottom: 0.25rem;">Modul Brankas Digital</h2>
                    <p style="color: var(--text-secondary);">Fitur perlindungan data tingkat lanjut yang sedang disiapkan untuk rilis berikutnya:</p>
                </div>

                <div class="feature-grid">
                    <!-- Fitur 1: Secret Notes -->
                    <div class="feature-card disabled">
                        <div class="feature-icon">📝</div>
                        <h3 class="feature-title">Secret Notes</h3>
                        <p class="feature-desc">
                            Penyimpanan catatan pribadi terenkripsi end-to-end dengan enkripsi simetris (AES-256-GCM).
                        </p>
                        <span class="badge badge-pending">Belum Tersedia</span>
                    </div>

                    <!-- Fitur 2: Secure File Vault -->
                    <div class="feature-card disabled">
                        <div class="feature-icon">🗄️</div>
                        <h3 class="feature-title">Secure File Vault</h3>
                        <p class="feature-desc">
                            Brankas penyimpanan dokumen dan berkas penting dengan enkripsi data-at-rest.
                        </p>
                        <span class="badge badge-pending">Belum Tersedia</span>
                    </div>

                    <!-- Fitur 3: Image Steganography -->
                    <div class="feature-card disabled">
                        <div class="feature-icon">🖼️</div>
                        <h3 class="feature-title">Image Steganography</h3>
                        <p class="feature-desc">
                            Menyembunyikan pesan rahasia di balik berkas gambar tanpa mengubah tampilan kasat mata.
                        </p>
                        <span class="badge badge-pending">Belum Tersedia</span>
                    </div>

                    <!-- Fitur 4: Blockchain -->
                    <div class="feature-card disabled">
                        <div class="feature-icon">⛓️</div>
                        <h3 class="feature-title">Blockchain</h3>
                        <p class="feature-desc">
                            Buku besar desentralisasi untuk verifikasi integritas data dan riwayat audit anti-manipulasi.
                        </p>
                        <span class="badge badge-pending">Belum Tersedia</span>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> SAFEVAULT. Versi 0.1 - Dibangun di atas XAMPP & PHP Native.</p>
        </div>
    </footer>

</body>
</html>

