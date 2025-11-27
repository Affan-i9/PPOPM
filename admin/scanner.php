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
    <title>Scanner Advanced - PPOPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --neon-green: #00ff88;
            --neon-red: #ff0055;
            --neon-blue: #00d4ff;
            --neon-orange: #ff9900;
            --dark-bg: #121212;
            --glass-bg: rgba(20, 20, 20, 0.9);
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
            transition: border-color 0.3s;
        }

        .scanner-title {
            color: var(--neon-green);
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.5);
        }

        /* Mode Switcher */
        .mode-switch {
            background: rgba(0,0,0,0.5);
            border-radius: 10px;
            padding: 5px;
            display: flex;
            margin-bottom: 15px;
            border: 1px solid #444;
        }
        .mode-btn {
            flex: 1;
            border: none;
            background: transparent;
            color: #888;
            padding: 8px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .mode-btn.active {
            color: #000;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        
        /* Warna Mode */
        .mode-ib.active { background: var(--neon-green); }
        .mode-gate.active { background: var(--neon-orange); }

        /* Container Border Color Change based on Mode */
        .scanner-container.mode-gate-active { border-color: var(--neon-orange); box-shadow: 0 0 30px rgba(255, 153, 0, 0.2); }
        .scanner-title.mode-gate-text { color: var(--neon-orange); text-shadow: 0 0 10px rgba(255, 153, 0, 0.5); }

        #reader { width: 100%; border-radius: 10px; overflow: hidden; border: 1px solid #333; }
        
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

        #reader__scan_region { background: black; }
    </style>
</head>
<body>

<div id="particles-container" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1;"></div>

<div class="scanner-container" id="scannerBox">
    <h3 class="scanner-title" id="scanTitle"><i class="fas fa-qrcode"></i> SCAN ABSENSI</h3>
    
    <!-- MODE SWITCHER -->
    <div class="mode-switch">
        <button class="mode-btn mode-ib active" onclick="setMode('IB')" id="btnIB">
            <i class="fas fa-bed"></i> IB / Harian
        </button>
        <button class="mode-btn mode-gate" onclick="setMode('GATE')" id="btnGate">
            <i class="fas fa-door-open"></i> Ijin Keluar
        </button>
    </div>

    <div id="reader"></div>

    <div id="statusText" class="mt-3 small text-muted">Arahkan kamera ke QR Code Atlet</div>

    <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="../assets/js/particles.js"></script>

<script>
    initParticles('#particles-container', { count: 50, colors: ['#00ff88', '#ff9900'], speed: 0.8 });

    const beepSound = new Audio('https://www.soundjay.com/buttons/beep-01a.mp3'); 
    const errorSound = new Audio('https://www.soundjay.com/buttons/button-10.mp3');
    
    let currentMode = 'IB'; // Default: IB/Harian
    let isProcessing = false;

    function setMode(mode) {
        currentMode = mode;
        const box = document.getElementById('scannerBox');
        const title = document.getElementById('scanTitle');
        const btnIB = document.getElementById('btnIB');
        const btnGate = document.getElementById('btnGate');

        if (mode === 'GATE') {
            box.classList.add('mode-gate-active');
            title.classList.add('mode-gate-text');
            title.innerHTML = '<i class="fas fa-door-open"></i> SCAN IJIN KELUAR';
            btnIB.classList.remove('active');
            btnGate.classList.add('active');
        } else {
            box.classList.remove('mode-gate-active');
            title.classList.remove('mode-gate-text');
            title.innerHTML = '<i class="fas fa-bed"></i> SCAN IB / HARIAN';
            btnIB.classList.add('active');
            btnGate.classList.remove('active');
        }
    }

    let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 }, false);

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return; 
        isProcessing = true;
        html5QrcodeScanner.pause(); 
        
        // Kirim Mode yang dipilih ke Backend
        fetch('process_absensi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ qr_code: decodedText, mode: currentMode })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                beepSound.play();
                let icon = 'success';
                let title = 'BERHASIL';
                let color = '#00ff88';

                // Bedakan visual jika OUT atau IN
                if (data.type === 'OUT') {
                    title = 'SELAMAT JALAN';
                    icon = 'warning'; // Kuning/Oranye
                    color = '#ff9900';
                } else if (data.type === 'IN') {
                    title = 'SELAMAT DATANG';
                }

                showResultPopup(icon, title, `<b>${data.nama}</b><br>${data.message}`, color);
            } else if (data.status === 'warning') {
                errorSound.play();
                showResultPopup('warning', 'PERHATIAN', data.message, '#ffcc00');
            } else {
                errorSound.play();
                showResultPopup('error', 'GAGAL', data.message, '#ff0055');
            }
        })
        .catch(err => {
            console.error(err);
            showResultPopup('error', 'ERROR', 'Koneksi Gagal', '#ff0055');
        });
    }

    function showResultPopup(icon, title, htmlText, confirmColor) {
        Swal.fire({
            title: title,
            html: htmlText,
            icon: icon,
            background: '#1a1a1a',
            color: '#fff',
            showCancelButton: true,
            confirmButtonText: 'SCAN LAGI',
            cancelButtonText: 'Tutup',
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#555',
            allowOutsideClick: false
        }).then((result) => {
            isProcessing = false;
            if (result.isConfirmed) {
                html5QrcodeScanner.resume();
            } else {
                document.getElementById('statusText').innerHTML = "<button onclick='resumeScanner()' class='btn btn-sm btn-outline-light'>Aktifkan Kamera</button>";
            }
        });
    }

    function resumeScanner() {
        isProcessing = false;
        html5QrcodeScanner.resume();
        document.getElementById('statusText').innerText = "Arahkan kamera ke QR Code Atlet";
    }

    html5QrcodeScanner.render(onScanSuccess, (e)=>{});

</script>
</body>
</html>