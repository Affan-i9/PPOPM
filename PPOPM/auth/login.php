<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, arahkan sesuai role
if (current_user()) {
    if (is_admin()) redirect('/ppopm-absensi/admin/index.php');
    if (is_user()) redirect('/ppopm-absensi/user/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid';
    } else {
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!login($email, $password, $role)) {
            $error = 'Login gagal (cek email, password, atau status akun)';
        } else {
            if ($role === 'admin') redirect('/ppopm-absensi/admin/index.php');
            else redirect('/ppopm-absensi/user/index.php');
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login PPOPM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-header">Login PPOPM</div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="mb-3">
              <label class="form-label">Peran</label>
              <select name="role" class="form-select" required>
                <option value="user">User (Atlet)</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
          <div class="mt-3 text-center">
            <a href="/ppopm-absensi/auth/register.php">Registrasi Atlet Baru</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>

