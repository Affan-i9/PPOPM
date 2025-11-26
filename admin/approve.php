<?php
// File: /var/www/html/PPOPM/admin/approve.php

require_once '../includes/functions.php';
require_login();
checkAdmin();

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action']; // 'approve' atau 'reject'
    $pdo = getPDO();

    if ($action === 'approve') {
        // 1. Set status jadi ACTIVE
        // 2. Generate string QR Code unik (format: PPOPM-ID-WAKTU)
        $qr_string = "PPOPM-" . $id . "-" . time();
        
        $stmt = $pdo->prepare("UPDATE users SET status = 'active', qr_code = ? WHERE id = ?");
        $stmt->execute([$qr_string, $id]);
        
    } elseif ($action === 'reject') {
        // Jika ditolak, set status REJECTED
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Setelah selesai, kembali ke dashboard
redirect('index.php');
?>