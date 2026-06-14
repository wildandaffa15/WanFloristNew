# Implementation Plan: WanFlorist System

## Overview

Implementasi sistem WanFlorist secara bertahap, dimulai dari fondasi teknis (database, konfigurasi, komponen bersama), kemudian halaman publik, lalu panel admin, dan diakhiri dengan pengujian menyeluruh. Setiap langkah dibangun di atas langkah sebelumnya sehingga tidak ada kode yang menggantung tanpa integrasi.

Teknologi: **PHP Native**, **MySQL**, **Pure CSS**, **Vanilla JavaScript** — tanpa framework apapun.

---

## Tasks

- [x] 1. Setup Fondasi Proyek: Struktur Direktori, Database, dan Konfigurasi Inti
  - Buat seluruh struktur direktori sesuai arsitektur (`WanFloristWebsite/`, `assets/`, `components/`, `config/`, `admin/`, `pages/`, `database/`, `admin/ajax/`, `tests/Unit/`, `tests/Integration/`, `tests/Property/`)
  - Buat file `database/schema.sql` dengan DDL lengkap semua tabel: `kategori`, `produk`, `pesanan`, `detail_pesanan`, `dp`, `lunas`, `pengguna`, `status_toko`, `stok_bahan`, `pengeluaran`, beserta semua indeks yang disarankan
  - Buat file `database/seed.sql` dengan data awal: 5+ kategori, 8+ produk dengan foto placeholder, 1 admin dengan password bcrypt, dan 1 baris `status_toko` bernilai `aktif`
  - Buat `config/database.php` dengan fungsi `get_pdo()` singleton: DSN `mysql:host=localhost;dbname=wanflorist;charset=utf8mb4`, `ERRMODE_EXCEPTION`, `DEFAULT_FETCH_MODE=ASSOC`, `EMULATE_PREPARES=false`
  - Buat `config/session.php`: `session_start()` dan redirect ke `login.php` dengan HTTP 302 jika `$_SESSION['id_pengguna']` tidak ada (digunakan khusus oleh halaman admin)
  - Buat file `.htaccess` di root: konfigurasi Apache (aturan direktori `config/` tidak dapat diakses langsung dari browser, `Options -Indexes` untuk `assets/img/produk/`)
  - Buat `assets/img/placeholder.jpg` sebagai gambar placeholder produk default
  - _Requirements: 15.7, 17.1, 17.6_

