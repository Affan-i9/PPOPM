<?php
// admin/index.php
require_once '../includes/functions.php';
require_login();
checkAdmin();

// Initial Data Load (Agar tidak kosong saat loading awal)
$pdo = getPDO();
$total_atlet = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = CURDATE()")->fetchColumn();
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$all_users = $pdo->query("SELECT * FROM users WHERE role='user' AND status IN ('active', 'inactive') ORDER BY nama_lengkap ASC")->fetchAll();

// Chart Data Init
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    $chart_data[] = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$date'")->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PPOPM</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* --- TEMA KONTRAS TINGGI --- */
        :root {
            --neon-green: #00ff88;
            --neon-yellow: #ffcc00;
            --neon-red: #ff0055;
            --neon-blue: #00d4ff;
            --dark-bg: #050505;
            --card-bg: #101010;
            --text-main: #ffffff;
            --text-dim: #cccccc; /* Abu terang, BUKAN gelap */
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-main);
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(10, 10, 10, 0.95);
            border-bottom: 2px solid var(--neon-green);
        }
        .navbar-brand {
            color: var(--neon-green) !important;
            font-weight: bold;
            letter-spacing: 2px;
        }

        /* Kartu Statistik */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            transition: 0.3s;
        }
        .stat-card:hover {
            border-color: var(--neon-green);
            transform: translateY(-5px);
        }
        .stat-card h3 { font-size: 2.5rem; font-weight: bold; margin: 0; color: white; }
        .stat-card .small { color: var(--text-dim) !important; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase; }

        /* Tombol Scan */
        .btn-scan {
            background: var(--neon-green);
            color: black;
            font-weight: bold;
            border-radius: 50px;
            padding: 10px 30px;
            text-decoration: none;
            box-shadow: 0 0 15px var(--neon-green);
        }
        .btn-scan:hover { background: #fff; color: black; box-shadow: 0 0 25px #fff; }

        /* Tabel & Container */
        .card-table {
            background: var(--card-bg);
            border: 1px solid #333;
            border-radius: 15px;
            overflow: hidden;
        }
        
        /* Tabel Custom - Paksa Putih */
        .table-dark-custom {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-main);
        }
        .table-dark-custom th {
            background: #1a1a1a;
            color: var(--neon-yellow);
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            border-bottom: 2px solid #444;
        }
        .table-dark-custom td {
            padding: 15px;
            border-bottom: 1px solid #333;
            vertical-align: middle;
            color: white !important; /* Paksa Warna Putih */
        }
        
        /* Helper */
        .text-dim { color: var(--text-dim) !important; }
        .badge-active { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); border: 1px solid var(--neon-green); padding: 5px 12px; }
        .badge-inactive { background: rgba(255, 0, 85, 0.15); color: var(--neon-red); border: 1px solid var(--neon-red); padding: 5px 12px; }

        /* Modal */
        .modal-content {
            background-color: #1a1a1a;
            border: 1px solid var(--neon-green);
            color: white;
        }
        .modal-header, .modal-footer { border-color: #333; }
        .btn-close-white { filter: invert(1); }
        .detail-label { color: var(--neon-yellow); font-size: 0.9rem; margin-bottom: 2px; }
        .detail-value { font-size: 1.1rem; font-weight: bold; color: white; margin-bottom: 15px; }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-shield-alt"></i> PPOPM ADMIN</a>
        <div class="d-flex align-items-center">
            <div class="me-3 text-dim small d-none d-md-block"><i class="fas fa-circle text-success fa-xs fa-beat"></i> Live System</div>
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-circle">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px; margin-bottom: 100px;">
    
    <div class="row align-items-center mb-4">
        <div class="col-8">
            <h2 class="fw-bold text-white">Dashboard</h2>
            <p class="text-dim mb-0">Overview Realtime</p>
        </div>
        <div class="col-4 text-end">
            <a href="scanner.php" class="btn-scan">
                <i class="fas fa-qrcode"></i> <span class="d-none d-md-inline">SCAN</span>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small">Total Atlet</span>
                        <h3 id="stat-total"><?= $total_atlet ?></h3>
                    </div>
                    <i class="fas fa-users fa-3x" style="color: var(--neon-blue); opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small">Hadir Hari Ini</span>
                        <h3 id="stat-hadir"><?= $hadir_today ?></h3>
                    </div>
                    <i class="fas fa-check-circle fa-3x" style="color: var(--neon-green); opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small">Pending</span>
                        <h3 id="stat-pending"><?= $pending ?></h3>
                    </div>
                    <i class="fas fa-clock fa-3x" style="color: var(--neon-yellow); opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card-table p-3 h-100">
                <h5 class="text-white mb-3 ms-2"><i class="fas fa-chart-line text-info"></i> Grafik 7 Hari</h5>
                <div style="height: 250px; width: 100%;">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card-table h-100">
                <div class="p-3 bg-dark border-bottom border-secondary">
                    <h6 class="text-warning mb-0 fw-bold"><i class="fas fa-user-clock"></i> Approval Pending</h6>
                </div>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table-dark-custom">
                        <tbody id="table-pending-body">
                            <?php if (count($pending_users) > 0): ?>
                                <?php foreach ($pending_users as $u): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                        <small class="text-dim"><?= htmlspecialchars($u['cabang_olahraga']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <a href="approve.php?id=<?= $u['id'] ?>&action=approve" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
                                        <a href="approve.php?id=<?= $u['id'] ?>&action=reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td class="text-center text-dim py-5">Tidak ada permintaan baru.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card-table mt-2">
        <div class="p-3 bg-dark border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0"><i class="fas fa-users-cog" style="color:var(--neon-green)"></i> Data Atlet</h5>
            <span class="badge bg-secondary"><?= count($all_users) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table-dark-custom">
                <thead>
                    <tr>
                        <th class="ps-4">Nama</th>
                        <th>Cabor</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_users) > 0): ?>
                        <?php foreach ($all_users as $user): ?>
                        <tr id="row-<?= $user['id'] ?>">
                            <td class="ps-4">
                                <span class="fw-bold"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-white"><?= htmlspecialchars($user['cabang_olahraga']) ?></span>
                            </td>
                            <td>
                                <div class="small text-white"><?= htmlspecialchars($user['email']) ?></div>
                                <div class="small text-dim"><?= htmlspecialchars($user['no_telepon']) ?></div>
                            </td>
                            <td>
                                <?php if($user['status'] == 'active'): ?>
                                    <span class="badge badge-active" id="status-badge-<?= $user['id'] ?>">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive" id="status-badge-<?= $user['id'] ?>">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-info text-white me-1" onclick="viewUser(<?= $user['id'] ?>)"><i class="fas fa-eye"></i></button>
                                <?php if($user['status'] == 'active'): ?>
                                    <button class="btn btn-sm btn-warning me-1" id="btn-toggle-<?= $user['id'] ?>" onclick="toggleStatus(<?= $user['id'] ?>)"><i class="fas fa-ban"></i></button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success me-1" id="btn-toggle-<?= $user['id'] ?>" onclick="toggleStatus(<?= $user['id'] ?>)"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-dim">Belum ada data atlet.</td></tr>
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
                <h5 class="modal-title fw-bold">Detail Biodata</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modal_foto" src="" class="rounded-circle mb-3 border border-3 border-success" width="100" height="100" style="object-fit: cover; background: #000;">
                <h3 id="modal_nama" class="fw-bold mb-1 text-white"></h3>
                <span id="modal_cabor" class="badge bg-warning text-dark mb-4"></span>
                <div class="row text-start px-3">
                    <div class="col-12 mb-3 border-bottom border-secondary pb-2">
                        <div class="detail-label">Email</div>
                        <div class="detail-value" id="modal_email"></div>
                    </div>
                    <div class="col-12 mb-3 border-bottom border-secondary pb-2">
                        <div class="detail-label">Telepon</div>
                        <div class="detail-value" id="modal_telepon"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="detail-label">QR Code</div>
                        <div class="detail-value font-monospace text-success text-break" id="modal_qr"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/particles.js"></script>
