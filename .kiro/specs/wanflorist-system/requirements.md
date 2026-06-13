# Dokumen Kebutuhan Sistem — WanFlorist

## Pendahuluan

WanFlorist adalah sistem informasi pemesanan dan layanan berbasis web untuk toko buket bunga UMKM WanFlorist yang berlokasi di Singojuruh, Banyuwangi, Jawa Timur, Indonesia. Sistem ini dibangun menggunakan PHP Native, MySQL, HTML, Pure CSS, dan Vanilla JavaScript — tanpa framework apapun.

Sistem melayani dua jenis pengguna:
- **Pembeli (Publik)** — mengakses katalog, melakukan pemesanan, dan memantau status pesanan tanpa perlu membuat akun.
- **Admin (Pemilik/Staf)** — mengelola seluruh operasional toko melalui panel admin yang dilindungi autentikasi.

Sistem terdiri dari halaman publik (dengan navbar + footer) dan halaman admin (dengan sidebar tetap berlatar belakang gelap `#1E1040`). Kedua layout ini sepenuhnya terpisah.

---

## Glosarium

- **Sistem**: Aplikasi web WanFlorist secara keseluruhan.
- **Pembeli**: Pengguna publik yang mengakses situs tanpa akun untuk melihat produk dan membuat pesanan.
- **Admin**: Pengguna terautentikasi (pemilik atau staf toko) yang memiliki akses penuh ke panel manajemen.
- **Produk**: Buket bunga atau rangkaian bunga yang dijual oleh WanFlorist, disimpan di tabel `produk`.
- **Kategori**: Pengelompokan produk (contoh: Mawar, Sunflower, Wedding), disimpan di tabel `kategori`.
- **Pesanan**: Transaksi pemesanan dari Pembeli, disimpan di tabel `pesanan` dan `detail_pesanan` dengan nomor unik format `WF-YYYYMMDD-XXXX`.
- **DP (Down Payment)**: Uang muka yang wajib dibayarkan untuk pesanan dengan nilai di atas Rp 100.000, dicatat di tabel `dp`.
- **Lunas**: Pembayaran penuh yang menyelesaikan transaksi, dicatat di tabel `lunas`. Pesanan COD langsung masuk ke tabel `lunas` tanpa melalui tabel `dp`.
- **Status Toko**: Indikator ketersediaan pemilik (Aktif/Nonaktif), disimpan di tabel `status_toko`.
- **Stok Bahan**: Bahan baku yang digunakan untuk merangkai buket (contoh: kertas, pita, bunga potong), disimpan di tabel `stok_bahan`.
- **Pengeluaran**: Biaya operasional toko yang dikeluarkan Admin, disimpan di tabel `pengeluaran`.
- **CSRF Token**: Token keamanan yang disertakan di setiap form POST untuk mencegah serangan Cross-Site Request Forgery.
- **PDO**: PHP Data Objects, antarmuka akses database yang digunakan dengan prepared statements untuk mencegah SQL Injection.
- **no_pesanan**: Nomor unik pesanan dengan format `WF-YYYYMMDD-XXXX` (contoh: `WF-20250115-0001`).
- **COD (Cash on Delivery)**: Metode pembayaran tunai saat produk diterima; tidak melalui alur DP.
- **Validator**: Komponen logika PHP yang memvalidasi input dari form sebelum data disimpan ke database.
- **Router**: Mekanisme PHP yang mengarahkan permintaan HTTP ke halaman yang tepat berdasarkan parameter URL.
- **Pretty_Printer**: Fungsi pemformat yang mengubah data internal (angka, tanggal) menjadi format tampilan yang sesuai (Rp format dan format tanggal Indonesia).
- **Sidebar**: Komponen navigasi admin yang ditampilkan sebagai panel kiri tetap pada halaman admin.
- **Navbar**: Komponen navigasi publik yang ditampilkan sebagai bilah atas pada halaman publik.

---

## Kebutuhan

### Kebutuhan 1: Manajemen Status Toko

**Kisah Pengguna:** Sebagai Admin, saya ingin mengubah status ketersediaan toko secara real-time, agar pembeli mengetahui apakah saya sedang tersedia menerima pesanan hari ini.

#### Kriteria Penerimaan

