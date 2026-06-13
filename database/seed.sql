-- ============================================================
-- WanFlorist — Data Awal (Seed)
-- Jalankan SETELAH schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Kategori (minimal 5)
-- ------------------------------------------------------------
INSERT INTO kategori (nama_kategori, slug, ikon_emoji, is_active) VALUES
('Mawar',       'mawar',       '🌹', 1),
('Sunflower',   'sunflower',   '🌻', 1),
('Tulip',       'tulip',       '🌷', 1),
('Wedding',     'wedding',     '💍', 1),
('Wisuda',      'wisuda',      '🎓', 1),
('Bunga Meja',  'bunga-meja',  '🌸', 1),
('Hadiah',      'hadiah',      '🎁', 1);

-- ------------------------------------------------------------
-- Produk (minimal 8 — foto pakai placeholder)
-- ------------------------------------------------------------
INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, foto, status, is_featured) VALUES
-- Mawar (id_kategori = 1)
(1, 'Buket Mawar Merah Romantis',
 'Buket mawar merah segar pilihan, dikemas elegan dengan kertas kraft dan pita sutra merah. Cocok untuk ulang tahun, anniversary, maupun ungkapan perasaan.',
 150000.00, 'placeholder.jpg', 'tersedia', 1),

(1, 'Buket Mawar Pink Cantik',
 'Rangkaian mawar pink lembut yang indah, diikat dengan pita pastel. Memberikan kesan hangat dan penuh kasih sayang.',
 125000.00, 'placeholder.jpg', 'tersedia', 0),

-- Sunflower (id_kategori = 2)
(2, 'Buket Sunflower Ceria',
 'Buket bunga matahari segar yang cerah dan menyenangkan. Dikemas dengan kertas coklat alami dan twine untuk tampilan rustic yang menawan.',
 135000.00, 'placeholder.jpg', 'tersedia', 1),

(2, 'Buket Mixed Sunflower & Baby Breath',
 'Kombinasi bunga matahari dan baby breath yang anggun. Perpaduan warna kuning dan putih menciptakan kesan segar dan elegan.',
 145000.00, 'placeholder.jpg', 'tersedia', 0),

-- Tulip (id_kategori = 3)
(3, 'Buket Tulip Ungu Elegan',
 'Buket tulip ungu premium yang mewah dan anggun. Pilihan sempurna untuk hadiah spesial kepada orang tersayang.',
 175000.00, 'placeholder.jpg', 'tersedia', 1),

-- Wedding (id_kategori = 4)
(4, 'Hand Bouquet Wedding Premium',
 'Buket tangan pernikahan premium dengan pilihan bunga mawar, lily, dan baby breath. Dikonsultasikan terlebih dahulu untuk menyesuaikan tema pernikahan.',
 450000.00, 'placeholder.jpg', 'tersedia', 1),

-- Wisuda (id_kategori = 5)
(5, 'Buket Wisuda Colorful Jumbo',
 'Buket wisuda besar yang meriah dan penuh warna. Terdiri dari berbagai bunga segar dengan aksen bintang dan balon. Bisa request warna sesuai toga.',
 200000.00, 'placeholder.jpg', 'tersedia', 1),

(5, 'Buket Wisuda Elegant White',
 'Buket wisuda elegan dominasi putih dengan sentuhan hijau daun. Memberikan kesan formal dan berkelas untuk momen kelulusan bersejarah.',
 185000.00, 'placeholder.jpg', 'tersedia', 0),

-- Bunga Meja (id_kategori = 6)
(6, 'Rangkaian Bunga Meja Minimalis',
 'Rangkaian bunga meja estetik bergaya minimalis modern. Cocok untuk dekorasi ruang tamu, meja kerja, atau sebagai hadiah housewarming.',
 95000.00, 'placeholder.jpg', 'tersedia', 0),

-- Hadiah (id_kategori = 7)
(7, 'Hampers Bunga & Coklat',
 'Paket hadiah spesial berisi buket bunga mini dan coklat premium pilihan. Pilihan hadiah yang berkesan untuk berbagai momen spesial.',
 250000.00, 'placeholder.jpg', 'tersedia', 0);

-- ------------------------------------------------------------
-- Pengguna Admin
-- Password: admin123 (bcrypt hash, cost=12)
-- Hash ini dihasilkan dari: password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12])
-- ------------------------------------------------------------
INSERT INTO pengguna (username, password, nama_lengkap, role, is_active) VALUES
(
    'admin',
    '$2y$12$.4cAZ0cNGomiO32Y15dD..haM87OqmuZeKWKYl6Mwk3Gi7Pxnt3s.',
    'Wan Florist Admin',
    'owner',
    1
);

-- ------------------------------------------------------------
-- Status Toko (1 baris — selalu aktif di awal)
-- ------------------------------------------------------------
INSERT INTO status_toko (status) VALUES ('aktif');

-- ------------------------------------------------------------
-- Stok Bahan Awal (contoh data)
-- ------------------------------------------------------------
INSERT INTO stok_bahan (nama_bahan, satuan, stok_saat_ini, stok_minimum) VALUES
('Kertas Kraft Coklat',    'lembar', 50,  20),
('Kertas Kado Glossy',     'lembar', 30,  15),
('Pita Sutra Merah',       'meter',  25,  10),
('Pita Pastel Mix',        'meter',  20,  10),
('Cellophane Wrap',        'lembar', 40,  15),
('Bunga Mawar Merah',      'tangkai', 0,  30),
('Bunga Mawar Pink',       'tangkai', 0,  25),
('Bunga Matahari',         'tangkai', 0,  20),
('Bunga Tulip',            'tangkai', 0,  15),
('Baby Breath',            'ikat',   3,   5),
('Kawat Floral',           'meter',  15,  10),
('Spons Floral (Oasis)',   'buah',   8,   5),
('Kartu Ucapan',           'pcs',   100,  30),
('Twine / Tali Rami',      'meter',  30,  15),
('Plastik Wrapping Bening','lembar', 60,  20);

SET FOREIGN_KEY_CHECKS = 1;
