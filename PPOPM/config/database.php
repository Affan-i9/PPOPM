<?php
// Konfigurasi database dan helper koneksi PDO yang aman.
// Membaca konstanta sesuai spesifikasi dan menyediakan fungsi getPDO().

declare(strict_types=1);

// Konstanta koneksi sesuai requirement
define('DB_HOST', '192.168.1.20');
define('DB_USER', 'PPOPM');
define('DB_PASS', 'PPOPM');
define('DB_NAME', 'PPOPM');
define('DB_PORT', '3306');

// DSN builder untuk PDO MySQL
function db_dsn(): string {
    return sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
}

// Koneksi PDO singleton
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO(db_dsn(), DB_USER, DB_PASS, $options);
    return $pdo;
}

// Helper untuk memastikan timezone konsisten
date_default_timezone_set('Asia/Jakarta');

?>

