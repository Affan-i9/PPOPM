<?php
// admin/index.php
require_once '../includes/functions.php';
require_login();
checkAdmin();

$pdo = getPDO();

// Statistik Cepat
$total_atlet = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = CURDATE()")->fetchColumn();

// Data Pending
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Command Center</title>
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
      color: #e0e0e0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
    }

    /* Animasi Background (Canvas akan menutupi ini) */
    #bg-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      opacity: 0.3;
    }

    /* Navbar */
    .navbar {
      background: rgba(30, 30, 30, 0.9);
      border-bottom: 2px solid var(--neon-green);
      backdrop-filter: blur(10px);
    }

    .navbar-brand {
      font-weight: bold;
      color: var(--neon-green) !important;
      text-transform: uppercase;
      letter-spacing: 2px;
    }

    /* Kartu Statistik */
    .stat-card {
      background: var(--card-bg);
      border: 1px solid #333;
      border-radius: 15px;
      padding: 20px;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
      border-color: var(--neon-green);
    }

    .stat-card h3 {
      font-size: 3rem;
      font-weight: bold;
      margin: 0;
    }

    .stat-card.green {
      color: var(--neon-green);
    }

    .stat-card.yellow {
      color: var(--neon-yellow);
    }

    .stat-card.blue {
      color: #00d4ff;
    }

    /* Tombol Scan Utama */
    .btn-scan-big {
      background: linear-gradient(45deg, var(--neon-green), #00cc6a);
      color: #000;
      font-weight: bold;
      border: none;
      padding: 15px 40px;
      border-radius: 50px;
      font-size: 1.2rem;
      box-shadow: 0 0 15px var(--neon-green);
      transition: 0.3s;
      text-decoration: none;
      display: inline-block;
    }

    .btn-scan-big:hover {
      transform: scale(1.05);
      box-shadow: 0 0 30px var(--neon-green);
      color: #000;
    }

    /* Tabel */
    .table-dark-custom {
      background: var(--card-bg);
      border-radius: 10px;
      overflow: hidden;
    }

    .table-dark-custom th {
      background: #2a2a2a;
      color: var(--neon-yellow);
      border: none;
    }

    .table-dark-custom td {
      background: var(--card-bg);
      border-bottom: 1px solid #333;
      color: #ccc;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#"><i class="fas fa-shield-alt"></i> PPOPM COMMAND</a>
      <div class="d-flex">
        <span class="text-white me-3 align-self-center">Admin Mode</span>
        <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm border-0"><i class="fas fa-power-off"></i></a>
      </div>
    </div>
  </nav>

  <div class="container" style="margin-top: 100px;">

    <div class="row align-items-center mb-5">
      <div class="col-md-8">
        <h1 class="display-5 fw-bold text-white">Dashboard <span style="color:var(--neon-yellow)">Monitoring</span></h1>
        <p class="text-muted">Sistem Absensi Digital Atlet PPOPM</p>
      </div>
      <div class="col-md-4 text-end">
        <a href="scanner.php" class="btn-scan-big">
          <i class="fas fa-qrcode fa-spin hover-stop"></i> SCAN ABSEN
        </a>
      </div>
    </div>

    <div class="row mb-5">
      <div class="col-md-4 mb-3">
        <div class="stat-card blue">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-muted small text-uppercase">Total Atlet</span>
              <h3><?= $total_atlet ?></h3>
            </div>
            <i class="fas fa-users fa-3x opacity-25"></i>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="stat-card green">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-muted small text-uppercase">Hadir Hari Ini</span>
              <h3><?= $hadir_today ?></h3>
            </div>
            <i class="fas fa-check-circle fa-3x opacity-25"></i>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="stat-card yellow">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-muted small text-uppercase">Pending Approval</span>
              <h3><?= $pending ?></h3>
            </div>
            <i class="fas fa-clock fa-3x opacity-25"></i>
          </div>
        </div>
      </div>
    </div>

    <?php if (count($pending_users) > 0): ?>
      <div class="card bg-transparent border-0 mb-5">
        <div class="card-header bg-transparent border-0 ps-0">
          <h4 class="text-white"><i class="fas fa-user-plus" style="color:var(--neon-yellow)"></i> Permintaan Registrasi Baru</h4>
        </div>
        <div class="table-responsive">
          <table class="table table-dark-custom align-middle">
            <thead>
              <tr>
                <th>NAMA ATLET</th>
                <th>CABOR</th>
                <th>EMAIL</th>
                <th>FOTO</th>
                <th>AKSI</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pending_users as $u): ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($u['cabang_olahraga']) ?></span></td>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td>
                    <?php if ($u['foto_profil']): ?>
                      <a href="../<?= $u['foto_profil'] ?>" target="_blank" class="text-decoration-none text-info">
                        <i class="fas fa-image"></i> Lihat
                      </a>
                    <?php else: ?> - <?php endif; ?>
                  </td>
                  <td>
                    <a href="approve.php?id=<?= $u['id'] ?>&action=approve" class="btn btn-sm btn-success me-2"><i class="fas fa-check"></i></a>
                    <a href="approve.php?id=<?= $u['id'] ?>&action=reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <script src="../assets/js/particles.js"></script>
  <script>
    initParticles('#particles-container', {
      count: 120,
      colors: ['#00ff88', '#0044ff'], // Hijau & Biru untuk Admin
      speed: 0.7
    });
  </script>

  <script>
    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789@#$%^&*()*&^%';
    const fontSize = 16;
    const columns = canvas.width / fontSize;
    const drops = [];

    for (let x = 0; x < columns; x++) {
      drops[x] = 1;
    }

    function draw() {
      ctx.fillStyle = 'rgba(18, 18, 18, 0.05)'; // Trail effect
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      ctx.fillStyle = '#00ff88'; // Warna Hijau Neon
      ctx.font = fontSize + 'px monospace';

      for (let i = 0; i < drops.length; i++) {
        const text = letters.charAt(Math.floor(Math.random() * letters.length));
        ctx.fillText(text, i * fontSize, drops[i] * fontSize);

        if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
          drops[i] = 0;
        }
        drops[i]++;
      }
    }

    setInterval(draw, 33);

    // Resize handler
    window.addEventListener('resize', () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>