# Dokumen Desain Teknis — WanFlorist

## Overview

WanFlorist adalah sistem manajemen pesanan berbasis web untuk toko buket bunga UMKM di Singojuruh, Banyuwangi. Sistem ini dibangun sepenuhnya dengan **PHP Native**, **MySQL**, **Pure CSS**, dan **Vanilla JavaScript** — tanpa framework apapun, sesuai batasan teknis proyek.

Sistem melayani dua kelompok pengguna yang memiliki antarmuka terpisah:
- **Pembeli (Publik)** — mengakses katalog, membuat pesanan, dan melacak status pesanan tanpa akun.
- **Admin (Pemilik/Staf)** — mengelola seluruh operasional toko melalui panel manajemen yang dilindungi autentikasi session PHP.

Filosofi desain mengikuti sistem visual yang ditetapkan di `DESIGN.md`: elegan, hangat, dan personal — mencerminkan identitas toko buket artisanal lokal, bukan sistem enterprise korporat.

---

## Architecture

### Struktur Direktori

```
WanFloristWebsite/
├── index.php                    # Halaman beranda (Landing Page)
├── login.php                    # Halaman login admin (berdiri sendiri)
│
├── pages/                       # Halaman publik (selain beranda)
│   ├── katalog.php
│   ├── detail-produk.php
│   ├── pemesanan.php
│   └── cek-pesanan.php
│
├── admin/                       # Seluruh halaman panel admin
│   ├── index.php                # Dashboard admin
│   ├── pesanan.php
│   ├── produk.php
│   ├── stok.php
│   ├── pembayaran.php
│   ├── pengeluaran.php
│   └── laporan.php
│
├── components/                  # Komponen PHP yang dapat digunakan ulang
│   ├── navbar.php               # Navbar publik (sticky top)
│   ├── footer.php               # Footer publik
│   ├── sidebar.php              # Sidebar admin (fixed left, dark-bg)
│   └── head.php                 # <head> HTML umum (meta, fonts, CSS)
│
├── config/                      # File konfigurasi sistem
│   ├── database.php             # Singleton koneksi PDO
│   ├── session.php              # Inisialisasi & validasi session
│   └── helpers.php             # Fungsi utilitas (Pretty_Printer, generator)
│
├── assets/
│   ├── css/
│   │   ├── main.css             # Variabel CSS & style global
│   │   ├── public.css           # Style khusus halaman publik
│   │   └── admin.css            # Style khusus panel admin
│   ├── js/
│   │   ├── main.js              # JavaScript global
│   │   ├── pemesanan.js         # Validasi form pemesanan (client-side)
│   │   ├── toggle-status.js     # AJAX toggle status toko
│   │   └── admin-modal.js       # Logika modal di panel admin
│   └── img/
│       ├── produk/              # Foto produk yang diunggah
│       └── placeholder.jpg      # Foto default produk
│
├── database/
│   ├── schema.sql               # DDL lengkap semua tabel
│   └── seed.sql                 # Data awal (kategori, produk, admin, status_toko)
│
└── .htaccess                    # Konfigurasi Apache (redirect, keamanan direktori)
```

### Alur Request HTTP

```
Browser → Apache/Nginx → .htaccess
                              │
                    ┌─────────┴──────────┐
                    │ Public Request      │ Admin Request
                    │ (index.php,        │ (admin/*.php,
                    │  pages/*.php)       │  login.php)
                    └─────────┬──────────┘
                              │
                    ┌─────────▼──────────┐
                    │   PHP File Entry    │
                    │ 1. require_once     │
                    │    config/session   │
                    │ 2. require_once     │
                    │    config/database  │
                    │ 3. Logika bisnis    │
                    │    (query, validasi)│
                    │ 4. Output HTML      │
                    │    (komponen +      │
                    │     konten halaman) │
                    └────────────────────┘
```

Setiap halaman PHP mengikuti pola ini:
1. **Blok logika** di bagian atas file (sebelum output HTML): require config, validasi session (khusus admin), query database, validasi POST.
2. **Blok presentasi** setelah blok logika: output HTML menggunakan variabel yang sudah disiapkan.

Tidak ada front controller (`router.php`) terpusat — routing dilakukan secara langsung oleh filesystem PHP, dibantu parameter query string (`?id=`, `?page=`, `?kategori=`).

### Routing Query String

| URL | File yang Dieksekusi | Parameter |
|---|---|---|
| `/` | `index.php` | — |
| `/pages/katalog.php` | `pages/katalog.php` | `?q=`, `?kategori=`, `?urut=`, `?page=` |
| `/pages/detail-produk.php?id=5` | `pages/detail-produk.php` | `?id=` (wajib) |
| `/pages/pemesanan.php?id=5` | `pages/pemesanan.php` | `?id=` (opsional, pra-isi produk) |
| `/pages/cek-pesanan.php` | `pages/cek-pesanan.php` | `?no=` (hasil POST redirect) |
| `/login.php` | `login.php` | — |
| `/admin/index.php` | `admin/index.php` | — |
| `/admin/pesanan.php` | `admin/pesanan.php` | `?q=`, `?status=`, `?page=` |

### Pola Keamanan File

