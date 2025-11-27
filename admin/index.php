<?php
// admin/index.php
require_once '../includes/functions.php';
require_login();
checkAdmin();

// SET TIMEZONE WIB
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d'); 

$pdo = getPDO();

// Initial Data Load
$total_atlet = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM absensi WHERE tanggal = '$today'")->fetchColumn();
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$all_users = $pdo->query("SELECT * FROM users WHERE role='user' AND status IN ('active', 'inactive') ORDER BY nama_lengkap ASC")->fetchAll();

// Init Chart Data Kosong (Nanti diisi via JS/API)
$chart_labels = []; 
$chart_ib = [];
$chart_gate = [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --neon-green: #00ff88; --neon-yellow: #ffcc00; --neon-red: #ff0055; --neon-blue: #00d4ff; --dark-bg: #050505; --card-bg: #101010; --text-main: #ffffff; --text-dim: #cccccc; }
        body { background-color: var(--dark-bg); color: var(--text-main); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        .navbar { background: rgba(10, 10, 10, 0.95); border-bottom: 2px solid var(--neon-green); }
        .stat-card { background: var(--card-bg); border: 1px solid #333; border-radius: 12px; padding: 20px; transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .stat-card:hover { border-color: var(--neon-green); transform: translateY(-5px); }
        .stat-card h3 { font-size: 2.5rem; font-weight: bold; margin: 0; color: white; }
        .btn-scan { background: var(--neon-green); color: black; font-weight: bold; border-radius: 50px; padding: 10px 30px; text-decoration: none; box-shadow: 0 0 15px var(--neon-green); }
        .card-table { background: var(--card-bg); border: 1px solid #333; border-radius: 15px; overflow: hidden; }
        .table-dark-custom { width: 100%; border-collapse: collapse; color: var(--text-main); }
        .table-dark-custom th { background: #1a1a1a; color: var(--neon-yellow); padding: 15px; text-transform: uppercase; font-size: 0.85rem; border-bottom: 2px solid #444; }
        .table-dark-custom td { padding: 15px; border-bottom: 1px solid #333; vertical-align: middle; color: white !important; }
        .badge-active { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); border: 1px solid var(--neon-green); padding: 5px 12px; }
        .badge-inactive { background: rgba(255, 0, 85, 0.15); color: var(--neon-red); border: 1px solid var(--neon-red); padding: 5px 12px; }
        .modal-content { background-color: #1a1a1a; border: 1px solid var(--neon-green); color: white; }
        .form-control-dark { background: #222; border: 1px solid #444; color: white; }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#" style="color:var(--neon-green); letter-spacing:2px;">SUPER ADMIN</a>
        <div class="d-flex align-items-center">
            <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-circle"><i class="fas fa-power-off"></i></a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px; margin-bottom: 100px;">
    
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-white">Dashboard</h2>
            <p class="text-dim mb-0">Manajemen Lengkap PPOPM</p>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="bukaModalManual()" class="btn btn-outline-warning me-2 fw-bold"><i class="fas fa-hand-pointer"></i> Absen Manual</button>
            <a href="laporan.php" class="btn btn-outline-info me-2 fw-bold"><i class="fas fa-print"></i> Laporan</a>
            <a href="scanner.php" class="btn btn-scan"><i class="fas fa-qrcode"></i> SCAN</a>
        </div>
    </div>

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-center"><div><span class="small text-dim">Total Atlet</span><h3 id="stat-total"><?= $total_atlet ?></h3></div><i class="fas fa-users fa-3x" style="color:var(--neon-blue);opacity:0.5"></i></div></div></div>
        <div class="col-md-4 mb-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-center"><div><span class="small text-dim">Aktivitas Hari Ini</span><h3 id="stat-hadir"><?= $hadir_today ?></h3></div><i class="fas fa-check-circle fa-3x" style="color:var(--neon-green);opacity:0.5"></i></div></div></div>
        <div class="col-md-4 mb-3"><div class="stat-card"><div class="d-flex justify-content-between align-items-center"><div><span class="small text-dim">Pending</span><h3 id="stat-pending"><?= $pending ?></h3></div><i class="fas fa-clock fa-3x" style="color:var(--neon-yellow);opacity:0.5"></i></div></div></div>
    </div>

    <div class="row">
        <!-- Grafik -->
        <div class="col-lg-8 mb-4">
            <div class="card-table p-3 h-100">
                <h5 class="text-white mb-3 ms-2"><i class="fas fa-chart-line text-info"></i> Statistik Mingguan</h5>
                <div style="height: 250px; width: 100%;"><canvas id="attendanceChart"></canvas></div>
            </div>
        </div>
        <!-- Pending -->
        <div class="col-lg-4 mb-4">
            <div class="card-table h-100">
                <div class="p-3 bg-dark border-bottom border-secondary"><h6 class="text-warning mb-0 fw-bold">Approval Pending</h6></div>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table-dark-custom"><tbody id="table-pending-body">
                        <?php if (count($pending_users) > 0): foreach ($pending_users as $u): ?>
                        <tr>
                            <td><div class="fw-bold"><?= htmlspecialchars($u['nama_lengkap']) ?></div><small class="text-dim"><?= htmlspecialchars($u['cabang_olahraga']) ?></small></td>
                            <td class="text-end">
                                <a href="approve.php?id=<?= $u['id'] ?>&action=approve" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
                                <a href="approve.php?id=<?= $u['id'] ?>&action=reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; else: echo '<tr><td class="text-center text-dim py-5">Kosong.</td></tr>'; endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Manajemen -->
    <div class="card-table mt-2">
        <div class="p-3 bg-dark border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0"><i class="fas fa-users-cog" style="color:var(--neon-green)"></i> Data Atlet</h5>
            <span class="badge bg-secondary"><?= count($all_users) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table-dark-custom">
                <thead><tr><th class="ps-4">Nama</th><th>Cabor</th><th>Kontak</th><th>Status</th><th class="text-end pe-4">Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                    <tr id="row-<?= $user['id'] ?>">
                        <td class="ps-4"><span class="fw-bold"><?= htmlspecialchars($user['nama_lengkap']) ?></span></td>
                        <td><span class="badge bg-dark border border-secondary text-white"><?= htmlspecialchars($user['cabang_olahraga']) ?></span></td>
                        <td><div class="small"><?= htmlspecialchars($user['email']) ?></div><div class="small text-dim"><?= htmlspecialchars($user['no_telepon']) ?></div></td>
                        <td>
                            <?php if($user['status'] == 'active'): ?><span class="badge" style="background:rgba(0,255,136,0.2);color:#00ff88">Active</span>
                            <?php else: ?><span class="badge" style="background:rgba(255,0,85,0.2);color:#ff0055">Inactive</span><?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-primary" onclick="bukaModalEdit(<?= $user['id'] ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-warning" onclick="resetPass(<?= $user['id'] ?>)" style="color:black"><i class="fas fa-key"></i></button>
                            <?php if($user['status'] == 'active'): ?>
                                <button class="btn btn-sm btn-secondary" onclick="toggleStatus(<?= $user['id'] ?>)"><i class="fas fa-ban"></i></button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="toggleStatus(<?= $user['id'] ?>)"><i class="fas fa-check"></i></button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= $user['id'] ?>)"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit, Reset Pass, Manual Absen -->
<div class="modal fade" id="modalEdit" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Atlet</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" id="edit_id"><div class="mb-3"><label>Nama</label><input type="text" id="edit_nama" class="form-control form-control-dark"></div><div class="mb-3"><label>Cabor</label><input type="text" id="edit_cabor" class="form-control form-control-dark"></div><div class="mb-3"><label>Email</label><input type="email" id="edit_email" class="form-control form-control-dark"></div><div class="mb-3"><label>Telepon</label><input type="text" id="edit_telepon" class="form-control form-control-dark"></div></div><div class="modal-footer"><button onclick="simpanEdit()" class="btn btn-success w-100">Simpan</button></div></div></div></div>

<div class="modal fade" id="modalManual" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title text-warning">Absen Manual</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label>Pilih Atlet</label><select id="manual_user_id" class="form-control form-control-dark"><?php foreach($all_users as $u): if($u['status']=='active'): ?><option value="<?= $u['id'] ?>"><?= $u['nama_lengkap'] ?></option><?php endif; endforeach; ?></select></div></div><div class="modal-footer"><button onclick="prosesManual()" class="btn btn-warning w-100 fw-bold">Absen</button></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/particles.js"></script>
<script>
    initParticles('#particles-container', { count: 60, colors: ['#00ff88', '#0099ff'], speed: 0.4 });
    
    // SETUP CHART JS (DUA DATASET)
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // Diisi via API
            datasets: [
                { 
                    label: 'IB / Asrama', 
                    data: [], 
                    borderColor: '#00ff88', // HIJAU
                    backgroundColor: 'rgba(0, 255, 136, 0.1)', 
                    borderWidth: 2, 
                    tension: 0.3,
                    pointBackgroundColor: '#fff',
                    pointRadius: 4
                },
                { 
                    label: 'Ijin Keluar', 
                    data: [], 
                    borderColor: '#ffcc00', // KUNING
                    backgroundColor: 'rgba(255, 204, 0, 0.1)', 
                    borderWidth: 2, 
                    borderDash: [5, 5], // Garis putus-putus biar beda
                    tension: 0.3,
                    pointBackgroundColor: '#fff',
                    pointRadius: 4
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { display: true, labels: { color: '#ccc' } } 
            }, 
            scales: { 
                y: { beginAtZero: true, grid: { color: '#333' }, ticks: { color: '#ccc', stepSize: 1 } }, 
                x: { grid: { display: false }, ticks: { color: '#ccc' } } 
            } 
        }
    });

    // REALTIME REFRESH
    setInterval(() => {
        fetch('../api/admin_stats.php')
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if(data && data.status === 'success') {
                document.getElementById('stat-total').innerText = data.total_atlet;
                document.getElementById('stat-hadir').innerText = data.hadir_today;
                document.getElementById('stat-pending').innerText = data.pending_count;
                document.getElementById('table-pending-body').innerHTML = data.pending_html;
                
                // Update Grafik
                if(JSON.stringify(myChart.data.datasets[0].data) !== JSON.stringify(data.chart_data_ib) || 
                   JSON.stringify(myChart.data.datasets[1].data) !== JSON.stringify(data.chart_data_gate)) {
                    
                    myChart.data.labels = data.chart_labels;
                    myChart.data.datasets[0].data = data.chart_data_ib;
                    myChart.data.datasets[1].data = data.chart_data_gate;
                    myChart.update();
                }
            }
        }).catch(e=>{});
    }, 3000);

    // FUNGSI JS
    function bukaModalManual() { new bootstrap.Modal(document.getElementById('modalManual')).show(); }
    function prosesManual() {
        fetch('user_action.php', { method: 'POST', body: JSON.stringify({ action: 'manual_absen', user_id: document.getElementById('manual_user_id').value }) })
        .then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); else Swal.fire('Gagal', d.message, 'error'); });
    }
    function bukaModalEdit(id) {
        fetch('user_action.php', { method: 'POST', body: JSON.stringify({ action: 'get_detail', id: id }) })
        .then(r=>r.json()).then(d=>{ 
            if(d.status==='success') {
                document.getElementById('edit_id').value=d.data.id; document.getElementById('edit_nama').value=d.data.nama_lengkap;
                document.getElementById('edit_cabor').value=d.data.cabang_olahraga; document.getElementById('edit_email').value=d.data.email;
                document.getElementById('edit_telepon').value=d.data.no_telepon;
                new bootstrap.Modal(document.getElementById('modalEdit')).show();
            }
        });
    }
    function simpanEdit() {
        const d = { action:'edit_user', id:document.getElementById('edit_id').value, nama:document.getElementById('edit_nama').value, cabor:document.getElementById('edit_cabor').value, email:document.getElementById('edit_email').value, telepon:document.getElementById('edit_telepon').value };
        fetch('user_action.php', { method:'POST', body:JSON.stringify(d) }).then(r=>r.json()).then(res=>{ if(res.status==='success') location.reload(); });
    }
    function resetPass(id) { Swal.fire({title:'Reset?',showCancelButton:true,confirmButtonText:'Reset'}).then(r=>{ if(r.isConfirmed) fetch('user_action.php',{method:'POST',body:JSON.stringify({action:'reset_pass',id:id})}).then(r=>r.json()).then(d=>{ if(d.status==='success') Swal.fire('Sukses','Pass: 123456','success'); }); }); }
    function toggleStatus(id) { Swal.fire({title:'Ubah?',showCancelButton:true,confirmButtonText:'Ya'}).then(r=>{ if(r.isConfirmed) fetch('user_action.php',{method:'POST',body:JSON.stringify({action:'toggle_status',id:id})}).then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); }); }); }
    function deleteUser(id) { Swal.fire({title:'Hapus?',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Hapus'}).then(r=>{ if(r.isConfirmed) fetch('user_action.php',{method:'POST',body:JSON.stringify({action:'delete_user',id:id})}).then(r=>r.json()).then(d=>{ if(d.status==='success') location.reload(); }); }); }
</script>
</body>
</html>