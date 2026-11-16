<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['pembeli']);

$id = (int)($_GET['id'] ?? 0);

// Hanya pesanan milik pembeli yang login
$st = mysqli_prepare($koneksi,
    "SELECT id_pesanan, tanggal, total, alamat_kirim, status
     FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
mysqli_stmt_bind_param($st, 'ii', $id, $_SESSION['id_user']);
mysqli_stmt_execute($st);
$ps = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$ps) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    header('Location: ' . BASE_URL . '/pages/pesanan_saya.php');
    exit;
}

$st = mysqli_prepare($koneksi,
    "SELECT p.nama_produk, d.jumlah, d.harga_satuan
     FROM detail_pesanan d JOIN produk p ON p.id_produk = d.id_produk
     WHERE d.id_pesanan = ? ORDER BY d.id_detail");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$det = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

$judul = 'Pesanan #' . $ps['id_pesanan'];
$menu_aktif = 'pesanan';
include __DIR__ . '/../includes/layout_atas.php';
?>

<p><a href="<?= BASE_URL ?>/pages/pesanan_saya.php" class="text-decoration-none">&larr; Semua pesanan</a></p>

<?= tampil_flash() ?>

<div class="card kartu-kakao"><div class="card-body p-4">
  <div class="d-flex justify-content-between flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Pesanan #<?= (int)$ps['id_pesanan'] ?></h4>
    <span class="badge badge-emas fs-6"><?= e(ucfirst($ps['status'])) ?></span>
  </div>
  <p class="text-secondary mb-1">Tanggal: <?= e(date('d-m-Y H:i', strtotime($ps['tanggal']))) ?></p>
  <p>Dikirim ke: <?= nl2br(e($ps['alamat_kirim'])) ?></p>

  <table class="table mb-0">
    <thead><tr><th>Produk</th><th>Harga</th><th>Jml</th><th class="text-end">Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($det as $d): ?>
        <tr>
          <td><?= e($d['nama_produk']) ?></td>
          <td><?= rupiah($d['harga_satuan']) ?></td>
          <td><?= (int)$d['jumlah'] ?></td>
          <td class="text-end"><?= rupiah($d['harga_satuan'] * $d['jumlah']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><th colspan="3">Total</th><th class="text-end harga"><?= rupiah($ps['total']) ?></th></tr>
    </tfoot>
  </table>
</div></div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>