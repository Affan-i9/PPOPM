<?php
// /var/www/html/PPOPM/auth/logout.php

// 1. Mulai session (agar sistem tahu session mana yang mau dihapus)
session_start();

// 2. Kosongkan semua variabel session
$_SESSION = [];

// 3. Hapus session dari server
session_unset();
session_destroy();

// 4. Redirect pengguna kembali ke halaman login
header("Location: login.php");
exit;
?>