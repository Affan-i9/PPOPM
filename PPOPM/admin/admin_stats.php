<?php
// admin/admin_stats.php
require_once '../includes/functions.php';
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error']);
    exit;
}

$pdo = getPDO();

$total_atlet = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = CURDATE()")->fetchColumn();
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

$pending_html = '';
if (count($pending_users) > 0) {
    foreach ($pending_users as $u) {
        $nama = htmlspecialchars($u['nama_lengkap']);
        $cabor = htmlspecialchars($u['cabang_olahraga']);
        $pending_html .= "
        <tr>
            <td>
                <div class='fw-bold'>$nama</div>
                <small class='text-dim'>$cabor</small>
            </td>
            <td class='text-end'>
                <a href='approve.php?id={$u['id']}&action=approve' class='btn btn-sm btn-success'><i class='fas fa-check'></i></a>
                <a href='approve.php?id={$u['id']}&action=reject' class='btn btn-sm btn-danger'><i class='fas fa-times'></i></a>
            </td>
        </tr>";
    }
} else {
    $pending_html = "<tr><td class='text-center text-dim py-5'>Tidak ada permintaan baru.</td></tr>";
}

$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data[] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$date'")->fetchColumn();
}

echo json_encode([
    'status' => 'success',
    'total_atlet' => $total_atlet,
    'hadir_today' => $hadir_today,
    'pending_count' => $pending,
    'pending_html' => $pending_html,
    'chart_data' => $chart_data
]);
?>