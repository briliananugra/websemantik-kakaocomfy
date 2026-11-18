<?php
/** @var mysqli $koneksi disiapkan oleh halaman yang meng-include file ini */
require_once __DIR__ . '/../includes/api.php';
api_metode(['GET', 'POST']);

// Error "yang diharapkan" (produk tidak ada, stok kurang). Kodenya = kode HTTP.
class ApiException extends Exception {}

$pembeli = api_wajib_pembeli();   // 401 atau 403 berhenti di sini
$idUser  = $pembeli['id_user'];

// =====================================================================
// GET ?id=N : lihat pesanan milik sendiri
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id'])) json_error(400, 'Parameter id wajib diisi.');
    $id = $_GET['id'];
    if (!is_string($id) || !ctype_digit($id) || (int)$id < 1 || (int)$id > 2147483647) {
        json_error(400, 'Parameter id harus berupa angka bulat positif.');
    }
    $id = (int)$id;

    // id_user ikut dicek: pesanan orang lain dijawab 404 (keberadaannya tidak dibocorkan)
    $st = mysqli_prepare($koneksi,
        "SELECT id_pesanan, tanggal, total, alamat_kirim, status
         FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
    mysqli_stmt_bind_param($st, 'ii', $id, $idUser);
    mysqli_stmt_execute($st);
    $ps = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if (!$ps) json_error(404, 'Pesanan tidak ditemukan.');

    $st = mysqli_prepare($koneksi,
        "SELECT d.id_produk, p.nama_produk, d.jumlah, d.harga_satuan
         FROM detail_pesanan d JOIN produk p ON p.id_produk = d.id_produk
         WHERE d.id_pesanan = ? ORDER BY d.id_detail");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $det = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);

    json_out([
        'status' => 'ok',
        'data'   => [
            'id_pesanan'   => (int)$ps['id_pesanan'],
            'tanggal'      => $ps['tanggal'],
            'status'       => $ps['status'],
            'total'        => (int)$ps['total'],
            'alamat_kirim' => $ps['alamat_kirim'],
            'items'        => array_map(fn($d) => [
                'id_produk'    => (int)$d['id_produk'],
                'nama'         => $d['nama_produk'],
                'jumlah'       => (int)$d['jumlah'],
                'harga_satuan' => (int)$d['harga_satuan'],
                'subtotal'     => (int)$d['harga_satuan'] * (int)$d['jumlah'],
            ], $det),
        ],
    ]);
}

// =====================================================================
// POST : buat pesanan baru
// =====================================================================
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) json_error(400, 'Body harus berupa JSON yang valid.');

// Alamat
$alamat = (isset($body['alamat_kirim']) && is_string($body['alamat_kirim'])) ? trim($body['alamat_kirim']) : '';
if (mb_strlen($alamat) < 10 || mb_strlen($alamat) > 500) {
    json_error(400, 'alamat_kirim wajib diisi, 10 sampai 500 karakter.');
}

// Items
$items = $body['items'] ?? null;
if (!is_array($items) || count($items) < 1 || count($items) > 50) {
    json_error(400, 'items wajib berupa daftar 1 sampai 50 produk.');
}
$minta = [];   // id_produk => total jumlah (produk kembar digabung)
foreach ($items as $it) {
    if (!is_array($it)
        || !isset($it['id_produk'], $it['jumlah'])
        || !is_int($it['id_produk']) || !is_int($it['jumlah'])
        || $it['id_produk'] < 1 || $it['jumlah'] < 1 || $it['jumlah'] > 1000) {
        json_error(400, 'Setiap item wajib berisi id_produk dan jumlah berupa angka bulat positif (jumlah maksimal 1000).');
    }
    $minta[$it['id_produk']] = ($minta[$it['id_produk']] ?? 0) + $it['jumlah'];
}
ksort($minta);   // urutan tetap, mencegah dua transaksi saling tunggu (deadlock)

try {
    mysqli_begin_transaction($koneksi);

    // 1. Kunci baris produk, cek ada dan cukup stok. Harga diambil dari DB, bukan dari klien.
    $stP   = mysqli_prepare($koneksi,
        "SELECT nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
    $baris = [];
    $total = 0;
    foreach ($minta as $idProduk => $jumlah) {
        mysqli_stmt_bind_param($stP, 'i', $idProduk);
        mysqli_stmt_execute($stP);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stP));
        if (!$p) {
            throw new ApiException("Produk dengan id $idProduk tidak ditemukan.", 404);
        }
        if ($jumlah > (int)$p['stok']) {
            throw new ApiException("Stok \"{$p['nama_produk']}\" tidak cukup (tersisa {$p['stok']}).", 422);
        }
        $harga   = (int)$p['harga'];
        $total  += $harga * $jumlah;
        $baris[] = [$idProduk, $jumlah, $harga];
    }
    mysqli_stmt_close($stP);

    // 2. Simpan header pesanan
    $st = mysqli_prepare($koneksi, "INSERT INTO pesanan (id_user, total, alamat_kirim) VALUES (?,?,?)");
    mysqli_stmt_bind_param($st, 'iis', $idUser, $total, $alamat);
    mysqli_stmt_execute($st);
    $idPesanan = mysqli_insert_id($koneksi);
    mysqli_stmt_close($st);

    // 3. Simpan detail (harga disalin) dan kurangi stok
    $stD = mysqli_prepare($koneksi,
        "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan) VALUES (?,?,?,?)");
    $stS = mysqli_prepare($koneksi,
        "UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?");
    foreach ($baris as [$idProduk, $jumlah, $harga]) {
        mysqli_stmt_bind_param($stD, 'iiii', $idPesanan, $idProduk, $jumlah, $harga);
        mysqli_stmt_execute($stD);

        mysqli_stmt_bind_param($stS, 'iii', $jumlah, $idProduk, $jumlah);
        mysqli_stmt_execute($stS);
        if (mysqli_stmt_affected_rows($stS) !== 1) {
            throw new ApiException('Stok berubah saat diproses, silakan coba lagi.', 409);
        }
    }
    mysqli_stmt_close($stD);
    mysqli_stmt_close($stS);

    // 4. Semua sukses -> simpan permanen
    mysqli_commit($koneksi);

} catch (ApiException $ex) {
    mysqli_rollback($koneksi);                    // batalkan semua perubahan
    json_error($ex->getCode(), $ex->getMessage());
} catch (Throwable $ex) {
    mysqli_rollback($koneksi);
    throw $ex;                                    // ditangani handler: JSON 500 pesan umum
}

header('Location: ' . BASE_URL . '/api/pesanan.php?id=' . $idPesanan);
json_out([
    'status' => 'ok',
    'pesan'  => 'Pesanan berhasil dibuat.',
    'data'   => ['id_pesanan' => (int)$idPesanan, 'total' => $total, 'status' => 'baru'],
], 201);