File di direktori `config/` dilindungi dari akses langsung browser melalui `.htaccess`:

```apache
<FilesMatch "\.(php)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

Hanya file yang di-`require_once` dari file PHP utama yang dapat diakses.

---

## Components and Interfaces

### Komponen Publik

#### `components/navbar.php`
Navbar sticky di bagian atas setiap halaman publik. Membaca status toko dari database saat di-include untuk menentukan banner ketersediaan.

```
Input:  $pdo (koneksi database dari config/database.php)
Output: HTML <nav> + <div class="banner-status">
```

Tautan navigasi: Beranda, Produk, Cek Pesanan, Tentang Kami, Kontak.
Banner status toko ditampilkan di bawah navbar — hijau dengan animasi pulse jika `aktif`, abu-abu jika `nonaktif`.

#### `components/footer.php`
Footer yang sama untuk semua halaman publik. Berisi informasi kontak, tautan cepat, dan nama toko. Tidak memerlukan input database.

#### `components/head.php`
Tag `<head>` standar: charset, viewport, judul halaman (diterima sebagai variabel `$page_title`), link Google Fonts (Playfair Display + Inter), link ke `assets/css/main.css`.

### Komponen Admin

#### `components/sidebar.php`
Sidebar navigasi tetap di sisi kiri semua halaman admin. Latar belakang `#1E1040` (dark-bg). Lebar 240px di desktop, tersembunyi dan dapat ditampilkan via tombol hamburger di mobile.

```
Input:  $active_page (string: 'dashboard', 'pesanan', 'produk', dll.)
Output: HTML <aside> dengan item navigasi, item aktif di-highlight
```

Menu item: Dashboard, Pesanan, Produk, Stok Bahan, Pembayaran, Pengeluaran, Laporan, Pengaturan, Keluar.
Item aktif mendapat background `rgba(107,33,168,0.4)`, teks putih, border kiri 3px `primary-light`.

### Halaman Publik

| Halaman | File | Komponen yang Di-include |
|---|---|---|
| Beranda | `index.php` | head, navbar, footer |
| Katalog | `pages/katalog.php` | head, navbar, footer |
| Detail Produk | `pages/detail-produk.php` | head, navbar, footer |
| Form Pemesanan | `pages/pemesanan.php` | head, navbar, footer |
| Cek Pesanan | `pages/cek-pesanan.php` | head, navbar, footer |

### Halaman Admin

| Halaman | File | Komponen yang Di-include |
|---|---|---|
| Login | `login.php` | head saja (tanpa navbar/sidebar) |
| Dashboard | `admin/index.php` | head, sidebar |
| Manajemen Pesanan | `admin/pesanan.php` | head, sidebar |
| Manajemen Produk | `admin/produk.php` | head, sidebar |
| Stok Bahan | `admin/stok.php` | head, sidebar |
| Pencatatan Pembayaran | `admin/pembayaran.php` | head, sidebar |
| Pencatatan Pengeluaran | `admin/pengeluaran.php` | head, sidebar |
| Laporan Otomatis | `admin/laporan.php` | head, sidebar |

### Antarmuka `config/database.php`

```php
// Mengembalikan instance PDO singleton
function get_pdo(): PDO

// Konfigurasi internal (tidak di-expose):
// DSN: mysql:host=localhost;dbname=wanflorist;charset=utf8mb4
// Atribut: ERRMODE_EXCEPTION, DEFAULT_FETCH_MODE=ASSOC, EMULATE_PREPARES=false
```

### Antarmuka `config/helpers.php`

```php
// Format angka menjadi string mata uang Rupiah
// Contoh: format_rupiah(250000) → "Rp 250.000"
function format_rupiah(int $angka): string

// Format objek DateTime menjadi string tanggal Indonesia
// Contoh: format_tanggal_id(new DateTime('2025-01-15')) → "Rabu, 15 Januari 2025"
function format_tanggal_id(DateTime $tanggal): string

// Generate nomor pesanan unik format WF-YYYYMMDD-XXXX
// XXXX adalah nomor urut hari ini, di-reset setiap hari
function generate_no_pesanan(PDO $pdo): string

// Sanitasi output HTML (wrapper htmlspecialchars dengan encoding UTF-8)
// Wajib digunakan untuk semua output dari input pengguna atau database
function e(string $str): string

// Validasi CSRF token dari POST request
// Mengembalikan true jika token cocok dengan session, false jika tidak
function validate_csrf(string $token): bool

// Generate CSRF token baru dan simpan ke session
function generate_csrf(): string
```

### Endpoint AJAX

Semua endpoint AJAX menerima `Content-Type: application/json` dan mengembalikan JSON.

| Endpoint | Method | Fungsi |
|---|---|---|
| `admin/ajax/toggle-status.php` | POST | Toggle status toko aktif/nonaktif |
| `admin/ajax/update-status-pesanan.php` | POST | Ubah status pesanan via modal |
| `admin/ajax/update-stok.php` | POST | Perbarui jumlah stok bahan |

Semua endpoint AJAX memvalidasi CSRF token dari body request sebelum memproses.

---

## Data Models

### Diagram Relasi Tabel

