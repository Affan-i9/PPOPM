<?php
// includes/functions.php

// Pastikan config database terpanggil
// Gunakan __DIR__ agar path selalu benar relatif terhadap file ini
require_once __DIR__ . '/../config/database.php';

/**
 * 1. Mendapatkan Koneksi PDO
 * Menggunakan variabel global $pdo dari config/database.php
 */
if (!function_exists('getPDO')) {
    function getPDO() {
        global $pdo;
        // Jika $pdo belum ada, coba panggil lagi config
        if (!isset($pdo) || $pdo === null) {
            require __DIR__ . '/../config/database.php';
        }
        // Jika masih gagal, hentikan proses
        if (!isset($pdo)) {
            die("Error: Koneksi database tidak tersedia. Cek config/database.php");
        }
        return $pdo;
    }
}

/**
 * 2. Sanitasi Input (Mencegah XSS)
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * 3. Helper Redirect
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * 4. Cek User ID yang sedang login
 * Return: ID user (int) atau NULL
 */
function current_user() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * 5. Cek Login User (Wajib Login & Status Active)
 * Fitur: Auto-Logout jika status diubah Admin jadi 'inactive'/'rejected' atau dihapus.
 */
function checkUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // a. Cek Session
    if (!isset($_SESSION['user_id'])) {
        redirect('../auth/login.php');
    }

    // b. Cek Status Terbaru di Database (Security Check)
    $pdo = getPDO();
    try {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_status = $stmt->fetchColumn();

        // c. Jika user tidak ditemukan (dihapus) ATAU status bukan 'active'
        if (!$user_status || $user_status !== 'active') {
            // Hapus session & tendang keluar
            session_unset();
            session_destroy();
            
            // Redirect dengan pesan (opsional)
            header("Location: ../auth/login.php?msg=suspended");
            exit;
        }
    } catch (PDOException $e) {
        // Jika error DB, biarkan dulu (opsional: log error)
    }
}

/**
 * 6. Cek Login Admin (Wajib Role Admin)
 */
function checkAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Cek User ID & Role Session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Jika bukan admin, lempar ke login
        redirect('../auth/login.php');
    }
}

/**
 * 7. Require Login Cerdas
 * Otomatis mendeteksi apakah halaman ini butuh Admin atau User biasa
 */
function require_login() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Deteksi jika URL mengandung kata '/admin/'
    $isInAdminFolder = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

    if ($isInAdminFolder) {
        checkAdmin(); // Harus Admin
    } else {
        checkUser();  // Minimal User Aktif
    }
}

/**
 * 8. Auto-Schema (Database Migration)
 * Membuat tabel otomatis jika database kosong/baru
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
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            scan_by_admin_id INT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // C. Buat Admin Default (Jika tabel users kosong)
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'");
        if ($stmt->fetchColumn() == 0) {
            // Password default: admin123
            $pass = password_hash('admin123', PASSWORD_DEFAULT);
            
            // Insert Admin
            $pdo->exec("INSERT INTO users (nama_lengkap, email, password, role, status) 
                        VALUES ('Administrator', 'admin@ppopm.com', '$pass', 'admin', 'active')");
        }

    } catch (PDOException $e) {
        die("Gagal inisialisasi database schema: " . $e->getMessage());
    }
}
?>