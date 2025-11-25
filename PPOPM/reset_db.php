<?php
// File: /var/www/html/PPOPM/reset_db.php

require_once 'includes/functions.php';

try {
    $pdo = getPDO();

    // 1. Matikan pengecekan Foreign Key (agar bisa hapus tabel induk)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. HAPUS TABEL LAMA (Urutan tidak masalah karena FK mati)
    $pdo->exec("DROP TABLE IF EXISTS absensi");
    $pdo->exec("DROP TABLE IF EXISTS users");
    
    // 3. Nyalakan lagi Foreign Key
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 4. BUAT ULANG STRUKTUR (Panggil fungsi otomatis dari functions.php)
    ensure_schema();

    // Tampilan Sukses (Tema Hacker)
    echo '<body style="background-color: #000; color: #0f0; font-family: monospace; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center;">';
    echo '<div>';
    echo '<h1 style="font-size: 3rem; border: 2px solid #0f0; padding: 20px;">DATABASE RESET SUCCESSFUL</h1>';
    echo '<p style="font-size: 1.5rem;">All tables dropped & recreated.</p>';
    echo '<p>Default Admin Created:</p>';
    echo '<p>User: <b>admin@ppopm.com</b><br>Pass: <b>admin123</b></p>';
    echo '<br><a href="auth/login.php" style="background: #0f0; color: #000; padding: 15px 30px; text-decoration: none; font-weight: bold; font-size: 1.2rem;">LOGIN NOW >></a>';
    echo '</div>';
    echo '</body>';

} catch (PDOException $e) {
    echo "<h1>ERROR: " . $e->getMessage() . "</h1>";
}
?>