```
pengguna (1) ─────────── (session admin)

kategori (1) ──────────< produk (n)
                              │
                    (1) ──────┘
               detail_pesanan (n) >──── (1) pesanan
                                              │
                                      ┌───────┼───────┐
                                      │               │
                                    dp (n)        lunas (n)

status_toko (1 baris)

stok_bahan (berdiri sendiri)

pengeluaran (berdiri sendiri)
```

### DDL Lengkap Semua Tabel

#### Tabel `kategori`

```sql
CREATE TABLE kategori (
    id_kategori  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    slug         VARCHAR(100) NOT NULL UNIQUE,
    ikon_emoji   VARCHAR(10)  DEFAULT NULL,   -- contoh: '🌹'
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `produk`

```sql
CREATE TABLE produk (
    id_produk    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kategori  INT UNSIGNED NOT NULL,
    nama_produk  VARCHAR(200) NOT NULL,
    deskripsi    TEXT         DEFAULT NULL,
    harga        DECIMAL(12,2) NOT NULL CHECK (harga > 0),
    foto         VARCHAR(255)  DEFAULT 'placeholder.jpg',
    status       ENUM('tersedia', 'nonaktif') NOT NULL DEFAULT 'tersedia',
    is_featured  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `pesanan`

```sql
CREATE TABLE pesanan (
    id_pesanan      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_pesanan      VARCHAR(20)  NOT NULL UNIQUE,    -- format: WF-YYYYMMDD-XXXX
    nama_pemesan    VARCHAR(100) NOT NULL,
    no_whatsapp     VARCHAR(15)  NOT NULL,
    alamat          TEXT         NOT NULL,
    tanggal_kirim   DATE         NOT NULL,
    metode_bayar    ENUM('transfer', 'cod') NOT NULL,
    catatan         TEXT         DEFAULT NULL,
    status          ENUM(
                        'menunggu_konfirmasi',
                        'diproses',
                        'selesai',
                        'dibatalkan'
                    ) NOT NULL DEFAULT 'menunggu_konfirmasi',
    total_harga     DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `detail_pesanan`

```sql
CREATE TABLE detail_pesanan (
    id_detail    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan   INT UNSIGNED NOT NULL,
    id_produk    INT UNSIGNED NOT NULL,
    nama_produk  VARCHAR(200) NOT NULL,   -- snapshot nama saat memesan
    harga_satuan DECIMAL(12,2) NOT NULL,  -- snapshot harga saat memesan
    jumlah       INT UNSIGNED NOT NULL DEFAULT 1 CHECK (jumlah >= 1),
    subtotal     DECIMAL(12,2) NOT NULL,  -- harga_satuan * jumlah
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_produk)  REFERENCES produk(id_produk) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Catatan desain:** `nama_produk` dan `harga_satuan` disimpan sebagai snapshot untuk menjaga integritas historis — jika nama atau harga produk diubah di kemudian hari, riwayat pesanan tetap akurat.

#### Tabel `dp`

```sql
CREATE TABLE dp (
    id_dp        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan   INT UNSIGNED NOT NULL UNIQUE,  -- satu pesanan, satu DP
    jumlah_dp    DECIMAL(12,2) NOT NULL CHECK (jumlah_dp > 0),
    metode       ENUM('transfer_bca', 'transfer_mandiri', 'transfer_bni', 'lainnya') NOT NULL,
    bukti_foto   VARCHAR(255)  DEFAULT NULL,
    dicatat_pada TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `lunas`

```sql
CREATE TABLE lunas (
    id_lunas     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan   INT UNSIGNED NOT NULL UNIQUE,  -- satu pesanan, satu record lunas
    jumlah_lunas DECIMAL(12,2) NOT NULL CHECK (jumlah_lunas > 0),
    metode       ENUM('transfer_bca', 'transfer_mandiri', 'transfer_bni', 'cod', 'lainnya') NOT NULL,
    dicatat_pada TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `pengguna`

```sql
CREATE TABLE pengguna (
    id_pengguna  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,   -- bcrypt hash via password_hash()
    nama_lengkap VARCHAR(100) NOT NULL,
    role         ENUM('owner', 'staf') NOT NULL DEFAULT 'staf',
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `status_toko`

```sql
CREATE TABLE status_toko (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status       ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Selalu berisi tepat 1 baris; diisi via seed.sql
```

#### Tabel `stok_bahan`

```sql
CREATE TABLE stok_bahan (
    id_bahan     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_bahan   VARCHAR(150) NOT NULL,
    satuan       VARCHAR(30)  NOT NULL DEFAULT 'pcs',   -- pcs, lembar, meter, kg
    stok_saat_ini INT UNSIGNED NOT NULL DEFAULT 0,
    stok_minimum INT UNSIGNED NOT NULL DEFAULT 5,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Tabel `pengeluaran`

```sql
CREATE TABLE pengeluaran (
    id_pengeluaran INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keterangan     VARCHAR(255) NOT NULL,
    jumlah         DECIMAL(12,2) NOT NULL CHECK (jumlah > 0),
    tanggal        DATE         NOT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Indeks yang Disarankan

```sql
-- Untuk pencarian dan filter katalog
CREATE INDEX idx_produk_status     ON produk(status);
CREATE INDEX idx_produk_featured   ON produk(is_featured);
CREATE INDEX idx_produk_kategori   ON produk(id_kategori);

-- Untuk pencarian pesanan
CREATE INDEX idx_pesanan_no        ON pesanan(no_pesanan);
CREATE INDEX idx_pesanan_status    ON pesanan(status);
CREATE INDEX idx_pesanan_created   ON pesanan(created_at);

-- Untuk laporan keuangan berdasarkan tanggal
CREATE INDEX idx_lunas_dicatat     ON lunas(dicatat_pada);
CREATE INDEX idx_pengeluaran_tgl   ON pengeluaran(tanggal);
```

---

## Arsitektur Keamanan

### 1. PDO Prepared Statements

Semua query database menggunakan PDO dengan prepared statements. Interpolasi string langsung ke dalam query SQL sepenuhnya dilarang.

```php
// BENAR
$stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = :id");
$stmt->execute([':id' => $id]);

// SALAH — dilarang
$hasil = $pdo->query("SELECT * FROM produk WHERE id_produk = $id");
```

Koneksi PDO dikonfigurasi dengan `EMULATE_PREPARES = false` untuk memastikan prepared statements dieksekusi di sisi database, bukan di PHP.

### 2. Output Escaping (XSS Prevention)

Semua data dari input pengguna atau dari database yang akan ditampilkan di HTML harus melalui fungsi `e()`:

```php
// Di helpers.php
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Penggunaan di template
echo e($produk['nama_produk']);
echo e($_GET['q'] ?? '');
```

### 3. CSRF Token

Setiap form POST mengandung hidden input `csrf_token`. Setiap handler POST memvalidasi token ini sebelum memproses data.

```php
// Di generate_csrf() — dijalankan saat halaman form dimuat
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Di template form
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

// Di handler POST
if (!validate_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Permintaan tidak valid.');
}
```

### 4. Autentikasi Session

Semua halaman di direktori `admin/` menyertakan `require_once '../config/session.php'` sebagai baris pertama. File ini memeriksa session dan melakukan redirect jika tidak ada:

```php
// config/session.php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: /login.php', true, 302);
    exit;
}
```

Password disimpan menggunakan `password_hash($password, PASSWORD_BCRYPT)` dan diverifikasi menggunakan `password_verify($input, $hash)`.

### 5. Rate Limiting Login

Login rate limiting menggunakan session dan timestamp untuk mencatat percobaan gagal per IP:

```php
// Struktur di session:
$_SESSION['login_attempts'][$ip] = [
    'count'      => 3,
    'first_try'  => 1704067200  // Unix timestamp percobaan pertama
];

// Logika: jika count >= 5 dan (now - first_try) < 900 detik, tolak
```

### 6. Keamanan Upload File

Validasi upload foto produk dilakukan di sisi server:

```php
$allowed_types  = ['image/jpeg', 'image/png'];
$allowed_exts   = ['jpg', 'jpeg', 'png'];
$max_size_bytes = 2 * 1024 * 1024; // 2 MB

// Validasi: MIME type via finfo_file(), ekstensi, dan ukuran
// Nama file baru: uniqid('produk_', true) . '.' . $ext
// Direktori: assets/img/produk/
```

`finfo_file()` digunakan (bukan `$_FILES['type']`) karena MIME type dari browser dapat dipalsukan.

---

## Pola Interaksi AJAX

### Toggle Status Toko

Diimplementasikan di `assets/js/toggle-status.js`. Menggunakan `fetch()` API.

```javascript
// assets/js/toggle-status.js
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('ownerToggle');
    if (!toggle) return;

    toggle.addEventListener('change', async function () {
        const statusText = document.getElementById('ownerStatusText');
        try {
            const response = await fetch('/admin/ajax/toggle-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: document.getElementById('csrf_token_ajax').value
                })
            });
            const data = await response.json();
            if (data.success) {
                statusText.textContent = data.status_baru === 'aktif' ? 'Aktif' : 'Nonaktif';
            } else {
                // Kembalikan toggle ke posisi sebelumnya jika gagal
                this.checked = !this.checked;
                alert('Gagal mengubah status: ' + data.message);
            }
        } catch (err) {
            this.checked = !this.checked;
        }
    });
});
```

### Modal Ubah Status Pesanan

`assets/js/admin-modal.js` menangani pembukaan modal konfirmasi dan pengiriman perubahan status via `fetch()`.

```javascript
// Alur:
// 1. Klik tombol "Ubah Status" di baris tabel
// 2. addEventListener membaca data-id-pesanan dan data-status-saat-ini
// 3. Modal ditampilkan dengan <select> pilihan status baru
// 4. Klik "Konfirmasi" → fetch POST ke admin/ajax/update-status-pesanan.php
// 5. Response JSON → update badge status di baris tabel tanpa reload
```

Semua event listener ditambahkan via `addEventListener()`, tidak ada atribut `onclick=""` inline di HTML.

### Format Response AJAX

Semua endpoint AJAX mengembalikan JSON dengan struktur seragam:

```json
// Sukses
{ "success": true, "message": "Status berhasil diperbarui", "data": { ... } }

