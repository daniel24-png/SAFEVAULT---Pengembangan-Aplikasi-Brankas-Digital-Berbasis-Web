<<<<<<< HEAD
# SAFEVAULT - Brankas Digital Pribadi (Versi 0.1)

SAFEVAULT adalah aplikasi web brankas digital pribadi yang dibangun menggunakan **PHP Native** dan **MySQL** yang berjalan di lingkungan web server **XAMPP**. Aplikasi ini dirancang untuk menyediakan tempat penyimpanan dan pengelolaan informasi sensitif secara terstruktur, aman, dan mudah dipelihara.

---

## 🔒 Standar Keamanan Wajib (v0.1)

1. **Koneksi Database PDO & Prepared Statements**:
   - Seluruh interaksi basis data menggunakan `PDO` dengan mode `PDO::ERRMODE_EXCEPTION` dan `PDO::ATTR_EMULATE_PREPARES => false`.
   - Tidak ada penggabungan string (*string concatenation*) pada query SQL untuk mencegah SQL Injection.
2. **Password Hashing Modern (Argon2id)**:
   - Menggunakan `password_hash($password, PASSWORD_ARGON2ID)` sesuai standar kriptografi terkini.
   - Tidak menyimpan password plaintext atau membuat kolom salt terpisah karena Argon2id telah mengemas salt dan metadata algoritma secara aman.
   - Dilengkapi pemeriksaan lingkungan: jika `PASSWORD_ARGON2ID` tidak tersedia pada PHP, aplikasi akan berhenti dan memberikan panduan teknis tanpa mengganti ke algoritma yang lebih lemah secara diam-diam.
3. **Perlindungan Session Ketat**:
   - Cookie session dikonfigurasi dengan flag `HttpOnly` (mencegah pencurian cookie via XSS) dan `SameSite=Lax` (mencegah eksploitasi CSRF).
   - Memanggil `session_regenerate_id(true)` setelah login berhasil untuk mencegah serangan *Session Fixation*.
4. **Proteksi CSRF (Cross-Site Request Forgery)**:
   - Token kriptografis acak 64 karakter (`bin2hex(random_bytes(32))`) divalidasi pada seluruh form yang mengubah state (Registrasi, Login, dan Logout).
   - Validasi menggunakan `hash_equals()` untuk mencegah *timing attack*.
5. **Sanitasi Output & Validasi Backend**:
   - Seluruh data dinamis yang ditampilkan ke antarmuka disanitasi menggunakan `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
   - Validasi ketat di sisi server untuk panjang username (3–30 karakter alfanumerik + underscore), format email standar RFC, kecocokan konfirmasi password, serta panjang password minimal 8 karakter.
6. **Logout Aman (POST Only)**:
   - Tombol logout mengirim permintaan `POST` yang dilengkapi token CSRF, mencegah logout paksa melalui URL gambar atau link `GET`.

---

## 📁 Struktur Direktori & Berkas

```text
safevault/
│
├── app/
│   ├── controllers/             # Tempat logika controller masa depan
│   ├── models/                  # Tempat model data masa depan
│   ├── services/                # Logika bisnis tambahan (enkripsi, dekripsi, dll.)
│   └── views/                   # Template antarmuka tambahan
│
├── config/
│   ├── database.example.php     # Template konfigurasi database aman untuk Git
│   └── database.local.php       # Konfigurasi aktif lokal (diabaikan oleh Git via .gitignore)
│
├── database/
│   └── schema.sql               # Skrip DDL untuk database safevault_db dan tabel users
│
├── includes/
│   ├── bootstrap.php            # Inisialisasi session aman, cek Argon2id, singleton koneksi PDO
│   ├── csrf.php                 # Modul token CSRF (generate, render input, validasi)
│   └── functions.php            # Helper sanitasi, autentikasi, validasi, dan query pengguna
│
├── public/                      # Document Root yang dapat diakses publik melalui web server
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css        # Stylesheet antarmuka responsif bertema dark/slate
│   │   ├── images/              # Folder aset gambar & ikon
│   │   └── js/                  # Folder script interaktivitas JavaScript
│   ├── index.php                # Halaman pembuka (Homepage) SAFEVAULT
│   ├── register.php             # Halaman & pemrosesan registrasi pengguna
│   ├── login.php                # Halaman & pemrosesan masuk (login) pengguna
│   ├── dashboard.php            # Halaman beranda pribadi pengguna (terproteksi auth)
│   └── logout.php               # Handler keluar sesi pengguna (hanya menerima POST + CSRF)
│
├── tests/
│   └── test_safevault.php       # Skrip pengujian otomatis (33 test cases, 100% lolos)
│
├── .gitignore                   # Menjaga kredensial database lokal tidak terunggah ke Git
└── README.md                    # Dokumentasi lengkap proyek
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
   (atau C:\xampp\htdocs\savefault\database\schema.sql)
   ```
4. Klik tombol **Import** (atau **Go**) di bagian bawah.
5. Skrip akan secara otomatis membuat:
   - Database: `safevault_db`
   - Tabel: `users` (dengan kolom `id`, `username`, `email`, `password_hash`, `created_at`, `updated_at`).

### Langkah 3: Periksa Konfigurasi Database Lokal
Pastikan berkas `config/database.local.php` sudah ada. Pengaturan standar XAMPP:
```php
<?php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'safevault_db',
    'charset'  => 'utf8mb4',
    'username' => 'root',
    'password' => '', // Kosong untuk instalasi default XAMPP
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
```

### Langkah 4: Buka Aplikasi di Browser
Buka browser dan kunjungi alamat URL berikut:

- Jika nama folder adalah **`safevault`**:
  ```text
  http://localhost/safevault/public/
  ```

- *(Catatan: Folder workspace saat ini bernama `savefault`. Jika Anda belum mengubah namanya di Windows Explorer, Anda dapat langsung membukanya di:)*
  ```text
  http://localhost/savefault/public/
  ```

---

## 🧪 Hasil Pengujian Fitur Otomatis

Seluruh logika backend dan keamanan diuji menggunakan skrip `tests/test_safevault.php`:

Jalankan perintah berikut di terminal:
```powershell
php tests/test_safevault.php
```

**Ringkasan Hasil Uji (33 Test Cases):**
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

---

## 📌 Rencana Tahap Pengembangan Berikutnya (v0.2)
1. **Secret Notes Module**: Implementasi penyimpanan teks terenkripsi simetris (AES-256-GCM) dengan kunci turunan dari master key pengguna.
2. **Secure File Vault**: Manajemen unggah berkas terenkripsi data-at-rest.
3. **Image Steganography**: Fitur penyembunyian pesan di berkas citra digital.
4. **Blockchain Audit Trail**: Buku besar log aktivitas brankas data.
=======
# SAFEVAULT---Pengembangan-Aplikasi-Brankas-Digital-Berbasis-Web
SAFEVAULT merupakan aplikasi brankas digital pribadi yang memungkinkan pengguna menyimpan pesan rahasia, file, dan gambar dengan perlindungan kriptografi.  Pengguna tidak perlu mengirim pesan kepada orang lain. Mereka cukup menyimpan, melihat, dan mengambil kembali informasi rahasia melalui aplikasi setelah melewati proses autentikasi. 
>>>>>>> 1cf98fcaf2b0d1f50f12fef6df11c2d8be28f74a