- [x] 2. Implementasi `config/helpers.php` — Fungsi Utilitas (Pretty_Printer)
  - [x] 2.1 Implementasi fungsi `format_rupiah(int $angka): string`
    - Gunakan `number_format($angka, 0, ',', '.')` dengan prefix `"Rp "`
    - Handle kasus nol: `format_rupiah(0)` → `"Rp 0"`
    - _Requirements: 16.5, 17.7_

  - [ ]* 2.2 Tulis property test untuk `format_rupiah()`
    - **Property 11: Format Rupiah Selalu Valid**
    - **Validates: Requirements 16.5**
    - Gunakan eris `Generator\pos()` untuk semua integer positif, verifikasi output cocok pola `^Rp \d{1,3}(\.\d{3})*$`
    - Tambahkan tag `// Feature: wanflorist-system, Property 11`
    - File: `tests/Property/FormatRupiahPropertyTest.php`

  - [x] 2.3 Implementasi fungsi `format_tanggal_id(DateTime $tanggal): string`
    - Definisikan array statis nama hari (Senin–Minggu, indeks `date('N')`) dan nama bulan (Januari–Desember, indeks `date('n')`)
    - Output format: `"Rabu, 15 Januari 2025"`
    - _Requirements: 16.6, 17.7_

  - [ ]* 2.4 Tulis property test untuk `format_tanggal_id()`
    - **Property 12: Format Tanggal Indonesia Selalu Valid**
    - **Validates: Requirements 16.6**
    - Gunakan eris `Generator\date()`, verifikasi output mengandung nama hari Indonesia dan nama bulan Indonesia
    - File: `tests/Property/FormatTanggalPropertyTest.php`

  - [x] 2.5 Implementasi fungsi `generate_no_pesanan(PDO $pdo): string`
    - Query `SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()`
    - Format output: `sprintf("WF-%s-%04d", date('Ymd'), $urut)`
    - _Requirements: 5.5, 17.7_

  - [ ]* 2.6 Tulis property test untuk `generate_no_pesanan()`
    - **Property 10: Format Nomor Pesanan Selalu Valid**
    - **Validates: Requirements 5.5**
    - Gunakan generator untuk berbagai tanggal dan nomor urut, verifikasi cocok pola `^WF-\d{8}-\d{4}$`
    - File: `tests/Property/NoPesananPropertyTest.php`

  - [x] 2.7 Implementasi fungsi `e(string $str): string`
    - Wrapper `htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8')`
    - _Requirements: 15.2, 17.7_

  - [ ]* 2.8 Tulis property test untuk `e()`
    - **Property 13: Escaping Output Mencegah XSS**
    - **Validates: Requirements 15.2**
    - Gunakan eris generator string yang mengandung `<`, `>`, `&`, `"`, `'` — verifikasi tidak ada karakter tersebut dalam bentuk mentah di output
    - File: `tests/Property/EscapingPropertyTest.php`

  - [x] 2.9 Implementasi fungsi `validate_csrf(string $token): bool` dan `generate_csrf(): string`
    - `generate_csrf()`: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`
    - `validate_csrf()`: bandingkan dengan `hash_equals()` (tahan timing attack)
    - _Requirements: 15.3, 5.10, 12.8_

  - [ ]* 2.10 Tulis property test untuk `validate_csrf()`
    - **Property 14: Validasi CSRF Konsisten**
    - **Validates: Requirements 15.3, 1.3**
    - Verifikasi token kosong, token salah, dan token tidak ada selalu ditolak; token yang cocok selalu diterima
    - File: `tests/Property/CsrfPropertyTest.php`

- [x] 3. Implementasi Fungsi Validator Form
  - [x] 3.1 Implementasi `validasi_form_pemesanan(array $input): array`
    - Validasi: nama pemesan tidak kosong (maks 100 karakter), `no_whatsapp` 8–15 digit angka, alamat tidak kosong (maks 500 karakter), `tanggal_kirim` tidak masa lalu, minimal satu produk dengan jumlah ≥ 1
    - Kembalikan array error per field (kosong berarti valid)
    - _Requirements: 5.2, 5.3, 5.4_

  - [ ]* 3.2 Tulis property test untuk validasi pemesanan
    - **Property 9: Validasi Input Pemesanan Deterministik**
    - **Validates: Requirements 5.2**
    - Uji input no_whatsapp < 8 digit selalu ditolak, > 15 digit selalu ditolak; nama kosong selalu ditolak; tanggal masa lalu selalu ditolak
    - File: `tests/Property/ValidasiPemesananPropertyTest.php`

  - [x] 3.3 Implementasi `validasi_pelunasan_transfer(int $total, int $dp, int $lunas): bool`
    - Kembalikan `true` jika `$dp + $lunas >= $total`, `false` jika tidak
    - _Requirements: 12.6_

  - [ ]* 3.4 Tulis property test untuk validasi pelunasan
    - **Property 15: Invariant Keuangan Pelunasan**
    - **Validates: Requirements 12.6**
    - Gunakan `Generator\pos()` untuk total, dp, lunas; verifikasi hasil selalu konsisten dengan kondisi `dp + lunas >= total`
    - File: `tests/Property/KalkulasiKeuanganPropertyTest.php`

  - [x] 3.5 Implementasi `hitung_laba_bersih(array $pemasukan, array $pengeluaran): int|float`
    - Hitung `array_sum($pemasukan) - array_sum($pengeluaran)`
    - _Requirements: 14.5_

  - [ ]* 3.6 Tulis property test untuk laba bersih
    - **Property 16: Perhitungan Laba Bersih Konsisten**
    - **Validates: Requirements 14.5**
    - Gunakan `Generator\vector()` untuk array pemasukan dan pengeluaran acak; verifikasi hasil selalu sama dengan `array_sum($pemasukan) - array_sum($pengeluaran)`
    - File: `tests/Property/KalkulasiKeuanganPropertyTest.php` (tambahkan method baru)

- [x] 4. Checkpoint — Pastikan semua tes utilitas dan validator lolos
  - Jalankan `./vendor/bin/phpunit --testsuite unit` dan `./vendor/bin/phpunit --testsuite property`
  - Pastikan semua tes hijau sebelum melanjutkan ke implementasi halaman
  - Tanyakan ke pengguna jika ada pertanyaan atau kendala

- [x] 5. Implementasi Komponen Bersama: `head.php`, `navbar.php`, `footer.php`, `sidebar.php`
  - [x] 5.1 Buat `components/head.php`
    - Terima variabel `$page_title`; output tag `<head>` standar: charset, viewport, judul, link Google Fonts (`Playfair Display` + `Inter`), link ke `assets/css/main.css`
    - _Requirements: 16.2, 17.1_

  - [x] 5.2 Buat `assets/css/main.css`
    - Definisikan CSS custom properties (variabel): warna primer `#6B21A8`, sidebar bg `#1E1040`, warna status, token warna lainnya dari DESIGN.md
    - Style global: reset, tipografi dasar (font `Inter` untuk body, `Playfair Display` untuk heading)
    - Tombol pill shape: `border-radius: 9999px` untuk semua `.btn`
    - _Requirements: 16.1, 16.3, 16.4_

  - [x] 5.3 Buat `components/navbar.php`
    - Query status toko dari DB via `$pdo`; tampilkan banner status di bawah navbar (hijau + animasi pulse jika `aktif`, abu-abu jika `nonaktif`)
    - Tautan navigasi: Beranda, Produk, Cek Pesanan, Tentang Kami, Kontak
    - Responsive: hamburger menu untuk mobile
    - _Requirements: 1.4, 1.5, 1.6, 2.5, 16.7_

  - [ ]* 5.4 Tulis property test untuk status toko
    - **Property 1: Nilai Status Toko Selalu dalam Enum yang Valid**
    - **Validates: Requirements 1.1**
    - Verifikasi hanya nilai `'aktif'` atau `'nonaktif'` yang lolos validasi; nilai lain apapun ditolak
    - File: `tests/Property/StatusTokoPropertyTest.php`

  - [ ]* 5.5 Tulis property test untuk toggle status toko
    - **Property 2: Toggle Status Toko adalah Round-Trip**
    - **Validates: Requirements 1.2**
    - Verifikasi `toggle(toggle(status)) === status` untuk semua nilai status valid
    - File: `tests/Property/StatusTokoPropertyTest.php` (tambahkan method baru)

  - [x] 5.6 Buat `components/footer.php`
    - Konten statis: informasi kontak WanFlorist, tautan cepat, nama toko
    - _Requirements: 2.6, 17.1_

  - [x] 5.7 Buat `components/sidebar.php`
    - Latar belakang `#1E1040`, lebar 240px; terima `$active_page` untuk highlight item aktif
    - Menu: Dashboard, Pesanan, Produk, Stok Bahan, Pembayaran, Pengeluaran, Laporan, Keluar
    - Item aktif: bg `rgba(107,33,168,0.4)`, border kiri 3px `primary-light`
    - Mobile: tersembunyi default, tampil via tombol hamburger dengan JavaScript (`addEventListener`)
    - _Requirements: 8.8, 16.7, 16.9_

  - [x] 5.8 Buat `assets/css/public.css` dan `assets/css/admin.css`
    - `public.css`: style khusus halaman publik (hero section, card produk, katalog grid, stepper, form pemesanan)
    - `admin.css`: style khusus panel admin (tabel, badge status, modal, kartu statistik)
    - Implementasi responsive: desktop (≥1024px), tablet (768–1023px), mobile (<768px) via media queries
    - _Requirements: 16.1, 16.8_

