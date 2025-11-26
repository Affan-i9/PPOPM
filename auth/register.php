<?php
// auth/register.php
require_once '../includes/functions.php';

$alert_script = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $telepon = sanitize($_POST['telepon']);
    $cabor = sanitize($_POST['cabor']);
    
    // Logika Upload Foto & Insert DB
    // (Kode logika sama seperti sebelumnya, hanya bagian error handling diubah ke $alert_script)
    $pdo = getPDO();
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $cek->execute([$email]);

    if ($cek->rowCount() > 0) {
        $alert_script = "Swal.fire({icon: 'error', title: 'Gagal', text: 'Email sudah terdaftar!', confirmButtonColor: '#d33'});";
    } else {
        // Upload logic
        $foto_path = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/uploads/profiles/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); chmod($upload_dir, 0777); }
            
            $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $new_filename)) {
                $foto_path = 'assets/uploads/profiles/' . $new_filename;
            }
        }

        // Insert
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (nama_lengkap, email, password, no_telepon, cabang_olahraga, foto_profil, role, status) VALUES (?, ?, ?, ?, ?, ?, 'user', 'pending')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nama, $email, $hashed, $telepon, $cabor, $foto_path])) {
            $alert_script = "
                Swal.fire({
                    icon: 'success',
                    title: 'Registrasi Berhasil!',
                    text: 'Silakan tunggu persetujuan Admin.',
                    confirmButtonText: 'Ke Halaman Login',
                    confirmButtonColor: '#00ff88'
                }).then((result) => {
                    if (result.isConfirmed) { window.location.href = 'login.php'; }
                });
            ";
        } else {
            $alert_script = "Swal.fire({icon: 'error', title: 'Error', text: 'Gagal menyimpan data.', confirmButtonColor: '#d33'});";
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --neon-green: #00ff88; --neon-yellow: #ffcc00; --dark-bg: #121212; }
        body { 
            background-color: var(--dark-bg); 
            min-height: 100vh; 
            font-family: 'Segoe UI', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center;
            padding: 20px;
        }
        #particles-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .register-card {
            background: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid var(--neon-yellow);
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 600px;
            position: relative;
            z-index: 10;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            color: white;
        }
        .form-control, .form-select {
            background: rgba(0,0,0,0.4); border: 1px solid #555; color: white;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(0,0,0,0.6); border-color: var(--neon-green); color: var(--neon-green); box-shadow: none;
        }
        .btn-register {
            background: var(--neon-yellow); color: black; font-weight: bold; width: 100%; border: none; padding: 12px; border-radius: 8px; margin-top: 10px;
        }
        .btn-register:hover { background: #e6b800; box-shadow: 0 0 15px var(--neon-yellow); }
    </style>
</head>
<body>
    <div id="particles-container"></div>
    
    <div class="register-card">
        <h3 class="text-center mb-4 fw-bold" style="color:var(--neon-green)">REGISTRASI ATLET</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Nama Lengkap</label>
                    <input type="text" class="form-control" name="nama" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>No. WhatsApp</label>
                    <input type="text" class="form-control" name="telepon" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Cabang Olahraga</label>
                <select class="form-select" name="cabor" required>
                    <option value="">-- Pilih Cabor --</option>
                    <option value="Taekwondo">Taekwondo</option>
                    <option value="Pencak Silat">Pencak Silat</option>
                    <option value="Karate">Karate</option>
                    <option value="Atletik">Atletik</option>
                    <option value="Renang">Renang</option>
                    <option value="Bulu Tangkis">Bulu Tangkis</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="mb-4">
                <label>Foto Profil</label>
                <input type="file" class="form-control" name="foto" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-register">DAFTAR SEKARANG</button>
            <div class="text-center mt-3">
                <a href="login.php" class="text-white text-decoration-none small">Sudah punya akun? Login</a>
            </div>
        </form>
    </div>

    <script src="../assets/js/particles.js"></script>
    <script>
        initParticles('#particles-container', { count: 80, colors: ['#ffcc00', '#ffffff'], connectDistance: 120 });
        <?= $alert_script ?>
    </script>
</body>
</html>