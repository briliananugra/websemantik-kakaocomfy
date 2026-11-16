<?php
require_once __DIR__ . '/includes/helper.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/produk_helper.php';

$q   = trim($_GET['q'] ?? '');
$kat = (int)($_GET['kat'] ?? 0);

$where = ['1=1'];
$tipe  = '';
$param = [];
if ($q !== '') {
    $where[] = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $like = '%' . $q . '%';
    $tipe .= 'ss';
    array_push($param, $like, $like);
}
if ($kat > 0) {
    $where[] = "p.id_kategori = ?";
    $tipe .= 'i';
    $param[] = $kat;
}

$st = mysqli_prepare($koneksi,
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori
     WHERE " . implode(' AND ', $where) . "
     ORDER BY p.id_produk DESC");
if ($param) mysqli_stmt_bind_param($st, $tipe, ...$param);
mysqli_stmt_execute($st);
$data = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

$listKategori = daftar_kategori($koneksi);

$judul = 'Daftar Produk';
$menu_aktif = 'produk';
include __DIR__ . '/includes/layout_atas.php';
?>

<div class="hero-kakao p-4 mb-4">
  <h2 class="fw-bold mb-1">Kakao Comfy</h2>
  <p class="mb-0">Perabot nyaman untuk rumah para petualang.</p>
</div>

<?= tampil_flash() ?>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-5">
    <input type="text" name="q" class="form-control" placeholder="Cari produk..." value="<?= e($q) ?>">
  </div>
  <div class="col-md-4">
    <select name="kat" class="form-select">
      <option value="0">Semua Kategori</option>
      <?php foreach ($listKategori as $k): ?>
        <option value="<?= (int)$k['id_kategori'] ?>" <?= $kat === (int)$k['id_kategori'] ? 'selected' : '' ?>>
          <?= e($k['nama_kategori']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3 d-flex gap-2">
    <button class="btn btn-hijau"><i class="bi bi-search"></i> Cari</button>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary">Reset</a>
  </div>
</form>

<?php if (!$data): ?>
  <div class="alert alert-info">Produk tidak ditemukan.</div>
<?php endif; ?>

<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
  <?php foreach ($data as $p): ?>
    <?php $img = url_gambar($p['gambar']); ?>
    <div class="col">
      <div class="card kartu-kakao kartu-produk h-100">
        <?php if ($img): ?>
          <img src="<?= e($img) ?>" class="gambar-produk" alt="<?= e($p['nama_produk']) ?>">
        <?php else: ?>
          <div class="gambar-produk gambar-kosong"><i class="bi bi-image"></i></div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
          <small class="text-secondary"><?= e($p['nama_kategori']) ?></small>
          <h6 class="fw-bold mb-1"><?= e($p['nama_produk']) ?></h6>
          <div class="harga mb-2"><?= rupiah($p['harga']) ?></div>
          <div class="mt-auto">
            <?php if ((int)$p['stok'] === 0): ?>
              <span class="badge text-bg-secondary mb-2">Stok habis</span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/pages/produk_detail.php?id=<?= (int)$p['id_produk'] ?>"
               class="btn btn-sm btn-hijau w-100">Lihat Detail</a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/layout_bawah.php'; ?>