// Gagal
{ "success": false, "message": "CSRF token tidak valid" }
```

---

## Pembuatan Grafik SVG oleh PHP

Grafik di halaman laporan dan dashboard **tidak menggunakan library JavaScript**. Grafik dihasilkan sepenuhnya oleh PHP sebagai string SVG inline.

### Grafik Donat (Distribusi Status Pesanan)

```php
// Algoritma: conic-gradient tidak tersedia di SVG, gunakan path arc
// Setiap segmen = arc SVG yang dihitung dari sudut (proporsi * 360 derajat)

function generate_svg_donat(array $data, int $radius = 80): string {
    // $data = ['Selesai' => 45, 'Diproses' => 30, ...]
    // Hitung total, lalu untuk setiap segmen:
    //   startAngle += sebelumnya
    //   endAngle = startAngle + (nilai/total * 360)
    //   x1 = cx + r * cos(startAngle), y1 = cy + r * sin(startAngle)
    //   x2 = cx + r * cos(endAngle),   y2 = cy + r * sin(endAngle)
    //   largeArcFlag = (endAngle - startAngle > 180) ? 1 : 0
    //   path d="M cx cy L x1 y1 A r r 0 largeArcFlag 1 x2 y2 Z"
    // Lubang donat: lingkaran putih di tengah (r = radius * 0.55)
}
```

### Grafik Batang (Pemasukan vs Pengeluaran per Bulan)

```php
function generate_svg_bar(array $pemasukan, array $pengeluaran): string {
    // Hitung nilai maksimum untuk menentukan skala Y
    // Setiap pasangan bulan = dua <rect> dengan lebar 20px, jarak antar kelompok 8px
    // Tinggi bar = (nilai / max_nilai) * tinggi_area_grafik
    // Label bulan di sumbu X, label nilai di sumbu Y (4 garis grid horizontal)
    // Warna pemasukan: #16A34A (success), pengeluaran: #DC2626 (danger)
}
```

SVG dihasilkan PHP dan di-echo langsung ke dalam HTML — tidak ada JavaScript yang terlibat dalam rendering grafik.

---

## Penanganan Upload File

### Proses Upload Foto Produk

```
POST /admin/produk.php (action=tambah|edit)
         │
         ▼
  1. Validasi CSRF token
         │
         ▼
  2. Validasi file:
     - isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK
     - Ukuran <= 2MB
     - MIME type via finfo_file() ∈ {image/jpeg, image/png}
     - Ekstensi ∈ {jpg, jpeg, png}
         │
         ▼
  3. Generate nama file unik:
     $nama_file = uniqid('produk_', true) . '.' . $ekstensi;
         │
         ▼
  4. move_uploaded_file() ke assets/img/produk/
         │
         ▼
  5. Simpan $nama_file ke kolom 'foto' di tabel produk
         │
         ▼
  6. (Saat edit) Hapus file lama jika ada dan berbeda dengan placeholder