1. THE Sistem SHALL menyimpan satu baris data status toko aktif di tabel `status_toko` dengan nilai `aktif` atau `nonaktif`.
2. WHEN Admin mengklik tombol toggle status di dashboard, THE Sistem SHALL memperbarui kolom `status` di tabel `status_toko` melalui AJAX tanpa memuat ulang halaman.
3. WHEN permintaan AJAX perubahan status diterima, THE Sistem SHALL memvalidasi CSRF token sebelum memproses perubahan.
4. WHEN status toko bernilai `aktif`, THE Sistem SHALL menampilkan banner bertuliskan "Owner sedang tersedia — Homestore buka hari ini" di halaman publik dengan indikator hijau beranimasi.
5. WHEN status toko bernilai `nonaktif`, THE Sistem SHALL menampilkan banner bertuliskan "Owner sedang tidak tersedia" di halaman publik dengan indikator abu-abu.
6. THE Navbar SHALL membaca status toko dari database setiap kali halaman publik dimuat.

---

### Kebutuhan 2: Halaman Beranda (Landing Page)

**Kisah Pengguna:** Sebagai Pembeli, saya ingin melihat halaman beranda yang menarik dengan informasi toko dan produk unggulan, agar saya bisa memahami apa yang ditawarkan WanFlorist dan tertarik untuk membeli.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman beranda (`index.php`) yang terdiri dari: banner status toko, seksi hero, seksi kategori, seksi produk terlaris, seksi CTA tentang toko, dan seksi testimoni.
2. WHEN halaman beranda dimuat, THE Sistem SHALL mengambil maksimal 4 produk dengan `is_featured = 1` dari tabel `produk` untuk ditampilkan di seksi produk terlaris.
3. WHEN halaman beranda dimuat, THE Sistem SHALL mengambil semua kategori aktif dari tabel `kategori` dan menampilkannya sebagai tag pill di seksi kategori.
4. THE Sistem SHALL menampilkan seksi testimoni statis yang berisi minimal 3 ulasan pelanggan.
5. THE Navbar SHALL ditampilkan di bagian atas halaman beranda dengan tautan ke: Beranda, Produk, Cek Pesanan, Tentang Kami, dan Kontak.
6. THE Footer SHALL ditampilkan di bagian bawah setiap halaman publik.
7. WHEN Pembeli mengklik tombol "Belanja Sekarang" di seksi hero, THE Sistem SHALL mengarahkan Pembeli ke halaman katalog (`pages/katalog.php`).
8. WHEN Pembeli mengklik salah satu kategori di seksi kategori beranda, THE Sistem SHALL mengarahkan Pembeli ke halaman katalog dengan filter kategori tersebut sudah aktif.

---

### Kebutuhan 3: Katalog Produk

**Kisah Pengguna:** Sebagai Pembeli, saya ingin menelusuri seluruh koleksi produk dengan kemampuan pencarian, filter, dan pengurutan, agar saya dapat menemukan buket yang sesuai dengan kebutuhan dan anggaran saya.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman katalog (`pages/katalog.php`) yang memuat semua produk dengan status `tersedia` dari tabel `produk`.
2. WHEN Pembeli memasukkan kata kunci di kotak pencarian katalog, THE Sistem SHALL memfilter produk berdasarkan kecocokan dengan kolom `nama_produk` atau `deskripsi` menggunakan query PDO prepared statement dengan parameter LIKE.
3. WHEN Pembeli memilih kategori dari filter kategori, THE Sistem SHALL memfilter produk yang hanya memiliki `id_kategori` yang sesuai.
4. WHEN Pembeli memilih opsi urutan, THE Sistem SHALL mengurutkan produk berdasarkan pilihan: harga terendah, harga tertinggi, atau terbaru.
5. THE Sistem SHALL menampilkan produk dalam tata letak grid: 4 kolom di desktop, 2 kolom di tablet, 1 kolom di mobile.
6. THE Sistem SHALL menerapkan paginasi dengan maksimal 12 produk per halaman.
7. WHEN tidak ada produk yang cocok dengan filter atau kata kunci yang diberikan, THE Sistem SHALL menampilkan pesan "Produk tidak ditemukan."
8. WHEN Pembeli mengklik kartu produk di katalog, THE Sistem SHALL mengarahkan Pembeli ke halaman detail produk yang sesuai.
9. WHEN Pembeli mengklik tombol "Pesan" di kartu produk, THE Sistem SHALL mengarahkan Pembeli ke halaman pemesanan dengan ID produk tersebut sudah terisi.

