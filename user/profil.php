<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (!is_user()) { redirect('/ppopm-absensi/auth/login.php'); }

$pdo = getPDO();
$u = current_user();
$msg = null; $err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $err = 'Token CSRF tidak valid';
    } else {
        $no = sanitize_string($_POST['no_telepon'] ?? '');
        $fotoPath = null;
        if (!empty($_FILES['foto_profil']['tmp_name']) && is_uploaded_file($_FILES['foto_profil']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png'];
            if (!in_array($ext, $allowed, true)){
                $err = 'Format foto harus JPG/PNG';
            } else {
                $newName = 'profile_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }
                $dest = $destDir . $newName;
                if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $dest)) {
                    $fotoPath = '/ppopm-absensi/assets/uploads/' . $newName;
                }
            }
        }
        if (!$err) {
            if ($fotoPath) {
                $stmt = $pdo->prepare('UPDATE users SET no_telepon = ?, foto_profil = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$no, $fotoPath, (int)$u['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET no_telepon = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$no, (int)$u['id']]);
            }
            $msg = 'Profil diperbarui';
        }
    }
}

$info = $pdo->prepare('SELECT nama_lengkap,email,no_telepon,cabang_olahraga,foto_profil FROM users WHERE id = ?');
$info->execute([(int)$u['id']]);
$info = $info->fetch();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profil User</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <h3>Profil</h3>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="col-md-6">
      <label class="form-label">Nama Lengkap</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($info['nama_lengkap']) ?>" disabled>
    </div>
    <div class="col-md-6">
      <label class="form-label">Email</label>
      <input type="email" class="form-control" value="<?= htmlspecialchars($info['email']) ?>" disabled>
    </div>
    <div class="col-md-6">
      <label class="form-label">Nomor Telepon</label>
      <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($info['no_telepon']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Cabang Olahraga</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($info['cabang_olahraga']) ?>" disabled>
    </div>
    <div class="col-md-6">
      <label class="form-label">Foto Profil</label>
      <input type="file" name="foto_profil" class="form-control" accept="image/*">
      <?php if (!empty($info['foto_profil'])): ?><img src="<?= htmlspecialchars($info['foto_profil']) ?>" class="img-thumbnail mt-2" style="max-width:150px"><?php endif; ?>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Simpan</button>
      <a class="btn btn-secondary" href="/ppopm-absensi/user/index.php">Kembali</a>
    </div>
  </form>
</div>
</body>
</html>