```

### Aturan Penanganan

- Jika edit produk tanpa upload foto baru: kolom `foto` tidak diubah.
- Direktori `assets/img/produk/` tidak dapat diakses listing-nya (`.htaccess`: `Options -Indexes`).
- File placeholder (`placeholder.jpg`) tidak pernah dihapus.

---

## Fungsi Utilitas (Helpers)

### `format_rupiah(int $angka): string`

Mengubah angka integer menjadi string mata uang Rupiah dengan format standar Indonesia.

```php
format_rupiah(0)          // → "Rp 0"
format_rupiah(150000)     // → "Rp 150.000"
format_rupiah(1500000)    // → "Rp 1.500.000"
format_rupiah(25000000)   // → "Rp 25.000.000"
```

Implementasi menggunakan `number_format($angka, 0, ',', '.')` dengan prefix `"Rp "`.

### `format_tanggal_id(DateTime $tanggal): string`

Mengubah objek DateTime menjadi string tanggal Bahasa Indonesia lengkap.

```php
format_tanggal_id(new DateTime('2025-01-15')) // → "Rabu, 15 Januari 2025"
format_tanggal_id(new DateTime('2025-12-25')) // → "Kamis, 25 Desember 2025"
```

Nama hari dan nama bulan didefinisikan sebagai array statis di dalam fungsi, diindeks berdasarkan `date('N')` dan `date('n')`.

### `generate_no_pesanan(PDO $pdo): string`

Menghasilkan nomor pesanan unik berformat `WF-YYYYMMDD-XXXX`.

```
Algoritma:
1. $tanggal = date('Ymd')  // contoh: "20250115"
2. Query: SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()
3. $urut = hasil_query + 1
4. return sprintf("WF-%s-%04d", $tanggal, $urut)
// Contoh: "WF-20250115-0001", "WF-20250115-0002"
```

Operasi ini dieksekusi dalam transaksi yang sama dengan INSERT pesanan untuk menghindari kondisi race.

### `e(string $str): string`

Wrapper `htmlspecialchars()` yang wajib digunakan untuk semua output dinamis di template HTML.

### `validate_csrf(string $token): bool`

Membandingkan token dari POST request dengan nilai di `$_SESSION['csrf_token']` menggunakan `hash_equals()` (safe dari timing attack).

---

## Correctness Properties

*Properti adalah karakteristik atau perilaku yang harus berlaku di semua eksekusi sistem yang valid — pada dasarnya pernyataan formal tentang apa yang harus dilakukan sistem. Properti menjadi jembatan antara spesifikasi yang dapat dibaca manusia dan jaminan kebenaran yang dapat diverifikasi mesin.*

### Property 1: Nilai Status Toko Selalu dalam Enum yang Valid

*Untuk semua* nilai yang disimpan atau diproses oleh fungsi manajemen status toko, nilai tersebut harus selalu berupa `'aktif'` atau `'nonaktif'` — tidak ada nilai lain yang boleh lolos dari lapisan validasi.

**Validates: Requirements 1.1**

---

### Property 2: Toggle Status Toko adalah Round-Trip

*Untuk semua* nilai status toko yang valid (`'aktif'` atau `'nonaktif'`), melakukan operasi toggle dua kali berturut-turut harus menghasilkan nilai yang sama dengan nilai awal.

**Validates: Requirements 1.2**

---

### Property 3: Query Beranda Membatasi Produk Featured

*Untuk semua* dataset produk dengan jumlah berapapun (termasuk nol) yang memiliki `is_featured = 1`, fungsi query beranda harus mengembalikan paling banyak 4 produk.

**Validates: Requirements 2.2**

---

### Property 4: Filter Kategori Aktif Konsisten

*Untuk semua* dataset yang berisi campuran kategori aktif (`is_active = 1`) dan nonaktif (`is_active = 0`), hasil dari fungsi query kategori publik harus hanya berisi kategori dengan `is_active = 1`.

**Validates: Requirements 2.3**

---

### Property 5: Filter Pencarian Katalog Konsisten

*Untuk semua* kata kunci pencarian dan dataset produk, setiap produk yang dikembalikan oleh fungsi filter pencarian harus mengandung kata kunci tersebut (case-insensitive) di kolom `nama_produk` atau `deskripsi`.

**Validates: Requirements 3.2**

---

### Property 6: Filter Kategori Katalog Konsisten

*Untuk semua* `id_kategori` yang dipilih dan dataset produk, setiap produk yang dikembalikan oleh fungsi filter kategori harus memiliki `id_kategori` yang identik dengan nilai filter yang dipilih.

**Validates: Requirements 3.3**

---

### Property 7: Urutan Katalog Deterministik

*Untuk semua* dataset produk dan pilihan opsi urutan (`harga_asc`, `harga_desc`, `terbaru`), hasil yang dikembalikan harus terurut secara konsisten: untuk `harga_asc` setiap produk ke-i harus memiliki harga ≤ produk ke-i+1, dan sebaliknya untuk `harga_desc`.

**Validates: Requirements 3.4**

---

### Property 8: Paginasi Membatasi Ukuran Halaman

*Untuk semua* dataset produk dengan jumlah berapapun dan nomor halaman berapapun, fungsi paginasi harus mengembalikan paling banyak 12 produk per halaman.

**Validates: Requirements 3.6**

---

### Property 9: Validasi Input Pemesanan Deterministik

*Untuk semua* kombinasi input form pemesanan (nama, nomor WhatsApp, alamat, tanggal, produk), fungsi validator harus selalu menolak input yang tidak valid (nama kosong, WhatsApp < 8 digit atau > 15 digit, tanggal di masa lalu, tidak ada produk dipilih) dan selalu menerima input yang valid.

**Validates: Requirements 5.2**

---

### Property 10: Format Nomor Pesanan Selalu Valid

*Untuk semua* nomor pesanan yang dihasilkan oleh `generate_no_pesanan()` pada tanggal dan dengan nomor urut berapapun, hasilnya harus selalu cocok dengan pola regex `^WF-\d{8}-\d{4}$`.

**Validates: Requirements 5.5**

---

### Property 11: Format Rupiah Selalu Valid

*Untuk semua* angka integer non-negatif, output dari `format_rupiah()` harus selalu cocok dengan pola `^Rp \d{1,3}(\.\d{3})*$` — dengan titik sebagai pemisah ribuan dan prefix "Rp ".

**Validates: Requirements 16.5**

---

### Property 12: Format Tanggal Indonesia Selalu Valid

*Untuk semua* objek `DateTime` yang valid, output dari `format_tanggal_id()` harus selalu mengandung nama hari dalam Bahasa Indonesia (Senin–Minggu) dan nama bulan dalam Bahasa Indonesia (Januari–Desember) dengan format `[Hari], [Tanggal] [Bulan] [Tahun]`.

**Validates: Requirements 16.6**

---

### Property 13: Escaping Output Mencegah XSS

*Untuk semua* string yang mengandung karakter HTML khusus (`<`, `>`, `&`, `"`, `'`), output dari fungsi `e()` tidak boleh mengandung karakter-karakter tersebut dalam bentuk mentah — setiap karakter harus diubah menjadi entitas HTML yang sesuai.