---

### Kebutuhan 4: Detail Produk

**Kisah Pengguna:** Sebagai Pembeli, saya ingin melihat informasi lengkap tentang suatu produk termasuk foto, deskripsi, dan harga, agar saya dapat membuat keputusan pembelian yang tepat.

#### Kriteria Penerimaan

1. WHEN Pembeli mengakses halaman detail produk (`pages/detail-produk.php`) dengan parameter `id` yang valid, THE Sistem SHALL menampilkan nama produk, foto utama, deskripsi lengkap, harga, kategori, dan status ketersediaan produk tersebut.
2. WHEN parameter `id` yang dikirimkan ke halaman detail produk tidak ditemukan di database, THE Sistem SHALL menampilkan pesan "Produk tidak ditemukan" dan tautan kembali ke katalog.
3. THE Sistem SHALL menampilkan seksi "Produk Terkait" yang berisi maksimal 4 produk dari kategori yang sama dengan produk yang sedang ditampilkan.
4. WHEN Pembeli mengklik tombol "Pesan Sekarang" di halaman detail produk, THE Sistem SHALL mengarahkan Pembeli ke halaman pemesanan (`pages/pemesanan.php`) dengan ID produk tersebut sudah terisi di form.
5. THE Sistem SHALL menampilkan harga produk dalam format Rp dengan titik sebagai pemisah ribuan (contoh: `Rp 250.000`).

---

### Kebutuhan 5: Pemesanan Produk

**Kisah Pengguna:** Sebagai Pembeli, saya ingin mengisi formulir pemesanan dengan mudah, agar saya dapat memesan buket tanpa harus membuat akun dan mendapatkan nomor pesanan untuk melacak status pesanan saya.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan formulir pemesanan (`pages/pemesanan.php`) dengan kolom: nama pemesan, nomor WhatsApp, alamat pengiriman, tanggal pengiriman yang diinginkan, metode pembayaran (Transfer/COD), catatan tambahan, dan pilihan produk.
2. WHEN Pembeli mengirimkan formulir pemesanan, THE Validator SHALL memvalidasi bahwa: nama pemesan tidak kosong (maksimal 100 karakter), nomor WhatsApp valid (8–15 digit angka), alamat tidak kosong (maksimal 500 karakter), tanggal pengiriman tidak boleh hari yang sudah lewat, dan minimal satu produk dipilih dengan jumlah minimal 1.
3. IF salah satu validasi gagal di sisi klien, THEN THE Sistem SHALL menampilkan pesan kesalahan di bawah kolom yang bermasalah tanpa mengirimkan data ke server.
4. IF salah satu validasi gagal di sisi server, THEN THE Validator SHALL mengembalikan respons dengan pesan kesalahan yang sesuai dan tidak menyimpan data ke database.
5. WHEN formulir pemesanan berhasil divalidasi, THE Sistem SHALL menghasilkan `no_pesanan` dengan format `WF-YYYYMMDD-XXXX` di mana XXXX adalah nomor urut 4 digit yang di-reset setiap hari (dimulai dari 0001).
6. WHEN pesanan berhasil disimpan, THE Sistem SHALL menyimpan data ke tabel `pesanan` dan `detail_pesanan` menggunakan PDO prepared statement dalam satu transaksi database.
7. WHEN pesanan berhasil disimpan, THE Sistem SHALL menampilkan halaman konfirmasi dengan `no_pesanan` yang dihasilkan dan instruksi langkah selanjutnya.
8. WHEN total nilai pesanan melebihi Rp 100.000 dan metode pembayaran adalah Transfer, THE Sistem SHALL menampilkan informasi bahwa DP diperlukan sebelum pesanan diproses.
9. WHEN metode pembayaran adalah COD, THE Sistem SHALL mencatat pesanan tanpa memerlukan data DP; pembayaran akan langsung dicatat di tabel `lunas`.
10. EVERY formulir POST di halaman pemesanan SHALL menyertakan CSRF token yang divalidasi oleh server sebelum data diproses.

---

### Kebutuhan 6: Pelacakan Status Pesanan

