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

/** Nama file foto produk default bila tidak ada atau masih memakai placeholder lama. */
const PRODUK_FOTO_DEFAULT = 'buket_mawar_pink.webp';

/**
 * Periksa apakah nilai foto dari database masih placeholder / kosong.
 */
function produk_foto_is_legacy(?string $foto): bool
{
    $foto = trim((string) $foto);

    return $foto === '' || in_array($foto, ['placeholder.jpg', 'placeholder.svg', 'placeholder.webp'], true);
}

/**
 * Resolve path foto produk untuk ditampilkan di HTML.
 *
 * @param string|null $foto      Nama file dari kolom produk.foto
 * @param string      $urlPrefix Prefix URL relatif, mis. '../' atau '/'
 */
function produk_foto_src(?string $foto, string $urlPrefix = ''): string
{
    $filename = produk_foto_is_legacy($foto) ? PRODUK_FOTO_DEFAULT : trim((string) $foto);
    $absPath  = dirname(__DIR__) . '/assets/img/produk/' . $filename;

    if (!is_file($absPath)) {
        $filename = PRODUK_FOTO_DEFAULT;
    }

    return $urlPrefix . 'assets/img/produk/' . $filename;
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
 *   - nama_pembeli (string)
 *   - no_hp (string)
 *   - tanggal_ambil (string, format Y-m-d)
 *   - produk (array, minimal satu item dengan jumlah >= 1)
 * @return array Array error per field; array kosong berarti semua valid.
 *
 * Requirements: 5.2, 5.3, 5.4
 */
function validasi_form_pemesanan(array $input): array
{
    $errors = [];

    $nama = trim($input['nama_pembeli'] ?? '');
    if ($nama === '') {
        $errors['nama_pembeli'] = 'Nama pembeli tidak boleh kosong.';
    } elseif (mb_strlen($nama) > 100) {
        $errors['nama_pembeli'] = 'Nama pembeli maksimal 100 karakter.';
    }

    $hp = trim($input['no_hp'] ?? '');
    if ($hp === '') {
        $errors['no_hp'] = 'Nomor HP tidak boleh kosong.';
    } elseif (!preg_match('/^\d{8,15}$/', $hp)) {
        $errors['no_hp'] = 'Nomor HP harus berupa 8–15 digit angka.';
    }

    $tgl_ambil = trim($input['tanggal_ambil'] ?? '');
    if ($tgl_ambil === '') {
        $errors['tanggal_ambil'] = 'Tanggal pengambilan tidak boleh kosong.';
    } else {
        $tgl_dt = DateTime::createFromFormat('Y-m-d', $tgl_ambil);
        $today  = new DateTime('today');
        if (!$tgl_dt || $tgl_dt->format('Y-m-d') !== $tgl_ambil) {
            $errors['tanggal_ambil'] = 'Format tanggal tidak valid.';
        } elseif ($tgl_dt < $today) {
            $errors['tanggal_ambil'] = 'Tanggal pengambilan tidak boleh di masa lalu.';
        }
    }

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

/**
 * Generate grafik donat SVG distribusi pesanan per status.
 *
 * Setiap segmen dihitung sebagai arc path SVG berdasarkan proporsi
 * nilai terhadap total. Lubang donat dibuat dengan lingkaran putih
 * di tengah berradius 55% dari radius donat.
 *
 * Warna per label status:
 *   - Selesai              → #16A34A (hijau)
 *   - Diproses             → #D97706 (kuning)
 *   - Menunggu Konfirmasi  → #2563EB (biru)
 *   - Dibatalkan           → #DC2626 (merah)
 *
 * Edge cases:
 *   - total = 0    → SVG teks "Tidak ada data"
 *   - satu segmen  → dua arc (near-complete + 1° sisa) untuk menghindari
 *                    degenerate path (titik awal = titik akhir)
 *
 * @param array $data   Asosiatif [label => count], contoh:
 *                      ['Menunggu Konfirmasi' => 15, 'Diproses' => 30,
 *                       'Selesai' => 45, 'Dibatalkan' => 10]
 * @param int   $radius Radius lingkaran luar donat (default 80).
 * @return string HTML string berupa <div class="donut-chart-wrap">…</div>
 *
 * Requirements: 8.5, 14.8
 */
function generate_svg_donat(array $data, int $radius = 80): string
{
    $warna = [
        'Selesai'             => '#16A34A',
        'Diproses'            => '#D97706',
        'Menunggu Konfirmasi' => '#2563EB',
        'Dibatalkan'          => '#DC2626',
    ];

    // Warna fallback untuk label di luar daftar di atas
    $warna_fallback = ['#7C3AED', '#0891B2', '#D97706', '#BE185D', '#065F46'];

    $total = array_sum($data);

    // Margin supaya arc tidak terpotong di tepi viewport
    $margin = 20;
    $cx     = $radius + $margin;
    $cy     = $radius + $margin;
    $size   = ($radius + $margin) * 2;

    if ($total === 0) {
        $svg = '<svg viewBox="0 0 ' . $size . ' ' . $size . '" '
             . 'width="' . $size . '" height="' . $size . '" '
             . 'xmlns="http://www.w3.org/2000/svg">'
             . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" '
             . 'fill="#E5E7EB"/>'
             . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . ($radius * 0.55) . '" '
             . 'fill="#ffffff"/>'
             . '<text x="' . $cx . '" y="' . ($cy + 5) . '" '
             . 'text-anchor="middle" font-family="Inter,sans-serif" '
             . 'font-size="12" fill="#6B7280">Tidak ada data</text>'
             . '</svg>';

        return '<div class="donut-chart-wrap">' . $svg . '</div>';
    }

    $segmen_count = count(array_filter($data, fn($v) => $v > 0));
    $paths        = '';
    $current_angle = -90.0; // mulai dari atas (jam 12)
    $fallback_idx  = 0;

    foreach ($data as $label => $nilai) {
        if ($nilai <= 0) {
            continue;
        }

        $color = $warna[$label] ?? $warna_fallback[$fallback_idx++ % count($warna_fallback)];

        if ($segmen_count === 1) {
            // Satu segmen 100%: gambar sebagai dua arc agar titik awal ≠ titik akhir
            $angle1 = 359.0;
            $angle2 = 1.0;

            $x1 = $cx + $radius * cos(deg2rad($current_angle));
            $y1 = $cy + $radius * sin(deg2rad($current_angle));
            $x2 = $cx + $radius * cos(deg2rad($current_angle + $angle1));
            $y2 = $cy + $radius * sin(deg2rad($current_angle + $angle1));
            $paths .= '<path d="M ' . $cx . ' ' . $cy
                    . ' L ' . round($x1, 4) . ' ' . round($y1, 4)
                    . ' A ' . $radius . ' ' . $radius . ' 0 1 1 '
                    . round($x2, 4) . ' ' . round($y2, 4)
                    . ' Z" fill="' . $color . '"/>';

            $x3 = $cx + $radius * cos(deg2rad($current_angle + $angle1 + $angle2));
            $y3 = $cy + $radius * sin(deg2rad($current_angle + $angle1 + $angle2));
            $paths .= '<path d="M ' . $cx . ' ' . $cy
                    . ' L ' . round($x2, 4) . ' ' . round($y2, 4)
                    . ' A ' . $radius . ' ' . $radius . ' 0 0 1 '
                    . round($x3, 4) . ' ' . round($y3, 4)
                    . ' Z" fill="' . $color . '"/>';
        } else {
            $angle = ($nilai / $total) * 360.0;

            $x1 = $cx + $radius * cos(deg2rad($current_angle));
            $y1 = $cy + $radius * sin(deg2rad($current_angle));
            $x2 = $cx + $radius * cos(deg2rad($current_angle + $angle));
            $y2 = $cy + $radius * sin(deg2rad($current_angle + $angle));

            $large_arc_flag = ($angle > 180) ? 1 : 0;

            $paths .= '<path d="M ' . $cx . ' ' . $cy
                    . ' L ' . round($x1, 4) . ' ' . round($y1, 4)
                    . ' A ' . $radius . ' ' . $radius . ' 0 ' . $large_arc_flag . ' 1 '
                    . round($x2, 4) . ' ' . round($y2, 4)
                    . ' Z" fill="' . $color . '"/>';

            $current_angle += $angle;
        }
    }

    $hole_r  = round($radius * 0.55);
    $paths  .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $hole_r . '" fill="#ffffff"/>';

    $svg = '<svg viewBox="0 0 ' . $size . ' ' . $size . '" '
         . 'width="' . $size . '" height="' . $size . '" '
         . 'xmlns="http://www.w3.org/2000/svg">'
         . $paths
         . '</svg>';

    $legend      = '<div class="donut-legend">';
    $fallback_idx = 0;

    foreach ($data as $label => $nilai) {
        if ($nilai <= 0) {
            continue;
        }
        $color   = $warna[$label] ?? $warna_fallback[$fallback_idx++ % count($warna_fallback)];
        $persen  = round(($nilai / $total) * 100, 1);
        $legend .= '<div class="donut-legend-item">'
                 . '<span class="donut-legend-swatch" style="background:' . $color . ';'
                 . 'display:inline-block;width:12px;height:12px;border-radius:2px;'
                 . 'margin-right:6px;vertical-align:middle;flex-shrink:0;"></span>'
                 . '<span style="vertical-align:middle;">'
                 . htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                 . '</span>'
                 . '<span class="donut-legend-pct" style="margin-left:auto;font-weight:600;">'
                 . $persen . '%</span>'
                 . '</div>';
    }
    $legend .= '</div>';

    return '<div class="donut-chart-wrap">' . $svg . $legend . '</div>';
}

/**
 * Generate grafik batang SVG perbandingan pemasukan vs pengeluaran per bulan.
 *
 * @param array $pemasukan   Asosiatif [label_bulan => nilai], e.g. ['Jan 2025' => 500000]
 * @param array $pengeluaran Asosiatif [label_bulan => nilai], keys same as $pemasukan
 * @return string HTML string <div class="bar-chart-wrap">...</div> dengan SVG di dalamnya
 *
 * Requirements: 14.3, 14.8
 */
function generate_svg_bar(array $pemasukan, array $pengeluaran): string
{
    $months = array_keys($pemasukan);
    $n      = count($months);

    if ($n === 0) {
        return '<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg">'
             . '<text x="200" y="100" text-anchor="middle" font-family="Inter,sans-serif" '
             . 'font-size="14" fill="#6B7280">Tidak ada data</text></svg>';
    }

    $bar_w     = 20;
    $bar_gap   = 4;
    $group_gap = 16;
    $pad_left  = 70;
    $pad_right = 20;
    $pad_top   = 20;
    $pad_bot   = 50;
    $chart_h   = 180;

    $group_w   = $bar_w * 2 + $bar_gap;
    $total_w   = $pad_left + $n * $group_w + ($n - 1) * $group_gap + $pad_right;
    $svg_h     = $pad_top + $chart_h + $pad_bot + 20;

    $all_vals = array_merge(array_values($pemasukan), array_values($pengeluaran));
    $max_val  = max(array_merge([1], array_map('intval', $all_vals)));

    $rects  = '';
    $labels = '';
    $grids  = '';

    for ($i = 1; $i <= 4; $i++) {
        $y_grid   = $pad_top + $chart_h - round(($i / 4) * $chart_h);
        $val_grid = (int)(($i / 4) * $max_val);
        $grids   .= '<line x1="' . $pad_left . '" y1="' . $y_grid . '" '
                  . 'x2="' . ($total_w - $pad_right) . '" y2="' . $y_grid . '" '
                  . 'stroke="#E5E7EB" stroke-width="1"/>';
        $lbl = number_format($val_grid / 1000, 0, ',', '.') . 'K';
        $grids .= '<text x="' . ($pad_left - 5) . '" y="' . ($y_grid + 4) . '" '
                . 'text-anchor="end" font-family="Inter,sans-serif" font-size="10" fill="#9CA3AF">'
                . htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') . '</text>';
    }

    foreach ($months as $idx => $month) {
        $x_group = $pad_left + $idx * ($group_w + $group_gap);

        $p_val = (int)($pemasukan[$month] ?? 0);
        $p_h   = $p_val > 0 ? max(2, (int)round(($p_val / $max_val) * $chart_h)) : 0;
        if ($p_h > 0) {
            $rects .= '<rect x="' . $x_group . '" y="' . ($pad_top + $chart_h - $p_h) . '" '
                    . 'width="' . $bar_w . '" height="' . $p_h . '" fill="#16A34A" rx="3" '
                    . 'aria-label="Pemasukan ' . htmlspecialchars($month, ENT_QUOTES, 'UTF-8')
                    . ': Rp ' . number_format($p_val, 0, ',', '.') . '"/>';
        }

        $e_val = (int)($pengeluaran[$month] ?? 0);
        $e_h   = $e_val > 0 ? max(2, (int)round(($e_val / $max_val) * $chart_h)) : 0;
        $e_x   = $x_group + $bar_w + $bar_gap;
        if ($e_h > 0) {
            $rects .= '<rect x="' . $e_x . '" y="' . ($pad_top + $chart_h - $e_h) . '" '
                    . 'width="' . $bar_w . '" height="' . $e_h . '" fill="#DC2626" rx="3" '
                    . 'aria-label="Pengeluaran ' . htmlspecialchars($month, ENT_QUOTES, 'UTF-8')
                    . ': Rp ' . number_format($e_val, 0, ',', '.') . '"/>';
        }

        $lx      = $x_group + $group_w / 2;
        $labels .= '<text x="' . round($lx) . '" y="' . ($pad_top + $chart_h + 16) . '" '
                 . 'text-anchor="middle" font-family="Inter,sans-serif" font-size="10" fill="#6B7280">'
                 . htmlspecialchars($month, ENT_QUOTES, 'UTF-8') . '</text>';
    }

    $baseline  = '<line x1="' . $pad_left . '" y1="' . ($pad_top + $chart_h) . '" '
               . 'x2="' . ($total_w - $pad_right) . '" y2="' . ($pad_top + $chart_h) . '" '
               . 'stroke="#D1D5DB" stroke-width="1.5"/>';

    $leg_y = $pad_top + $chart_h + 36;
    $legend = '<rect x="' . $pad_left . '" y="' . $leg_y . '" width="12" height="12" fill="#16A34A" rx="2"/>'
            . '<text x="' . ($pad_left + 16) . '" y="' . ($leg_y + 10) . '" font-family="Inter,sans-serif" font-size="11" fill="#374151">Pemasukan</text>'
            . '<rect x="' . ($pad_left + 100) . '" y="' . $leg_y . '" width="12" height="12" fill="#DC2626" rx="2"/>'
            . '<text x="' . ($pad_left + 116) . '" y="' . ($leg_y + 10) . '" font-family="Inter,sans-serif" font-size="11" fill="#374151">Pengeluaran</text>';

    return '<div class="bar-chart-wrap" style="overflow-x:auto;">'
         . '<svg viewBox="0 0 ' . $total_w . ' ' . $svg_h . '" width="' . $total_w . '" '
         . 'height="' . $svg_h . '" xmlns="http://www.w3.org/2000/svg" '
         . 'aria-label="Grafik pemasukan vs pengeluaran 6 bulan terakhir">'
         . $grids . $rects . $baseline . $labels . $legend
         . '</svg></div>';
}
