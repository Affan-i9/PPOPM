<?php
// api/user_stats.php
require_once '../includes/functions.php';
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

// Cek Login User
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

// 1. Cek Status User (Untuk Auto-Kick Realtime)
$stmt = $pdo->prepare("SELECT status, qr_code, foto_profil, nama_lengkap, cabang_olahraga FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active') {
    echo json_encode(['status' => 'suspended']); // Sinyal untuk JS agar reload/logout
    exit;
}

// 2. Ambil Riwayat Absensi Terbaru
$stmt_log = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt_log->execute([$user_id]);
$logs = $stmt_log->fetchAll();

$history_html = '';

if (count($logs) > 0) {
    foreach ($logs as $log) {
        $tanggal = date('d F Y', strtotime($log['tanggal']));
        $jam = date('H:i', strtotime($log['waktu_absen']));
        $ket = $log['keterangan'] ?? 'Hadir'; // Tepat Waktu / Terlambat
        
        // Warna Badge Berdasarkan Keterangan
        $badgeColor = ($ket === 'Terlambat') ? 'bg-danger' : 'bg-success';
        
        $history_html .= "
        <tr>
            <td class='fw-bold text-white'>$tanggal</td>
            <td class='font-monospace text-info fs-5'>$jam</td>
            <td>
                <span class='badge $badgeColor rounded-pill px-3'>
                    <i class='fas fa-check-circle'></i> $ket
                </span>
            </td>
        </tr>";
    }
} else {
    $history_html = "
    <tr>
        <td colspan='3' class='text-center py-5 text-muted'>
            <i class='fas fa-ghost fa-3x mb-3 opacity-25'></i><br>
            Belum ada data absensi.
        </td>
    </tr>";
}

// 3. Logic Foto & QR (Agar kalau admin update foto/qr, di user langsung berubah)
$foto_url = (!empty($user['foto_profil']) && file_exists(__DIR__ . '/../' . $user['foto_profil'])) 
    ? '../' . $user['foto_profil'] 
    : "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=00ff88&color=000&size=256&bold=true";

$qr_url = $user['qr_code'] 
    ? "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . $user['qr_code']
    : ""; 

echo json_encode([
    'status' => 'success',
    'history_html' => $history_html,
    'foto_url' => $foto_url,
    'qr_code' => $user['qr_code'],
    'qr_url' => $qr_url,
    'user_status' => $user['status']
]);
?>