**Kisah Pengguna:** Sebagai Pembeli, saya ingin memeriksa status pesanan saya menggunakan nomor pesanan yang saya terima, agar saya mengetahui tahapan pemrosesan pesanan saya saat ini.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman cek pesanan (`pages/cek-pesanan.php`) dengan kolom input untuk memasukkan `no_pesanan`.
2. WHEN Pembeli memasukkan `no_pesanan` yang valid dan mengklik tombol "Cek Pesanan", THE Sistem SHALL menampilkan detail pesanan: nomor pesanan, nama pemesan, produk yang dipesan, total harga, metode pembayaran, status pesanan terkini, dan tanggal pengiriman yang diminta.
3. WHEN `no_pesanan` yang dimasukkan tidak ditemukan di database, THE Sistem SHALL menampilkan pesan "Nomor pesanan tidak ditemukan. Periksa kembali nomor pesanan Anda."
4. THE Sistem SHALL menampilkan progress stepper visual yang menunjukkan tahapan: Pesanan Diterima → Diproses → Siap Kirim → Selesai, dengan tahapan yang sudah terlewati ditandai berbeda dari tahapan yang belum.
5. WHEN status pesanan adalah `dibatalkan`, THE Sistem SHALL menampilkan keterangan pembatalan secara jelas pada halaman pelacakan.
6. THE Sistem SHALL menampilkan informasi pembayaran: status DP (sudah/belum dibayar) dan status pelunasan.

---

### Kebutuhan 7: Autentikasi Admin

**Kisah Pengguna:** Sebagai Admin, saya ingin masuk ke panel manajemen menggunakan username dan password yang aman, agar hanya saya dan staf yang berwenang dapat mengakses data sensitif bisnis.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman login admin (`login.php`) yang berdiri sendiri tanpa navbar atau footer publik.
2. WHEN Admin mengirimkan formulir login, THE Validator SHALL memverifikasi bahwa username ditemukan di tabel `pengguna` dan password cocok menggunakan fungsi `password_verify()` terhadap hash yang disimpan.
3. IF username tidak ditemukan atau password tidak cocok, THEN THE Sistem SHALL menampilkan pesan "Username atau password salah." tanpa menjelaskan kolom mana yang salah.
4. WHEN autentikasi berhasil, THE Sistem SHALL membuat session PHP dengan data `id_pengguna`, `username`, dan `role` Admin tersebut, lalu mengarahkan Admin ke halaman dashboard (`admin/index.php`).
5. EVERY halaman admin SHALL memeriksa keberadaan session yang valid di awal eksekusi PHP; IF session tidak ada, THEN THE Sistem SHALL mengarahkan pengguna ke halaman `login.php`.
6. WHEN Admin mengklik tombol "Keluar", THE Sistem SHALL menghancurkan session PHP sepenuhnya menggunakan `session_destroy()` lalu mengarahkan ke halaman `login.php`.
7. THE Sistem SHALL membatasi jumlah percobaan login menjadi maksimal 5 kali dalam 15 menit dari alamat IP yang sama; IF batas terlampaui, THEN THE Sistem SHALL menampilkan pesan bahwa akses sementara diblokir.

---

### Kebutuhan 8: Dashboard Admin

**Kisah Pengguna:** Sebagai Admin, saya ingin melihat ringkasan operasional toko dalam satu tampilan, agar saya dapat memantau kinerja bisnis dan mengambil tindakan cepat.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman dashboard admin (`admin/index.php`) dengan: kartu statistik, kontrol toggle status toko, tabel pesanan terbaru, grafik pesanan per status (donat), daftar produk terlaris, dan daftar pengeluaran terakhir.
2. THE Sistem SHALL menampilkan 4 kartu statistik: jumlah pesanan hari ini, jumlah pesanan berstatus `diproses`, total pemasukan bulan ini (dari tabel `lunas`), dan jumlah item stok bahan yang berada di bawah batas stok minimum.
3. WHEN Admin mengubah toggle status toko di dashboard, THE Sistem SHALL memperbarui status di tabel `status_toko` melalui AJAX fetch() dan memperbarui teks label status tanpa memuat ulang halaman.
4. THE Sistem SHALL menampilkan 5 pesanan terbaru di tabel pesanan terbaru, diurutkan berdasarkan `created_at` secara menurun.
5. THE Sistem SHALL menampilkan grafik donat SVG yang mengilustrasikan distribusi pesanan berdasarkan status (Selesai, Diproses, Menunggu Konfirmasi, Dibatalkan) untuk bulan berjalan.
6. THE Sistem SHALL menampilkan 3 produk terlaris berdasarkan jumlah total item yang terjual dari tabel `detail_pesanan`.
7. THE Sistem SHALL menampilkan 3 pengeluaran terbaru dari tabel `pengeluaran` dengan nama dan nominal.
8. THE Sidebar SHALL ditampilkan di sisi kiri sebagai navigasi tetap pada semua halaman admin, dengan lebar 240px dan latar belakang `#1E1040`.