<script>
    initParticles('#particles-container', { count: 60, colors: ['#00ff88', '#0099ff'], speed: 0.4 });

    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Hadir',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#00ff88', backgroundColor: 'rgba(0, 255, 136, 0.1)', borderWidth: 2, tension: 0.3, fill: true, pointBackgroundColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#333' }, ticks: { color: '#ccc', stepSize: 1 } }, x: { grid: { display: false }, ticks: { color: '#ccc' } } }
        }
    });

    // --- REALTIME FETCHING (Mencegah Error 404) ---
    setInterval(() => {
        fetch('../api/admin_stats.php')
        .then(response => {
            if (!response.ok) throw new Error('API Not Found'); // Cek jika 404
            return response.json();
        })
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('stat-total').innerText = data.total_atlet;
                document.getElementById('stat-hadir').innerText = data.hadir_today;
                document.getElementById('stat-pending').innerText = data.pending_count;
                document.getElementById('table-pending-body').innerHTML = data.pending_html;
                
                const currentData = myChart.data.datasets[0].data;
                const newData = data.chart_data;
                if(JSON.stringify(currentData) !== JSON.stringify(newData)) {
                    myChart.data.datasets[0].data = newData;
                    myChart.update();
                }
            }
        })
        .catch(err => {
            console.warn("Realtime sync paused (API not ready)."); 
        });
    }, 3000);

    function viewUser(id) {
        fetch('user_action.php', { method: 'POST', body: JSON.stringify({ action: 'get_detail', id: id }) })
        .then(res => res.json()).then(res => {
            if(res.status === 'success') {
                const u = res.data;
                document.getElementById('modal_foto').src = u.foto_url;
                document.getElementById('modal_nama').innerText = u.nama_lengkap;
                document.getElementById('modal_cabor').innerText = u.cabang_olahraga;
                document.getElementById('modal_email').innerText = u.email;
                document.getElementById('modal_telepon').innerText = u.no_telepon;
                document.getElementById('modal_qr').innerText = u.qr_code || '-';
                new bootstrap.Modal(document.getElementById('userModal')).show();
            }
        });
    }

    function toggleStatus(id) {
        Swal.fire({
            title: 'Ubah Status?', icon: 'warning', background: '#222', color: '#fff',
            showCancelButton: true, confirmButtonColor: '#ffcc00', confirmButtonText: 'Ya'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('user_action.php', { method: 'POST', body: JSON.stringify({ action: 'toggle_status', id: id }) })
                .then(res => res.json()).then(res => { if(res.status === 'success') location.reload(); });
            }
        })
    }

    function deleteUser(id) {
        Swal.fire({
            title: 'Hapus Permanen?', text: "Data tidak bisa kembali!", icon: 'warning',
            background: '#222', color: '#fff', showCancelButton: true, confirmButtonColor: '#ff0055', confirmButtonText: 'Hapus'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('user_action.php', { method: 'POST', body: JSON.stringify({ action: 'delete_user', id: id }) })
                .then(res => res.json()).then(res => {
                    if(res.status === 'success') {
                        document.getElementById('row-' + id).remove();
                        Swal.fire({title:'Terhapus', icon:'success', background:'#222', color:'#fff', confirmButtonColor: '#00ff88'});
                    }
                });
            }
        })
    }
</script>
</body>
</html>