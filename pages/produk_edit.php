<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/produk_helper.php';
wajib_role(['penjual']);

$id = (int)($_GET['id'] ?? 0);
$idPenjual = (int)$_SESSION['id_user'];

// Hanya produk milik penjual yang login (id_penjual ikut dicek)
$st = mysqli_prepare($koneksi,
    "SELECT id_kategori, nama_produk, deskripsi, harga, stok, gambar
     FROM produk WHERE id_produk = ? AND id_penjual = ?");
mysqli_stmt_bind_param($st, 'ii', $id, $idPenjual);
mysqli_stmt_execute($st);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$row) {
    set_flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
    header('Location: ' . BASE_URL . '/pages/produk_kelola.php');
    exit;
}

$listKategori = daftar_kategori($koneksi);
$edit = true;
$gambarLama = $row['gambar'];
$d = [
    'id_kategori' => (int)$row['id_kategori'],
    'nama_produk' => $row['nama_produk'],
    'deskripsi'   => (string)($row['deskripsi'] ?? ''),
    'harga'       => (string)(int)$row['harga'],
    'stok'        => (string)(int)$row['stok'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi form tidak valid. Muat ulang halaman.';
    } else {
        $d = ambil_input_produk();
        $errors = validasi_produk($koneksi, $d);
        $g = cek_gambar($errors);

        $namaFile = $gambarLama;          // default: pakai gambar lama
        if (!$errors && $g) {
            if (simpan_gambar($g)) $namaFile = $g['nama'];
            else $errors[] = 'Gambar gagal disimpan di server.';
        }

        if (!$errors) {
            $harga = (int)$d['harga'];
            $stok  = (int)$d['stok'];
            $st = mysqli_prepare($koneksi,
                "UPDATE produk SET id_kategori=?, nama_produk=?, deskripsi=?, harga=?, stok=?, gambar=?
                 WHERE id_produk=? AND id_penjual=?");
            mysqli_stmt_bind_param($st, 'issiisii', $d['id_kategori'], $d['nama_produk'],
                $d['deskripsi'], $harga, $stok, $namaFile, $id, $idPenjual);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            // Gambar baru terpasang -> buang file gambar lama
            if ($g && $gambarLama && $gambarLama !== $namaFile) hapus_gambar($gambarLama);

            set_flash('success', 'Produk berhasil diubah.');
            header('Location: ' . BASE_URL . '/pages/produk_kelola.php');
            exit;
        }
    }
}

$judul = 'Ubah Produk';
$menu_aktif = 'kelola';
include __DIR__ . '/../includes/layout_atas.php';
?>

<h4 class="fw-bold mb-1">Ubah Produk</h4>
<p class="text-secondary"><a href="produk_kelola.php" class="text-decoration-none">Kelola Produk</a> &rsaquo; Ubah</p>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="card kartu-kakao"><div class="card-body p-4">
  <?php include __DIR__ . '/../includes/produk_form.php'; ?>
</div></div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>