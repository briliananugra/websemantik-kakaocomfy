<?php
require_once __DIR__ . '/../includes/helper.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_role(['pembeli']);

$idUser = (int)$_SESSION['id_user'];

// Mulai di sini error MySQL dilempar sebagai exception, supaya bisa di-rollback
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Isi keranjang + data produk. $kunci = true untuk mengunci baris saat transaksi.
function ambil_keranjang(mysqli $koneksi, int $idUser, bool $kunci): array {
    $sql = "SELECT k.id_keranjang, k.jumlah, p.id_produk, p.nama_produk, p.harga, p.stok
            FROM keranjang k JOIN produk p ON p.id_produk = k.id_produk
            WHERE k.id_user = ? ORDER BY k.id_keranjang" . ($kunci ? " FOR UPDATE" : "");
    $st = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($st, 'i', $idUser);
    mysqli_stmt_execute($st);
    $r = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $r;
}

$alamat = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Sesi form tidak valid. Muat ulang halaman.';
    } else {
        $alamat = trim($_POST['alamat_kirim'] ?? '');
        if (mb_strlen($alamat) < 10 || mb_strlen($alamat) > 500) {
            $errors[] = 'Alamat kirim 10 sampai 500 karakter.';
        }

        if (!$errors) {
            try {
                mysqli_begin_transaction($koneksi);

                // 1. Baca keranjang dan kunci baris produk (cegah dua pembeli rebutan stok)
                $items = ambil_keranjang($koneksi, $idUser, true);
                if (!$items) throw new Exception('Keranjang kosong.');

                // 2. Cek stok semua item
                $total = 0;
                foreach ($items as $it) {
                    if ($it['jumlah'] > $it['stok']) {
                        throw new Exception("Stok \"{$it['nama_produk']}\" tidak cukup (tersisa {$it['stok']}).");
                    }
                    $total += $it['harga'] * $it['jumlah'];
                }

                // 3. Simpan header pesanan
                $st = mysqli_prepare($koneksi,
                    "INSERT INTO pesanan (id_user, total, alamat_kirim) VALUES (?,?,?)");
                mysqli_stmt_bind_param($st, 'iis', $idUser, $total, $alamat);
                mysqli_stmt_execute($st);
                $idPesanan = mysqli_insert_id($koneksi);
                mysqli_stmt_close($st);

                // 4. Simpan detail (harga disalin) dan kurangi stok
                $stD = mysqli_prepare($koneksi,
                    "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan) VALUES (?,?,?,?)");
                $stS = mysqli_prepare($koneksi,
                    "UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");
                foreach ($items as $it) {
                    $harga = (int)$it['harga'];
                    mysqli_stmt_bind_param($stD, 'iiii', $idPesanan, $it['id_produk'], $it['jumlah'], $harga);
                    mysqli_stmt_execute($stD);

                    mysqli_stmt_bind_param($stS, 'iii', $it['jumlah'], $it['id_produk'], $it['jumlah']);
                    mysqli_stmt_execute($stS);
                    if (mysqli_stmt_affected_rows($stS) !== 1) {
                        throw new Exception("Stok \"{$it['nama_produk']}\" berubah, coba lagi.");
                    }
                }
                mysqli_stmt_close($stD);
                mysqli_stmt_close($stS);

                // 5. Kosongkan keranjang
                $st = mysqli_prepare($koneksi, "DELETE FROM keranjang WHERE id_user = ?");
                mysqli_stmt_bind_param($st, 'i', $idUser);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);

                // 6. Semua sukses -> simpan permanen
                mysqli_commit($koneksi);

                set_flash('success', 'Pesanan berhasil dibuat. Terima kasih sudah berbelanja!');
                header('Location: ' . BASE_URL . '/pages/pesanan_detail.php?id=' . $idPesanan);
                exit;

            } catch (Throwable $ex) {
                // Ada yang gagal -> batalkan SEMUA perubahan
                mysqli_rollback($koneksi);
                if ($ex instanceof mysqli_sql_exception) {
                    error_log('Checkout gagal: ' . $ex->getMessage());
                    $errors[] = 'Terjadi kesalahan saat menyimpan pesanan. Coba lagi.';
                } else {
                    $errors[] = $ex->getMessage();
                }
            }
        }
    }
}

// Data untuk tampilan ringkasan
$items = ambil_keranjang($koneksi, $idUser, false);
if (!$items && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('warning', 'Keranjang masih kosong.');
    header('Location: ' . BASE_URL . '/pages/keranjang.php');
    exit;
}
$total = 0;
foreach ($items as $it) { $total += $it['harga'] * $it['jumlah']; }

$judul = 'Checkout';
$menu_aktif = 'keranjang';
include __DIR__ . '/../includes/layout_atas.php';
?>

<h4 class="fw-bold mb-3">Checkout</h4>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
  </ul>
  <?php if (!$items): ?><a href="<?= BASE_URL ?>/index.php">Kembali belanja</a><?php endif; ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card kartu-kakao"><div class="card-body">
      <h6 class="fw-bold">Ringkasan Pesanan</h6>
      <table class="table mb-0">
        <thead><tr><th>Produk</th><th>Jml</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= e($it['nama_produk']) ?><div class="small text-secondary"><?= rupiah($it['harga']) ?></div></td>
              <td><?= (int)$it['jumlah'] ?></td>
              <td class="text-end"><?= rupiah($it['harga'] * $it['jumlah']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><th colspan="2">Total</th><th class="text-end harga"><?= rupiah($total) ?></th></tr>
        </tfoot>
      </table>
    </div></div>
  </div>

  <div class="col-lg-5">
    <div class="card kartu-kakao"><div class="card-body">
      <h6 class="fw-bold">Alamat Pengiriman</h6>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <textarea name="alamat_kirim" class="form-control mb-3" rows="4" maxlength="500" required
                  placeholder="Nama penerima, jalan, kota, kode pos"><?= e($alamat) ?></textarea>
        <button class="btn btn-emas w-100" <?= $items ? '' : 'disabled' ?>>
          <i class="bi bi-bag-check"></i> Buat Pesanan</button>
        <a href="<?= BASE_URL ?>/pages/keranjang.php" class="btn btn-outline-secondary w-100 mt-2">Kembali ke Keranjang</a>
      </form>
    </div></div>
  </div>
</div>

<?php include __DIR__ . '/../includes/layout_bawah.php'; ?>