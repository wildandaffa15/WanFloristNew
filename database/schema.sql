-- ============================================================
-- WanFlorist — DDL Lengkap Semua Tabel
-- Charset: utf8mb4 | Engine: InnoDB
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabel: kategori
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kategori (
    id_kategori   INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100)    NOT NULL,
    slug          VARCHAR(100)    NOT NULL UNIQUE,
    ikon_emoji    VARCHAR(10)     DEFAULT NULL,   -- contoh: '🌹'
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: produk
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produk (
    id_produk   INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT UNSIGNED    NOT NULL,
    nama_produk VARCHAR(200)    NOT NULL,
    deskripsi   TEXT            DEFAULT NULL,
    harga       DECIMAL(12,2)   NOT NULL CHECK (harga > 0),
    foto        VARCHAR(255)    DEFAULT 'buket_mawar_pink.webp',
    status      ENUM('tersedia', 'nonaktif') NOT NULL DEFAULT 'tersedia',
    is_featured TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: pesanan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    no_pesanan          VARCHAR(20)     NOT NULL UNIQUE,    -- format: WF-YYYYMMDD-XXXX
    nama_pembeli        VARCHAR(100)    NOT NULL,
    no_hp               VARCHAR(15)     NOT NULL,
    tanggal_ambil       DATE            NOT NULL,
    metode_pengambilan  ENUM('ambil_sendiri', 'cod') NOT NULL,
    catatan             TEXT            DEFAULT NULL,
    status              ENUM(
                            'menunggu_konfirmasi',
                            'diproses',
                            'selesai',
                            'dibatalkan'
                        ) NOT NULL DEFAULT 'menunggu_konfirmasi',
    total_harga         DECIMAL(12,2)   NOT NULL DEFAULT 0,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: detail_pesanan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail    INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_pesanan   INT UNSIGNED    NOT NULL,
    id_produk    INT UNSIGNED    NOT NULL,
    nama_produk  VARCHAR(200)    NOT NULL,   -- snapshot nama saat memesan
    harga_satuan DECIMAL(12,2)   NOT NULL,   -- snapshot harga saat memesan
    jumlah       INT UNSIGNED    NOT NULL DEFAULT 1 CHECK (jumlah >= 1),
    subtotal     DECIMAL(12,2)   NOT NULL,   -- harga_satuan * jumlah
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_produk)  REFERENCES produk(id_produk)   ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: dp (Down Payment)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dp (
    id_dp        INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_pesanan   INT UNSIGNED    NOT NULL UNIQUE,    -- satu pesanan, satu DP
    jumlah_dp    DECIMAL(12,2)   NOT NULL CHECK (jumlah_dp > 0),
    metode       ENUM('transfer_bca', 'transfer_mandiri', 'transfer_bni', 'lainnya') NOT NULL,
    bukti_foto   VARCHAR(255)    DEFAULT NULL,
    dicatat_pada TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: lunas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS lunas (
    id_lunas     INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    id_pesanan   INT UNSIGNED    NOT NULL UNIQUE,    -- satu pesanan, satu record lunas
    jumlah_lunas DECIMAL(12,2)   NOT NULL CHECK (jumlah_lunas > 0),
    metode       ENUM('transfer_bca', 'transfer_mandiri', 'transfer_bni', 'cod', 'lainnya') NOT NULL,
    dicatat_pada TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: pengguna
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)     NOT NULL UNIQUE,
    password     VARCHAR(255)    NOT NULL,   -- bcrypt hash via password_hash()
    nama_lengkap VARCHAR(100)    NOT NULL,
    role         ENUM('owner', 'staf') NOT NULL DEFAULT 'staf',
    is_active    TINYINT(1)      NOT NULL DEFAULT 1,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: status_toko
-- Selalu berisi tepat 1 baris; diisi via seed.sql
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS status_toko (
    id         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    status     ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: stok_bahan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stok_bahan (
    id_bahan      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nama_bahan    VARCHAR(150)    NOT NULL,
    satuan        VARCHAR(30)     NOT NULL DEFAULT 'pcs',   -- pcs, lembar, meter, kg
    stok_saat_ini INT UNSIGNED    NOT NULL DEFAULT 0,
    stok_minimum  INT UNSIGNED    NOT NULL DEFAULT 5,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabel: pengeluaran
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengeluaran (
    id_pengeluaran INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    keterangan     VARCHAR(255)    NOT NULL,
    jumlah         DECIMAL(12,2)   NOT NULL CHECK (jumlah > 0),
    tanggal        DATE            NOT NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Indeks untuk performa query
-- ============================================================

-- Indeks untuk pencarian dan filter katalog
CREATE INDEX idx_produk_status   ON produk(status);
CREATE INDEX idx_produk_featured ON produk(is_featured);
CREATE INDEX idx_produk_kategori ON produk(id_kategori);

-- Indeks untuk pencarian pesanan
CREATE INDEX idx_pesanan_no      ON pesanan(no_pesanan);
CREATE INDEX idx_pesanan_status  ON pesanan(status);
CREATE INDEX idx_pesanan_created ON pesanan(created_at);

-- Indeks untuk laporan keuangan berdasarkan tanggal
CREATE INDEX idx_lunas_dicatat   ON lunas(dicatat_pada);
CREATE INDEX idx_pengeluaran_tgl ON pengeluaran(tanggal);

SET FOREIGN_KEY_CHECKS = 1;
