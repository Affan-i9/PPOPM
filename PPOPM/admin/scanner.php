<?php
// admin/scanner.php
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-red: #ff0055;
            --neon-blue: #00d4ff;
            --dark-bg: #121212;
            --glass-bg: rgba(20, 20, 20, 0.8);
        }
        body { 
            background-color: var(--dark-bg); 
            color: #eee; 
            font-family: 'Segoe UI', sans-serif; 
            overflow: hidden; 
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Container Scanner Kaca */
        .scanner-container {
            position: relative;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 2px solid var(--neon-green);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
            border-radius: 20px;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            z-index: 10;
            text-align: center;
        }

        /* Judul */
        .scanner-title {
            color: var(--neon-green);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.5);
        }

        /* Area Kamera */
        #reader {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #333;
        }
        
        /* Tombol Kembali */
        .btn-back {
            margin-top: 20px;
            background: transparent;
            border: 1px solid #555;
            color: #aaa;
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-back:hover {
            border-color: var(--neon-green);
            color: var(--neon-green);
            background: rgba(0, 255, 136, 0.1);
        }

        /* Animasi Garis Scan */
        .scan-line {
            position: absolute;
            top: 20%;
            left: 5%;
            width: 90%;
            height: 2px;
            background: var(--neon-red);
            box-shadow: 0 0 10px var(--neon-red);
            animation: scanAnim 2s infinite alternate;
            z-index: 20;
            pointer-events: none;
            display: none; /* Muncul saat kamera aktif */
        }
        @keyframes scanAnim {
            0% { top: 20%; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 60%; opacity: 0; }
        }

        /* Override Style Bawaan Library QR */
        #reader__scan_region { background: black; }
        #reader__dashboard_section_csr button { 
            background: var(--neon-green); color: black; border: none; padding: 5px 15px; border-radius: 5px; font-weight: bold; cursor: pointer; 
        }
        #reader__dashboard_section_swaplink { text-decoration: none; color: var(--neon-blue); }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<div class="scanner-container">
    <h3 class="scanner-title"><i class="fas fa-qrcode"></i> Scan Absensi</h3>
    
    <div class="scan-line" id="scanLine"></div>

    <div id="reader"></div>

    <div id="statusText" class="mt-3 small text-muted">Arahkan kamera ke QR Code Atlet</div>

    <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="../assets/js/particles.js"></script>

<script>
    // 1. Init Background
    initParticles('#particles-container', { count: 50, colors: ['#00ff88', '#ff0055'], speed: 0.8 });

    // 2. Audio Effects
    const beepSound = new Audio('https://www.soundjay.com/buttons/beep-01a.mp3'); 
    const errorSound = new Audio('https://www.soundjay.com/buttons/button-10.mp3');

    // 3. Logic Scanner
    let isProcessing = false; // Flag biar gak double scan
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { 
            fps: 10, 
            qrbox: {width: 250, height: 250},
            aspectRatio: 1.0
        },
        /* verbose= */ false
    );

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return; // Cegah spam scan
        isProcessing = true;
        
        // Pause scanner sementara
        html5QrcodeScanner.pause(); 
        
        // Kirim ke Backend
        fetch('process_absensi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr_code: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                beepSound.play();
                showResultPopup('success', 'BERHASIL!', `<b>${data.nama}</b><br>Hadir: ${data.waktu}`);
            } else if (data.status === 'warning') {
                errorSound.play();
                showResultPopup('warning', 'SUDAH ABSEN', data.message);
            } else {
                errorSound.play();
                showResultPopup('error', 'GAGAL', data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showResultPopup('error', 'ERROR SYSTEM', 'Terjadi kesalahan koneksi.');
        });
    }

    function showResultPopup(icon, title, htmlText) {
        let confirmColor = '#3085d6';
        if(icon === 'success') confirmColor = '#00ff88';
        if(icon === 'warning') confirmColor = '#ffcc00';
        if(icon === 'error') confirmColor = '#ff0055';

        Swal.fire({
            title: title,
            html: htmlText,
            icon: icon,
            background: '#1a1a1a',
            color: '#fff',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-qrcode"></i> SCAN LAGI',
            cancelButtonText: 'Tutup',
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#555',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Resume Scanning untuk orang berikutnya
                isProcessing = false;
                html5QrcodeScanner.resume();
                document.getElementById('statusText').innerText = "Siap scan berikutnya...";
            } else {
                // Jika tutup, arahkan ke dashboard atau biarkan pause?
                // Kita biarkan pause, tapi kasih tombol resume manual di bawah kalau mau
                document.getElementById('statusText').innerHTML = "<button onclick='resumeScanner()' class='btn btn-sm btn-outline-success'>Aktifkan Kamera Lagi</button>";
            }
        });
    }

    function resumeScanner() {
        isProcessing = false;
        html5QrcodeScanner.resume();
        document.getElementById('statusText').innerText = "Arahkan kamera ke QR Code Atlet";
    }

    function onScanFailure(error) {
        // Biarkan kosong agar tidak spam log console
    }

    // Mulai Scanner
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);

    // Efek Garis Merah (Hanya hiasan visual)
    setInterval(() => {
        const line = document.getElementById('scanLine');
        if(!isProcessing) {
            line.style.display = 'block';
        } else {
            line.style.display = 'none';
        }
    }, 1000);

</script>

</body>
</html>