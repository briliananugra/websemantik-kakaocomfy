<?php
// Pembantu semua endpoint API. Jawaban selalu JSON.
// Sengaja TIDAK memuat includes/helper.php karena file itu memulai session.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/koneksi.php';

ini_set('display_errors', '0'); // error PHP jangan bocor sebagai HTML, merusak JSON
header('Content-Type: application/json; charset=utf-8');

// Error MySQL dilempar sebagai exception, ditangkap handler di bawah
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function json_out($data, int $kode = 200) {
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function json_error(int $kode, string $pesan) {
    json_out(['status' => 'error', 'pesan' => $pesan], $kode);
}

// Error tak terduga -> JSON 500 pesan umum, detailnya masuk log server
set_exception_handler(function ($ex) {
    error_log($ex->getMessage());
    json_error(500, 'Terjadi kesalahan pada server.');
});

// Contoh: api_metode(['GET']) atau api_metode(['GET', 'POST'])
function api_metode(array $boleh) {
    $metode = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($metode, $boleh, true)) {
        header('Allow: ' . implode(', ', $boleh));
        json_error(405, 'Metode tidak diizinkan. Endpoint ini hanya menerima ' . implode(', ', $boleh) . '.');
    }
}

// Alamat gambar lengkap (http://...), atau null kalau produk tidak punya gambar
function url_gambar_api(?string $nama): ?string {
    if (!$nama) return null;
    $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $skema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
         . BASE_URL . '/uploads/produk/' . rawurlencode($nama);
}