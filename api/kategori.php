<?php
/** @var mysqli $koneksi disiapkan oleh halaman yang meng-include file ini */
require_once __DIR__ . '/../includes/api.php';
api_metode(['GET']);

$r    = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori");
$data = [];
while ($row = mysqli_fetch_assoc($r)) {
    $data[] = [
        'id'   => (int)$row['id_kategori'],
        'nama' => $row['nama_kategori'],
    ];
}

json_out(['status' => 'ok', 'data' => $data]);