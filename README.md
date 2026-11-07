# SAFEVAULT - Brankas Digital Pribadi (Versi 0.1)

SAFEVAULT adalah aplikasi web brankas digital pribadi yang dibangun menggunakan **PHP Native** dan **MySQL** yang berjalan di lingkungan web server **XAMPP**. Aplikasi ini dirancang untuk menyediakan tempat penyimpanan dan pengelolaan informasi sensitif secara terstruktur, aman, dan mudah dipelihara.

---

## 🔒 Standar Keamanan & Proteksi (v0.1 Hardened)

1. **Perlindungan Direktori & File Non-Public (Defense-in-Depth)**:
   - *Directory Listing* dinonaktifkan secara ketat menggunakan `Options -Indexes`.
   - Permintaan ke root URL diarahkan secara otomatis ke direktori `public/`.
   - Blokir akses web terhadap direktori internal (`app/`, `config/`, `database/`, `includes/`, `tests/`) menggunakan aturan Apache `.htaccess` (`Require all denied`).
   - Blokir akses langsung ke berkas sensitif (`.env`, `.sql`, `.log`, `.pem`, `.key`, `.md`, `database.local.php`).
   - Setiap skrip internal dan pengujian dilindungi *guard* PHP (`SAFEVAULT_APP` dan `php_sapi_name() === 'cli'`).

2. **Login Rate Limiting & Proteksi Brute-Force**:
   - Pembatasan maksimal **5 kegagalan login berturut-turut dalam jendela waktu 15 menit**.
   - Diterapkan secara ganda: **per IP address** dan **per akun (username/email)**.
   - Throttling bersifat sementara (*temporary lockout*) berbasis hitungan waktu mundur, tanpa mengunci akun secara permanen.
   - Menggunakan tabel persisten `login_attempts` di database MySQL/MariaDB.
   - Reset otomatis saat login berhasil dan pesan error yang seragam untuk mencegah *account enumeration*.

3. **Manajemen Siklus Sesi (Session Timeout)**:
   - **Idle Timeout (15 Menit)**: Sesi otomatis berakhir jika tidak ada aktivitas pengguna selama 15 menit.
   - **Absolute Timeout (8 Jam)**: Sesi otomatis berakhir setelah 8 jam sejak waktu awal login.
   - Pembersihan sesi total (penghapusan array `$_SESSION`, penghapusan cookie dengan waktu kedaluwarsa lampau, dan `session_destroy()`).
   - Pengalihan otomatis ke halaman login dengan pesan notifikasi kedaluwarsa sesi.
   - Permintaan di latar belakang (*background request*) tidak memperpanjang *idle timeout*.

4. **Keamanan Cookie Session**:
   - Cookie dikonfigurasi dengan flag `HttpOnly` (mencegah pencurian cookie via XSS) dan `SameSite=Lax` (mencegah CSRF).
   - Pengaturan `session.use_strict_mode = 1` dan `session.use_only_cookies = 1`.
   - Flag `Secure` aktif secara otomatis jika berjalan di lingkungan HTTPS, dan tetap kompatibel di lingkungan localhost HTTP.

5. **Proteksi Session Fixation**:
   - Memanggil `session_regenerate_id(true)` secara tepat setelah otentikasi kredensial berhasil untuk menerbitkan ID sesi baru.

6. **Proteksi CSRF (Cross-Site Request Forgery)**:
   - Token kriptografis 64-karakter heksadesimal (`bin2hex(random_bytes(32))`) divalidasi pada seluruh form yang mengubah state (Registrasi, Login, dan Logout).
   - Validasi menggunakan `hash_equals()` untuk mencegah *timing attack*.

7. **Koneksi Database PDO & Prepared Statements**:
   - Seluruh interaksi basis data menggunakan `PDO` dengan mode `PDO::ERRMODE_EXCEPTION` dan `PDO::ATTR_EMULATE_PREPARES => false`.
   - Tidak ada penggabungan string (*string concatenation*) pada query SQL untuk mencegah SQL Injection.

8. **Password Hashing Modern (Argon2id)**:
   - Menggunakan `password_hash($password, PASSWORD_ARGON2ID)` sesuai standar kriptografi terkini.
   - Tidak menyimpan password plaintext atau membuat kolom salt manual.
   - Dilengkapi pemeriksaan lingkungan runtime Argon2id.

9. **HTTP Security Headers Lengkap**:
   - `X-Frame-Options: DENY` (perlindungan Clickjacking).
   - `X-Content-Type-Options: nosniff` (perlindungan MIME-sniffing).
   - `Referrer-Policy: strict-origin-when-cross-origin`.
   - `Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none';` (perlindungan injeksi skrip, clickjacking, dan pembatasan resource ke origin terpercaya).
   - `Permissions-Policy: camera=(self), microphone=(), geolocation=()` (izin kamera untuk origin mandiri demi kesiapan Face Recognition v0.2, mikrofon & lokasi dinonaktifkan).

---

## 📁 Struktur Direktori & Berkas