---

### Kebutuhan 9: Manajemen Pesanan (Admin)

**Kisah Pengguna:** Sebagai Admin, saya ingin melihat semua pesanan yang masuk, mengubah statusnya, dan mengekspornya, agar saya dapat mengelola alur kerja pemrosesan pesanan dengan efisien.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman manajemen pesanan (`admin/pesanan.php`) dengan tabel yang memuat semua pesanan dari tabel `pesanan` disertai detail dari tabel `detail_pesanan`.
2. WHEN Admin memasukkan kata kunci di kotak pencarian pesanan, THE Sistem SHALL memfilter pesanan berdasarkan `no_pesanan` atau `nama_pemesan` menggunakan PDO prepared statement.
3. WHEN Admin memilih filter status pesanan, THE Sistem SHALL menampilkan hanya pesanan dengan status yang dipilih: `menunggu_konfirmasi`, `diproses`, `selesai`, atau `dibatalkan`.
4. THE Sistem SHALL menampilkan status pesanan dalam bentuk badge berwarna: biru untuk `menunggu_konfirmasi`, kuning untuk `diproses`, hijau untuk `selesai`, dan merah untuk `dibatalkan`.
5. WHEN Admin mengklik tombol ubah status pada baris pesanan, THE Sistem SHALL menampilkan modal konfirmasi yang memungkinkan Admin memilih status baru dari daftar yang tersedia.
6. WHEN Admin mengonfirmasi perubahan status di modal, THE Sistem SHALL memperbarui kolom `status` di tabel `pesanan` menggunakan PDO prepared statement dan CSRF token yang valid.
7. WHEN Admin mengklik tombol "Ekspor CSV", THE Sistem SHALL mengunduh file CSV yang berisi semua data pesanan yang sedang ditampilkan (sesuai filter aktif) dengan header kolom dalam Bahasa Indonesia.
8. THE Sistem SHALL menampilkan paginasi pada tabel pesanan dengan maksimal 20 baris per halaman.

---

### Kebutuhan 10: Manajemen Produk (Admin)

**Kisah Pengguna:** Sebagai Admin, saya ingin menambahkan produk baru, mengedit produk yang ada, dan menonaktifkan produk yang tidak tersedia, agar katalog publik selalu mencerminkan koleksi toko yang aktual.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman manajemen produk (`admin/produk.php`) dengan tabel yang memuat semua produk dari tabel `produk` beserta nama kategorinya.
2. WHEN Admin mengisi dan mengirimkan formulir tambah produk, THE Validator SHALL memvalidasi bahwa: nama produk tidak kosong (maksimal 200 karakter), harga adalah angka positif, kategori dipilih dari daftar yang ada, dan file foto berformat JPG, JPEG, atau PNG dengan ukuran maksimal 2 MB.
3. WHEN validasi formulir produk berhasil, THE Sistem SHALL menyimpan data produk ke tabel `produk` dan menyimpan file foto ke direktori `assets/img/produk/` dengan nama file yang dihasilkan secara unik untuk mencegah tabrakan nama.
4. WHEN Admin mengklik tombol "Edit" pada baris produk, THE Sistem SHALL memuat data produk tersebut ke dalam formulir edit dan memungkinkan Admin memperbarui semua kolom termasuk mengganti foto.
5. WHEN Admin memperbarui produk tanpa mengunggah foto baru, THE Sistem SHALL mempertahankan foto yang sudah ada dan tidak mengubah nama file foto di database.
6. WHEN Admin mengubah status produk menjadi `nonaktif`, THE Sistem SHALL menyembunyikan produk tersebut dari halaman publik (katalog dan beranda) tanpa menghapus data dari database.
7. THE Sistem SHALL menampilkan pratinjau foto produk berukuran kecil di dalam tabel manajemen produk.

