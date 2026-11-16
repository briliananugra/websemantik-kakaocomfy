<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/produk_helper.php';
wajib_role(['penjual']);

$kembali = BASE_URL . '/pages/produk_kelola.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $kembali);
    exit;
}
if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Sesi form tidak valid. Coba lagi.');
    header('Location: ' . $kembali);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$idPenjual = (int)$_SESSION['id_user'];

// Ambil produk milik sendiri
$st = mysqli_prepare($koneksi, "SELECT gambar FROM produk WHERE id_produk = ? AND id_penjual = ?");
mysqli_stmt_bind_param($st, 'ii', $id, $idPenjual);
mysqli_stmt_execute($st);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$row) {
    set_flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
    header('Location: ' . $kembali);
    exit;
}

// Produk yang sudah pernah dipesan tidak boleh dihapus (riwayat pesanan harus utuh)
$st = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM detail_pesanan WHERE id_produk = ?");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
mysqli_stmt_bind_result($st, $dipesan);
mysqli_stmt_fetch($st);
mysqli_stmt_close($st);

if ($dipesan > 0) {
    set_flash('danger', 'Produk tidak bisa dihapus karena sudah pernah dipesan. Ubah stoknya jadi 0 sebagai gantinya.');
    header('Location: ' . $kembali);
    exit;
}

$st = mysqli_prepare($koneksi, "DELETE FROM produk WHERE id_produk = ? AND id_penjual = ?");
mysqli_stmt_bind_param($st, 'ii', $id, $idPenjual);
mysqli_stmt_execute($st);
mysqli_stmt_close($st);

hapus_gambar($row['gambar']);

set_flash('success', 'Produk berhasil dihapus.');
header('Location: ' . $kembali);
exit;