- [x] 6. Implementasi Halaman Publik: Beranda (`index.php`)
  - [x] 6.1 Implementasi logika PHP `index.php`
    - Query maks 4 produk `is_featured = 1` dari tabel `produk`
    - Query semua kategori aktif (`is_active = 1`) dari tabel `kategori`
    - Include `config/database.php` dan `config/helpers.php`
    - _Requirements: 2.2, 2.3_

  - [ ]* 6.2 Tulis property test untuk query produk featured
    - **Property 3: Query Beranda Membatasi Produk Featured**
    - **Validates: Requirements 2.2**
    - Verifikasi hasil query selalu ≤ 4 produk untuk dataset berapapun
    - File: `tests/Property/BerandaPropertyTest.php`

  - [ ]* 6.3 Tulis property test untuk filter kategori aktif
    - **Property 4: Filter Kategori Aktif Konsisten**
    - **Validates: Requirements 2.3**
    - Verifikasi semua kategori dalam hasil memiliki `is_active = 1` untuk dataset campuran
    - File: `tests/Property/BerandaPropertyTest.php` (tambahkan method baru)

  - [x] 6.4 Implementasi template HTML `index.php`
    - Susun seksi: banner status toko (dari `navbar.php`), hero dengan tombol "Belanja Sekarang" → `pages/katalog.php`, seksi kategori (tag pill → katalog dengan filter), seksi produk terlaris (4 kartu), seksi CTA, seksi testimoni statis (minimal 3 ulasan)
    - Semua output dari DB dibungkus `e()`; harga menggunakan `format_rupiah()`
    - Include `head.php`, `navbar.php`, `footer.php`
    - _Requirements: 2.1, 2.4, 2.5, 2.6, 2.7, 2.8_

