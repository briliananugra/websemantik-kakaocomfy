<?php
/** @var mysqli $koneksi disiapkan oleh halaman yang meng-include file ini */
require_once __DIR__ . '/../includes/api.php';
api_metode(['GET']);

$r   = mysqli_query($koneksi, "SELECT COUNT(*) FROM produk");
$jml = (int) mysqli_fetch_row($r)[0];

json_out(['status' => 'ok', 'data' => ['pesan' => 'API hidup', 'jumlah_produk' => $jml]]);