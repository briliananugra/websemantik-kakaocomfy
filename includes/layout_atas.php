<?php
/** @var mysqli $koneksi disiapkan oleh halaman yang meng-include file ini */
// Halaman yang memakai file ini harus mengisi dulu: $judul dan $menu_aktif
$judul      = $judul      ?? 'Kakao Comfy';
$menu_aktif = $menu_aktif ?? '';
$role       = $_SESSION['role'] ?? '';

// Jumlah item di keranjang (hanya pembeli)
$jmlKeranjang = 0;
if ($role === 'pembeli') {
    $st = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(jumlah),0) FROM keranjang WHERE id_user = ?");
    mysqli_stmt_bind_param($st, 'i', $_SESSION['id_user']);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $jmlKeranjang);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul) ?> - <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-kakao sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
      <i class="bi bi-house-heart-fill"></i> Kakao Comfy
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menuNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link <?= $menu_aktif === 'produk' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Produk</a>
        </li>
        <?php if ($role === 'penjual' && is_file(__DIR__ . '/../pages/produk_kelola.php')): ?>
        <li class="nav-item">
          <a class="nav-link <?= $menu_aktif === 'kelola' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/produk_kelola.php">Kelola Produk</a>
        </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-md-center gap-md-2">
        <?php if ($role === 'pembeli' && is_file(__DIR__ . '/../pages/pesanan_saya.php')): ?>
        <li class="nav-item">
          <a class="nav-link <?= $menu_aktif === 'pesanan' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/pesanan_saya.php">Pesanan Saya</a>
        </li>
        <?php endif; ?>
        <?php if ($role === 'pembeli' && is_file(__DIR__ . '/../pages/keranjang.php')): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?= BASE_URL ?>/pages/keranjang.php">
            <i class="bi bi-cart3"></i> Keranjang
            <span class="badge badge-emas"><?= (int)$jmlKeranjang ?></span>
          </a>
        </li>
        <?php endif; ?>
        <?php if (sudah_login()): ?>
        <li class="nav-item text-white small"><?= e($_SESSION['nama']) ?> (<?= e($role) ?>)</li>
        <li class="nav-item">
          <a class="btn btn-sm btn-emas" href="<?= BASE_URL ?>/auth/logout.php">Keluar</a>
        </li>
        <?php else: ?>
        <li class="nav-item">
          <a class="btn btn-sm btn-emas" href="<?= BASE_URL ?>/auth/login.php">Masuk</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">