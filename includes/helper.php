<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cetak teks dengan aman (cegah XSS)
function e($t) {
    return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
}

// 1500000 -> Rp 1.500.000
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function set_flash($tipe, $pesan) {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function tampil_flash() {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($f['tipe']) . ' alert-dismissible fade show">'
         . e($f['pesan'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}