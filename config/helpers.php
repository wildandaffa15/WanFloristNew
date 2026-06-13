<?php

/**
 * config/helpers.php
 * 
 * Fungsi-fungsi utilitas (helper) untuk WanFlorist.
 * File ini di-include oleh halaman yang membutuhkan pemformatan atau operasi bersama.
 */

/**
 * Format angka integer menjadi format mata uang Rupiah Indonesia.
 *
 * Contoh:
 *   format_rupiah(0)       → "Rp 0"
 *   format_rupiah(150000)  → "Rp 150.000"
 *   format_rupiah(1500000) → "Rp 1.500.000"
 *
 * @param int $angka Nilai angka yang akan diformat (non-negatif).
 * @return string String berformat "Rp X.XXX" atau "Rp 0" untuk nilai nol.
 *
 * Requirements: 16.5, 17.7
 */
function format_rupiah(int $angka): string
{
    if ($angka === 0) {
        return 'Rp 0';
    }

    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format objek DateTime menjadi string tanggal dalam Bahasa Indonesia.
 *
 * Contoh:
 *   format_tanggal_id(new DateTime('2025-01-15')) → "Rabu, 15 Januari 2025"
 *
 * Nama hari menggunakan indeks ISO-8601 dari date('N'): 1=Senin … 7=Minggu.
 * Nama bulan menggunakan indeks dari date('n'): 1=Januari … 12=Desember.
 *
 * @param DateTime $tanggal Objek tanggal yang akan diformat.
 * @return string String berformat "Hari, D Bulan YYYY".
 *
 * Requirements: 16.6, 17.7
 */
function format_tanggal_id(DateTime $tanggal): string
{
    static $nama_hari = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
        5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];
    static $nama_bulan = [
        1  => 'Januari',  2  => 'Februari', 3  => 'Maret',    4  => 'April',
        5  => 'Mei',      6  => 'Juni',     7  => 'Juli',      8  => 'Agustus',
        9  => 'September',10 => 'Oktober',  11 => 'November',  12 => 'Desember',
    ];

    $hari  = (int) $tanggal->format('N');
    $tgl   = (int) $tanggal->format('j');
    $bulan = (int) $tanggal->format('n');
    $tahun = $tanggal->format('Y');

    return $nama_hari[$hari] . ', ' . $tgl . ' ' . $nama_bulan[$bulan] . ' ' . $tahun;
}

/**
 * Generate nomor pesanan unik berformat WF-YYYYMMDD-XXXX.
 *
 * Algoritma:
 *   1. Hitung jumlah pesanan yang dibuat hari ini.
 *   2. Nomor urut = count + 1.
 *   3. Return sprintf("WF-%s-%04d", date('Ymd'), $urut).
 *
 * Harus dieksekusi dalam transaksi yang sama dengan INSERT pesanan
 * untuk menghindari race condition.
 *
 * @param PDO $pdo Koneksi PDO aktif.
 * @return string Nomor pesanan, contoh: "WF-20250115-0001".
 *
 * Requirements: 5.5, 17.7
 */
function generate_no_pesanan(PDO $pdo): string
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $count = (int) $stmt->fetchColumn();
    $urut  = $count + 1;
    return sprintf('WF-%s-%04d', date('Ymd'), $urut);
}

/**
 * Sanitasi output HTML untuk mencegah XSS.
 *
 * Wajib digunakan untuk semua data dari input pengguna atau database
 * yang ditampilkan di template HTML.
 *
 * @param string $str String yang akan di-escape.
 * @return string String yang sudah di-escape.
 *
 * Requirements: 15.2, 17.7
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Generate CSRF token baru dan simpan ke session.
 *
 * @return string Token CSRF 64-karakter hex.
 *
 * Requirements: 15.3, 5.10, 12.8
 */
