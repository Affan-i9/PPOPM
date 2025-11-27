<?php
// api/admin_stats.php
require_once '../includes/functions.php';

// PAKSA WIB
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error']);
    exit;
}

$pdo = getPDO();

// --- SAFEGUARD DATABASE ---
// Cek apakah kolom 'kategori' sudah ada di tabel absensi. 
// Jika belum, panggil ensure_schema() untuk update struktur DB.
try {
    $cek_kolom = $pdo->query("SHOW COLUMNS FROM absensi LIKE 'kategori'");
    if ($cek_kolom->rowCount() == 0) {
        ensure_schema(); // Auto-fix database structure
    }
} catch (Exception $e) {
    // Silent error
}

// STATISTIK UTAMA
$total_atlet = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();

// Hadir Today = Total unik orang yang melakukan aktivitas apapun hari ini
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$today'")->fetchColumn();

// List Pending Approval
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

$pending_html = '';
if (count($pending_users) > 0) {
    foreach ($pending_users as $u) {
        $nama = htmlspecialchars($u['nama_lengkap']);
        $cabor = htmlspecialchars($u['cabang_olahraga']);
        $pending_html .= "<tr><td><div class='fw-bold'>$nama</div><small class='text-dim'>$cabor</small></td><td class='text-end'><a href='approve.php?id={$u['id']}&action=approve' class='btn btn-sm btn-success'><i class='fas fa-check'></i></a> <a href='approve.php?id={$u['id']}&action=reject' class='btn btn-sm btn-danger'><i class='fas fa-times'></i></a></td></tr>";
    }
} else {
    $pending_html = "<tr><td class='text-center text-dim py-5'>Kosong.</td></tr>";
}

// --- LOGIKA BARU UNTUK GRAFIK TERPISAH ---
$chart_ib = [];
$chart_gate = [];
$chart_labels = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    
    // Hitung IB (Asrama) - Pastikan query handle error jika kolom belum siap
    try {
        $chart_ib[] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$date' AND kategori = 'IB'")->fetchColumn();
        $chart_gate[] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$date' AND kategori = 'GATE_PASS'")->fetchColumn();
    } catch (Exception $e) {
        $chart_ib[] = 0;
        $chart_gate[] = 0;
    }
}

echo json_encode([
    'status' => 'success',
    'total_atlet' => $total_atlet,
    'hadir_today' => $hadir_today,
    'pending_count' => $pending,
    'pending_html' => $pending_html,
    'chart_labels' => $chart_labels, // Kirim label tanggal dari server
    'chart_data_ib' => $chart_ib,    // Data Garis Hijau
    'chart_data_gate' => $chart_gate // Data Garis Kuning
]);
?>