- [x] 7. Implementasi Halaman Publik: Katalog (`pages/katalog.php`)
  - [x] 7.1 Implementasi logika filter dan paginasi katalog
    - Baca parameter `?q=`, `?kategori=`, `?urut=`, `?page=` dari `$_GET`
    - Bangun query PDO prepared statement dinamis dengan filter `nama_produk` LIKE atau `deskripsi` LIKE untuk pencarian
    - Terapkan filter `id_kategori` jika dipilih; terapkan ORDER BY berdasarkan opsi urutan (`harga_asc`, `harga_desc`, `terbaru`)
    - Hitung total produk dan offset untuk paginasi (maks 12 per halaman)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.6, 3.7_

  - [ ]* 7.2 Tulis property test untuk filter pencarian katalog
    - **Property 5: Filter Pencarian Katalog Konsisten**
    - **Validates: Requirements 3.2**
    - Verifikasi setiap produk yang dikembalikan mengandung kata kunci (case-insensitive) di `nama_produk` atau `deskripsi`
    - File: `tests/Property/KatalogPropertyTest.php`

  - [ ]* 7.3 Tulis property test untuk filter kategori katalog
    - **Property 6: Filter Kategori Katalog Konsisten**
    - **Validates: Requirements 3.3**
    - Verifikasi semua produk yang dikembalikan memiliki `id_kategori` yang sesuai dengan filter yang dipilih
    - File: `tests/Property/KatalogPropertyTest.php` (tambahkan method baru)

  - [ ]* 7.4 Tulis property test untuk urutan katalog
    - **Property 7: Urutan Katalog Deterministik**
    - **Validates: Requirements 3.4**
    - Verifikasi untuk `harga_asc` setiap produk ke-i ≤ ke-i+1, dan sebaliknya untuk `harga_desc`
    - File: `tests/Property/KatalogPropertyTest.php` (tambahkan method baru)

  - [ ]* 7.5 Tulis property test untuk paginasi katalog
    - **Property 8: Paginasi Membatasi Ukuran Halaman**
    - **Validates: Requirements 3.6**
    - Verifikasi hasil query paginasi selalu ≤ 12 produk untuk dataset dan halaman berapapun
    - File: `tests/Property/KatalogPropertyTest.php` (tambahkan method baru)

  - [x] 7.6 Implementasi template HTML katalog
    - Tampilkan form pencarian dan filter (kategori dropdown, urutan select)
    - Grid produk: 4 kolom desktop, 2 kolom tablet, 1 kolom mobile (CSS grid + media queries)
    - Kartu produk: foto, nama, harga (format_rupiah), tombol "Detail" dan tombol "Pesan" → `pemesanan.php?id=`
    - Tampilkan pesan "Produk tidak ditemukan" jika hasil kosong
    - Tampilkan komponen paginasi (link halaman sebelum/sesudah)
    - Include `head.php`, `navbar.php`, `footer.php`
    - _Requirements: 3.5, 3.7, 3.8, 3.9_

