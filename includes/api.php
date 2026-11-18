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


// ---------- Basic Auth khusus pembeli ----------
// Kalau kredensial dikirim, HANYA itu yang dipakai. Salah = 401. Tidak ada session.
// 401 = "kamu siapa?", 403 = "kamu dikenal, tapi tidak boleh".
function api_wajib_pembeli(): array {
    global $koneksi;

    $user = $_SERVER['PHP_AUTH_USER'] ?? null;
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

    // Cadangan: sebagian server tidak mengisi PHP_AUTH_*, tapi header mentahnya ada
    if ($user === null) {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (stripos($hdr, 'basic ') === 0) {
            $dec = base64_decode(substr($hdr, 6), true);
            if ($dec !== false && strpos($dec, ':') !== false) {
                [$user, $pass] = explode(':', $dec, 2);
            }
        }
    }

    $u = null;
    if ($user !== null) {
        $st = mysqli_prepare($koneksi,
            "SELECT id_user, password_hash, nama_lengkap, role FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $user);
        mysqli_stmt_execute($st);
        $u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
    }

    if (!$u || !password_verify((string)$pass, $u['password_hash'])) {
        header('WWW-Authenticate: Basic realm="Kakao Comfy API"');
        json_error(401, 'Belum login atau kredensial salah. Gunakan Basic Auth.');
    }
    if ($u['role'] !== 'pembeli') {
        json_error(403, 'Hanya akun pembeli yang boleh mengakses endpoint ini.');
    }
    return ['id_user' => (int)$u['id_user'], 'nama' => $u['nama_lengkap']];
}