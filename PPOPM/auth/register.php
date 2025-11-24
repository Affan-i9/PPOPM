<?php
// /var/www/html/PPOPM/auth/register.php

require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, redirect
if (current_user()) {
    redirect('../user/index.php');
}

$error = '';
$success = '';

// Proses Registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $telepon = sanitize($_POST['telepon']);
    $cabor = sanitize($_POST['cabor']);
    
    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($password) || empty($cabor)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        $pdo = getPDO();
        
        // Cek apakah email sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email sudah terdaftar. Silakan login.";
        } else {
            // Handle Upload Foto
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../assets/uploads/profiles/';
                
                // Buat folder jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                
                if (in_array($file_ext, $allowed)) {
                    // Nama file unik: time_random.ext
                    $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    $dest_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest_path)) {
                        // Simpan path relatif untuk database
                        $foto_path = 'assets/uploads/profiles/' . $new_filename;
                    } else {
                        $error = "Gagal mengupload foto.";
                    }
                } else {
                    $error = "Format foto harus JPG, JPEG, atau PNG.";
                }
            }

            // Jika tidak ada error upload, lanjutkan simpan ke DB
            if (empty($error)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert User Baru (Status default: pending)
                $sql = "INSERT INTO users (nama_lengkap, email, password, no_telepon, cabang_olahraga, foto_profil, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'user', 'pending')";
                $stmt = $pdo->prepare($sql);
                
                try {
                    $stmt->execute([$nama, $email, $hashed_password, $telepon, $cabor, $foto_path]);
                    $success = "Registrasi berhasil! Akun Anda sedang menunggu persetujuan Admin. Silakan tunggu atau hubungi pelatih.";
                } catch (PDOException $e) {
                    $error = "Terjadi kesalahan database: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Atlet - PPOPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .register-card {
            max-width: 600px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        .register-header {
            background: #198754; /* Warna hijau sukses */
            color: white;
            text-align: center;
            padding: 20px;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="card register-card">
        <div class="register-header">
            FORM REGISTRASI ATLET
        </div>
        <div class="card-body p-4">
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?= $success ?>
                    <hr>
                    <a href="login.php" class="btn btn-outline-success btn-sm w-100">Ke Halaman Login</a>
                </div>
            <?php else: ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama" required placeholder="Sesuai KTP/Absen">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nomor Telepon/WA</label>
                        <input type="text" class="form-control" name="telepon" placeholder="08xxxxxxxxxx">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cabang Olahraga (Cabor)</label>
                    <select class="form-select" name="cabor" required>
                        <option value="">-- Pilih Cabor --</option>
                        <option value="Taekwondo">Taekwondo</option>
                        <option value="Pencak Silat">Pencak Silat</option>
                        <option value="Karate">Karate</option>
                        <option value="Atletik">Atletik</option>
                        <option value="Renang">Renang</option>
                        <option value="Bulu Tangkis">Bulu Tangkis</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto Profil (Wajib)</label>
                    <input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png" required>
                    <div class="form-text">Format: JPG/PNG. Foto wajah jelas untuk identifikasi.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required placeholder="email@contoh.com">
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required placeholder="Minimal 6 karakter">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Daftar Sekarang</button>
                    <a href="login.php" class="btn btn-light text-muted">Sudah punya akun? Login</a>
                </div>
            </form>
            <?php endif; ?>
            
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>