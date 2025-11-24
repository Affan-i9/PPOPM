<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) { redirect('/ppopm-absensi/auth/login.php'); }

$pdo = getPDO();

// Export CSV bila diminta
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'tanggal' => $_GET['tanggal'] ?? null,
        'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
        'cabang_olahraga' => $_GET['cabang_olahraga'] ?? null,
    ];
    export_csv_attendance($pdo, $filters);
}

// Data untuk tampilan
$filters = [];
if (!empty($_GET['tanggal'])) { $filters['tanggal'] = $_GET['tanggal']; }
if (!empty($_GET['user_id'])) { $filters['user_id'] = (int)$_GET['user_id']; }
if (!empty($_GET['cabang_olahraga'])) { $filters['cabang_olahraga'] = $_GET['cabang_olahraga']; }

$sql = 'SELECT a.id, u.nama_lengkap, u.cabang_olahraga, a.tanggal, a.waktu_absen, a.keterangan FROM absensi a JOIN users u ON a.user_id = u.id WHERE 1=1';
$params = [];
foreach ($filters as $k=>$v){
  if ($k==='tanggal'){ $sql.=' AND a.tanggal = ?'; $params[]=$v; }
  if ($k==='user_id'){ $sql.=' AND a.user_id = ?'; $params[]=$v; }
  if ($k==='cabang_olahraga'){ $sql.=' AND u.cabang_olahraga = ?'; $params[]=$v; }
}
$sql .= ' ORDER BY a.tanggal DESC, a.waktu_absen DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laporan Absensi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <h3>Laporan Absensi</h3>
  <form class="row g-3 mb-3" method="get">
    <div class="col-md-3">
      <input type="date" name="tanggal" value="<?= htmlspecialchars($_GET['tanggal'] ?? '') ?>" class="form-control" placeholder="Tanggal">
    </div>
    <div class="col-md-3">
      <input type="number" name="user_id" value="<?= htmlspecialchars($_GET['user_id'] ?? '') ?>" class="form-control" placeholder="ID Atlet">
    </div>
    <div class="col-md-3">
      <input type="text" name="cabang_olahraga" value="<?= htmlspecialchars($_GET['cabang_olahraga'] ?? '') ?>" class="form-control" placeholder="Cabor">
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary" type="submit">Filter</button>
      <a class="btn btn-success" href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>">Export CSV</a>
      <a class="btn btn-secondary" href="/ppopm-absensi/admin/index.php">Kembali</a>
    </div>
  </form>
  <table class="table table-bordered table-sm">
    <thead>
      <tr>
        <th>ID</th><th>Nama</th><th>Cabor</th><th>Tanggal</th><th>Waktu</th><th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
          <td><?= htmlspecialchars($r['cabang_olahraga']) ?></td>
          <td><?= htmlspecialchars($r['tanggal']) ?></td>
          <td><?= htmlspecialchars($r['waktu_absen']) ?></td>
          <td><?= htmlspecialchars($r['keterangan']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>