**Validates: Requirements 15.2**

---

### Property 14: Validasi CSRF Konsisten

*Untuk semua* permintaan POST, fungsi `validate_csrf()` harus selalu menolak token yang kosong, salah, atau tidak ada — dan hanya menerima token yang secara tepat cocok dengan nilai yang disimpan di session.

**Validates: Requirements 15.3, 1.3**

---

### Property 15: Invariant Keuangan Pelunasan

*Untuk semua* kombinasi nilai (total_pesanan, jumlah_dp, jumlah_lunas), fungsi validasi pelunasan Transfer harus selalu menolak jika `jumlah_dp + jumlah_lunas < total_pesanan`, dan selalu menerima jika `jumlah_dp + jumlah_lunas >= total_pesanan`.

**Validates: Requirements 12.6**

---

### Property 16: Perhitungan Laba Bersih Konsisten

*Untuk semua* dataset pemasukan dari tabel `lunas` dan pengeluaran dari tabel `pengeluaran`, nilai laba bersih yang dihitung harus selalu sama dengan `SUM(lunas.jumlah_lunas) - SUM(pengeluaran.jumlah)` untuk rentang tanggal yang diberikan.

**Validates: Requirements 14.5**

---

## Error Handling

### Error Database

Semua operasi PDO dibungkus dalam blok `try-catch`:

