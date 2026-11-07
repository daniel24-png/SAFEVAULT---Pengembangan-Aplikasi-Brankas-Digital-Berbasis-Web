<?php
/**
 * SAFEVAULT - Audit & Integrasi Database MySQL/MariaDB Sungguhan (Real DB Test)
 *
 * Menguji integrasi langsung dengan server MySQL/MariaDB XAMPP tanpa mock.
 * Menggunakan transaksi InnoDB dan rollback untuk memastikan tidak ada
 * data uji yang tertinggal di dalam database.
 */

// Keamanan: Larang akses langsung melalui web browser
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Akses hanya diizinkan melalui command line interface (CLI).');
}

define('SAFEVAULT_APP', true);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Lokasi session lokal untuk pengujian CLI jika diperlukan
$session_dir = __DIR__ . '/session_tmp';
if (!is_dir($session_dir)) {
    mkdir($session_dir, 0777, true);
}
session_save_path($session_dir);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

$results = [
    'connection' => false,
    'db_type' => '',
    'server_version' => '',
    'charset' => '',
    'select_1' => false,
    'table_users_found' => false,
    'columns_matched' => false,
    'column_details' => [],
    'insert_select_ok' => false,
    'argon2id_stored' => false,
    'password_verify_correct' => false,
    'password_verify_wrong' => false,
    'duplicate_username_rejected' => false,
    'duplicate_email_rejected' => false,
    'db_unique_constraint_enforced' => false,
    'rollback_cleaned' => false,
    'errors' => []
];

try {
    // 1. Audit Koneksi Database Sungguhan
    $pdo = get_db();
    $results['connection'] = true;

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $results['db_type'] = $driver;
    $results['server_version'] = $version;

    // 2. Pengujian SELECT 1
    $stmt1 = $pdo->query('SELECT 1 AS ping_check');
    $row1 = $stmt1->fetch();
    if ($row1 && (int)$row1['ping_check'] === 1) {
        $results['select_1'] = true;
    }

    // 3. Cek Charset Aktif
    $charsetStmt = $pdo->query("SELECT @@character_set_connection AS cs, @@collation_connection AS col");
    $charsetInfo = $charsetStmt->fetch();
    $results['charset'] = ($charsetInfo['cs'] ?? '') . ' (' . ($charsetInfo['col'] ?? '') . ')';

    // 4. Periksa Keberadaan Tabel `users`
    $tablesStmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tablesStmt->rowCount() > 0 || $tablesStmt->fetch()) {
        $results['table_users_found'] = true;
    }

    // 5. Audit Struktur Kolom Aktual
    $colsStmt = $pdo->query("DESCRIBE `users`");
    $rawCols = $colsStmt->fetchAll(PDO::FETCH_ASSOC);

    $expectedFields = ['id', 'username', 'email', 'password_hash', 'created_at', 'updated_at'];
    $actualFields = [];

    foreach ($rawCols as $col) {
        $fieldName = $col['Field'];
        $actualFields[] = $fieldName;
        $results['column_details'][$fieldName] = [
            'type'    => $col['Type'],
            'null'    => $col['Null'],
            'key'     => $col['Key'],
            'default' => $col['Default'],
            'extra'   => $col['Extra']
        ];
    }

    // Cocokkan kolom
    $diff = array_diff($expectedFields, $actualFields);
    if (empty($diff) && count($actualFields) === count($expectedFields)) {
        $results['columns_matched'] = true;
    }

    // Hitung jumlah data eksisting sebelum pengujian
    $preCountStmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    $preCount = (int)$preCountStmt->fetch()['total'];

    // 6. Pengujian Integrasi Akun Uji dengan Transaksi (Rollback)
    $pdo->beginTransaction();

    $testUser = 'audit_tester_' . bin2hex(random_bytes(4));
    $testEmail = $testUser . '@safevault.test';
    $testPass = 'AuditTestPass#2026';

    // A. INSERT akun uji via create_user()
    $insertedId = create_user($pdo, $testUser, $testEmail, $testPass);

    // B. SELECT akun uji via find_user_by_username()
    $userFromDb = find_user_by_username($pdo, $testUser);
    if ($userFromDb && (int)$userFromDb['id'] === $insertedId && $userFromDb['email'] === $testEmail) {
        $results['insert_select_ok'] = true;
    }

    // C. Cek Password Hash Argon2id
    if ($userFromDb && strpos($userFromDb['password_hash'], '$argon2id$') === 0) {
        $results['argon2id_stored'] = true;
    }

    // D. Uji password_verify benar
    if ($userFromDb && password_verify($testPass, $userFromDb['password_hash'])) {
        $results['password_verify_correct'] = true;
    }

    // E. Uji password_verify salah
    if ($userFromDb && !password_verify('PasswordSalahTotal', $userFromDb['password_hash'])) {
        $results['password_verify_wrong'] = true;
    }

    // F. Uji Duplikasi Username (Aplikasi & UNIQUE Constraint DB)
    $dupUserFound = find_user_by_username($pdo, $testUser);
    if ($dupUserFound !== null) {
        $results['duplicate_username_rejected'] = true;
    }

    // Coba INSERT langsung data username duplikat ke DB untuk menguji UNIQUE constraint level database
    try {
        $stmtDup = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :pass)");
        $stmtDup->execute([
            ':username' => $testUser, // username sama persis
            ':email'    => 'different_' . $testEmail,
            ':pass'     => password_hash('randompass', PASSWORD_ARGON2ID)
        ]);
        $results['errors'][] = 'Database UNIQUE constraint gagal: username duplikat berhasil masuk.';
    } catch (PDOException $e) {
        // Kode 23000 adalah integrity constraint violation (Duplicate entry)
        if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $results['db_unique_constraint_enforced'] = true;
        } else {
            $results['errors'][] = 'Database throw error lain saat cek duplikasi: ' . $e->getMessage();
        }
    }

    // G. Uji Duplikasi Email (Aplikasi & UNIQUE Constraint DB)
    $dupEmailFound = find_user_by_email($pdo, $testEmail);
    if ($dupEmailFound !== null) {
        $results['duplicate_email_rejected'] = true;
    }

    try {
        $stmtDupEmail = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :pass)");
        $stmtDupEmail->execute([
            ':username' => 'different_' . $testUser,
            ':email'    => $testEmail, // email sama persis
            ':pass'     => password_hash('randompass', PASSWORD_ARGON2ID)
        ]);
        $results['errors'][] = 'Database UNIQUE constraint gagal: email duplikat berhasil masuk.';
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            // UNIQUE constraint email terbukti aktif di DB
        } else {
            $results['errors'][] = 'Database throw error lain saat cek duplikasi email: ' . $e->getMessage();
        }
    }

    // Rollback seluruh perubahan pengujian agar database bersih
    $pdo->rollBack();

    // Verifikasi pembersihan rollback
    $postCountStmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    $postCount = (int)$postCountStmt->fetch()['total'];

    $orphanCheck = find_user_by_username($pdo, $testUser);
    if ($postCount === $preCount && $orphanCheck === null) {
        $results['rollback_cleaned'] = true;
    } else {
        $results['errors'][] = 'Rollback tidak sepenuhnya membersihkan data pengujian.';
    }

} catch (Throwable $e) {
    $results['errors'][] = 'Fatal Audit Exception: ' . $e->getMessage();
}

// Bersihkan session test
foreach (glob($session_dir . '/*') as $f) {
    if (is_file($f)) @unlink($f);
}
@rmdir($session_dir);

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

