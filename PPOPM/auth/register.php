<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$success = false; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid';
    } else {
        $res = register_user($_POST, $_FILES['foto_profil'] ?? null);
        if ($res['ok']) { $success = true; }
        else { $errors = $res['errors'] ?? ['Registrasi gagal']; }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registrasi Atlet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card shadow">
        <div class="card-header">Form Registrasi Atlet</div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success">Registrasi berhasil. Status: Pending. Menunggu approval admin.</div>
          <?php endif; ?>
          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Nomor Telepon</label>
                <input type="text" name="no_telepon" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Cabang Olahraga</label>
                <input type="text" name="cabang_olahraga" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Foto Profil</label>
                <input type="file" name="foto_profil" class="form-control" accept="image/*">
              </div>
            </div>
            <div class="mt-3">
              <button class="btn btn-success" type="submit">Daftar</button>
              <a class="btn btn-outline-secondary" href="/ppopm-absensi/auth/login.php">Kembali ke Login</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>

