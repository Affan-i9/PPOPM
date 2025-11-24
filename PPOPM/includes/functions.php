<?php
// Fungsi utilitas: session, CSRF, auth, RBAC, validasi input, dan operasi inti.

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Mulai sesi dengan opsi aman
function start_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $params = [
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Lax',
        ];
        session_start($params);
        session_regenerate_id(true);
    }
}

start_secure_session();

// Inisialisasi skema database jika belum tersedia
function ensure_schema(): void {
    $pdo = getPDO();
    // Tabel users
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_lengkap VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_telepon VARCHAR(15),
  cabang_olahraga VARCHAR(50),
  foto_profil VARCHAR(255),
  qr_code VARCHAR(255) UNIQUE,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  status ENUM('pending','active','inactive','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
SQL);
    // Tabel absensi
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS absensi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tanggal DATE NOT NULL,
  waktu_absen TIME NOT NULL,
  timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  keterangan TEXT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  scan_by_admin_id INT NULL,
  CONSTRAINT fk_absensi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_absensi_admin FOREIGN KEY (scan_by_admin_id) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uniq_user_date (user_id, tanggal)
);
SQL);
    // Tabel approval_log
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS approval_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  admin_id INT NOT NULL,
  action ENUM('approved','rejected') NOT NULL,
  keterangan TEXT,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_approval_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_approval_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL);
}

ensure_schema();

// CSRF token utilities
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $tokenFromRequest): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $tokenFromRequest);
}

// Helpers
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function sanitize_string(string $v): string {
    return trim(filter_var($v, FILTER_SANITIZE_STRING));
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        redirect('/ppopm-absensi/auth/login.php');
    }
}

function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function is_user(): bool { return (current_user()['role'] ?? '') === 'user'; }

// Auth
function login(string $email, string $password, string $role): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();
    if (!$user) { return false; }
    if (!password_verify($password, $user['password'])) { return false; }
    if ($user['status'] === 'pending' || $user['status'] === 'inactive' || $user['status'] === 'rejected') { return false; }
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'nama_lengkap' => $user['nama_lengkap'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'],
        'qr_code' => $user['qr_code'] ?? null,
    ];
    session_regenerate_id(true);
    return true;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// Registrasi user baru (status pending)
function register_user(array $data, ?array $file): array {
    $pdo = getPDO();
    $errors = [];

    $nama = sanitize_string($data['nama_lengkap'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $no_telp = sanitize_string($data['no_telepon'] ?? '');
    $cabor = sanitize_string($data['cabang_olahraga'] ?? '');

    if ($nama === '' || $email === '' || $password === '' || $cabor === '') {
        $errors[] = 'Semua field wajib diisi';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }

    // Cek duplikasi email
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) { $errors[] = 'Email sudah terdaftar'; }

    // Upload foto profil (opsional)
    $fotoPath = null;
    if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Format foto harus JPG/PNG';
        } else {
            $newName = 'profile_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }
            $dest = $destDir . $newName;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Gagal upload foto';
            } else {
                $fotoPath = '/ppopm-absensi/assets/uploads/' . $newName;
            }
        }
    }

    if ($errors) { return ['ok' => false, 'errors' => $errors]; }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (nama_lengkap,email,password,no_telepon,cabang_olahraga,foto_profil,qr_code,role,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $role = 'user';
    $status = 'pending';
    $qr = null; // akan diisi saat approval admin
    $stmt->execute([$nama,$email,$hash,$no_telp,$cabor,$fotoPath,$qr,$role,$status]);
    return ['ok' => true];
}

// Generate kode QR unik (string) saat approval
function generate_qr_token(int $userId): string {
    return 'PPOPM:' . $userId . ':' . bin2hex(random_bytes(8));
}

