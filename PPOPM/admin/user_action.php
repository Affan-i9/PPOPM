<?php
// admin/user_action.php
require_once '../includes/functions.php';
require_login();
checkAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $id = $input['id'] ?? 0;
    $pdo = getPDO();

    // 1. Ambil Detail Biodata
    if ($action === 'get_detail') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Fix path foto untuk ditampilkan
            $user['foto_url'] = (!empty($user['foto_profil']) && file_exists(__DIR__ . '/../' . $user['foto_profil'])) 
                ? '../' . $user['foto_profil'] 
                : "https://ui-avatars.com/api/?name=" . urlencode($user['nama_lengkap']) . "&background=00ff88&color=000&bold=true";
            
            echo json_encode(['status' => 'success', 'data' => $user]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
        }
    }

    // 2. Ubah Status (Aktif/Nonaktif)
    elseif ($action === 'toggle_status') {
        // Cek status saat ini dulu
        $curr = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $curr->execute([$id]);
        $current_status = $curr->fetchColumn();

        // Jika active -> jadi inactive. Jika inactive/rejected -> jadi active.
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $id])) {
            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal update database']);
        }
    }
}
?>