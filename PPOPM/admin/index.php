<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) { redirect('/ppopm-absensi/auth/login.php'); }

$stats = admin_stats();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">PPOPM Admin</a>
    <div class="d-flex">
      <a class="btn btn-outline-light" href="/ppopm-absensi/admin/approval.php">Approval</a>
      <a class="btn btn-outline-light ms-2" href="/ppopm-absensi/admin/scanner.php">Scanner</a>
      <a class="btn btn-outline-light ms-2" href="/ppopm-absensi/admin/laporan.php">Laporan</a>
      <a class="btn btn-danger ms-2" href="/ppopm-absensi/auth/logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container py-4">
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5>Total Atlet</h5>
          <p class="display-6"><?= (int)$stats['total_atlet'] ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5>Hadir Hari Ini</h5>
          <p class="display-6"><?= (int)$stats['hadir_hari_ini'] ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5>Pending Approval</h5>
          <p class="display-6"><?= (int)$stats['pending_approval'] ?></p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>