```php
try {
    $pdo->beginTransaction();
    // ... operasi INSERT/UPDATE/DELETE
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    // Log error (jangan tampilkan detail ke user)
    error_log('DB Error: ' . $e->getMessage());
    // Tampilkan pesan generik ke user
    $error_message = 'Terjadi kesalahan. Silakan coba lagi.';
}
```

Detail error PDO tidak pernah ditampilkan ke pengguna — hanya di-log ke server.

### Error Validasi Form

Validasi berjalan di dua lapis:

1. **Client-side (JavaScript)** — `assets/js/pemesanan.js` memvalidasi input sebelum form dikirim. Menampilkan pesan error di bawah field yang bermasalah. Mencegah pengiriman data yang jelas tidak valid ke server.
2. **Server-side (PHP)** — Validator PHP di bagian atas setiap halaman form memvalidasi ulang semua input yang diterima. Client-side validation adalah kenyamanan UX, bukan pengganti keamanan.

Pesan error ditampilkan dalam Bahasa Indonesia, spesifik per field, tanpa mengekspos detail teknis sistem.

### Halaman 404 / Produk Tidak Ditemukan

Jika `id` yang diterima di `detail-produk.php` tidak ditemukan di database, halaman menampilkan pesan "Produk tidak ditemukan" dengan tautan kembali ke katalog. Kode HTTP tetap 200 (bukan redirect ke halaman error global).

### Error AJAX

Jika endpoint AJAX mengembalikan `success: false` atau terjadi network error, JavaScript menampilkan pesan error kepada pengguna dan mengembalikan UI ke kondisi sebelumnya (misalnya, toggle dikembalikan ke posisi awal).

### Redirect Setelah POST (PRG Pattern)

Semua form POST yang berhasil diikuti dengan redirect HTTP 302 ke halaman konfirmasi atau halaman yang sama. Ini mencegah pengiriman ulang data jika pengguna me-refresh halaman.

```
POST /pages/pemesanan.php → validasi → simpan → redirect ke /pages/cek-pesanan.php?no=WF-...
```

---

## Testing Strategy

### Pendekatan Dual-Testing

Sistem WanFlorist menggunakan dua jenis pengujian yang saling melengkapi:

1. **Unit Test / Example-Based Test** — Memverifikasi contoh konkret dan kondisi batas.
2. **Property-Based Test (PBT)** — Memverifikasi properti universal yang berlaku untuk semua input yang valid.

Keduanya menggunakan **PHPUnit** sebagai test runner utama, dengan **eris** (library PBT untuk PHP) untuk properti-properti yang memerlukan generasi input acak.

