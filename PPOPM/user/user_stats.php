<?php
// user/user_stats.php
require_once '../includes/functions.php';

// Cek sesi (API ini dipanggil oleh browser user)
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$pdo = getPDO();
$user_id = $_SESSION['user_id'];

// 1. Ambil Data User Terbaru (untuk cek status/QR)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// 2. Ambil Riwayat Absensi Terbaru (10 Terakhir)
$stmt_log = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt_log->execute([$user_id]);
$logs = $stmt_log->fetchAll(PDO::FETCH_ASSOC);

// Format HTML untuk tabel absensi
$logs_html = '';
if (count($logs) > 0) {
    foreach ($logs as $log) {
        $date = date('d F Y', strtotime($log['tanggal']));
        $time = date('H:i', strtotime($log['waktu_absen']));
        $logs_html .= "
        <tr>
            <td class='fw-bold text-white'>$date</td>
            <td class='font-monospace text-info fs-5'>$time</td>
            <td>
                <span class='badge bg-success rounded-pill px-3'>
                    <i class='fas fa-check-circle'></i> Hadir
                </span>
            </td>
        </tr>";
    }
} else {
    $logs_html = "
    <tr>
        <td colspan='3' class='text-center py-5 text-muted'>
            <i class='fas fa-ghost fa-3x mb-3 opacity-25'></i><br>
            Belum ada data absensi.
        </td>
    </tr>";
}

// Response JSON
echo json_encode([
    'status' => 'success',
    'user_status' => $user['status'],
    'qr_code' => $user['qr_code'],
    'logs_html' => $logs_html
]);
?>
