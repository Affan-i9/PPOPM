<?php
require_once '../includes/functions.php';
require_login();
checkAdmin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Pro - PPOPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #000; color: #0f0; font-family: 'Courier New', monospace; overflow: hidden; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border: 2px solid #0f0; box-shadow: 0 0 20px #0f0; }
        .scan-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 999; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .back-btn { margin-top: 20px; border: 1px solid #ffcc00; color: #ffcc00; background: transparent; padding: 10px 30px; text-decoration: none; transition: 0.3s; }
        .back-btn:hover { background: #ffcc00; color: #000; box-shadow: 0 0 15px #ffcc00; }
        h2 { text-shadow: 0 0 10px #0f0; }
    </style>
</head>
<body>

<div class="scan-overlay">
    <h2 class="mb-4">SCANNER ABSENSI</h2>
    <div id="reader"></div>
    <div id="result" class="mt-3 text-center fw-bold" style="min-height: 30px;"></div>
    <a href="index.php" class="back-btn">KEMBALI KE DASHBOARD</a>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    // Audio Effect
    const beep = new Audio('https://www.soundjay.com/button/beep-07.wav');
    const errorSound = new Audio('https://www.soundjay.com/button/button-10.wav');

    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning sementara biar gak double input
        html5QrcodeScanner.clear();

        // Kirim ke Backend
        fetch('process_absensi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr_code: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                beep.play();
                Swal.fire({
                    title: 'BERHASIL!',
                    text: data.nama + ' hadir pada ' + data.waktu,
                    icon: 'success',
                    background: '#1a1a1a',
                    color: '#0f0',
                    confirmButtonColor: '#0f0',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload(); // Reload untuk scan berikutnya
                });
            } else if (data.status === 'warning') {
                errorSound.play();
                Swal.fire({
                    title: 'SUDAH ABSEN',
                    text: data.message,
                    icon: 'warning',
                    background: '#1a1a1a',
                    color: '#ffcc00',
                    confirmButtonColor: '#ffcc00'
                }).then(() => {
                    location.reload();
                });
            } else {
                errorSound.play();
                Swal.fire({
                    title: 'ERROR',
                    text: data.message,
                    icon: 'error',
                    background: '#1a1a1a',
                    color: '#ff0000'
                }).then(() => {
                    location.reload();
                });
            }
        })
        .catch(err => {
            console.error(err);
            location.reload();
        });
    }

    // Config Scanner
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { fps: 10, qrbox: {width: 250, height: 250} },
        /* verbose= */ false
    );
    html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>