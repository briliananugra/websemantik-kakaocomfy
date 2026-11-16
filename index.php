<?php
require_once __DIR__ . '/includes/helper.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';

$judul = 'Beranda';
$menu_aktif = 'produk';
include __DIR__ . '/includes/layout_atas.php';
?>

<div class="hero-kakao p-5 mb-4">
  <h1 class="fw-bold">Selamat datang di Kakao Comfy</h1>
  <p class="lead mb-0">Perabot nyaman untuk rumah para petualang.</p>
</div>

<?= tampil_flash() ?>

<?php if (sudah_login()): ?>
  <div class="alert alert-success">
    Login berhasil sebagai <b><?= e($_SESSION['nama']) ?></b> dengan role <b><?= e($_SESSION['role']) ?></b>.
  </div>
<?php else: ?>
  <div class="alert alert-info">Belum login. Klik <b>Masuk</b> di pojok kanan atas.</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/layout_bawah.php'; ?>