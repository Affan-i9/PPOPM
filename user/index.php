<?php
// user/index.php
require_once '../includes/functions.php';

// Cek Login
require_login();
checkUser();

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

// Ambil Data Awal (Agar tidak kosong saat loading pertama)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil Riwayat Awal
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
        
        /* Navbar */
        .navbar {
            background: rgba(20, 20, 20, 0.95);
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
        .card-custom:hover { border-color: var(--neon-yellow); }
        
        /* Foto Profil */
        .profile-container {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }
        .profile-img {
            width: 140px; height: 140px; object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--neon-green);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.4);
            background: #000;
        }
        
        /* Tabel */
        .table-dark-custom { width: 100%; border-collapse: collapse; color: white; }
        .table-dark-custom th {
            color: var(--neon-yellow); border-bottom: 2px solid var(--neon-green);
            text-transform: uppercase; padding: 15px; background: #222;
        }
        .table-dark-custom td {
            padding: 15px; border-bottom: 1px solid #333; vertical-align: middle;
        }
    </style>
</head>
<body>

<!-- Partikel Background -->
<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#" style="color:var(--neon-green); letter-spacing:2px;">
            <i class="fas fa-dumbbell"></i> PPOPM USER
        </a>
        <div class="d-flex align-items-center">
            <div class="me-3 small text-muted d-none d-md-block"><i class="fas fa-circle text-success fa-xs fa-beat"></i> Live</div>
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">
                <i class="fas fa-power-off"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="row">
        
        <!-- KIRI: KARTU IDENTITAS -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom text-center p-4">
                <h5 class="text-muted text-uppercase small ls-2">Kartu Atlet Digital</h5>
                
                <!-- Foto Profil (Initial) -->
                <?php
                    $foto_db = $user['foto_profil'];
                    $file_fisik = __DIR__ . '/../' . $foto_db;
                    if (!empty($foto_db) && file_exists($file_fisik)) {
                        $avatar_url = '../' . $foto_db;
                    } else {
                        $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=00ff88&color=000&size=256&bold=true";
                    }
                ?>
                
                <div class="profile-container">
                    <img id="user-foto" src="<?= $avatar_url ?>" class="profile-img" alt="Foto Profil">
                </div>

                <h3 class="fw-bold text-white mb-1"><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                <span class="badge bg-warning text-dark mb-4 fs-6 px-3 py-2 rounded-pill">
                    <?= htmlspecialchars($user['cabang_olahraga']) ?>
                </span>

                <!-- QR Code Section -->
                <div class="bg-white p-3 rounded-3 mx-auto" style="max-width: 220px; min-height: 220px; display: flex; align-items: center; justify-content: center;">
                    <div id="qr-container">
                        <?php if ($user['qr_code']): ?>
                            <img id="user-qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $user['qr_code'] ?>" class="img-fluid" alt="QR Code">
                            <small id="user-qr-text" class="d-block mt-2 text-dark font-monospace fw-bold"><?= $user['qr_code'] ?></small>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-clock fa-2x mb-2"></i><br>
                                Menunggu Approval
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0">Tunjukkan QR Code ini ke pelatih untuk absen.</p>
            </div>
        </div>

        <!-- KANAN: RIWAYAT ABSENSI -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-white mb-0"><i class="fas fa-history text-warning"></i> Riwayat Kehadiran</h4>
                    <span class="badge bg-secondary">Live Update</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-dark-custom align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam Absen</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="history-body">
                            <!-- Data Awal PHP -->
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
                                        <?php $badge = ($log['keterangan'] == 'Terlambat') ? 'bg-danger' : 'bg-success'; ?>
                                        <span class="badge <?= $badge ?> rounded-pill px-3">
                                            <i class="fas fa-check-circle"></i> <?= $log['keterangan'] ?? 'Hadir' ?>
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/particles.js"></script>
<script>
    // 1. Init Particles
    initParticles('#particles-container', { count: 70, colors: ['#00ff88', '#ffffff'], speed: 0.4, connectDistance: 110 });

    // 2. REALTIME UPDATE (Setiap 3 Detik)
    setInterval(() => {
        fetch('../api/user_stats.php')
        .then(response => {
            if (!response.ok) throw new Error("API Error");
            return response.json();
        })
        .then(data => {
            // A. Cek Status Akun (Auto Kick jika suspended)
            if (data.status === 'suspended') {
                window.location.href = '../auth/logout.php';
                return;
            }

            if (data.status === 'success') {
                // B. Update Tabel Riwayat
                const tbody = document.getElementById('history-body');
                if (tbody.innerHTML !== data.history_html) {
                    tbody.innerHTML = data.history_html;
                }

                // C. Update Foto (Jika admin ganti foto)
                const imgFoto = document.getElementById('user-foto');
                if (imgFoto.src !== data.foto_url && !data.foto_url.includes('ui-avatars')) {
                     // Hack kecil untuk refresh gambar jika URL sama tapi file berubah (jarang terjadi tapi bagus untuk cache busting)
                     // imgFoto.src = data.foto_url; 
                }

                // D. Update QR Code (Jika baru diapprove)
                const qrContainer = document.getElementById('qr-container');
                if (data.qr_code && !document.getElementById('user-qr-img')) {
                    // Jika sebelumnya belum ada QR, tapi sekarang ada -> Render Ulang Container
                    qrContainer.innerHTML = `
                        <img id="user-qr-img" src="${data.qr_url}" class="img-fluid" alt="QR Code">
                        <small id="user-qr-text" class="d-block mt-2 text-dark font-monospace fw-bold">${data.qr_code}</small>
                    `;
                }
            }
        })
        .catch(err => console.warn("Sync paused:", err));
    }, 3000); // 3000ms = 3 detik
</script>

</body>
</html>