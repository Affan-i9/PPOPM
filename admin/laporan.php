<?php
// admin/laporan.php
require_once '../includes/functions.php';
require_login();
checkAdmin();

// Set Timezone WIB
date_default_timezone_set('Asia/Jakarta');

$pdo = getPDO();
$tgl_pilih = $_GET['tgl'] ?? date('Y-m-d'); // Default Hari Ini

$data = $pdo->query("SELECT a.*, u.nama_lengkap, u.cabang_olahraga 
                     FROM absensi a 
                     JOIN users u ON a.user_id = u.id 
                     WHERE a.tanggal = '$tgl_pilih' 
                     ORDER BY a.waktu_absen ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Absensi</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #eee; }
        .no-print { margin-bottom: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <form method="GET" style="display:inline;">
            <label>Pilih Tanggal: </label>
            <input type="date" name="tgl" value="<?= $tgl_pilih ?>">
            <button type="submit">Lihat</button>
        </form>
        <button onclick="window.print()" style="margin-left:20px;">🖨️ Cetak / PDF</button>
        <button onclick="window.close()">❌ Tutup</button>
    </div>

    <center>
        <h2>LAPORAN HARIAN ABSENSI ATLET</h2>
        <p>Tanggal: <?= date('d F Y', strtotime($tgl_pilih)) ?></p>
    </center>

    <table>
        <thead>
            <tr><th>No</th><th>Nama Atlet</th><th>Cabor</th><th>Jam Masuk</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            <?php $no=1; foreach($data as $d): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['nama_lengkap']) ?></td>
                <td><?= htmlspecialchars($d['cabang_olahraga']) ?></td>
                <td><?= $d['waktu_absen'] ?></td>
                <td><?= $d['keterangan'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if(count($data) == 0) echo "<p style='text-align:center; margin-top:20px;'>Tidak ada data.</p>"; ?>
</body>
</html>