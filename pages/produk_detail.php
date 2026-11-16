<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/produk_helper.php';

$id = (int)($_GET['id'] ?? 0);

$st = mysqli_prepare($koneksi,
    "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar,
            k.nama_kategori, u.nama_lengkap AS penjual
     FROM produk p
     JOIN kategori k ON k.id_kategori = p.id_kategori
     JOIN users u    ON u.id_user = p.id_penjual
     WHERE p.id_produk = ?");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$p) {
    set_flash('danger', 'Produk tidak ditemukan.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
$img  = url_gambar($p['gambar']);
$adaKeranjang = is_file(__DIR__ . '/keranjang_tambah.php');   // dibuat di CP4

$judul = $p['nama_produk'];
$menu_aktif = 'produk';
include __DIR__ . '/../includes/layout_atas.php';
?>

<p><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">&larr; Kembali ke daftar produk</a></p>

<?= tampil_flash() ?>

<div class="card kartu-kakao"><div class="card-body p-4">
  <div class="row g-4">
    <div class="col-md-5">
      <?php if ($img): ?>
        <img src="<?= e($img) ?>" class="img-fluid rounded" alt="<?= e($p['nama_produk']) ?>">
      <?php else: ?>
        <div class="gambar-produk gambar-kosong rounded"><i class="bi bi-image"></i></div>
      <?php endif; ?>
    </div>
    <div class="col-md-7">
      <small class="text-secondary"><?= e($p['nama_kategori']) ?> &middot; dijual oleh <?= e($p['penjual']) ?></small>
      <h3 class="fw-bold"><?= e($p['nama_produk']) ?></h3>
      <div class="harga fs-4 mb-2"><?= rupiah($p['harga']) ?></div>
      <p class="mb-2">Stok:
        <?php if ((int)$p['stok'] > 0): ?>
          <b><?= (int)$p['stok'] ?></b>
        <?php else: ?>
          <span class="badge text-bg-secondary">Habis</span>
        <?php endif; ?>
      </p>
      <p><?= nl2br(e($p['deskripsi'] ?? '')) ?></p>

      <?php if ((int)$p['stok'] > 0): ?>
        <?php if ($role === 'pembeli' && $adaKeranjang): ?>
          <form method="post" action="<?= BASE_URL ?>/pages/keranjang_tambah.php" class="d-flex gap-2" style="max-width:320px">
            <?= csrf_field() ?>
            <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
            <input type="number" name="jumlah" class="form-control" value="1" min="1" max="<?= (int)$p['stok'] ?>" required>
            <button class="btn btn-emas text-nowrap"><i class="bi bi-cart-plus"></i> Tambah ke Keranjang</button>
          </form>
        <?php elseif (!sudah_login()): ?>
          <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-emas">Masuk untuk membeli</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div></div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>