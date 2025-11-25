<?php
// auth/login.php
require_once '../includes/functions.php';

$alert_script = ""; // Variabel untuk menyimpan script SweetAlert

// Cek session
$current_id = current_user();
if ($current_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$current_id]);
    $user = $stmt->fetch();
    redirect($user['role'] === 'admin' ? '../admin/index.php' : '../user/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        // Simpan pesan error untuk SweetAlert
        $alert_script = "Swal.fire({icon: 'warning', title: 'Oops...', text: 'Email dan Password wajib diisi!', confirmButtonColor: '#ffcc00'});";
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $alert_script = "Swal.fire({icon: 'info', title: 'Menunggu Persetujuan', text: 'Akun Anda sedang diverifikasi Admin.', confirmButtonColor: '#00ff88'});";
            } elseif ($user['status'] === 'rejected' || $user['status'] === 'inactive') {
                $alert_script = "Swal.fire({icon: 'error', title: 'Akses Ditolak', text: 'Akun Anda dinonaktifkan.', confirmButtonColor: '#d33'});";
            } else {
                // Login Sukses
                if (session_status() === PHP_SESSION_NONE) session_start();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama'] = $user['nama_lengkap'];

                // Redirect dengan script JS biar smooth
                $redirect_url = ($user['role'] === 'admin') ? '../admin/index.php' : '../user/index.php';
                $alert_script = "
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Berhasil!',
                        text: 'Selamat datang, " . htmlspecialchars($user['nama_lengkap']) . "',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '$redirect_url';
                    });
                ";
            }
        } else {
            $alert_script = "Swal.fire({icon: 'error', title: 'Gagal Masuk', text: 'Email atau Password salah.', confirmButtonColor: '#d33'});";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pro - PPOPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --neon-green: #00ff88; --neon-yellow: #ffcc00; --dark-bg: #121212; }
        body { 
            background-color: var(--dark-bg); 
            height: 100vh; 
            overflow: hidden; 
            font-family: 'Segoe UI', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        
        /* Container Partikel */
        #particles-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }

        /* Kartu Glassmorphism */
        .login-card {
            background: rgba(30, 30, 30, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 2px solid var(--neon-green);
            border-bottom: 2px solid var(--neon-yellow);
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10; /* Di atas partikel */
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        .login-title { color: white; font-weight: 700; text-align: center; margin-bottom: 30px; letter-spacing: 2px; }
        .form-control {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #444;
            color: var(--neon-green);
            border-radius: 10px;
            padding: 12px;
        }
        .form-control:focus {
            background: rgba(0, 0, 0, 0.8);
            border-color: var(--neon-yellow);
            color: var(--neon-yellow);
            box-shadow: 0 0 10px rgba(255, 204, 0, 0.3);
        }
        .btn-login {
            background: linear-gradient(45deg, var(--neon-green), #00cc6a);
            border: none;
            color: #000;
            font-weight: bold;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-login:hover { transform: scale(1.02); box-shadow: 0 0 20px var(--neon-green); }
        .register-link { color: #aaa; text-align: center; display: block; margin-top: 20px; text-decoration: none; font-size: 0.9rem; }
        .register-link:hover { color: var(--neon-yellow); }
    </style>
</head>
<body>

    <div id="particles-container"></div>

    <div class="login-card">
        <h2 class="login-title">LOGIN PPOPM</h2>
        <form method="POST">
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email Atlet" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-login">MASUK SISTEM</button>
            <a href="register.php" class="register-link">Belum punya akun? Daftar Atlet</a>
        </form>
    </div>

    <script src="../assets/js/particles.js"></script>
    <script>
        // Init partikel dengan warna tema kita
        initParticles('#particles-container', {
            count: 100,
            colors: ['#00ff88', '#ffcc00', '#ffffff'], // Hijau, Kuning, Putih
            connectDistance: 150
        });

        // Eksekusi SweetAlert dari PHP
        <?= $alert_script ?>
    </script>
</body>
</html>