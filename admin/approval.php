<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_admin()) { redirect('/ppopm-absensi/auth/login.php'); }

$pdo = getPDO();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Token CSRF tidak valid';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] === 'approved' ? 'approved' : 'rejected';
        $note = sanitize_string($_POST['keterangan'] ?? '');
        $ok = process_approval($userId, (int)current_user()['id'], $action, $note);
        $msg = $ok ? 'Berhasil memproses' : 'Gagal memproses';
    }
}

$pending = $pdo->query("SELECT id,nama_lengkap,email,cabang_olahraga,created_at FROM users WHERE status='pending' ORDER BY created_at ASC")->fetchAll();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Approval Registrasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <h3>Pending Registrations</h3>
  <?php if (!empty($msg)): ?>
    <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th><th>Nama</th><th>Email</th><th>Cabor</th><th>Daftar</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pending as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
          <td><?= htmlspecialchars($p['email']) ?></td>
          <td><?= htmlspecialchars($p['cabang_olahraga']) ?></td>
          <td><?= htmlspecialchars($p['created_at']) ?></td>
          <td>
            <form method="post" class="d-flex gap-2">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="user_id" value="<?= (int)$p['id'] ?>">
              <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Keterangan">
              <button name="action" value="approved" class="btn btn-success btn-sm">Approve</button>
              <button name="action" value="rejected" class="btn btn-danger btn-sm">Reject</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <a href="/ppopm-absensi/admin/index.php" class="btn btn-secondary">Kembali</a>
  <a href="/ppopm-absensi/auth/logout.php" class="btn btn-outline-danger">Logout</a>
</div>
</body>
</html>