function generate_csrf(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

/**
 * Validasi CSRF token dari request POST.
 *
 * Menggunakan hash_equals() untuk menghindari timing attack.
 *
 * @param string $token Token dari POST request.
 * @return bool true jika token valid, false jika tidak.
 *
 * Requirements: 15.3, 5.10, 12.8
 */
function validate_csrf(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $stored = $_SESSION['csrf_token'] ?? '';
    if ($stored === '' || $token === '') {
        return false;
    }
    return hash_equals($stored, $token);
}

/**
 * Validasi input form pemesanan.
 *
 * @param array $input Array input dengan kunci:
 *   - nama_pemesan (string)
 *   - no_whatsapp (string)
 *   - alamat (string)
 *   - tanggal_kirim (string, format Y-m-d)
 *   - produk (array, minimal satu item dengan jumlah >= 1)
 * @return array Array error per field; array kosong berarti semua valid.
 *
 * Requirements: 5.2, 5.3, 5.4
 */
function validasi_form_pemesanan(array $input): array
{
    $errors = [];

    // Validasi nama pemesan
    $nama = trim($input['nama_pemesan'] ?? '');
    if ($nama === '') {
        $errors['nama_pemesan'] = 'Nama pemesan tidak boleh kosong.';
    } elseif (mb_strlen($nama) > 100) {
        $errors['nama_pemesan'] = 'Nama pemesan maksimal 100 karakter.';
    }

    // Validasi nomor WhatsApp (8-15 digit angka)
    $wa = trim($input['no_whatsapp'] ?? '');
    if ($wa === '') {
        $errors['no_whatsapp'] = 'Nomor WhatsApp tidak boleh kosong.';
    } elseif (!preg_match('/^\d{8,15}$/', $wa)) {
        $errors['no_whatsapp'] = 'Nomor WhatsApp harus berupa 8–15 digit angka.';
    }

    // Validasi alamat
    $alamat = trim($input['alamat'] ?? '');
    if ($alamat === '') {
        $errors['alamat'] = 'Alamat tidak boleh kosong.';
    } elseif (mb_strlen($alamat) > 500) {
        $errors['alamat'] = 'Alamat maksimal 500 karakter.';
    }

    // Validasi tanggal kirim (tidak boleh masa lalu)
    $tgl_kirim = trim($input['tanggal_kirim'] ?? '');
    if ($tgl_kirim === '') {
        $errors['tanggal_kirim'] = 'Tanggal pengiriman tidak boleh kosong.';
    } else {
        $tgl_dt = DateTime::createFromFormat('Y-m-d', $tgl_kirim);
        $today  = new DateTime('today');
        if (!$tgl_dt || $tgl_dt->format('Y-m-d') !== $tgl_kirim) {
            $errors['tanggal_kirim'] = 'Format tanggal tidak valid.';
        } elseif ($tgl_dt < $today) {
            $errors['tanggal_kirim'] = 'Tanggal pengiriman tidak boleh di masa lalu.';
        }
    }

    // Validasi minimal satu produk dengan jumlah >= 1
    $produk = $input['produk'] ?? [];
    if (!is_array($produk) || empty($produk)) {
        $errors['produk'] = 'Minimal satu produk harus dipilih.';
    } else {
        $ada_produk_valid = false;
        foreach ($produk as $item) {
            $jumlah = (int) ($item['jumlah'] ?? 0);
            if ($jumlah >= 1) {
                $ada_produk_valid = true;
                break;
            }
        }
        if (!$ada_produk_valid) {
            $errors['produk'] = 'Jumlah produk minimal 1.';
        }
    }

    return $errors;
}

/**
 * Validasi apakah total pembayaran transfer sudah lunas.
 *
 * @param int $total  Total harga pesanan.
 * @param int $dp     Jumlah DP yang sudah dibayar.
 * @param int $lunas  Jumlah pelunasan yang akan dibayar.
 * @return bool true jika dp + lunas >= total.
 *
 * Requirements: 12.6
 */
function validasi_pelunasan_transfer(int $total, int $dp, int $lunas): bool
{
    return ($dp + $lunas) >= $total;
}

/**
 * Hitung laba bersih dari array pemasukan dan pengeluaran.
 *
 * @param array $pemasukan   Array nilai pemasukan (numeric).
 * @param array $pengeluaran Array nilai pengeluaran (numeric).
 * @return int|float Laba bersih (pemasukan - pengeluaran).
 *
 * Requirements: 14.5
 */
function hitung_laba_bersih(array $pemasukan, array $pengeluaran): int|float
{
    return array_sum($pemasukan) - array_sum($pengeluaran);
}
