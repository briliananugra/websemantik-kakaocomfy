<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/produk_helper.php';
wajib_role(['penjual']);

// Penjual hanya melihat produk miliknya sendiri
$st = mysqli_prepare($koneksi,
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori
     WHERE p.id_penjual = ?
     ORDER BY p.id_produk DESC");
mysqli_stmt_bind_param($st, 'i', $_SESSION['id_user']);
mysqli_stmt_execute($st);
$data = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

$judul = 'Kelola Produk';
$menu_aktif = 'kelola';
include __DIR__ . '/../includes/layout_atas.php';
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-1">Kelola Produk</h4>
    <p class="text-secondary mb-0">Produk milik <?= e($_SESSION['nama']) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/pages/produk_tambah.php" class="btn btn-hijau"><i class="bi bi-plus-lg"></i> Tambah Produk</a>
</div>

<?= tampil_flash() ?>

<div class="card kartu-kakao"><div class="card-body">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr><th>No</th><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php if (!$data): ?>
          <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada produk.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $i => $p): ?>
          <?php $img = url_gambar($p['gambar']); ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td>
              <?php if ($img): ?>
                <img src="<?= e($img) ?>" class="gambar-kecil" alt="">
              <?php else: ?>
                <span class="text-secondary"><i class="bi bi-image fs-4"></i></span>
              <?php endif; ?>
            </td>
            <td><?= e($p['nama_produk']) ?></td>
            <td><?= e($p['nama_kategori']) ?></td>
            <td class="harga"><?= rupiah($p['harga']) ?></td>
            <td><?= (int)$p['stok'] ?></td>
            <td class="text-nowrap">
              <a href="<?= BASE_URL ?>/pages/produk_detail.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-sm btn-biru" title="Lihat">
                <i class="bi bi-eye"></i></a>
              <a href="<?= BASE_URL ?>/pages/produk_edit.php?id=<?= (int)$p['id_produk'] ?>" class="btn btn-sm btn-hijau" title="Ubah">
                <i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= BASE_URL ?>/pages/produk_hapus.php" class="d-inline"
                    onsubmit="return confirm('Yakin hapus produk ini?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id_produk'] ?>">
                <button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>