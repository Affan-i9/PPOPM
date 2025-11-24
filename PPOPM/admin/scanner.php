<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) { redirect('/ppopm-absensi/auth/login.php'); }

$pdo = getPDO();

// Endpoint AJAX untuk submit hasil scan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_scan') {
    header('Content-Type: application/json');
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'msg' => 'CSRF invalid']);
        exit;
    }
    $payload = trim($_POST['payload'] ?? '');
    $keterangan = sanitize_string($_POST['keterangan'] ?? '');
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

    // Cari user berdasarkan qr_code string
    $stmt = $pdo->prepare('SELECT id FROM users WHERE qr_code = ? LIMIT 1');
    $stmt->execute([$payload]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['ok' => false, 'msg' => 'QR tidak valid']);
        exit;
    }
    $res = record_attendance((int)$user['id'], (int)current_user()['id'], $keterangan, $lat, $lng);
    echo json_encode($res);
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Scanner QR Absen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
<div class="container py-4">
  <h3>Scanner QR Code</h3>
  <div id="reader" style="width: 400px"></div>
  <div class="mt-3">
    <input type="text" id="keterangan" class="form-control" placeholder="Keterangan (opsional)">
    <button id="resetBtn" class="btn btn-secondary mt-2">Reset Kamera</button>
    <div id="result" class="mt-2"></div>
  </div>
  <a href="/ppopm-absensi/admin/index.php" class="btn btn-outline-primary mt-3">Kembali</a>
</div>
<script>
  const csrf = '<?= csrf_token() ?>';
  const reader = new Html5Qrcode("reader");
  const config = { fps: 10, qrbox: 250 };
  function onScanSuccess(decodedText, decodedResult) {
    fetch('/ppopm-absensi/admin/scanner.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'submit_scan', csrf_token: csrf, payload: decodedText, keterangan: document.getElementById('keterangan').value })
    }).then(r => r.json()).then(data => {
      document.getElementById('result').innerHTML = `<div class="alert ${data.ok ? 'alert-success' : 'alert-danger'}">${data.msg}</div>`;
    }).catch(err => {
      document.getElementById('result').innerHTML = '<div class="alert alert-danger">Terjadi kesalahan jaringan</div>';
    });
  }
  reader.start({ facingMode: "environment" }, config, onScanSuccess);
  document.getElementById('resetBtn').addEventListener('click', () => {
    reader.stop().then(() => reader.start({ facingMode: "environment" }, config, onScanSuccess));
  });
</script>
</body>
</html>