- [x] 8. Implementasi Halaman Publik: Detail Produk (`pages/detail-produk.php`)
  - [x] 8.1 Implementasi logika dan template detail produk
    - Baca `?id=` dari `$_GET`; query produk + nama kategori via JOIN; jika tidak ditemukan tampilkan pesan dan tautan kembali ke katalog
    - Query maks 4 produk terkait (kategori sama, bukan produk ini sendiri)
    - Tampilkan: nama, foto utama, deskripsi, harga (`format_rupiah()`), kategori, status ketersediaan
    - Tombol "Pesan Sekarang" → `pages/pemesanan.php?id=X`
    - Semua output dibungkus `e()`; include `head.php`, `navbar.php`, `footer.php`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 9. Implementasi Halaman Publik: Form Pemesanan (`pages/pemesanan.php`) dan JavaScript Validasi
  - [x] 9.1 Implementasi logika PHP pemesanan (handler POST)
    - Baca `?id=` dari `$_GET` untuk pra-isi produk; generate dan simpan CSRF token ke session
    - Saat POST: validasi CSRF, panggil `validasi_form_pemesanan()`, jika gagal kembalikan dengan error
    - Jika valid: jalankan transaksi PDO — `generate_no_pesanan()`, INSERT ke `pesanan`, INSERT ke `detail_pesanan`
    - Redirect PRG ke `pages/cek-pesanan.php?no=WF-...` setelah berhasil simpan
    - Jika total > Rp 100.000 dan Transfer: tampilkan informasi DP diperlukan di halaman konfirmasi
    - _Requirements: 5.1, 5.5, 5.6, 5.7, 5.8, 5.9, 5.10_

  - [x] 9.2 Implementasi template HTML form pemesanan
    - Field: nama pemesan, nomor WhatsApp, alamat, tanggal pengiriman, metode pembayaran (radio Transfer/COD), catatan, pilihan produk (pre-filled jika ada `?id=`)
    - Hidden input CSRF token; semua label dan placeholder dalam Bahasa Indonesia
    - Include `head.php`, `navbar.php`, `footer.php`
    - _Requirements: 5.1_

  - [x] 9.3 Implementasi `assets/js/pemesanan.js` — validasi client-side
    - Tambahkan `addEventListener('submit', ...)` pada form
    - Validasi: nama tidak kosong, WhatsApp 8–15 digit, alamat tidak kosong, tanggal tidak masa lalu, minimal satu produk terpilih
    - Tampilkan pesan error di bawah field yang bermasalah; batalkan submit jika ada error
    - Semua event listener via `addEventListener()`; tidak ada `onclick=""` inline
    - _Requirements: 5.2, 5.3, 17.4, 17.5_

- [x] 10. Implementasi Halaman Publik: Cek Pesanan (`pages/cek-pesanan.php`)
  - [x] 10.1 Implementasi logika dan template cek pesanan
    - Tampilkan form input `no_pesanan`; baca `?no=` dari `$_GET` setelah POST redirect
    - Query pesanan + detail_pesanan + dp + lunas berdasarkan `no_pesanan` (PDO prepared statement)
    - Jika tidak ditemukan: tampilkan pesan "Nomor pesanan tidak ditemukan. Periksa kembali nomor pesanan Anda."
    - Tampilkan: nomor pesanan, nama pemesan, produk dipesan, total harga (`format_rupiah()`), metode bayar, status, tanggal kirim (`format_tanggal_id()`)
    - Tampilkan progress stepper visual: Pesanan Diterima → Diproses → Siap Kirim → Selesai (tahapan terlewati diberi style berbeda)
    - Jika status `dibatalkan`: tampilkan keterangan pembatalan dengan jelas
    - Tampilkan info pembayaran: status DP (sudah/belum) dan status pelunasan
    - Include `head.php`, `navbar.php`, `footer.php`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 11. Checkpoint — Pastikan semua halaman publik berfungsi
  - Jalankan semua unit test dan property test: `./vendor/bin/phpunit --testdox`
  - Verifikasi bahwa `index.php`, `katalog.php`, `detail-produk.php`, `pemesanan.php`, `cek-pesanan.php` dapat dimuat tanpa error PHP
  - Tanyakan ke pengguna jika ada pertanyaan atau kendala

- [ ] 12. Implementasi Autentikasi Admin (`login.php`)
  - [x] 12.1 Implementasi logika PHP login
    - Rate limiting: cek `$_SESSION['login_attempts'][$ip]`; jika count ≥ 5 dan dalam 15 menit, tampilkan pesan blokir dan hentikan proses
    - Saat POST: validasi CSRF; query `pengguna` by username (PDO prepared statement); `password_verify()` terhadap hash; jika gagal increment counter dan tampilkan "Username atau password salah."
    - Jika berhasil: set `$_SESSION['id_pengguna']`, `$_SESSION['username']`, `$_SESSION['role']`; reset counter; redirect ke `admin/index.php`
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.7_

  - [ ] 12.2 Implementasi template HTML login
    - Halaman berdiri sendiri tanpa navbar/footer publik; include `head.php` saja
    - Form dengan field username dan password; hidden CSRF token; semua teks Bahasa Indonesia
    - _Requirements: 7.1_

  - [x] 12.3 Implementasi logout
    - Tombol "Keluar" di sidebar → POST ke handler yang memanggil `session_destroy()` lalu redirect ke `login.php`
    - _Requirements: 7.6_

