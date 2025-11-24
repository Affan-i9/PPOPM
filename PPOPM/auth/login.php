<?php
// /var/www/html/PPOPM/auth/login.php

require_once __DIR__ . '/../includes/functions.php';

// Jika user sudah login, langsung redirect sesuai role
$current_id = current_user();
if ($current_id) {
    // Ambil role user untuk redirect yang tepat
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$current_id]);
    $user = $stmt->fetch();
    
    if ($user['role'] === 'admin') {
        redirect('../admin/index.php');
    } else {
        redirect('../user/index.php');
    }
}

$error = '';

// Proses Form Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email dan Password wajib diisi.";
    } else {
        $pdo = getPDO();
        // Cari user berdasarkan email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Cek Status Akun
            if ($user['status'] === 'pending') {
                $error = "Akun Anda masih menunggu persetujuan Admin.";
            } elseif ($user['status'] === 'rejected' || $user['status'] === 'inactive') {
                $error = "Akun Anda dinonaktifkan atau ditolak.";
            } else {
                // Login Sukses
                if (session_status() === PHP_SESSION_NONE) session_start();
                session_regenerate_id(true); // Keamanan session

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama_lengkap'];

                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    redirect('../admin/index.php');
                } else {
                    redirect('../user/index.php');
                }
            }
        } else {
            $error = "Email atau Password salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PPOPM Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        .login-header {
            background: #0d6efd;
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .btn-primary {
            width: 100%;
            padding: 10px;
        }
    </style>
</head>
<body>

    <div class="card login-card">
        <div class="login-header">
            LOGIN PPOPM
        </div>
        <div class="card-body p-4">
            
            <?php if ($error): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="nama@email.com" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                </div>
                <button type="submit" class="btn btn-primary">Masuk</button>
            </form>

            <div class="text-center mt-3">
                <small>Belum punya akun? <a href="register.php">Daftar Atlet di sini</a></small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>