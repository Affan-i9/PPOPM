<?php
// admin/index.php
require_once '../includes/functions.php';
require_login();
checkAdmin();    

$pdo = getPDO();

// --- DATA STATISTIK KARTU ---
$total_atlet = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = CURDATE()")->fetchColumn();

// --- DATA PENDING APPROVAL ---
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

// --- DATA SELURUH ATLET (Untuk Tabel Manajemen) ---
// Kita ambil status active dan inactive saja (rejected/pending dipisah)
$all_users = $pdo->query("SELECT * FROM users WHERE role='user' AND status IN ('active', 'inactive') ORDER BY nama_lengkap ASC")->fetchAll();

// --- DATA UNTUK GRAFIK (7 Hari Terakhir) ---
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($date));
    // Hitung jumlah atlet unik yang hadir pada tanggal tersebut
    $count = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$date'")->fetchColumn();
    
    $chart_labels[] = $label;
    $chart_data[] = $count;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pro Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-yellow: #ffcc00;
            --neon-red: #ff0055;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
        }
        body { background-color: var(--dark-bg); color: #e0e0e0; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        
        /* Navbar */
        .navbar { background: rgba(30, 30, 30, 0.9); border-bottom: 2px solid var(--neon-green); backdrop-filter: blur(10px); }
        .navbar-brand { font-weight: bold; color: var(--neon-green) !important; letter-spacing: 2px; }

        /* Kartu Statistik */
        .stat-card {
            background: var(--card-bg); border: 1px solid #333; border-radius: 15px; padding: 20px;
            transition: 0.3s; position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--neon-green); box-shadow: 0 0 20px rgba(0, 255, 136, 0.2); }
        .stat-card h3 { font-size: 2.5rem; font-weight: bold; margin: 0; }
        
        /* Tombol Scan */
        .btn-scan {
            background: linear-gradient(45deg, var(--neon-green), #00cc6a); color: #000; font-weight: bold;
            border: none; padding: 10px 30px; border-radius: 50px; box-shadow: 0 0 15px var(--neon-green);
            text-decoration: none; transition: 0.3s;
        }
        .btn-scan:hover { transform: scale(1.05); box-shadow: 0 0 25px var(--neon-green); color: #000; }

        /* Tabel Custom */
        .card-table { background: var(--card-bg); border: 1px solid #333; border-radius: 15px; overflow: hidden; }
        .table-dark-custom { --bs-table-bg: transparent; color: #ccc; }
        .table-dark-custom th { background: #2a2a2a; color: var(--neon-yellow); border: none; text-transform: uppercase; font-size: 0.85rem; }
        .table-dark-custom td { border-bottom: 1px solid #333; vertical-align: middle; }
        
        /* Status Badge */
        .badge-active { background: rgba(0, 255, 136, 0.2); color: var(--neon-green); border: 1px solid var(--neon-green); }
        .badge-inactive { background: rgba(255, 0, 85, 0.2); color: var(--neon-red); border: 1px solid var(--neon-red); }

        /* Modal Custom */
        .modal-content { background: #222; border: 1px solid var(--neon-green); color: white; }
        .modal-header { border-bottom: 1px solid #444; }
        .modal-footer { border-top: 1px solid #444; }
        .detail-label { color: var(--neon-yellow); font-size: 0.9rem; margin-bottom: 2px; }
        .detail-value { font-size: 1.1rem; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-shield-alt"></i> PPOPM ADMIN</a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3 d-none d-md-block">Administrator</span>
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm border-0"><i class="fas fa-power-off"></i></a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px; margin-bottom: 50px;">
    
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-white">Dashboard <span style="color:var(--neon-yellow)">Overview</span></h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="scanner.php" class="btn-scan">
                <i class="fas fa-qrcode fa-spin hover-stop"></i> SCAN ABSEN
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card" style="color: #00d4ff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div><span class="small text-uppercase opacity-75">Total Atlet</span><h3><?= $total_atlet ?></h3></div>
                    <i class="fas fa-users fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card" style="color: var(--neon-green);">
                <div class="d-flex justify-content-between align-items-center">
                    <div><span class="small text-uppercase opacity-75">Hadir Hari Ini</span><h3><?= $hadir_today ?></h3></div>
                    <i class="fas fa-check-circle fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card" style="color: var(--neon-yellow);">
                <div class="d-flex justify-content-between align-items-center">
                    <div><span class="small text-uppercase opacity-75">Pending Approval</span><h3><?= $pending ?></h3></div>
                    <i class="fas fa-clock fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card-table p-3 h-100">
                <h5 class="text-white mb-3"><i class="fas fa-chart-line text-info"></i> Statistik Kehadiran (7 Hari)</h5>
                <canvas id="attendanceChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card-table h-100">
                <div class="p-3 border-bottom border-secondary bg-dark">
                    <h6 class="text-warning mb-0"><i class="fas fa-user-clock"></i> Menunggu Persetujuan</h6>
                </div>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-dark-custom mb-0">
                        <tbody>
                            <?php if (count($pending_users) > 0): ?>
                                <?php foreach ($pending_users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($u['cabang_olahraga']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <a href="approve.php?id=<?= $u['id'] ?>&action=approve" class="btn btn-sm btn-success mb-1"><i class="fas fa-check"></i></a>
                                        <a href="approve.php?id=<?= $u['id'] ?>&action=reject" class="btn btn-sm btn-danger mb-1"><i class="fas fa-times"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td class="text-center text-muted py-4">Tidak ada data pending.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card-table mt-2">
        <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0"><i class="fas fa-users-cog" style="color:var(--neon-green)"></i> Manajemen Data Atlet</h5>
            <span class="badge bg-secondary"><?= count($all_users) ?> Terdaftar</span>
        </div>
        <div class="table-responsive">
            <table class="table table-dark-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Nama Lengkap</th>
                        <th>Cabor</th>
                        <th>Email / Telepon</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_users) > 0): ?>
                        <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                            <td><span class="badge bg-dark border border-secondary"><?= htmlspecialchars($user['cabang_olahraga']) ?></span></td>
                            <td>
                                <div class="small"><?= htmlspecialchars($user['email']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($user['no_telepon']) ?></div>
                            </td>
                            <td>
                                <?php if($user['status'] == 'active'): ?>
                                    <span class="badge badge-active" id="status-badge-<?= $user['id'] ?>">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive" id="status-badge-<?= $user['id'] ?>">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-info me-1" onclick="viewUser(<?= $user['id'] ?>)" title="Lihat Biodata">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if($user['status'] == 'active'): ?>
                                    <button class="btn btn-sm btn-danger" id="btn-toggle-<?= $user['id'] ?>" onclick="toggleStatus(<?= $user['id'] ?>)" title="Nonaktifkan Akun">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success" id="btn-toggle-<?= $user['id'] ?>" onclick="toggleStatus(<?= $user['id'] ?>)" title="Aktifkan Akun">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">Belum ada atlet aktif.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-id-card"></i> Biodata Atlet</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modal_foto" src="" class="rounded-circle mb-3 border border-3 border-success" width="120" height="120" style="object-fit: cover;">
                
                <h3 id="modal_nama" class="fw-bold mb-1"></h3>
                <span id="modal_cabor" class="badge bg-warning text-dark mb-4"></span>
                
                <div class="row text-start px-3">
                    <div class="col-6 mb-3">
                        <div class="detail-label">Email</div>
                        <div class="detail-value" id="modal_email"></div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="detail-label">No. Telepon</div>
                        <div class="detail-value" id="modal_telepon"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="detail-label">Kode QR</div>
                        <div class="detail-value text-break font-monospace text-success" id="modal_qr"></div>
                    </div>
                    <div class="col-12">
                        <div class="detail-label">Bergabung Sejak</div>
                        <div class="detail-value" id="modal_join"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/particles.js"></script>

<script>
    // 1. Init Particles
    initParticles('#particles-container', { count: 80, colors: ['#00ff88', '#0099ff'], speed: 0.5 });

    // 2. Init Grafik Chart.js
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Jumlah Kehadiran',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#00ff88',
                backgroundColor: 'rgba(0, 255, 136, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#333' }, ticks: { color: '#aaa', stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: '#aaa' } }
            }
        }
    });

    // 3. Fungsi Lihat Biodata (AJAX)
    function viewUser(id) {
        fetch('user_action.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'get_detail', id: id })
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                const u = res.data;
                document.getElementById('modal_foto').src = u.foto_url;
                document.getElementById('modal_nama').innerText = u.nama_lengkap;
                document.getElementById('modal_cabor').innerText = u.cabang_olahraga;
                document.getElementById('modal_email').innerText = u.email;
                document.getElementById('modal_telepon').innerText = u.no_telepon;
                document.getElementById('modal_qr').innerText = u.qr_code || '-';
                document.getElementById('modal_join').innerText = u.created_at;
                
                new bootstrap.Modal(document.getElementById('userModal')).show();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        });
    }

    // 4. Fungsi Toggle Status (Aktif/Nonaktif)
    function toggleStatus(id) {
        Swal.fire({
            title: 'Konfirmasi',
            text: "Ubah status akun atlet ini?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffcc00',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Ubah!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('user_action.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'toggle_status', id: id })
                })
                .then(res => res.json())
                .then(res => {
                    if(res.status === 'success') {
                        // Update UI tanpa reload
                        const badge = document.getElementById('status-badge-' + id);
                        const btn = document.getElementById('btn-toggle-' + id);
                        
                        if(res.new_status === 'active') {
                            badge.className = 'badge badge-active';
                            badge.innerText = 'Active';
                            btn.className = 'btn btn-sm btn-danger';
                            btn.title = 'Nonaktifkan Akun';
                        } else {
                            badge.className = 'badge badge-inactive';
                            badge.innerText = 'Inactive';
                            btn.className = 'btn btn-sm btn-success';
                            btn.title = 'Aktifkan Akun';
                        }
                        Swal.fire('Sukses', 'Status berhasil diubah', 'success');
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                });
            }
        })
    }
</script>

</body>
</html>