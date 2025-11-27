<?php
// includes/functions.php

require_once __DIR__ . '/../config/database.php';

if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        if (!isset($pdo) || $pdo === null) {
            require __DIR__ . '/../config/database.php';
        }
        if (!isset($pdo)) {
            die("Error: Koneksi database tidak tersedia. Cek config/database.php");
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

function current_user() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function checkUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        redirect('../auth/login.php');
    }
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_status = $stmt->fetchColumn();
        if (!$user_status || $user_status !== 'active') {
            session_unset();
            session_destroy();
            header("Location: ../auth/login.php?msg=suspended");
            exit;
        }
    } catch (PDOException $e) {}
}

function checkAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        redirect('../auth/login.php');
    }
}

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
 * DATABASE MIGRATION HELPER
 * Menambahkan kolom 'tipe_log' (IN/OUT) dan 'kategori' (IB/GATE_PASS) jika belum ada
 */
function ensure_schema() {
    $pdo = getPDO();
    try {
        // A. Tabel Users
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

        // B. Tabel Absensi
        $pdo->exec("CREATE TABLE IF NOT EXISTS absensi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tanggal DATE NOT NULL,
            waktu_absen TIME NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            keterangan TEXT,
            scan_by_admin_id INT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // C. UPDATE STRUKTUR OTOMATIS (Mencegah error 'Unknown column')
        $cols = $pdo->query("DESCRIBE absensi")->fetchAll(PDO::FETCH_COLUMN);
        
        // Tambah kolom 'kategori' jika belum ada
        if (!in_array('kategori', $cols)) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN kategori ENUM('IB', 'GATE_PASS') DEFAULT 'IB' AFTER waktu_absen");
        }
        // Tambah kolom 'tipe_log' jika belum ada
        if (!in_array('tipe_log', $cols)) {
            $pdo->exec("ALTER TABLE absensi ADD COLUMN tipe_log ENUM('IN', 'OUT') DEFAULT 'IN' AFTER kategori");
        }
        
        // D. Admin Default
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");
        if ($stmt->fetchColumn() == 0) {
            $pass = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (nama_lengkap, email, password, role, status) 
                        VALUES ('Administrator', 'admin@ppopm.com', '$pass', 'admin', 'active')");
        }

    } catch (PDOException $e) {
        // Biarkan error, nanti ditangani caller
    }
}
?>