- [x] 13. Implementasi Dashboard Admin (`admin/index.php`)
  - [x] 13.1 Implementasi logika PHP dashboard
    - `require_once '../config/session.php'` sebagai pengecekan auth
    - Query 4 kartu statistik: pesanan hari ini, pesanan `diproses`, total pemasukan bulan ini (dari `lunas`), jumlah stok bahan di bawah minimum
    - Query 5 pesanan terbaru (ORDER BY `created_at` DESC)
    - Query distribusi pesanan per status bulan berjalan (untuk grafik donat)
    - Query 3 produk terlaris (SUM dari `detail_pesanan`)
    - Query 3 pengeluaran terbaru
    - Generate CSRF token untuk toggle status
    - _Requirements: 8.1, 8.2, 8.4, 8.6, 8.7_

  - [x] 13.2 Implementasi grafik donat SVG distribusi pesanan
    - Implementasi fungsi `generate_svg_donat(array $data, int $radius = 80): string` di `config/helpers.php`
    - Algoritma arc path SVG: hitung sudut per segmen, `largeArcFlag`, warna per status (biru/kuning/hijau/merah)
    - Lubang donat: lingkaran putih di tengah (`r = radius * 0.55`)
    - _Requirements: 8.5, 14.8_

  - [x] 13.3 Implementasi template HTML dashboard
    - Tampilkan 4 kartu statistik, toggle status toko (checkbox + label), tabel 5 pesanan terbaru, grafik donat SVG inline, daftar produk terlaris, daftar pengeluaran terbaru
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'dashboard'`)
    - _Requirements: 8.1, 8.3, 8.8_

  - [x] 13.4 Implementasi endpoint AJAX `admin/ajax/toggle-status.php`
    - POST; validasi CSRF dari JSON body; query status saat ini; toggle ke nilai berlawanan via PDO prepared statement; kembalikan JSON `{"success": true, "status_baru": "..."}`
    - _Requirements: 1.2, 1.3_

  - [x] 13.5 Implementasi `assets/js/toggle-status.js`
    - `addEventListener('DOMContentLoaded', ...)` pada toggle checkbox
    - `fetch()` POST ke `admin/ajax/toggle-status.php` dengan CSRF token
    - Update label teks status tanpa reload; kembalikan toggle ke posisi sebelumnya jika gagal
    - _Requirements: 1.2, 8.3, 17.4, 17.5_

- [x] 14. Implementasi Manajemen Pesanan Admin (`admin/pesanan.php`)
  - [x] 14.1 Implementasi logika dan template manajemen pesanan
    - `require_once '../config/session.php'` sebagai pengecekan auth
    - Baca `?q=`, `?status=`, `?page=` dari `$_GET`; bangun query PDO dengan filter pencarian (`no_pesanan` atau `nama_pemesan`) dan filter status
    - Paginasi maks 20 baris per halaman
    - Tampilkan tabel pesanan dengan badge berwarna: biru (`menunggu_konfirmasi`), kuning (`diproses`), hijau (`selesai`), merah (`dibatalkan`)
    - Tombol "Ubah Status" di setiap baris dengan `data-id-pesanan` dan `data-status-saat-ini`
    - Tombol "Ekspor CSV": generate dan download file CSV sesuai filter aktif dengan header Bahasa Indonesia
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'pesanan'`)
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.7, 9.8_

  - [x] 14.2 Implementasi endpoint AJAX `admin/ajax/update-status-pesanan.php`
    - POST; validasi CSRF; update kolom `status` di tabel `pesanan` via PDO prepared statement; kembalikan JSON response
    - _Requirements: 9.6_

  - [x] 14.3 Implementasi `assets/js/admin-modal.js` — modal ubah status
    - `addEventListener` pada semua tombol "Ubah Status": baca `data-id-pesanan` dan `data-status-saat-ini`; tampilkan modal dengan `<select>` pilihan status baru
    - Klik "Konfirmasi": `fetch()` POST ke `update-status-pesanan.php`; update badge di baris tabel tanpa reload
    - Tidak ada `onclick=""` inline di HTML
    - _Requirements: 9.5, 9.6, 17.4, 17.5_

