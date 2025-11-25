<?php
// admin/process_absensi.php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Ambil data JSON dari scanner
$input = json_decode(file_get_contents('php://input'), true);
$qr_code = $input['qr_code'] ?? '';

if (empty($qr_code)) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terbaca']);
    exit;
}

$pdo = getPDO();

try {
    // 1. Cek apakah QR Valid
    $stmt = $pdo->prepare("SELECT id, nama_lengkap FROM users WHERE qr_code = ? AND status = 'active'");
    $stmt->execute([$qr_code]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'QR Code Tidak Dikenal / User Tidak Aktif']);
        exit;
    }

    // 2. Cek apakah sudah absen hari ini
    $today = date('Y-m-d');
    $stmt_check = $pdo->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
    $stmt_check->execute([$user['id'], $today]);

    if ($stmt_check->rowCount() > 0) {
        echo json_encode(['status' => 'warning', 'message' => 'Atlet ini SUDAH absen hari ini!']);
        exit;
    }

    // 3. Simpan Absensi
    $waktu = date('H:i:s');
    $admin_id = $_SESSION['user_id'] ?? 1; // Default admin ID 1 jika session hilang
    
    $insert = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, waktu_absen, scan_by_admin_id) VALUES (?, ?, ?, ?)");
    $insert->execute([$user['id'], $today, $waktu, $admin_id]);

    echo json_encode([
        'status' => 'success', 
        'message' => 'Absensi Berhasil!',
        'nama' => $user['nama_lengkap'],
        'waktu' => $waktu
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>