<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
logout();
header('Location: /ppopm-absensi/auth/login.php');
exit;