- [x] 15. Implementasi Manajemen Produk Admin (`admin/produk.php`)
  - [x] 15.1 Implementasi logika tambah dan edit produk
    - `require_once '../config/session.php'`
    - Handler POST tambah produk: validasi CSRF, validasi field (nama maks 200, harga positif, kategori ada, foto JPG/JPEG/PNG maks 2MB via `finfo_file()`)
    - Jika valid: generate nama file unik `uniqid('produk_', true) . '.' . $ext`; `move_uploaded_file()` ke `assets/img/produk/`; INSERT ke `produk` via PDO
    - Handler POST edit produk: validasi sama; jika ada foto baru → hapus file lama (kecuali placeholder), simpan foto baru; jika tidak ada foto baru → pertahankan kolom foto lama
    - Handler ubah status produk: UPDATE kolom `status` via PDO prepared statement
    - _Requirements: 10.2, 10.3, 10.4, 10.5, 10.6, 15.6_

  - [x] 15.2 Implementasi template HTML manajemen produk
    - Tabel semua produk dengan kolom: pratinjau foto (kecil), nama, kategori, harga, status, tombol Edit dan Toggle Status
    - Form tambah produk (inline atau modal): nama, harga, kategori (dropdown dari DB), deskripsi, foto, is_featured, status
    - Form edit produk: sama seperti tambah, data di-prefill
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'produk'`)
    - _Requirements: 10.1, 10.7_

- [x] 16. Implementasi Manajemen Stok Bahan Admin (`admin/stok.php`)
  - [x] 16.1 Implementasi logika dan template stok bahan
    - `require_once '../config/session.php'`
    - Query semua bahan dari `stok_bahan`; hitung jumlah item stok kritis (`stok_saat_ini < stok_minimum`)
    - Handler POST update stok (via AJAX modal): validasi CSRF, validasi nilai stok baru non-negatif, UPDATE via PDO; endpoint: `admin/ajax/update-stok.php`
    - Handler POST tambah bahan baru: validasi nama tidak kosong, stok awal non-negatif; INSERT via PDO
    - Tampilkan ringkasan jumlah stok kritis di atas tabel
    - Baris stok kritis: tampilkan dengan penanda merah dan badge "Stok Kritis"
    - Tombol "Update Stok" pada setiap baris: buka modal input jumlah stok baru
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'stok'`)
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6_

- [x] 17. Implementasi Pencatatan Pembayaran Admin (`admin/pembayaran.php`)
  - [x] 17.1 Implementasi logika pencatatan pembayaran
    - `require_once '../config/session.php'`
    - Tampilkan dua tab: DP dan Lunas
    - Kartu ringkasan: total DP bulan ini, total lunas bulan ini, jumlah pesanan menunggu pembayaran
    - Handler POST catat DP: validasi CSRF, `id_pesanan` valid, jumlah DP positif, metode dipilih; INSERT ke `dp`; UPDATE status pesanan menjadi `diproses` dalam satu transaksi
    - Handler POST catat Lunas (COD): langsung INSERT ke `lunas`, UPDATE status → `selesai`
    - Handler POST catat Lunas (Transfer): cek ada DP, panggil `validasi_pelunasan_transfer()`; INSERT ke `lunas`; UPDATE status → `selesai`
    - Semua operasi dalam transaksi PDO dengan try-catch
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'pembayaran'`)
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7, 12.8_

- [x] 18. Implementasi Pencatatan Pengeluaran Admin (`admin/pengeluaran.php`)
  - [x] 18.1 Implementasi logika dan template pengeluaran
    - `require_once '../config/session.php'`
    - Query semua pengeluaran dengan filter tanggal opsional (`?dari=`, `?sampai=`)
    - Handler POST tambah pengeluaran: validasi CSRF, keterangan tidak kosong (maks 255), jumlah positif, tanggal valid; INSERT via PDO
    - Hitung total pengeluaran untuk rentang tanggal yang ditampilkan (tampilkan di bawah tabel)
    - Kartu ringkasan total pengeluaran bulan berjalan di atas halaman
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'pengeluaran'`)
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_