> Library PBT: [eris](https://github.com/giorgiosironi/eris) — tersedia via Composer, mendukung generasi data acak dan shrinking (memperkecil contoh yang gagal).

### Unit Tests (Example-Based)

Berfokus pada skenario konkret, integrasi antar komponen, dan kasus batas:

```
tests/
├── Unit/
│   ├── HelpersTest.php          # Test format_rupiah(), format_tanggal_id(), dll.
│   ├── ValidatorTest.php        # Test validasi form pemesanan, produk, stok
│   ├── AuthTest.php             # Test autentikasi login, rate limiting
│   └── CsrfTest.php             # Test generate dan validasi CSRF token
└── Integration/
    ├── PesananFlowTest.php      # Test alur lengkap pemesanan
    ├── PembayaranFlowTest.php   # Test alur DP dan pelunasan
    └── StatusTokoTest.php       # Test toggle status via AJAX endpoint
```

Contoh unit test:
```php
// HelpersTest.php
public function test_format_rupiah_zero(): void
{
    $this->assertEquals('Rp 0', format_rupiah(0));
}

public function test_format_rupiah_millions(): void
{
    $this->assertEquals('Rp 1.500.000', format_rupiah(1500000));
}

public function test_format_tanggal_januari(): void
{
    $tgl = new DateTime('2025-01-15');
    $this->assertStringContainsString('Januari', format_tanggal_id($tgl));
    $this->assertStringContainsString('Rabu', format_tanggal_id($tgl));
}
```

### Property-Based Tests

Setiap properti di bagian "Properti Kebenaran" diimplementasikan sebagai satu property-based test menggunakan eris, dengan minimum **100 iterasi** per test.

Setiap test diberi komentar tag referensi:
```
// Feature: wanflorist-system, Property [N]: [teks properti]
```

Contoh implementasi property test:

```php
// tests/Property/FormatRupiahPropertyTest.php
use Eris\TestTrait;
use Eris\Generator;

class FormatRupiahPropertyTest extends \PHPUnit\Framework\TestCase
{
    use TestTrait;

    // Feature: wanflorist-system, Property 11: Format Rupiah Selalu Valid
    public function test_format_rupiah_selalu_valid(): void
    {
        $this
            ->minimumEvaluationRatio(1.0)
            ->forAll(Generator\pos())  // semua integer positif
            ->then(function (int $angka) {
                $hasil = format_rupiah($angka);
                $this->assertMatchesRegularExpression(
                    '/^Rp \d{1,3}(\.\d{3})*$/',
                    $hasil,
                    "format_rupiah($angka) menghasilkan: $hasil"
                );
            });
    }

    // Feature: wanflorist-system, Property 11: Format Rupiah Selalu Valid (nol)
    public function test_format_rupiah_nol(): void
    {
        $this->assertEquals('Rp 0', format_rupiah(0));
    }
}
```

```php
// tests/Property/NoPesananPropertyTest.php

// Feature: wanflorist-system, Property 10: Format Nomor Pesanan Selalu Valid
public function test_no_pesanan_selalu_cocok_format(): void
{
    $this
        ->forAll(
            Generator\date(),        // berbagai tanggal
            Generator\choose(1, 9999) // berbagai nomor urut
        )
        ->then(function (DateTimeInterface $tgl, int $urut) {
            $no = sprintf("WF-%s-%04d", $tgl->format('Ymd'), $urut);
            $this->assertMatchesRegularExpression(
                '/^WF-\d{8}-\d{4}$/',
                $no
            );
        });
}
```

```php
// tests/Property/ValidasiPemesananPropertyTest.php

// Feature: wanflorist-system, Property 9: Validasi Input Pemesanan Deterministik
public function test_no_whatsapp_tidak_valid_selalu_ditolak(): void
{
    $this
        ->forAll(
            // Generator angka dengan panjang < 8 atau > 15 digit
            Generator\bind(
                Generator\choose(1, 7),
                fn($len) => Generator\string(Generator\elements(['0','1','2','3','4','5','6','7','8','9']), $len)
            )
        )
        ->then(function (string $nomorPendek) {
            $errors = validasi_form_pemesanan(['no_whatsapp' => $nomorPendek, ...]);
            $this->assertArrayHasKey('no_whatsapp', $errors);
        });
}
```

```php
// tests/Property/KalkulasiKeuanganPropertyTest.php

// Feature: wanflorist-system, Property 15: Invariant Keuangan Pelunasan
public function test_validasi_pelunasan_transfer(): void
{
    $this
        ->forAll(
            Generator\pos(),  // total pesanan
            Generator\pos(),  // jumlah DP
            Generator\pos()   // jumlah lunas yang diusulkan
        )
        ->then(function (int $total, int $dp, int $lunas) {
            $valid = validasi_pelunasan_transfer($total, $dp, $lunas);
            if ($dp + $lunas >= $total) {
                $this->assertTrue($valid);
            } else {
                $this->assertFalse($valid);
            }
        });
}

// Feature: wanflorist-system, Property 16: Perhitungan Laba Bersih Konsisten
public function test_laba_bersih_aritmatika(): void
{
    $this
        ->forAll(
            Generator\vector(Generator\pos(), Generator\choose(0, 20)), // array pemasukan
            Generator\vector(Generator\pos(), Generator\choose(0, 20))  // array pengeluaran
        )
        ->then(function (array $pemasukan, array $pengeluaran) {
            $ekspektasi = array_sum($pemasukan) - array_sum($pengeluaran);
            $aktual     = hitung_laba_bersih($pemasukan, $pengeluaran);
            $this->assertEquals($ekspektasi, $aktual);
        });
}
```

### Konfigurasi Test Runner

```bash
# Jalankan semua test (sekali, bukan watch mode)
./vendor/bin/phpunit --testdox

# Jalankan hanya property tests
./vendor/bin/phpunit --testsuite property

# Jalankan hanya unit tests
./vendor/bin/phpunit --testsuite unit
```

Konfigurasi di `phpunit.xml`:
```xml
<phpunit>
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="property">
            <directory>tests/Property</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Setiap property test menggunakan annotation `@eris-repeat 100` atau metode `->times(100)` untuk memastikan minimum 100 iterasi per properti.

### Cakupan Test yang Diprioritaskan

1. Fungsi `helpers.php` — mudah diisolasi, domain utama PBT (format, generate, escape).
2. Fungsi `Validator` — logika bisnis inti, banyak kondisi batas yang layak diuji PBT.
3. Kalkulasi keuangan (`laba_bersih`, validasi pelunasan) — invariant keuangan harus tepat.
4. Fungsi filter/sort katalog — universal property untuk filter dan pengurutan.
5. Alur pesanan end-to-end — integration test untuk memastikan transaksi database berjalan atomis.
