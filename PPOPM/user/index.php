<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_user()) { redirect('/ppopm-absensi/auth/login.php'); }

$u = current_user();
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT qr_code, status FROM users WHERE id = ?');
$stmt->execute([(int)$u['id']]);
$row = $stmt->fetch();
$qr = $row['qr_code'] ?? null;
$summary = user_attendance_summary((int)$u['id']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">PPOPM User</a>
    <div class="d-flex">
      <a class="btn btn-outline-light" href="/ppopm-absensi/user/profil.php">Profil</a>
      <a class="btn btn-danger ms-2" href="/ppopm-absensi/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container py-4">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">QR Code Personal</div>
        <div class="card-body text-center">
          <?php if (!$qr): ?>
            <div class="alert alert-warning">Akun belum di-approve. QR belum tersedia.</div>
          <?php else: ?>
            <div id="qrcode"></div>
            <button id="downloadBtn" class="btn btn-outline-primary mt-3">Download QR</button>
            <script>
              const qrText = <?= json_encode($qr) ?>;
              const qrContainer = document.getElementById('qrcode');
              const qrcode = new QRCode(qrContainer, { text: qrText, width: 256, height: 256 });
              document.getElementById('downloadBtn').addEventListener('click', () => {
                const img = qrContainer.querySelector('img') || qrContainer.querySelector('canvas');
                const a = document.createElement('a');
                a.href = img.src || img.toDataURL('image/png');
                a.download = 'qr_ppopm.png';
                a.click();
              });
            </script>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Status Kehadiran Bulan Ini</div>
        <div class="card-body">
          <p><strong>Bulan:</strong> <?= htmlspecialchars($summary['bulan']) ?></p>
          <p><strong>Total Hadir:</strong> <?= (int)$summary['total_hadir'] ?></p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>

