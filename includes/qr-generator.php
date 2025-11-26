<?php
// Wrapper generator QR server-side (opsional). Jika library phpqrcode tersedia,
// gunakan untuk menghasilkan PNG ke assets/qrcodes. Jika tidak, fallback ke client-side.

declare(strict_types=1);

function save_qr_png(string $text, string $filenameBase): ?string {
    $targetDir = __DIR__ . '/../assets/qrcodes/';
    if (!is_dir($targetDir)) { mkdir($targetDir, 0775, true); }
    $filePath = $targetDir . $filenameBase . '.png';

    if (file_exists(__DIR__ . '/phpqrcode/qrlib.php')) {
        require_once __DIR__ . '/phpqrcode/qrlib.php';
        // Level error dan ukuran
        $ecc = 'M';
        $size = 8;
        QRcode::png($text, $filePath, $ecc, $size, 2);
        return '/ppopm-absensi/assets/qrcodes/' . $filenameBase . '.png';
    }
    // Fallback: tidak ada library, return null (gunakan rendering JS di client)
    return null;
}

?>

