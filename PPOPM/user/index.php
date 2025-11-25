<?php
// user/index.php
require_once '../includes/functions.php';
require_login();
checkUser();

$pdo = getPDO();
$user_id = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $user->execute([$user_id]); $user = $user->fetch();
$logs = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10"); $logs->execute([$user_id]); $logs = $logs->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Atlet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --neon-green: #00ff88; --neon-yellow: #ffcc00; --dark-bg: #121212; --card-bg: #1e1e1e; }
        body { background-color: var(--dark-bg); color: #eee; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: rgba(0,0,0,0.8); border-bottom: 1px solid var(--neon-yellow); backdrop-filter: blur(10px); }
        .card { background: var(--card-bg); border: 1px solid #333; color: white; margin-bottom: 20px; }
        .card-header { background: rgba(255, 204, 0, 0.1); color: var(--neon-yellow); font-weight: bold; border-bottom: 1px solid #333; }
        .table-dark-custom { color: #ddd; }
        .table-dark-custom td { border-color: #333; }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" style="color:var(--neon-green)" href="#">DASHBOARD ATLET</a>
        <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header text-center">KARTU DIGITAL</div>
                <div class="card-body text-center">
                    <?php if($user['foto_profil']): ?>
                        <img src="../<?= $user['foto_profil'] ?>" class="rounded-circle mb-3 border border-2 border-success" width="100" height="100" style="object-fit:cover;">
                    <?php endif; ?>
                    <h5 class="fw-bold"><?= htmlspecialchars($user['nama_lengkap']) ?></h5>
                    <span class="badge bg-success mb-3"><?= htmlspecialchars($user['cabang_olahraga']) ?></span>
                    
                    <div class="bg-white p-2 rounded d-inline-block mt-2">
                        <?php if ($user['qr_code']): ?>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= $user['qr_code'] ?>" width="150">
                        <?php else: ?>
                            <span class="text-dark small">Menunggu Approval</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header"><i class="fas fa-history"></i> Riwayat Kehadiran</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped mb-0">
                            <thead><tr><th>Tanggal</th><th>Jam</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($log['tanggal'])) ?></td>
                                    <td><?= date('H:i', strtotime($log['waktu_absen'])) ?></td>
                                    <td><span class="badge bg-primary">Hadir</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if(count($logs)==0) echo '<p class="text-center p-3 text-muted">Belum ada data absensi.</p>'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/particles.js"></script>
<script>
    initParticles('#particles-container', { count: 80, colors: ['#ffffff', '#00ff88'], speed: 0.3 });
</script>
</body>
</html>