<?php
// admin/process_absensi.php
require_once '../includes/functions.php';

// 1. SET TIMEZONE WIB
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$qr_code = $input['qr_code'] ?? '';
$mode = $input['mode'] ?? 'IB'; // Default ke IB jika tidak dikirim

if (empty($qr_code)) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terbaca']);
    exit;
}

$pdo = getPDO();
$jam_sekarang = date('H:i');     
$tgl_sekarang = date('Y-m-d');   
$admin_id = $_SESSION['user_id'] ?? 1;

try {
    // 1. Validasi User
    $stmt = $pdo->prepare("SELECT id, nama_lengkap FROM users WHERE qr_code = ? AND status = 'active'");
    $stmt->execute([$qr_code]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'QR Code Tidak Dikenal / User Tidak Aktif']);
        exit;
    }

    // --- LOGIKA BERDASARKAN MODE ---

    if ($mode === 'GATE') {
        // === MODE IJIN KELUAR (TOGGLE OUT/IN) ===
        
        // Cek aktivitas terakhir hari ini untuk kategori GATE_PASS
        $stmt_last = $pdo->prepare("SELECT tipe_log FROM absensi WHERE user_id = ? AND tanggal = ? AND kategori = 'GATE_PASS' ORDER BY id DESC LIMIT 1");
        $stmt_last->execute([$user['id'], $tgl_sekarang]);
        $last_log = $stmt_last->fetch();

        $new_type = 'OUT'; // Default keluar
        $keterangan = 'Ijin Keluar (Berangkat)';

        // Jika ada log terakhir DAN log terakhir adalah OUT, maka sekarang IN
        if ($last_log && $last_log['tipe_log'] === 'OUT') {
            $new_type = 'IN';
            $keterangan = 'Ijin Keluar (Kembali)';
        }

        // Simpan
        $insert = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, waktu_absen, kategori, tipe_log, keterangan, scan_by_admin_id) VALUES (?, ?, ?, 'GATE_PASS', ?, ?, ?)");
        $insert->execute([$user['id'], $tgl_sekarang, $jam_sekarang, $new_type, $keterangan, $admin_id]);

        $pesan_respon = ($new_type === 'OUT') ? "Hati-hati di jalan!" : "Selamat beristirahat kembali!";
        
        echo json_encode([
            'status' => 'success',
            'type' => $new_type,
            'message' => "$keterangan<br>$jam_sekarang<br><small>$pesan_respon</small>",
            'nama' => $user['nama_lengkap']
        ]);

    } else {
        // === MODE IB / HARIAN (Logika Lama) ===

        // Cek apakah sudah absen IB hari ini?
        $stmt_check = $pdo->prepare("SELECT id, waktu_absen FROM absensi WHERE user_id = ? AND tanggal = ? AND kategori = 'IB'");
        $stmt_check->execute([$user['id'], $tgl_sekarang]);
        $existing = $stmt_check->fetch();

        if ($existing) {
            echo json_encode([
                'status' => 'warning', 
                'message' => 'Atlet ini sudah absen IB/Balik hari ini pada jam ' . $existing['waktu_absen']
            ]);
            exit;
        }

        // Tentukan Keterangan (Tepat Waktu / Terlambat)
        // Rule: Minggu Max 16.00 (atau sesuai kebutuhan)
        $status_keterangan = 'Terlambat';
        if ($jam_sekarang >= '06:00' && $jam_sekarang <= '16:00') {
            $status_keterangan = 'Tepat Waktu';
        }

        // Simpan sebagai IN (Karena IB itu absen balik ke asrama)
        $insert = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, waktu_absen, kategori, tipe_log, keterangan, scan_by_admin_id) VALUES (?, ?, ?, 'IB', 'IN', ?, ?)");
        $insert->execute([$user['id'], $tgl_sekarang, $jam_sekarang, $status_keterangan, $admin_id]);

        echo json_encode([
            'status' => 'success', 
            'type' => 'IN',
            'message' => "Absen IB Berhasil ($status_keterangan)<br>$jam_sekarang",
            'nama' => $user['nama_lengkap']
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>