---

### Kebutuhan 11: Manajemen Stok Bahan (Admin)

**Kisah Pengguna:** Sebagai Admin, saya ingin memantau stok bahan baku yang digunakan untuk merangkai buket dan mendapatkan peringatan saat stok hampir habis, agar saya dapat melakukan pembelian bahan sebelum kehabisan.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman manajemen stok (`admin/stok.php`) dengan tabel yang memuat semua bahan dari tabel `stok_bahan` beserta jumlah stok saat ini dan satuan.
2. WHEN jumlah stok suatu bahan berada di bawah nilai kolom `stok_minimum`, THE Sistem SHALL menampilkan baris tersebut dengan penanda peringatan berwarna merah dan badge "Stok Kritis".
3. WHEN Admin mengklik tombol "Update Stok" pada baris bahan, THE Sistem SHALL menampilkan modal yang memungkinkan Admin memasukkan jumlah stok baru.
4. WHEN Admin mengirimkan pembaruan stok di modal, THE Validator SHALL memvalidasi bahwa nilai stok baru adalah angka non-negatif sebelum menyimpan ke database menggunakan PDO prepared statement.
5. THE Sistem SHALL menampilkan ringkasan di bagian atas halaman yang menunjukkan jumlah total item stok kritis.
6. WHEN Admin menambahkan bahan baru melalui formulir tambah bahan, THE Validator SHALL memvalidasi bahwa nama bahan tidak kosong dan stok awal adalah angka non-negatif.

---

### Kebutuhan 12: Pencatatan Pembayaran (Admin)

**Kisah Pengguna:** Sebagai Admin, saya ingin mencatat pembayaran DP dan pelunasan dari pembeli, agar riwayat keuangan pesanan terdokumentasi dengan baik dan status pembayaran setiap pesanan selalu terkini.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman pencatatan pembayaran (`admin/pembayaran.php`) dengan dua tab: tab DP dan tab Lunas.
2. THE Sistem SHALL menampilkan kartu ringkasan di bagian atas yang menunjukkan: total DP diterima bulan ini, total pembayaran lunas bulan ini, dan jumlah pesanan yang menunggu pembayaran.
3. WHEN Admin memilih pesanan dan mengisi formulir pencatatan DP, THE Validator SHALL memvalidasi bahwa `id_pesanan` valid, jumlah DP adalah angka positif, dan metode pembayaran dipilih; kemudian THE Sistem SHALL menyimpan data ke tabel `dp` menggunakan PDO prepared statement.
4. WHEN DP berhasil dicatat, THE Sistem SHALL memperbarui status pesanan di tabel `pesanan` menjadi `diproses` secara otomatis.
5. WHEN Admin mencatat pembayaran lunas untuk pesanan yang menggunakan metode COD, THE Sistem SHALL menyimpan langsung ke tabel `lunas` tanpa memeriksa keberadaan data di tabel `dp`.
6. WHEN Admin mencatat pembayaran lunas untuk pesanan Transfer yang sudah ada DP-nya, THE Sistem SHALL memvalidasi bahwa jumlah pelunasan tidak kurang dari (total pesanan dikurangi jumlah DP yang sudah dibayarkan).
7. WHEN pembayaran lunas berhasil dicatat, THE Sistem SHALL memperbarui status pesanan di tabel `pesanan` menjadi `selesai` secara otomatis.
8. EVERY formulir pencatatan pembayaran SHALL menyertakan CSRF token yang divalidasi oleh server.

---

### Kebutuhan 13: Pencatatan Pengeluaran (Admin)

**Kisah Pengguna:** Sebagai Admin, saya ingin mencatat setiap pengeluaran operasional toko, agar saya dapat memantau biaya dan menghitung keuntungan bersih toko.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman pencatatan pengeluaran (`admin/pengeluaran.php`) dengan tabel yang memuat semua pengeluaran dari tabel `pengeluaran`.
2. WHEN Admin mengisi dan mengirimkan formulir tambah pengeluaran, THE Validator SHALL memvalidasi bahwa: keterangan pengeluaran tidak kosong (maksimal 255 karakter), jumlah pengeluaran adalah angka positif, dan tanggal pengeluaran valid.
3. WHEN validasi berhasil, THE Sistem SHALL menyimpan data pengeluaran ke tabel `pengeluaran` menggunakan PDO prepared statement.
4. WHEN Admin menggunakan filter tanggal di halaman pengeluaran, THE Sistem SHALL menampilkan hanya pengeluaran yang berada dalam rentang tanggal yang dipilih.
5. THE Sistem SHALL menampilkan total pengeluaran untuk rentang tanggal yang sedang ditampilkan di bagian bawah tabel.
6. THE Sistem SHALL menampilkan ringkasan total pengeluaran bulan berjalan di kartu ringkasan di bagian atas halaman.

