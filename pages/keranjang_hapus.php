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
$idUser = (int)$_SESSION['id_user'];

$st = mysqli_prepare($koneksi, "DELETE FROM keranjang WHERE id_keranjang = ? AND id_user = ?");
mysqli_stmt_bind_param($st, 'ii', $idK, $idUser);
mysqli_stmt_execute($st);
$terhapus = mysqli_stmt_affected_rows($st);
mysqli_stmt_close($st);

set_flash($terhapus > 0 ? 'success' : 'danger',
          $terhapus > 0 ? 'Item dihapus dari keranjang.' : 'Item tidak ditemukan.');
header('Location: ' . $balik);
exit;