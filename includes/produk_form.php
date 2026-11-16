<?php
/** @var array $d isi form */
/** @var array $listKategori */
/** @var bool $edit */
/** @var string|null $gambarLama */
?>
<form method="post" enctype="multipart/form-data" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label fw-semibold">Nama Produk</label>
      <input type="text" name="nama_produk" class="form-control" maxlength="150" required
             value="<?= e($d['nama_produk']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label fw-semibold">Kategori</label>
      <select name="id_kategori" class="form-select" required>
        <option value="">-- Pilih kategori --</option>
        <?php foreach ($listKategori as $k): ?>
          <option value="<?= (int)$k['id_kategori'] ?>"
            <?= (int)$d['id_kategori'] === (int)$k['id_kategori'] ? 'selected' : '' ?>>
            <?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Harga (Rp)</label>
      <input type="number" name="harga" class="form-control" min="1" required value="<?= e($d['harga']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label fw-semibold">Stok</label>
      <input type="number" name="stok" class="form-control" min="0" required value="<?= e($d['stok']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label fw-semibold">Deskripsi</label>
      <textarea name="deskripsi" class="form-control" rows="4" maxlength="2000"><?= e($d['deskripsi']) ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label fw-semibold">Gambar (opsional)</label>
      <?php if ($edit && $gambarLama): ?>
        <div class="mb-2">
          <img src="<?= e(url_gambar($gambarLama)) ?>" alt="Gambar saat ini" class="gambar-kecil">
          <div class="form-text">Gambar saat ini. Pilih file baru hanya jika ingin menggantinya.</div>
        </div>
      <?php endif; ?>
      <input type="file" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
      <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
    </div>
  </div>
  <div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-hijau"><i class="bi bi-check-lg"></i> Simpan</button>
    <a href="<?= BASE_URL ?>/pages/produk_kelola.php" class="btn btn-outline-secondary">Batal</a>
  </div>
</form>