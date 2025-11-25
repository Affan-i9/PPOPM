<?php
// user/index.php
require_once '../includes/functions.php';

// Cek Login User
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Atlet - PPOPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-yellow: #ffcc00;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
        }
        body {
            background-color: var(--dark-bg);
            color: #eee;
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }
        
        /* Navbar Glass Effect */
        .navbar {
            background: rgba(18, 18, 18, 0.85);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid var(--neon-green);
        }
        
        /* Kartu Profil */
        .card-custom {
            background: var(--card-bg);
            border: 1px solid #333;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            overflow: hidden;
            transition: transform 0.3s;
        }
        .card-custom:hover {
            border-color: var(--neon-yellow);
        }
        
        /* Foto Profil Neon */
        .profile-container {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }
        .profile-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--neon-green);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.4);
            background: #000;
        }
        
        /* Tabel Dark */
        .table-dark-custom {
            --bs-table-bg: transparent;
            --bs-table-color: #ddd;
            border-color: #333;
        }
        .table-dark-custom th {
            color: var(--neon-yellow);
            border-bottom: 2px solid var(--neon-green);
            text-transform: uppercase;
            letter-spacing: 1px;
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
            <span class="text-white me-3 d-none d-md-block">Halo, <?= htmlspecialchars($user['nama_lengkap']) ?></span>
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">
                <i class="fas fa-power-off"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="row">
        
        <div class="col-lg-4 mb-4">
            <div class="card card-custom text-center p-4">
                <h5 class="text-muted text-uppercase small ls-2">Kartu Atlet Digital</h5>
                
                <?php
                    // Cek path foto di server
                    $foto_db = $user['foto_profil'];
                    $file_fisik = __DIR__ . '/../' . $foto_db;
                    
                    // Jika data ada DAN file fisiknya benar-benar ada
                    if (!empty($foto_db) && file_exists($file_fisik)) {
                        $avatar_url = '../' . $foto_db;
                    } else {
                        // FALLBACK: Pakai Avatar Inisial Nama (API Gratis)
                        $nama_url = urlencode($user['nama_lengkap']);
                        $avatar_url = "https://ui-avatars.com/api/?name={$nama_url}&background=00ff88&color=000&size=256&bold=true";
                    }
                ?>
                
                <div class="profile-container">
                    <img src="<?= $avatar_url ?>" class="profile-img" alt="Foto Profil">
                </div>

                <h3 class="fw-bold text-white mb-1"><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                <span class="badge bg-warning text-dark mb-4 fs-6 px-3 py-2 rounded-pill">
                    <?= htmlspecialchars($user['cabang_olahraga']) ?>
                </span>

                <div class="bg-white p-3 rounded-3 mx-auto" style="max-width: 220px;">
                    <?php if ($user['qr_code']): ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $user['qr_code'] ?>" class="img-fluid" alt="QR Code">
                        <small class="d-block mt-2 text-dark font-monospace fw-bold"><?= $user['qr_code'] ?></small>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clock fa-2x mb-2"></i><br>
                            Menunggu Approval
                        </div>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mt-2 mb-0">Tunjukkan QR Code ini ke pelatih untuk absen.</p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white mb-0"><i class="fas fa-history text-warning"></i> Riwayat Kehadiran</h4>
                    <span class="badge bg-secondary">10 Terakhir</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam Absen</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="fw-bold text-white">
                                        <?= date('d F Y', strtotime($log['tanggal'])) ?>
                                    </td>
                                    <td class="font-monospace text-info fs-5">
                                        <?= date('H:i', strtotime($log['waktu_absen'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success rounded-pill px-3">
                                            <i class="fas fa-check-circle"></i> Hadir
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fas fa-ghost fa-3x mb-3 opacity-25"></i><br>
                                        Belum ada data absensi.
                                    </td>
                                </tr>
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
<script>
    initParticles('#particles-container', { 
        count: 70, 
        colors: ['#00ff88', '#ffffff'], 
        speed: 0.4,
        connectDistance: 110
    });
</script>

</body>
</html>