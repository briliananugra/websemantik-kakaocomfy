# Kakao Comfy: Marketplace Perabot Rumah Tangga

Aplikasi web marketplace sederhana bertema fantasi (terinspirasi dunia petualangan, tanpa aset resmi game mana pun). Penjual mengelola produk, pembeli memilih produk, memasukkan ke keranjang, lalu membuat pesanan yang disimpan ke database.

Tugas UTS mata kuliah Web Semantik, Soal 11.

**NPM:** 2455201233
**Penyusun:** Brilian Anugra
**Demo online:** http://websematic-2455201233.infinityfree.io/kakaocomfy/
**Repository:** https://github.com/briliananugra/websemantik-kakaocomfy

> Semua akun dan data di sini adalah data contoh. Demo online berjalan di HTTP tanpa enkripsi, jangan memakai password aslimu.

## Teknologi

| Bagian | Yang dipakai |
|---|---|
| Bahasa | PHP 8.x native, tanpa framework |
| Database | MySQL lewat MySQLi (prepared statement) |
| Antarmuka | Bootstrap 5.3.3 dan Bootstrap Icons (CDN) |
| Lokal | Laragon |
| Hosting demo | InfinityFree |

## Fitur

| Role | Yang bisa dilakukan |
|---|---|
| Pengunjung | Melihat daftar produk, mencari, memfilter kategori, melihat detail produk |
| Pembeli | Menambah produk ke keranjang, mengubah jumlah, menghapus item, checkout, melihat riwayat pesanan |
| Penjual | CRUD produk milik sendiri dengan upload gambar (JPG, PNG, WEBP, maksimal 2 MB) |

## Akun demo

Semua akun memakai password `password123`.

| Role | Username |
|---|---|
| Penjual | `penjual1` |
| Pembeli | `pembeli1` |

## Alur transaksi

Pembeli memilih produk, menambah ke keranjang, lalu checkout. Saat checkout, server menjalankan **satu transaksi database**: menyimpan pesanan, menyimpan detail (harga disalin), mengurangi stok, dan mengosongkan keranjang. Jika salah satu langkah gagal, semua perubahan dibatalkan (rollback).

## Struktur database

Enam tabel InnoDB: `users`, `kategori`, `produk`, `keranjang`, `pesanan`, `detail_pesanan`.

```
users ──< produk >── kategori
users ──< keranjang >── produk
users ──< pesanan ──< detail_pesanan >── produk
```

Skema dan data contoh ada di [database/kakaocomfy.sql](database/kakaocomfy.sql).

## Keamanan

- Prepared statement di semua query (SQL Injection).
- `password_hash` dan `password_verify`, `session_regenerate_id` saat login.
- Token CSRF pada semua form POST.
- `htmlspecialchars` pada semua output (XSS).
- Hak akses diperiksa di server (`wajib_role`), dan data dibatasi per pemilik (`AND id_user = ?`, `AND id_penjual = ?`).
- Upload gambar diperiksa isi filenya dengan `getimagesize()`, nama diacak, dan file PHP di folder `uploads` diblokir.
- `config/config.php` (kredensial hosting) tidak ada di repository.

## Menjalankan di komputer sendiri

1. Salin proyek ke `C:\laragon\www\kakaocomfy` (nama folder harus `kakaocomfy`).
2. Buat database kosong `kakaocomfy` (charset `utf8mb4`).
3. Impor `database/kakaocomfy.sql`.
4. Buka `http://localhost/kakaocomfy/`. Koneksi bawaan: `root` tanpa password.
5. Buat folder `uploads/produk` kalau belum ada.

Untuk hosting, salin `config/config.example.php` menjadi `config/config.php`, lalu isi data MySQL hosting.

## Keterbatasan

- Penjual belum punya halaman pesanan masuk dan belum bisa mengubah status pesanan.
- Pembayaran tidak dibuat, hanya status pesanan.
- Demo online memakai HTTP, bukan HTTPS.