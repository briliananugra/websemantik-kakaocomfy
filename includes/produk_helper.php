<?php
function ambil_input_produk(): array {
    return [
        'id_kategori' => (int)($_POST['id_kategori'] ?? 0),
        'nama_produk' => trim($_POST['nama_produk'] ?? ''),
        'deskripsi'   => trim($_POST['deskripsi'] ?? ''),
        'harga'       => trim($_POST['harga'] ?? ''),
        'stok'        => trim($_POST['stok'] ?? ''),
    ];
}

function daftar_kategori(mysqli $koneksi): array {
    return mysqli_fetch_all(
        mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori"),
        MYSQLI_ASSOC);
}

function validasi_produk(mysqli $koneksi, array $d): array {
    $er = [];

    $st = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM kategori WHERE id_kategori = ?");
    mysqli_stmt_bind_param($st, 'i', $d['id_kategori']);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $ada);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    if (!$ada) $er[] = 'Kategori wajib dipilih.';

    if ($d['nama_produk'] === '' || mb_strlen($d['nama_produk']) > 150) {
        $er[] = 'Nama produk wajib diisi, maksimal 150 karakter.';
    }
    if (mb_strlen($d['deskripsi']) > 2000) {
        $er[] = 'Deskripsi maksimal 2000 karakter.';
    }
    if (!ctype_digit($d['harga']) || (int)$d['harga'] < 1 || (int)$d['harga'] > 999999999) {
        $er[] = 'Harga harus angka bulat antara 1 dan 999.999.999.';
    }
    if (!ctype_digit($d['stok']) || (int)$d['stok'] > 100000) {
        $er[] = 'Stok harus angka bulat 0 sampai 100.000.';
    }
    return $er;
}

// ---------- Upload gambar ----------
// Mengembalikan ['tmp' => ..., 'nama' => ...] atau null (tidak ada file / error ditambahkan ke $er)
function cek_gambar(array &$er): ?array {
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES['gambar'];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $er[] = 'Upload gambar gagal (kode ' . $f['error'] . ').';
        return null;
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        $er[] = 'Ukuran gambar maksimal 2 MB.';
        return null;
    }
    // Periksa ISI file, bukan hanya nama/ekstensinya
    $info = @getimagesize($f['tmp_name']);
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ekstensi[$info[2]])) {
        $er[] = 'Gambar harus berformat JPG, PNG, atau WEBP.';
        return null;
    }
    return ['tmp' => $f['tmp_name'], 'nama' => bin2hex(random_bytes(8)) . '.' . $ekstensi[$info[2]]];
}

function simpan_gambar(array $g): bool {
    $folder = __DIR__ . '/../uploads/produk';
    if (!is_dir($folder)) mkdir($folder, 0755, true);
    return move_uploaded_file($g['tmp'], $folder . '/' . $g['nama']);
}

function hapus_gambar(?string $nama): void {
    if (!$nama) return;
    $path = __DIR__ . '/../uploads/produk/' . basename($nama);
    if (is_file($path)) unlink($path);
}

// Alamat gambar untuk <img>, atau null kalau produk belum punya gambar
function url_gambar(?string $nama): ?string {
    return $nama ? BASE_URL . '/uploads/produk/' . rawurlencode($nama) : null;
}