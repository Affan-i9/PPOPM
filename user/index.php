<?php
// user/index.php
require_once '../includes/functions.php';

// Cek Login
require_login();
checkUser();

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

// Ambil Data User
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil Riwayat Absensi
$stmt_log = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt_log->execute([$user_id]);
$logs = $stmt_log->fetchAll();

// Cek Status Terakhir (Untuk Menentukan Posisi User)
$stmt_last = $pdo->prepare("SELECT tipe_log, kategori FROM absensi WHERE user_id = ? ORDER BY timestamp DESC LIMIT 1");
$stmt_last->execute([$user_id]);
$last_activity = $stmt_last->fetch();

$posisi_text = "Di Wisma";
$posisi_class = "success"; // Hijau
$posisi_icon = "fa-home";

// Jika aktivitas terakhir adalah KELUAR (OUT), maka statusnya Di Luar
if ($last_activity && $last_activity['tipe_log'] === 'OUT') {
    $posisi_text = "Sedang Ijin Keluar";
    $posisi_class = "warning"; // Kuning/Oranye
    $posisi_icon = "fa-walking";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Atlet - PPOPM</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-yellow: #ffcc00;
            --dark-bg: #0a0a0a;
            --card-bg: #151515;
            --text-main: #ffffff;
        }
        body {
            background-color: var(--dark-bg);
            color: var(--text-main);
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }
        .navbar { background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(10px); border-bottom: 2px solid var(--neon-green); }
        .card-custom { background: var(--card-bg); border: 1px solid #333; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); overflow: hidden; }
        .profile-img { width: 140px; height: 140px; object-fit: cover; border-radius: 50%; border: 4px solid var(--neon-green); background: #000; }
        .table-dark-custom { width: 100%; color: white; }
        .table-dark-custom th { color: var(--neon-yellow); border-bottom: 2px solid var(--neon-green); padding: 15px; background: #222; }
        .table-dark-custom td { padding: 15px; border-bottom: 1px solid #333; }
        
        /* Status Badge Besar */
        .status-badge {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #333;
        }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#" style="color:var(--neon-green); letter-spacing:2px;">
            <i class="fas fa-dumbbell"></i> PPOPM USER
        </a>
        <div class="d-flex align-items-center">
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="row">
        
        <!-- KIRI: KARTU IDENTITAS -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom text-center p-4">
                <h5 class="text-muted text-uppercase small ls-2">Kartu Atlet Digital</h5>
                
                <?php
                    $foto_db = $user['foto_profil'];
                    $file_fisik = __DIR__ . '/../' . $foto_db;
                    $avatar_url = (!empty($foto_db) && file_exists($file_fisik)) ? '../' . $foto_db : "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=00ff88&color=000&size=256&bold=true";
                ?>
                
                <div class="profile-container">
                    <img id="user-foto" src="<?= $avatar_url ?>" class="profile-img" alt="Foto Profil">
                </div>

                <h3 class="fw-bold text-white mb-1"><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                <span class="badge bg-warning text-dark mb-4 fs-6 px-3 py-2 rounded-pill">
                    <?= htmlspecialchars($user['cabang_olahraga']) ?>
                </span>
                
                <!-- Indikator Posisi -->
                <div class="status-badge">
                    <small class="text-muted d-block mb-1">STATUS SAAT INI</small>
                    <h4 class="text-<?= $posisi_class ?> mb-0 fw-bold">
                        <i class="fas <?= $posisi_icon ?>"></i> <?= $posisi_text ?>
                    </h4>
                </div>

                <div class="bg-white p-3 rounded-3 mx-auto mt-4" style="max-width: 220px;">
                    <?php if ($user['qr_code']): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $user['qr_code'] ?>" class="img-fluid" alt="QR Code">
                    <?php else: ?>
                        <div class="text-muted py-4">Menunggu Approval</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- KANAN: RIWAYAT -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white mb-0"><i class="fas fa-history text-warning"></i> Riwayat Kehadiran</h4>
                </div>
                
                <div class="table-responsive">
                    <table class="table-dark-custom align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="fw-bold text-white"><?= date('d M Y', strtotime($log['tanggal'])) ?></td>
                                    <td class="font-monospace text-info"><?= date('H:i', strtotime($log['waktu_absen'])) ?></td>
                                    <td>
                                        <?php if(isset($log['tipe_log']) && $log['tipe_log'] == 'OUT'): ?>
                                            <span class="badge bg-danger">KELUAR</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">MASUK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $log['keterangan'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada data absensi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/particles.js"></script>
<script>initParticles('#particles-container', { count: 70, colors: ['#00ff88', '#ffffff'], speed: 0.4 });</script>
</body>
</html>