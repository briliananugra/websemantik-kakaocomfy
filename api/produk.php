<?php
/** @var mysqli $koneksi disiapkan oleh halaman yang meng-include file ini */
require_once __DIR__ . '/../includes/api.php';
api_metode(['GET']);

// Ambil parameter angka bulat dari URL. Tidak valid -> 400.
function param_angka(string $nama, int $default, int $min, int $max): int {
    if (!isset($_GET[$nama])) return $default;
    $v = $_GET[$nama];
    if (!is_string($v) || !ctype_digit($v) || (int)$v < $min || (int)$v > $max) {
        json_error(400, "Parameter $nama harus berupa angka bulat $min sampai $max.");
    }
    return (int)$v;
}

// Satu baris produk -> bentuk JSON untuk daftar
function bentuk_produk(array $r): array {
    return [
        'id'         => (int)$r['id_produk'],
        'nama'       => $r['nama_produk'],
        'kategori'   => $r['nama_kategori'],
        'harga'      => (int)$r['harga'],
        'stok'       => (int)$r['stok'],
        'gambar_url' => url_gambar_api($r['gambar']),
    ];
}

// ================= DETAIL: ?id=... =================
if (isset($_GET['id'])) {
    $id = param_angka('id', 0, 1, 2147483647);

    $st = mysqli_prepare($koneksi,
        "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar,
                k.nama_kategori, u.nama_lengkap AS penjual
         FROM produk p
         JOIN kategori k ON k.id_kategori = p.id_kategori
         JOIN users u    ON u.id_user = p.id_penjual
         WHERE p.id_produk = ?");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if (!$r) json_error(404, 'Produk tidak ditemukan.');

    json_out([
        'status' => 'ok',
        'data'   => [
            'id'         => (int)$r['id_produk'],
            'nama'       => $r['nama_produk'],
            'deskripsi'  => $r['deskripsi'],
            'kategori'   => $r['nama_kategori'],
            'penjual'    => $r['penjual'],
            'harga'      => (int)$r['harga'],
            'stok'       => (int)$r['stok'],
            'gambar_url' => url_gambar_api($r['gambar']),
        ],
    ]);
}

// ================= DAFTAR: q, kategori, page, per_page =================
$halaman = param_angka('page', 1, 1, 1000000);
$perHal  = param_angka('per_page', 10, 1, 50);
$kat     = param_angka('kategori', 0, 1, 2147483647); // 0 = tanpa filter
$q       = (isset($_GET['q']) && is_string($_GET['q'])) ? trim($_GET['q']) : '';

$where = ['1=1'];
$tipe  = '';
$param = [];
if ($q !== '') {
    $where[] = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $like  = '%' . $q . '%';
    $tipe .= 'ss';
    array_push($param, $like, $like);
}
if ($kat > 0) {
    $where[] = "p.id_kategori = ?";
    $tipe   .= 'i';
    $param[] = $kat;
}
$sqlWhere = implode(' AND ', $where);

// Hitung total untuk meta
$st = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM produk p WHERE $sqlWhere");
if ($param) mysqli_stmt_bind_param($st, $tipe, ...$param);
mysqli_stmt_execute($st);
mysqli_stmt_bind_result($st, $total);
mysqli_stmt_fetch($st);
mysqli_stmt_close($st);
$total = (int)$total;

// $perHal dan $offset sudah pasti integer, aman ditulis langsung
$offset = ($halaman - 1) * $perHal;
$st = mysqli_prepare($koneksi,
    "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar, k.nama_kategori
     FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori
     WHERE $sqlWhere
     ORDER BY p.id_produk
     LIMIT $perHal OFFSET $offset");
if ($param) mysqli_stmt_bind_param($st, $tipe, ...$param);
mysqli_stmt_execute($st);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

json_out([
    'status' => 'ok',
    'meta'   => [
        'halaman'        => $halaman,
        'per_halaman'    => $perHal,
        'total'          => $total,
        'jumlah_halaman' => max(1, (int)ceil($total / $perHal)),
    ],
    'data'   => array_map('bentuk_produk', $rows),
]);