---

### Kebutuhan 14: Laporan Otomatis (Admin)

**Kisah Pengguna:** Sebagai Admin, saya ingin melihat laporan ringkasan keuangan dan operasional secara otomatis, agar saya dapat memantau kinerja bisnis dan mengambil keputusan berdasarkan data.

#### Kriteria Penerimaan

1. THE Sistem SHALL menampilkan halaman laporan (`admin/laporan.php`) dengan tab-tab: Ringkasan Keuangan, Laporan Pesanan, Laporan Produk, dan Laporan Stok.
2. WHEN Admin memilih rentang tanggal laporan, THE Sistem SHALL menghitung ulang dan menampilkan data laporan yang sesuai dengan rentang tersebut dari database.
3. THE Sistem SHALL menampilkan grafik batang SVG pada tab Ringkasan Keuangan yang membandingkan total pemasukan (dari `lunas`) dan total pengeluaran (dari `pengeluaran`) per bulan dalam 6 bulan terakhir.
4. THE Sistem SHALL menampilkan pada tab Laporan Pesanan: total jumlah pesanan, distribusi pesanan per status dalam tabel, dan daftar produk terlaris berdasarkan jumlah terjual.
5. THE Sistem SHALL menghitung laba bersih sebagai: total pemasukan dari tabel `lunas` dikurangi total pengeluaran dari tabel `pengeluaran` untuk rentang periode yang dipilih.
6. WHEN Admin mengklik tombol "Cetak PDF", THE Sistem SHALL memicu fungsi cetak browser (`window.print()`) dengan CSS print yang memastikan layout laporan rapi di atas kertas.
7. WHEN Admin mengklik tombol "Ekspor CSV" di halaman laporan, THE Sistem SHALL mengunduh file CSV yang berisi data laporan yang sedang ditampilkan beserta header kolom dalam Bahasa Indonesia.
8. EVERY grafik di halaman laporan SHALL dibuat menggunakan elemen SVG murni yang dihasilkan oleh PHP, tanpa menggunakan library JavaScript grafik pihak ketiga.

---

### Kebutuhan 15: Keamanan Sistem

**Kisah Pengguna:** Sebagai Admin, saya ingin sistem dilindungi dari ancaman keamanan umum, agar data bisnis dan data pembeli aman dari akses atau manipulasi yang tidak berwenang.

#### Kriteria Penerimaan

1. EVERY query database di seluruh Sistem SHALL menggunakan PDO prepared statement dengan parameter terikat; penggunaan query string interpolasi langsung dilarang.
2. EVERY data yang ditampilkan dari input pengguna atau database di halaman HTML SHALL dibungkus dengan fungsi `htmlspecialchars()` sebelum di-output.
3. EVERY formulir POST di seluruh Sistem SHALL menyertakan hidden input CSRF token; THE Validator SHALL memeriksa kecocokan token ini dengan nilai yang disimpan di session sebelum memproses data.
4. THE Sistem SHALL menyimpan password Admin di tabel `pengguna` menggunakan hash `password_hash()` dengan algoritma `PASSWORD_BCRYPT`; THE Validator SHALL memverifikasi password menggunakan `password_verify()`.
5. EVERY halaman di bawah direktori `admin/` SHALL memeriksa keberadaan session admin yang valid menggunakan `require_once` ke file auth check di awal eksekusi; IF session tidak valid, THEN THE Sistem SHALL mengarahkan ke `login.php` dengan kode HTTP 302.
6. THE Sistem SHALL memvalidasi tipe dan ukuran file pada setiap upload foto produk di sisi server; IF file tidak valid, THEN THE Validator SHALL menolak upload dan menampilkan pesan kesalahan.
7. THE Sistem SHALL menggunakan koneksi database dari file konfigurasi terpusat (`config/database.php`) yang tidak dapat diakses langsung dari browser.

