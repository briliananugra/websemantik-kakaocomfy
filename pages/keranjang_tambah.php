<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['pembeli']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$idProduk = (int)($_POST['id_produk'] ?? 0);
$jumlah   = (int)($_POST['jumlah'] ?? 0);
$idUser   = (int)$_SESSION['id_user'];
$balik    = BASE_URL . '/pages/produk_detail.php?id=' . $idProduk;

if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Sesi form tidak valid. Coba lagi.');
    header('Location: ' . $balik);
    exit;
}
if ($jumlah < 1) {
    set_flash('danger', 'Jumlah minimal 1.');
    header('Location: ' . $balik);
    exit;
}

// Produk harus ada
$st = mysqli_prepare($koneksi, "SELECT stok FROM produk WHERE id_produk = ?");
mysqli_stmt_bind_param($st, 'i', $idProduk);
mysqli_stmt_execute($st);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
if (!$p) {
    set_flash('danger', 'Produk tidak ditemukan.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Jumlah yang sudah ada di keranjang
$ada = 0;
$st = mysqli_prepare($koneksi, "SELECT jumlah FROM keranjang WHERE id_user = ? AND id_produk = ?");
mysqli_stmt_bind_param($st, 'ii', $idUser, $idProduk);
mysqli_stmt_execute($st);
mysqli_stmt_bind_result($st, $ada);
mysqli_stmt_fetch($st);
mysqli_stmt_close($st);
$ada  = (int)$ada;
$baru = $ada + $jumlah;

if ($baru > (int)$p['stok']) {
    set_flash('danger', "Stok tidak cukup. Di keranjang sudah $ada, stok tersedia {$p['stok']}.");
    header('Location: ' . $balik);
    exit;
}

if ($ada > 0) {
    $st = mysqli_prepare($koneksi, "UPDATE keranjang SET jumlah = ? WHERE id_user = ? AND id_produk = ?");
    mysqli_stmt_bind_param($st, 'iii', $baru, $idUser, $idProduk);
} else {
    $st = mysqli_prepare($koneksi, "INSERT INTO keranjang (id_user, id_produk, jumlah) VALUES (?,?,?)");
    mysqli_stmt_bind_param($st, 'iii', $idUser, $idProduk, $jumlah);
}
mysqli_stmt_execute($st);
mysqli_stmt_close($st);

set_flash('success', 'Produk ditambahkan ke keranjang.');
header('Location: ' . BASE_URL . '/pages/keranjang.php');
exit;