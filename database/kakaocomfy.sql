CREATE TABLE users (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  role ENUM('penjual','pembeli') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kategori (
  id_kategori INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE produk (
  id_produk INT AUTO_INCREMENT PRIMARY KEY,
  id_kategori INT NOT NULL,
  id_penjual INT NOT NULL,
  nama_produk VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  harga DECIMAL(12,0) NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  gambar VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori),
  FOREIGN KEY (id_penjual) REFERENCES users(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE keranjang (
  id_keranjang INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_produk INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 1,
  UNIQUE KEY uk_user_produk (id_user, id_produk),
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_produk) REFERENCES produk(id_produk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pesanan (
  id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(12,0) NOT NULL,
  alamat_kirim TEXT NOT NULL,
  status ENUM('baru','diproses','dikirim','selesai') NOT NULL DEFAULT 'baru',
  FOREIGN KEY (id_user) REFERENCES users(id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE detail_pesanan (
  id_detail INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan INT NOT NULL,
  id_produk INT NOT NULL,
  jumlah INT NOT NULL,
  harga_satuan DECIMAL(12,0) NOT NULL,
  FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
  FOREIGN KEY (id_produk) REFERENCES produk(id_produk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data contoh. Semua akun berpassword: password123
INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES
('penjual1', '$2y$10$ZAlToJRVxQtnW/vjD9ZOQuqb7g.9GvoabAM.q0x7kdbKzdtZxuFHi', 'Toko Kakao Comfy', 'penjual'),
('pembeli1', '$2y$10$ZAlToJRVxQtnW/vjD9ZOQuqb7g.9GvoabAM.q0x7kdbKzdtZxuFHi', 'Pembeli Contoh', 'pembeli');

INSERT INTO kategori (nama_kategori) VALUES
('Ruang Tamu'), ('Kamar Tidur'), ('Dapur & Ruang Makan'), ('Dekorasi');