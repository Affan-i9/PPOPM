<?php
// /var/www/html/PPOPM/includes/functions.php

require_once __DIR__ . '/../config/database.php';

/**
 * Mendapatkan koneksi PDO
 */
if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        if (!isset($pdo) || $pdo === null) {
            require __DIR__ . '/../config/database.php';
        }
        if (!isset($pdo)) {
            die("Error: Koneksi database tidak tersedia.");
        }
        return $pdo;
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * [BARU] Fungsi current_user
 * Mengembalikan user_id jika sedang login, atau null jika belum.
 * Digunakan di login.php untuk redirect user yang sudah login.
 */
function current_user() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Cek Login Admin
 */
function checkAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        redirect('../auth/login.php');
    }
}

/**
 * Cek Login User
 */
function checkUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        redirect('../auth/login.php');
    }
}

/**
 * Fungsi require_login
 * Wrapper cerdas untuk mendeteksi kebutuhan login admin/user
 */
function require_login() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $isInAdminFolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

    if ($isInAdminFolder) {
        checkAdmin();
    } else {
        checkUser();
    }
}

/**
 * Fungsi Auto-Schema (Database Migration)
 */
function ensure_schema() {
    $pdo = getPDO();
    try {
        // Tabel Users
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama_lengkap VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            no_telepon VARCHAR(15),
            cabang_olahraga VARCHAR(50),
            foto_profil VARCHAR(255),
            qr_code VARCHAR(255) UNIQUE,
            role ENUM('admin', 'user') DEFAULT 'user',
            status ENUM('pending', 'active', 'inactive', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        // Tabel Absensi
        $pdo->exec("CREATE TABLE IF NOT EXISTS absensi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tanggal DATE NOT NULL,
            waktu_absen TIME NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            keterangan TEXT,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            scan_by_admin_id INT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Admin Default
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");
        if ($stmt->fetchColumn() == 0) {
            $pass = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (nama_lengkap, email, password, role, status) 
                        VALUES ('Administrator', 'admin@ppopm.com', '$pass', 'admin', 'active')");
        }

    } catch (PDOException $e) {
        die("Gagal inisialisasi database: " . $e->getMessage());
    }
}
?>