<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['pembeli']);

$balik = BASE_URL . '/pages/keranjang.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $balik); exit; }
if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Sesi form tidak valid. Coba lagi.');
    header('Location: ' . $balik); exit;
}

$idK    = (int)($_POST['id_keranjang'] ?? 0);
$jumlah = (int)($_POST['jumlah'] ?? 0);
$idUser = (int)$_SESSION['id_user'];

// Item harus milik pembeli yang login
$st = mysqli_prepare($koneksi,
    "SELECT p.stok FROM keranjang k JOIN produk p ON p.id_produk = k.id_produk
     WHERE k.id_keranjang = ? AND k.id_user = ?");
mysqli_stmt_bind_param($st, 'ii', $idK, $idUser);
mysqli_stmt_execute($st);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$r) {
    set_flash('danger', 'Item keranjang tidak ditemukan.');
} elseif ($jumlah < 1) {
    set_flash('danger', 'Jumlah minimal 1. Untuk membuang item, pakai tombol hapus.');
} elseif ($jumlah > (int)$r['stok']) {
    set_flash('danger', 'Jumlah melebihi stok (tersedia ' . (int)$r['stok'] . ').');
} else {
    $st = mysqli_prepare($koneksi, "UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ? AND id_user = ?");
    mysqli_stmt_bind_param($st, 'iii', $jumlah, $idK, $idUser);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    set_flash('success', 'Jumlah diperbarui.');
}
header('Location: ' . $balik);
exit;