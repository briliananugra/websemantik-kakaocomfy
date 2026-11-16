<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/produk_helper.php';
wajib_role(['penjual']);

$listKategori = daftar_kategori($koneksi);
$edit = false;
$gambarLama = null;
$d = ['id_kategori' => 0, 'nama_produk' => '', 'deskripsi' => '', 'harga' => '', 'stok' => '0'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi form tidak valid. Muat ulang halaman.';
    } else {
        $d = ambil_input_produk();
        $errors = validasi_produk($koneksi, $d);
        $g = cek_gambar($errors);

        $namaFile = null;
        if (!$errors && $g) {
            if (simpan_gambar($g)) $namaFile = $g['nama'];
            else $errors[] = 'Gambar gagal disimpan di server.';
        }

        if (!$errors) {
            $idPenjual = (int)$_SESSION['id_user'];
            $harga = (int)$d['harga'];
            $stok  = (int)$d['stok'];
            $st = mysqli_prepare($koneksi,
                "INSERT INTO produk (id_kategori, id_penjual, nama_produk, deskripsi, harga, stok, gambar)
                 VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($st, 'iissiis', $d['id_kategori'], $idPenjual,
                $d['nama_produk'], $d['deskripsi'], $harga, $stok, $namaFile);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            set_flash('success', 'Produk berhasil ditambahkan.');
            header('Location: ' . BASE_URL . '/pages/produk_kelola.php');
            exit;
        }
    }
}

$judul = 'Tambah Produk';
$menu_aktif = 'kelola';
include __DIR__ . '/../includes/layout_atas.php';
?>

<h4 class="fw-bold mb-1">Tambah Produk</h4>
<p class="text-secondary"><a href="produk_kelola.php" class="text-decoration-none">Kelola Produk</a> &rsaquo; Tambah</p>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="card kartu-kakao"><div class="card-body p-4">
  <?php include __DIR__ . '/../includes/produk_form.php'; ?>
</div></div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>