---

### Kebutuhan 16: Desain Sistem dan Antarmuka Pengguna

**Kisah Pengguna:** Sebagai Pembeli dan Admin, saya ingin antarmuka sistem konsisten, estetis, dan mudah digunakan, agar pengalaman menggunakan aplikasi ini terasa profesional dan menyenangkan.

#### Kriteria Penerimaan

1. THE Sistem SHALL mengimplementasikan seluruh antarmuka menggunakan Pure CSS tanpa framework CSS (tidak boleh menggunakan Tailwind, Bootstrap, atau sejenisnya).
2. THE Sistem SHALL menggunakan hanya dua font: `Playfair Display` (untuk judul dan heading) dan `Inter` (untuk semua teks body, label, dan tombol), dimuat dari Google Fonts.
3. EVERY tombol di seluruh Sistem SHALL menggunakan `border-radius: 9999px` (pill shape); tombol persegi atau kotak tidak diperbolehkan.
4. THE Sistem SHALL menggunakan palet warna yang didefinisikan dalam DESIGN.md: warna primer `#6B21A8`, latar belakang sidebar admin `#1E1040`, dan token warna lainnya secara konsisten.
5. THE Sistem SHALL menampilkan semua nilai mata uang dalam format `Rp X.XXX.XXX` menggunakan titik sebagai pemisah ribuan (bukan koma).
6. THE Sistem SHALL menampilkan semua tanggal dalam format Bahasa Indonesia: `Senin, 12 Januari 2026`, menggunakan fungsi PHP untuk konversi nama hari dan nama bulan.
7. THE Navbar (komponen publik) dan THE Sidebar (komponen admin) SHALL diimplementasikan sebagai dua file komponen PHP yang sepenuhnya terpisah: `components/navbar.php` dan `components/sidebar.php`.
8. THE Sistem SHALL responsif untuk layar desktop (≥1024px), tablet (768px–1023px), dan mobile (<768px) menggunakan CSS media queries tanpa CSS framework.
9. WHEN Sistem ditampilkan di perangkat mobile, THE Sidebar SHALL dapat disembunyikan dan ditampilkan melalui tombol toggle hamburger.
10. THE Sistem SHALL menggunakan seluruh teks antarmuka, label form, pesan kesalahan, dan notifikasi dalam Bahasa Indonesia.

---

### Kebutuhan 17: Arsitektur Teknis dan Standar Kode

**Kisah Pengguna:** Sebagai pengembang, saya ingin sistem dibangun dengan standar kode yang jelas dan konsisten, agar basis kode mudah dipelihara dan dikembangkan lebih lanjut.

#### Kriteria Penerimaan

1. THE Sistem SHALL mengorganisasikan file sesuai struktur direktori berikut: `WanFloristWebsite/` (root), `assets/` (CSS, JS, gambar), `components/` (navbar, footer, sidebar, head), `config/` (database, session), `admin/` (semua halaman admin), `pages/` (semua halaman publik selain beranda).
2. EVERY halaman PHP SHALL memisahkan logika (koneksi DB, query, validasi) dari presentasi (HTML output); logika dikerjakan di bagian atas file sebelum blok HTML dimulai.
3. EVERY operasi database yang memodifikasi data (INSERT, UPDATE, DELETE) SHALL menggunakan `require_once` ke `config/database.php` untuk mendapatkan objek PDO dan melakukan operasi dalam satu blok try-catch.
4. EVERY operasi AJAX di sisi klien SHALL menggunakan `fetch()` API bawaan browser; penggunaan library XMLHttpRequest eksternal dilarang.
5. EVERY event listener JavaScript SHALL ditambahkan menggunakan `addEventListener()`; penggunaan atribut inline event handler seperti `onclick=""` di HTML dilarang.
6. THE Sistem SHALL menyediakan file seed database (`database/seed.sql`) yang berisi data awal untuk tabel: kategori (minimal 5 kategori), produk (minimal 8 produk dengan foto placeholder), pengguna (minimal 1 admin dengan password ter-hash), dan status_toko (1 baris dengan status `aktif`).
7. THE Pretty_Printer SHALL menyediakan fungsi-fungsi utilitas PHP dalam file `config/helpers.php` untuk: format mata uang Rupiah, format tanggal Indonesia, generate `no_pesanan`, dan sanitasi output HTML.
