# Kakao Comfy: Marketplace Perabot Rumah Tangga

Aplikasi web marketplace sederhana bertema fantasi (terinspirasi dunia petualangan, tanpa aset resmi game mana pun). Penjual mengelola produk, pembeli memilih produk, memasukkan ke keranjang, lalu membuat pesanan yang disimpan ke database.

Tugas UTS mata kuliah Web Semantik, Soal 11 (aplikasi web) dan Soal 12 (REST API).

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

## Soal 12: REST API

REST API ini membaca dan menulis ke database `kakaocomfy` yang sama dengan aplikasi web Soal 11. API memakai PHP Native dan MySQLi, dijalankan dan diuji di XAMPP. Kode ada di folder [`api/`](api/). Kode Soal 11 saja bisa dilihat lewat tag `soal-11`.

### Endpoint

| No | Endpoint | Fungsi | Auth |
|---|---|---|---|
| 1 | `GET /api/produk.php` | Daftar produk (opsional `q`, `kategori`, `page`, `per_page`, maksimal 50) | Publik |
| 2 | `GET /api/produk.php?id=1` | Detail satu produk | Publik |
| 3 | `GET /api/kategori.php` | Daftar kategori | Publik |
| 4 | `POST /api/pesanan.php` | Membuat pesanan baru | Basic Auth (pembeli) |
| 5 | `GET /api/pesanan.php?id=1` | Melihat pesanan milik sendiri | Basic Auth (pembeli) |

Respons sukses berbentuk `{"status":"ok","data":...}`. Respons error berbentuk `{"status":"error","pesan":"..."}`.

### Contoh POST pesanan

```bash
curl -u pembeli1:password123 -X POST "http://localhost/kakaocomfy/api/pesanan.php" \
  -H "Content-Type: application/json" \
  -d '{"alamat_kirim":"Budi, Jl. Merdeka No. 5, Bengkulu 38225","items":[{"id_produk":1,"jumlah":2}]}'
```

Server menentukan pembeli dari Basic Auth dan mengambil harga dari database. Body tidak boleh menentukan `id_user` atau harga. Pesanan disimpan dalam satu transaksi: kalau satu langkah gagal, semua perubahan dibatalkan.

### Kode status

| Kode | Arti | Contoh kejadian |
|---|---|---|
| 200 | Berhasil | Data dikembalikan |
| 201 | Dibuat | Pesanan berhasil disimpan |
| 400 | Permintaan tidak valid | JSON rusak, `id` bukan angka, `items` kosong |
| 401 | Belum login atau kredensial salah | POST tanpa Basic Auth |
| 403 | Login sah tapi tidak berhak | Akun penjual membuat pesanan |
| 404 | Tidak ditemukan | Produk atau pesanan tidak ada |
| 405 | Metode tidak diizinkan | POST ke `produk.php` |
| 422 | Tidak bisa diproses | Stok tidak cukup |
| 500 | Kesalahan server | Pesan umum, detail masuk log server |

### Menjalankan di XAMPP

1. Salin proyek ke `C:\xampp\htdocs\kakaocomfy`. Nama folder harus `kakaocomfy`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buat database `kakaocomfy` (utf8mb4) di phpMyAdmin, lalu impor `database/kakaocomfy.sql`.
4. Buka `http://localhost/kakaocomfy/api/produk.php`.

Koneksi bawaan memakai `root` tanpa password, sama dengan default XAMPP. File `.htaccess` di root meneruskan header `Authorization` ke PHP supaya Basic Auth terbaca.

### Keterbatasan API

- Pesanan hanya bisa dibuat dan dilihat satu per satu. Belum ada daftar semua pesanan dan belum ada ubah atau batal pesanan.
- Autentikasi memakai Basic Auth, yang aman hanya jika dipakai lewat HTTPS.