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

if (empty($qr_code)) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terbaca']);
    exit;
}

$pdo = getPDO();
$jam_sekarang = date('H:i');     
$tgl_sekarang = date('Y-m-d');   

// --- LOGIKA 24 JAM TANPA PENOLAKAN ---
// Default: Terlambat
$status_keterangan = 'Terlambat'; 

// Jika jam 06:00 sampai 16:00 -> Tepat Waktu
if ($jam_sekarang >= '06:00' && $jam_sekarang <= '16:00') {
    $status_keterangan = 'Tepat Waktu';
}

try {
    // 1. Cek User Aktif
    $stmt = $pdo->prepare("SELECT id, nama_lengkap FROM users WHERE qr_code = ? AND status = 'active'");
    $stmt->execute([$qr_code]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'QR Code Tidak Dikenal / User Tidak Aktif']);
        exit;
    }

    // 2. Cek Double Absen Hari Ini
    $stmt_check = $pdo->prepare("SELECT id, waktu_absen FROM absensi WHERE user_id = ? AND tanggal = ?");
    $stmt_check->execute([$user['id'], $tgl_sekarang]);
    $existing = $stmt_check->fetch();

    if ($existing) {
        echo json_encode([
            'status' => 'warning', 
            'message' => 'Sudah absen hari ini pada jam ' . $existing['waktu_absen']
        ]);
        exit;
    }

    // 3. Simpan Absensi
    $insert = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, waktu_absen, keterangan, scan_by_admin_id) VALUES (?, ?, ?, ?, ?)");
    $admin_id = $_SESSION['user_id'] ?? 1;
    
    $insert->execute([$user['id'], $tgl_sekarang, $jam_sekarang, $status_keterangan, $admin_id]);

    // 4. Sukses
    echo json_encode([
        'status' => 'success', 
        'message' => "Berhasil! ($status_keterangan)",
        'nama' => $user['nama_lengkap'],
        'waktu' => $jam_sekarang
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>