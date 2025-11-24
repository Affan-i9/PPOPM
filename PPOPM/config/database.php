<?php
// /var/www/html/PPOPM/config/database.php

define('DB_HOST', '192.168.1.20'); // Sesuaikan IP Database
define('DB_USER', 'PPOPM');
define('DB_PASS', 'PPOPM');
define('DB_NAME', 'PPOPM');
define('DB_PORT', '3306');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>