// Approval/Reject oleh admin
function process_approval(int $userId, int $adminId, string $action, ?string $note = null): bool {
    $pdo = getPDO();
    $pdo->beginTransaction();
    try {
        if ($action === 'approved') {
            $qr = generate_qr_token($userId);
            $stmt = $pdo->prepare('UPDATE users SET status = "active", qr_code = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$qr, $userId]);
        } elseif ($action === 'rejected') {
            $stmt = $pdo->prepare('UPDATE users SET status = "rejected", updated_at = NOW() WHERE id = ?');
            $stmt->execute([$userId]);
        } else {
            throw new RuntimeException('Aksi tidak dikenal');
        }

        $log = $pdo->prepare('INSERT INTO approval_log (user_id, admin_id, action, keterangan, created_at) VALUES (?,?,?,?,NOW())');
        $log->execute([$userId, $adminId, $action, $note]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }
}

// Validasi absensi dan pencatatan
function can_checkin_today(int $userId): bool {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT 1 FROM absensi WHERE user_id = ? AND tanggal = CURDATE() LIMIT 1');
    $stmt->execute([$userId]);
    return $stmt->fetch() ? false : true;
}

function record_attendance(int $userId, int $adminId, ?string $keterangan = null, ?float $lat = null, ?float $lng = null): array {
    $pdo = getPDO();
    // Validasi status user
    $s = $pdo->prepare('SELECT status FROM users WHERE id = ?');
    $s->execute([$userId]);
    $u = $s->fetch();
    if (!$u || $u['status'] !== 'active') {
        return ['ok' => false, 'msg' => 'User tidak aktif'];
    }
    if (!can_checkin_today($userId)) {
        return ['ok' => false, 'msg' => 'Sudah absen hari ini'];
    }

    $stmt = $pdo->prepare('INSERT INTO absensi (user_id, tanggal, waktu_absen, timestamp, keterangan, latitude, longitude, scan_by_admin_id) VALUES (?, CURDATE(), CURTIME(), NOW(), ?, ?, ?, ?)');
    $stmt->execute([$userId, $keterangan, $lat, $lng, $adminId]);
    return ['ok' => true, 'msg' => 'Absensi berhasil'];
}

// Statistik dasar untuk dashboard admin
function admin_stats(): array {
    $pdo = getPDO();
    $totalAtlet = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    $hadirHariIni = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = CURDATE()")
        ->fetchColumn();
    $pendingApproval = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")
        ->fetchColumn();
    return [
        'total_atlet' => $totalAtlet,
        'hadir_hari_ini' => $hadirHariIni,
        'pending_approval' => $pendingApproval,
    ];
}

// Export CSV untuk laporan
function export_csv_attendance(PDO $pdo, array $filters = []): void {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_absensi.csv"');
    $sql = 'SELECT a.id, u.nama_lengkap, u.cabang_olahraga, a.tanggal, a.waktu_absen, a.keterangan, a.latitude, a.longitude FROM absensi a JOIN users u ON a.user_id = u.id WHERE 1=1';
    $params = [];
    if (!empty($filters['tanggal'])) { $sql .= ' AND a.tanggal = ?'; $params[] = $filters['tanggal']; }
    if (!empty($filters['user_id'])) { $sql .= ' AND a.user_id = ?'; $params[] = (int)$filters['user_id']; }
    if (!empty($filters['cabang_olahraga'])) { $sql .= ' AND u.cabang_olahraga = ?'; $params[] = $filters['cabang_olahraga']; }
    $sql .= ' ORDER BY a.tanggal DESC, a.waktu_absen DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Nama Lengkap','Cabor','Tanggal','Waktu','Keterangan','Lat','Lng']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Helper untuk format persen kehadiran user
function user_attendance_summary(int $userId): array {
    $pdo = getPDO();
    $bulan = date('Y-m');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE user_id = ? AND DATE_FORMAT(tanggal,'%Y-%m') = ?");
    $stmt->execute([$userId, $bulan]);
    $totalHadir = (int)$stmt->fetchColumn();
    // Catatan: total hari kerja tidak diketahui, tampilkan total hadir saja
    return ['bulan' => $bulan, 'total_hadir' => $totalHadir];
}

?>
