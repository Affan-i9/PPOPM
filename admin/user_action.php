<?php
// admin/user_action.php
require_once '../includes/functions.php';
require_login();
checkAdmin();

date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $id = $input['id'] ?? 0;
    $pdo = getPDO();

    // 1. GET DETAIL
    if ($action === 'get_detail') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user['foto_url'] = (!empty($user['foto_profil']) && file_exists(__DIR__ . '/../' . $user['foto_profil'])) 
                ? '../' . $user['foto_profil'] 
                : "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=00ff88&color=000&bold=true";
            echo json_encode(['status' => 'success', 'data' => $user]);
        } else { echo json_encode(['status' => 'error']); }
    }

    // 2. EDIT USER
    elseif ($action === 'edit_user') {
        $sql = "UPDATE users SET nama_lengkap=?, cabang_olahraga=?, email=?, no_telepon=? WHERE id=?";
        if ($pdo->prepare($sql)->execute([sanitize($input['nama']), sanitize($input['cabor']), sanitize($input['email']), sanitize($input['telepon']), $id])) {
            echo json_encode(['status' => 'success']);
        }
    }

    // 3. MANUAL ABSEN / INPUT KEHADIRAN (UPDATE FITUR)
    elseif ($action === 'manual_absen') {
        $user_id_target = $input['user_id'];
        $status_req = $input['status']; // Hadir, Izin, Sakit
        $today = date('Y-m-d');
        $time = date('H:i');
        $admin_id = $_SESSION['user_id'];

        $cek = $pdo->prepare("SELECT id FROM absensi WHERE user_id=? AND tanggal=?");
        $cek->execute([$user_id_target, $today]);
        
        if ($cek->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Atlet ini sudah didata hari ini!']);
        } else {
            // Tentukan Keterangan
            $ket = '';
            if ($status_req === 'Izin') $ket = 'Izin';
            elseif ($status_req === 'Sakit') $ket = 'Sakit';
            else {
                // Jika Hadir, cek jam
                $ket = ($time >= '06:00' && $time <= '16:00') ? 'Manual (Tepat)' : 'Manual (Telat)';
            }
            
            $sql = "INSERT INTO absensi (user_id, tanggal, waktu_absen, keterangan, scan_by_admin_id) VALUES (?, ?, ?, ?, ?)";
            if ($pdo->prepare($sql)->execute([$user_id_target, $today, $time, $ket, $admin_id])) {
                echo json_encode(['status' => 'success']);
            }
        }
    }

    // 4. RESET PASS & TOGGLE STATUS & DELETE (Kode Lama Tetap Sama)
    elseif ($action === 'reset_pass') {
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        if ($pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $id])) echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'toggle_status') {
        $curr = $pdo->prepare("SELECT status FROM users WHERE id = ?"); $curr->execute([$id]);
        $new = ($curr->fetchColumn() === 'active') ? 'inactive' : 'active';
        if ($pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new, $id])) echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'delete_user') {
        $pdo->prepare("DELETE FROM absensi WHERE user_id = ?")->execute([$id]);
        if ($pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id])) echo json_encode(['status' => 'success']);
    }
}
?>