```text
safevault/
│
├── .htaccess                    # Proteksi root: matikan Indexes, blokir berkas sensitif, redirect ke public/
├── .gitignore                   # Menjaga kredensial database lokal tidak terunggah ke Git
├── README.md                    # Dokumentasi lengkap proyek
│
├── app/
│   ├── .htaccess                # Blokir akses HTTP langsung ke direktori app (Require all denied)
│   ├── controllers/             # Tempat logika controller masa depan
│   ├── models/                  # Tempat model data masa depan
│   ├── services/                # Logika bisnis tambahan (enkripsi, dekripsi, dll.)
│   └── views/                   # Template antarmuka tambahan
│
├── config/
│   ├── .htaccess                # Blokir akses HTTP langsung ke direktori config (Require all denied)
│   ├── database.example.php     # Template konfigurasi database aman untuk Git
│   └── database.local.php       # Konfigurasi aktif lokal (diabaikan oleh Git via .gitignore)
│
├── database/
│   ├── .htaccess                # Blokir akses HTTP langsung ke direktori database (Require all denied)
│   └── schema.sql               # Skrip DDL untuk database safevault_db, tabel users & login_attempts
│
├── includes/
│   ├── .htaccess                # Blokir akses HTTP langsung ke direktori includes (Require all denied)
│   ├── bootstrap.php            # Inisialisasi session aman, security headers, singleton koneksi PDO
│   ├── csrf.php                 # Modul token CSRF (generate, render input, validasi)
│   └── functions.php            # Helper sanitasi, otentikasi, rate limiting, dan query pengguna
│
├── public/                      # Document Root yang dapat diakses publik melalui web server
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css        # Stylesheet antarmuka responsif bertema dark/slate
│   │   ├── images/              # Folder aset gambar & ikon
│   │   └── js/                  # Folder script interaktivitas JavaScript
│   ├── index.php                # Halaman pembuka (Homepage) SAFEVAULT
│   ├── register.php             # Halaman & pemrosesan registrasi pengguna
│   ├── login.php                # Halaman & pemrosesan masuk dengan proteksi rate limiting
│   ├── dashboard.php            # Halaman beranda pribadi pengguna (terproteksi auth & timeout)
│   └── logout.php               # Handler keluar sesi pengguna (hanya menerima POST + CSRF)
│
└── tests/
    ├── .htaccess                # Blokir akses HTTP langsung ke direktori tests (Require all denied)
    ├── audit_db_real.php        # Skrip audit integrasi langsung MariaDB (CLI only)
    └── test_safevault.php       # Skrip pengujian otomatis (43 test cases, 100% lolos, CLI only)
```

---

## ⚙️ Panduan Menjalankan Proyek di Localhost (XAMPP)

### Langkah 1: Nyalakan XAMPP
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache**.
3. Klik tombol **Start** pada modul **MySQL**.
4. Pastikan kedua indikator modul berwarna hijau.

### Langkah 2: Buat & Impor Database
1. Buka browser dan masuk ke **phpMyAdmin**:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Klik menu tab **Import** (atau tab **SQL**).
3. Klik tombol **Choose File** / **Pilih Berkas**, lalu pilih file:
   ```text
   C:\xampp\htdocs\safevault\database\schema.sql
   ```
4. Klik tombol **Import** (atau **Go**) di bagian bawah.
5. Skrip akan secara otomatis membuat:
   - Database: `safevault_db`
   - Tabel: `users` (dengan kolom `id`, `username`, `email`, `password_hash`, `created_at`, `updated_at`).
   - Tabel: `login_attempts` (dengan kolom `id`, `ip_address`, `identifier`, `attempted_at`).

### Langkah 3: Akses Aplikasi di Browser
Buka browser dan kunjungi alamat URL berikut:

- **Target URL Resmi**:
  ```text
  http://localhost/safevault/
  ```
  *(Akan diarahkan otomatis ke `http://localhost/safevault/public/`)*

---

## 🧪 Hasil Pengujian Fitur Otomatis

Jalankan pengujian menyeluruh melalui terminal:
```powershell
php tests/test_safevault.php
```

**Ringkasan Hasil Uji (43 Test Cases - 100% Lolos):**
- [x] Ketersediaan `PASSWORD_ARGON2ID` pada lingkungan PHP (Lolos)
- [x] Hashing password dengan format `$argon2id$` (Lolos)
- [x] Verifikasi password benar via `password_verify` (Lolos)
- [x] Penolakan password salah via `password_verify` (Lolos)
- [x] Pembuatan token CSRF acak kriptografis 64 heksadesimal (Lolos)
- [x] Validasi kecocokan token CSRF via `hash_equals` (Lolos)
- [x] Penolakan token CSRF salah atau kosong (Lolos)
- [x] Validasi aturan format username di backend (Lolos)
- [x] Validasi aturan format email standar di backend (Lolos)
- [x] Validasi panjang minimum password (Lolos)
- [x] Registrasi pengguna baru ke database (Lolos)
- [x] Pencegahan duplikasi username pada registrasi (Lolos)
- [x] Pencegahan duplikasi email pada registrasi (Lolos)
- [x] Login pengguna menggunakan username (Lolos)
- [x] Login pengguna menggunakan email (Lolos)
- [x] Penolakan login jika password salah (Lolos)
- [x] Penolakan login jika identitas tidak ditemukan (Lolos)
- [x] Regenerasi Session ID saat login untuk menangkal Session Fixation (Lolos)
- [x] Proteksi otentikasi dashboard (`is_authenticated`) (Lolos)
- [x] Penghapusan data sesi dan cookie saat logout (Lolos)
- [x] Login Rate Limiting (Maks 5 kegagalan dalam 15 menit per Akun & IP) (Lolos)
- [x] Reset hitungan kegagalan login saat login berhasil (Lolos)
- [x] Session Idle Timeout (Kedaluwarsa otomatis setelah 15 menit tidak aktif) (Lolos)
- [x] Session Absolute Timeout (Kedaluwarsa otomatis setelah 8 jam sejak login) (Lolos)
- [x] Background request tidak memperpanjang idle timeout (Lolos)
