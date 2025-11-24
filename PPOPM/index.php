<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    if (is_admin()) redirect('/ppopm-absensi/admin/index.php');
    if (is_user()) redirect('/ppopm-absensi/user/index.php');
}
redirect('/ppopm-absensi/auth/login.php');
?>

