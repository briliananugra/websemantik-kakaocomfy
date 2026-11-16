<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/produk_helper.php';
wajib_role(['pembeli']);

$st = mysqli_prepare($koneksi,
    "SELECT k.id_keranjang, k.jumlah, p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar
     FROM keranjang k JOIN produk p ON p.id_produk = k.id_produk
     WHERE k.id_user = ?
     ORDER BY k.id_keranjang");
mysqli_stmt_bind_param($st, 'i', $_SESSION['id_user']);
mysqli_stmt_execute($st);
$data = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

$total = 0;
foreach ($data as $r) { $total += $r['harga'] * $r['jumlah']; }

$judul = 'Keranjang Belanja';
$menu_aktif = 'keranjang';
include __DIR__ . '/../includes/layout_atas.php';
?>

<h4 class="fw-bold mb-3">Keranjang Belanja</h4>

<?= tampil_flash() ?>

<?php if (!$data): ?>
  <div class="alert alert-info">Keranjang masih kosong.
    <a href="<?= BASE_URL ?>/index.php">Mulai belanja</a></div>
<?php else: ?>
<div class="card kartu-kakao"><div class="card-body">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr><th>Produk</th><th>Harga</th><th style="width:190px">Jumlah</th><th>Subtotal</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $r): ?>
          <?php $img = url_gambar($r['gambar']); $kurang = $r['jumlah'] > $r['stok']; ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($img): ?><img src="<?= e($img) ?>" class="gambar-kecil" alt="">
                <?php else: ?><i class="bi bi-image fs-4 text-secondary"></i><?php endif; ?>
                <div>
                  <?= e($r['nama_produk']) ?>
                  <?php if ($kurang): ?>
                    <div class="small text-danger">Stok tersisa <?= (int)$r['stok'] ?>, kurangi jumlahnya.</div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><?= rupiah($r['harga']) ?></td>
            <td>
              <form method="post" action="keranjang_ubah.php" class="d-flex gap-1">
                <?= csrf_field() ?>
                <input type="hidden" name="id_keranjang" value="<?= (int)$r['id_keranjang'] ?>">
                <input type="number" name="jumlah" class="form-control form-control-sm"
                       value="<?= (int)$r['jumlah'] ?>" min="1" max="<?= (int)$r['stok'] ?>" required>
                <button class="btn btn-sm btn-biru">Ubah</button>
              </form>
            </td>
            <td class="harga"><?= rupiah($r['harga'] * $r['jumlah']) ?></td>
            <td>
              <form method="post" action="keranjang_hapus.php" class="d-inline"
                    onsubmit="return confirm('Hapus item ini dari keranjang?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id_keranjang" value="<?= (int)$r['id_keranjang'] ?>">
                <button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <hr>
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary">&larr; Lanjut Belanja</a>
    <div class="text-end">
      <div class="fs-5">Total: <span class="harga"><?= rupiah($total) ?></span></div>
      <a href="<?= BASE_URL ?>/pages/checkout.php" class="btn btn-emas mt-2">Checkout <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</div></div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>