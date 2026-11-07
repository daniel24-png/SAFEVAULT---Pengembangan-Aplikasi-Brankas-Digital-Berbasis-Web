<?php
/**
 * SAFEVAULT - Contoh Konfigurasi Database (Template)
 *
 * Salin file ini menjadi 'database.local.php' dan sesuaikan kredensial
 * dengan lingkungan server MySQL lokal Anda.
 * JANGAN menyimpan password produksi di file ini.
 */

return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'safevault_db',
    'charset'  => 'utf8mb4',
    'username' => 'root',        // Ganti dengan akun database Anda jika ada
    'password' => '',            // Default XAMPP adalah string kosong
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];