- [x] 19. Implementasi Laporan Otomatis Admin (`admin/laporan.php`)
  - [x] 19.1 Implementasi logika query laporan
    - `require_once '../config/session.php'`
    - Baca parameter rentang tanggal dari `$_GET`; hitung ulang semua data laporan sesuai rentang
    - Query Ringkasan Keuangan: total pemasukan (SUM dari `lunas`), total pengeluaran (SUM dari `pengeluaran`), laba bersih via `hitung_laba_bersih()`
    - Query Laporan Pesanan: total pesanan, distribusi per status, produk terlaris (SUM dari `detail_pesanan`)
    - Query data 6 bulan terakhir: pemasukan dan pengeluaran per bulan (untuk grafik batang)
    - Handler "Ekspor CSV": generate file CSV data laporan yang ditampilkan dengan header Bahasa Indonesia
    - Handler "Cetak PDF": output script `window.print()` via echo atau link dengan CSS print
    - _Requirements: 14.1, 14.2, 14.4, 14.5, 14.6, 14.7_

  - [x] 19.2 Implementasi grafik batang SVG pemasukan vs pengeluaran
    - Implementasi fungsi `generate_svg_bar(array $pemasukan, array $pengeluaran): string` di `config/helpers.php`
    - Hitung nilai maksimum untuk skala Y; dua `<rect>` per bulan (lebar 20px, jarak 8px antar kelompok)
    - Tinggi bar = `(nilai / max_nilai) * tinggi_area`; label bulan di sumbu X, 4 garis grid horizontal
    - Warna pemasukan `#16A34A`, warna pengeluaran `#DC2626`; output SVG inline, tanpa library JS
    - _Requirements: 14.3, 14.8_

  - [x] 19.3 Implementasi template HTML laporan dengan 4 tab
    - Tab Ringkasan Keuangan: grafik batang SVG, total pemasukan, pengeluaran, laba bersih
    - Tab Laporan Pesanan: total pesanan, distribusi per status (tabel), produk terlaris
    - Tab Laporan Produk: data produk (dapat diperluas sesuai kebutuhan)
    - Tab Laporan Stok: data stok bahan
    - Tombol "Cetak PDF" dan "Ekspor CSV"
    - Include `head.php`, `sidebar.php` (dengan `$active_page = 'laporan'`)
    - _Requirements: 14.1_

- [x] 20. Checkpoint Akhir — Pastikan semua tes lolos dan sistem terintegrasi
  - Jalankan seluruh test suite: `./vendor/bin/phpunit --testdox`
  - Verifikasi semua halaman admin dapat dimuat tanpa error PHP
  - Pastikan autentikasi session admin bekerja (redirect ke `login.php` jika tidak ada session)
  - Pastikan semua form POST memiliki CSRF token yang tervalidasi
  - Pastikan semua query database menggunakan PDO prepared statement
  - Tanyakan ke pengguna jika ada pertanyaan atau kendala

---

## Notes

- Task yang ditandai `*` bersifat opsional dan dapat dilewati untuk MVP yang lebih cepat
- Setiap task merujuk pada requirement spesifik untuk keterlacakan
- Checkpoint memastikan validasi bertahap sebelum melanjutkan
- Property tests memvalidasi properti universal (berlaku untuk semua input)
- Unit tests memvalidasi contoh konkret dan kondisi batas
- Seluruh implementasi menggunakan PHP Native, MySQL, Pure CSS, Vanilla JavaScript — tanpa framework apapun
- Seluruh teks antarmuka, pesan error, dan notifikasi dalam Bahasa Indonesia
- `config/` tidak dapat diakses langsung dari browser (dilindungi `.htaccess`)

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1"] },
    { "id": 1, "tasks": ["2.1", "2.3", "2.5", "2.7", "2.9"] },
    { "id": 2, "tasks": ["2.2", "2.4", "2.6", "2.8", "2.10", "3.1", "3.3", "3.5"] },
    { "id": 3, "tasks": ["3.2", "3.4", "3.6", "5.1", "5.2"] },
    { "id": 4, "tasks": ["5.3", "5.6", "5.7", "5.8"] },
    { "id": 5, "tasks": ["5.4", "5.5", "6.1"] },
    { "id": 6, "tasks": ["6.2", "6.3", "6.4", "7.1", "8.1", "12.1"] },
    { "id": 7, "tasks": ["7.2", "7.3", "7.4", "7.5", "7.6", "9.1", "10.1", "12.2", "12.3"] },
    { "id": 8, "tasks": ["9.2", "9.3", "13.1", "14.1"] },
    { "id": 9, "tasks": ["13.2", "13.4", "15.1", "16.1", "17.1", "18.1"] },
    { "id": 10, "tasks": ["13.3", "13.5", "14.2", "14.3", "15.2", "19.1"] },
    { "id": 11, "tasks": ["19.2"] },
    { "id": 12, "tasks": ["19.3"] }
  ]
}
```
