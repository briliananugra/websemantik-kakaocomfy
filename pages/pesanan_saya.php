<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['pembeli']);

$st = mysqli_prepare($koneksi,
    "SELECT id_pesanan, tanggal, total, status,
            (SELECT COALESCE(SUM(jumlah),0) FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS jml_item
     FROM pesanan p WHERE id_user = ? ORDER BY id_pesanan DESC");
mysqli_stmt_bind_param($st, 'i', $_SESSION['id_user']);
mysqli_stmt_execute($st);
$data = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

$judul = 'Pesanan Saya';
$menu_aktif = 'pesanan';
include __DIR__ . '/../includes/layout_atas.php';
?>

<h4 class="fw-bold mb-3">Pesanan Saya</h4>

<?= tampil_flash() ?>

<div class="card kartu-kakao"><div class="card-body">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>No. Pesanan</th><th>Tanggal</th><th>Jumlah Item</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (!$data): ?>
          <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada pesanan.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $p): ?>
          <tr>
            <td>#<?= (int)$p['id_pesanan'] ?></td>
            <td><?= e(date('d-m-Y H:i', strtotime($p['tanggal']))) ?></td>
            <td><?= (int)$p['jml_item'] ?></td>
            <td class="harga"><?= rupiah($p['total']) ?></td>
            <td><span class="badge badge-emas"><?= e(ucfirst($p['status'])) ?></span></td>
            <td><a href="pesanan_detail.php?id=<?= (int)$p['id_pesanan'] ?>" class="btn btn-sm btn-biru">
              <i class="bi bi-eye"></i> Lihat</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>