<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo        = get_pdo();
$csrf_token = generate_csrf();

// ─── Active tab ───────────────────────────────────────────────────────────────
$active_tab = in_array($_GET['tab'] ?? '', ['ringkasan', 'pesanan', 'produk', 'stok'], true)
    ? $_GET['tab'] : 'ringkasan';

// ─── Date range (defaults to current month) ──────────────────────────────────
$dari   = trim($_GET['dari']   ?? date('Y-m-01'));
$sampai = trim($_GET['sampai'] ?? date('Y-m-d'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari))   $dari   = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');
if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

// ─── Financial summary ────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(jumlah_lunas),0) FROM lunas
     WHERE DATE(dicatat_pada) BETWEEN :dari AND :sampai"
);
$stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
$total_pemasukan = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran
     WHERE tanggal BETWEEN :dari AND :sampai"
);
$stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
$total_pengeluaran = (int) $stmt->fetchColumn();

$laba_bersih = hitung_laba_bersih([$total_pemasukan], [$total_pengeluaran]);

// ─── 6-month bar chart data ───────────────────────────────────────────────────
$bar_pemasukan   = [];
$bar_pengeluaran = [];
for ($i = 5; $i >= 0; $i--) {
    $dt    = new DateTime("first day of -$i month");
    $label = $dt->format('M Y');
    $y     = $dt->format('Y');
    $m     = $dt->format('n');

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(jumlah_lunas),0) FROM lunas
         WHERE YEAR(dicatat_pada) = :y AND MONTH(dicatat_pada) = :m"
    );
    $stmt->execute([':y' => $y, ':m' => $m]);
    $bar_pemasukan[$label] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran
         WHERE YEAR(tanggal) = :y AND MONTH(tanggal) = :m"
    );
    $stmt->execute([':y' => $y, ':m' => $m]);
    $bar_pengeluaran[$label] = (int) $stmt->fetchColumn();
}

// ─── Order report ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM pesanan
     WHERE DATE(created_at) BETWEEN :dari AND :sampai"
);
$stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
$total_pesanan = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT status, COUNT(*) AS jumlah FROM pesanan
     WHERE DATE(created_at) BETWEEN :dari AND :sampai
     GROUP BY status"
);
$stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
$distribusi_status = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual
     FROM detail_pesanan dp
     JOIN produk p   ON p.id_produk   = dp.id_produk
     JOIN pesanan pes ON pes.id_pesanan = dp.id_pesanan
     WHERE DATE(pes.created_at) BETWEEN :dari AND :sampai
     GROUP BY dp.id_produk
     ORDER BY total_terjual DESC
     LIMIT 5"
);
$stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
$produk_terlaris = $stmt->fetchAll();

// ─── Product report ───────────────────────────────────────────────────────────
$stmt = $pdo->query(
    "SELECT p.*, k.nama_kategori, COALESCE(SUM(dp.jumlah), 0) AS total_terjual
     FROM produk p
     LEFT JOIN kategori k         ON k.id_kategori = p.id_kategori
     LEFT JOIN detail_pesanan dp  ON dp.id_produk   = p.id_produk
     GROUP BY p.id_produk
     ORDER BY total_terjual DESC"
);
$produk_list = $stmt->fetchAll();

$total_produk_aktif   = count(array_filter($produk_list, fn($r) => ($r['status'] ?? '') === 'tersedia'));
$total_produk_nonaktif = count(array_filter($produk_list, fn($r) => ($r['status'] ?? '') !== 'tersedia'));

// ─── Stock report ─────────────────────────────────────────────────────────────
$stmt      = $pdo->query("SELECT * FROM stok_bahan ORDER BY nama_bahan ASC");
$stok_list = $stmt->fetchAll();

$total_stok_kritis = count(array_filter(
    $stok_list,
    fn($r) => (int)($r['stok_saat_ini'] ?? 0) < (int)($r['stok_minimum'] ?? 0)
));

// ─── CSV export ───────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-' . $active_tab . '-' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    $out = fopen('php://output', 'w');

    if ($active_tab === 'ringkasan') {
        fputcsv($out, ['Periode', 'Total Pemasukan', 'Total Pengeluaran', 'Laba Bersih']);
        fputcsv($out, [
            $dari . ' s/d ' . $sampai,
            $total_pemasukan,
            $total_pengeluaran,
            $laba_bersih,
        ]);
        fputcsv($out, []);
        fputcsv($out, ['Bulan', 'Pemasukan', 'Pengeluaran']);
        foreach ($bar_pemasukan as $bulan => $val) {
            fputcsv($out, [$bulan, $val, $bar_pengeluaran[$bulan] ?? 0]);
        }
    } elseif ($active_tab === 'pesanan') {
        fputcsv($out, ['Total Pesanan', $total_pesanan]);
        fputcsv($out, []);
        fputcsv($out, ['Status', 'Jumlah']);
        foreach ($distribusi_status as $row) {
            fputcsv($out, [$row['status'], $row['jumlah']]);
        }
        fputcsv($out, []);
        fputcsv($out, ['Peringkat', 'Nama Produk', 'Total Terjual']);
        foreach ($produk_terlaris as $idx => $row) {
            fputcsv($out, [$idx + 1, $row['nama_produk'], $row['total_terjual']]);
        }
    } elseif ($active_tab === 'produk') {
        fputcsv($out, ['Nama Produk', 'Kategori', 'Harga', 'Status', 'Total Terjual']);
        foreach ($produk_list as $row) {
            fputcsv($out, [
                $row['nama_produk'],
                $row['nama_kategori'] ?? '-',
                $row['harga'],
                $row['status'],
                $row['total_terjual'],
            ]);
        }
    } elseif ($active_tab === 'stok') {
        fputcsv($out, ['Nama Bahan', 'Satuan', 'Stok Saat Ini', 'Stok Minimum', 'Status']);
        foreach ($stok_list as $row) {
            $status_stok = ((int)$row['stok_saat_ini'] < (int)$row['stok_minimum']) ? 'Stok Kritis' : 'Aman';
            fputcsv($out, [
                $row['nama_bahan'],
                $row['satuan'],
                $row['stok_saat_ini'],
                $row['stok_minimum'],
                $status_stok,
            ]);
        }
    }

    fclose($out);
    exit;
}

// ─── Page meta ────────────────────────────────────────────────────────────────
$page_title  = 'Laporan';
$active_page = 'laporan';
$css_extra   = '/assets/css/admin.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php require_once __DIR__ . '/../components/head.php'; ?>
<body>

<?php require_once __DIR__ . '/../components/sidebar.php'; ?>

<div class="admin-layout">
    <main class="admin-main">
        <div class="admin-content">

            <!-- ── Page header ──────────────────────────────────────────── -->
            <div class="page-header">
                <div>
                    <h1 class="page-header__title">📈 Laporan</h1>
                    <p class="page-header__subtitle">Ringkasan keuangan &amp; operasional toko</p>
                </div>
                <div class="page-header__actions">
                    <button id="btn-cetak" type="button" class="btn btn--outline" title="Cetak laporan ini">
                        🖨️ Cetak PDF
                    </button>
                    <a href="?tab=<?= e($active_tab) ?>&amp;dari=<?= e($dari) ?>&amp;sampai=<?= e($sampai) ?>&amp;export=csv"
                       class="btn btn--secondary">
                        ⬇️ Ekspor CSV
                    </a>
                </div>
            </div>

            <!-- ── Date filter form ─────────────────────────────────────── -->
            <div class="admin-card" style="margin-bottom:1.5rem;">
                <div class="admin-card__body" style="padding:1rem 1.5rem;">
                    <form method="GET" action="" style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                        <input type="hidden" name="tab" value="<?= e($active_tab) ?>">
                        <label for="dari" style="font-size:0.875rem;font-weight:600;color:#374151;">Dari:</label>
                        <input type="date" id="dari" name="dari" value="<?= e($dari) ?>"
                               style="padding:0.45rem 0.75rem;border:1.5px solid #D1D5DB;border-radius:8px;
                                      font-family:Inter,sans-serif;font-size:0.875rem;color:#374151;background:#fff;
                                      transition:border-color .15s;">

                        <label for="sampai" style="font-size:0.875rem;font-weight:600;color:#374151;">Sampai:</label>
                        <input type="date" id="sampai" name="sampai" value="<?= e($sampai) ?>"
                               style="padding:0.45rem 0.75rem;border:1.5px solid #D1D5DB;border-radius:8px;
                                      font-family:Inter,sans-serif;font-size:0.875rem;color:#374151;background:#fff;
                                      transition:border-color .15s;">

                        <button type="submit" class="btn btn--primary" style="padding:0.45rem 1.25rem;">
                            🔍 Filter
                        </button>
                        <a href="?tab=<?= e($active_tab) ?>" class="btn btn--outline" style="padding:0.45rem 1.25rem;">
                            ↺ Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- ── Tab navigation ──────────────────────────────────────── -->
            <div class="tab-nav" role="tablist" aria-label="Tab laporan">
                <button type="button" class="tab-btn<?= $active_tab === 'ringkasan' ? ' tab-btn--active' : '' ?>"
                        data-tab="ringkasan" role="tab"
                        aria-selected="<?= $active_tab === 'ringkasan' ? 'true' : 'false' ?>"
                        aria-controls="panel-ringkasan">
                    💰 Ringkasan Keuangan
                </button>
                <button type="button" class="tab-btn<?= $active_tab === 'pesanan' ? ' tab-btn--active' : '' ?>"
                        data-tab="pesanan" role="tab"
                        aria-selected="<?= $active_tab === 'pesanan' ? 'true' : 'false' ?>"
                        aria-controls="panel-pesanan">
                    📦 Laporan Pesanan
                </button>
                <button type="button" class="tab-btn<?= $active_tab === 'produk' ? ' tab-btn--active' : '' ?>"
                        data-tab="produk" role="tab"
                        aria-selected="<?= $active_tab === 'produk' ? 'true' : 'false' ?>"
                        aria-controls="panel-produk">
                    🌸 Laporan Produk
                </button>
                <button type="button" class="tab-btn<?= $active_tab === 'stok' ? ' tab-btn--active' : '' ?>"
                        data-tab="stok" role="tab"
                        aria-selected="<?= $active_tab === 'stok' ? 'true' : 'false' ?>"
                        aria-controls="panel-stok">
                    🌿 Laporan Stok
                </button>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 TAB 1 — RINGKASAN KEUANGAN
                 ═══════════════════════════════════════════════════════════ -->
            <div id="panel-ringkasan"
                 class="tab-panel<?= $active_tab === 'ringkasan' ? ' tab-panel--active' : '' ?>"
                 role="tabpanel" aria-labelledby="">

                <!-- Bar chart 6 bulan -->
                <div class="admin-card" style="margin-bottom:1.5rem;">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Pemasukan vs Pengeluaran — 6 Bulan Terakhir</span>
                    </div>
                    <div class="admin-card__body" style="overflow-x:auto;">
                        <?php echo generate_svg_bar($bar_pemasukan, $bar_pengeluaran); ?>
                    </div>
                </div>

                <!-- 3 summary cards -->
                <div class="stat-cards" style="grid-template-columns:repeat(3,1fr);">
                    <!-- Pemasukan -->
                    <div class="stat-card stat-card--success">
                        <div class="stat-card__header">
                            <span class="stat-card__icon">💵</span>
                        </div>
                        <div class="stat-card__value" style="font-size:1.35rem;">
                            <?= e(format_rupiah($total_pemasukan)) ?>
                        </div>
                        <div class="stat-card__label">Total Pemasukan</div>
                        <div class="stat-card__change"><?= e($dari) ?> s/d <?= e($sampai) ?></div>
                    </div>

                    <!-- Pengeluaran -->
                    <div class="stat-card stat-card--danger">
                        <div class="stat-card__header">
                            <span class="stat-card__icon">💸</span>
                        </div>
                        <div class="stat-card__value" style="font-size:1.35rem;">
                            <?= e(format_rupiah($total_pengeluaran)) ?>
                        </div>
                        <div class="stat-card__label">Total Pengeluaran</div>
                        <div class="stat-card__change"><?= e($dari) ?> s/d <?= e($sampai) ?></div>
                    </div>

                    <!-- Laba bersih -->
                    <div class="stat-card <?= $laba_bersih >= 0 ? 'stat-card--success' : 'stat-card--danger' ?>">
                        <div class="stat-card__header">
                            <span class="stat-card__icon"><?= $laba_bersih >= 0 ? '📈' : '📉' ?></span>
                        </div>
                        <div class="stat-card__value" style="font-size:1.35rem;color:<?= $laba_bersih >= 0 ? '#16A34A' : '#DC2626' ?>;">
                            <?= e(format_rupiah((int) abs($laba_bersih))) ?>
                            <?php if ($laba_bersih < 0): ?>
                                <span style="font-size:0.85rem;font-weight:500;">(rugi)</span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-card__label">Laba Bersih</div>
                        <div class="stat-card__change"><?= e($dari) ?> s/d <?= e($sampai) ?></div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 TAB 2 — LAPORAN PESANAN
                 ═══════════════════════════════════════════════════════════ -->
            <div id="panel-pesanan"
                 class="tab-panel<?= $active_tab === 'pesanan' ? ' tab-panel--active' : '' ?>"
                 role="tabpanel">

                <!-- Stat: total pesanan -->
                <div class="stat-cards" style="grid-template-columns:repeat(1,1fr);margin-bottom:1.5rem;">
                    <div class="stat-card stat-card--info">
                        <div class="stat-card__header">
                            <span class="stat-card__icon">📦</span>
                        </div>
                        <div class="stat-card__value"><?= e((string)$total_pesanan) ?></div>
                        <div class="stat-card__label">Total Pesanan pada periode ini</div>
                        <div class="stat-card__change"><?= e($dari) ?> s/d <?= e($sampai) ?></div>
                    </div>
                </div>

                <!-- Distribusi status -->
                <div class="admin-card" style="margin-bottom:1.5rem;">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Distribusi Pesanan per Status</span>
                    </div>
                    <div class="admin-card__body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Jumlah</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($distribusi_status)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;color:#9CA3AF;padding:2rem;">
                                                Tidak ada data pesanan pada periode ini.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($distribusi_status as $row): ?>
                                            <?php
                                                $persen = $total_pesanan > 0
                                                    ? round(($row['jumlah'] / $total_pesanan) * 100, 1)
                                                    : 0;
                                                $badge_map = [
                                                    'menunggu_konfirmasi' => 'badge-menunggu',
                                                    'diproses'            => 'badge-diproses',
                                                    'selesai'             => 'badge-selesai',
                                                    'dibatalkan'          => 'badge-dibatalkan',
                                                ];
                                                $badge_class = $badge_map[$row['status']] ?? 'badge-menunggu';
                                                $label_map = [
                                                    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                                                    'diproses'            => 'Diproses',
                                                    'selesai'             => 'Selesai',
                                                    'dibatalkan'          => 'Dibatalkan',
                                                ];
                                                $label = $label_map[$row['status']] ?? ucfirst($row['status']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= e($badge_class) ?>">
                                                        <?= e($label) ?>
                                                    </span>
                                                </td>
                                                <td><?= e((string)$row['jumlah']) ?></td>
                                                <td><?= e((string)$persen) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top 5 produk terlaris -->
                <div class="admin-card">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Top 5 Produk Terlaris</span>
                    </div>
                    <div class="admin-card__body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Peringkat</th>
                                        <th>Nama Produk</th>
                                        <th>Total Terjual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($produk_terlaris)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;color:#9CA3AF;padding:2rem;">
                                                Tidak ada data produk terjual pada periode ini.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($produk_terlaris as $idx => $row): ?>
                                            <tr>
                                                <td>
                                                    <span style="font-weight:700;color:#6B21A8;">
                                                        #<?= e((string)($idx + 1)) ?>
                                                    </span>
                                                </td>
                                                <td><?= e($row['nama_produk']) ?></td>
                                                <td><?= e((string)$row['total_terjual']) ?> pcs</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 TAB 3 — LAPORAN PRODUK
                 ═══════════════════════════════════════════════════════════ -->
            <div id="panel-produk"
                 class="tab-panel<?= $active_tab === 'produk' ? ' tab-panel--active' : '' ?>"
                 role="tabpanel">

                <!-- Summary counts -->
                <div class="stat-cards" style="grid-template-columns:repeat(2,1fr);margin-bottom:1.5rem;">
                    <div class="stat-card stat-card--success">
                        <div class="stat-card__header"><span class="stat-card__icon">✅</span></div>
                        <div class="stat-card__value"><?= e((string)$total_produk_aktif) ?></div>
                        <div class="stat-card__label">Produk Aktif (Tersedia)</div>
                    </div>
                    <div class="stat-card stat-card--warning">
                        <div class="stat-card__header"><span class="stat-card__icon">⛔</span></div>
                        <div class="stat-card__value"><?= e((string)$total_produk_nonaktif) ?></div>
                        <div class="stat-card__label">Produk Nonaktif</div>
                    </div>
                </div>

                <!-- Products table -->
                <div class="admin-card">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Daftar Produk</span>
                    </div>
                    <div class="admin-card__body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Kategori</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th>Total Terjual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($produk_list)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center;color:#9CA3AF;padding:2rem;">
                                                Belum ada data produk.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($produk_list as $row): ?>
                                            <tr>
                                                <td style="font-weight:500;"><?= e($row['nama_produk']) ?></td>
                                                <td><?= e($row['nama_kategori'] ?? '-') ?></td>
                                                <td><?= e(format_rupiah((int)$row['harga'])) ?></td>
                                                <td>
                                                    <?php if (($row['status'] ?? '') === 'tersedia'): ?>
                                                        <span class="badge badge-aktif">Tersedia</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-nonaktif">Nonaktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= e((string)$row['total_terjual']) ?> pcs</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 TAB 4 — LAPORAN STOK
                 ═══════════════════════════════════════════════════════════ -->
            <div id="panel-stok"
                 class="tab-panel<?= $active_tab === 'stok' ? ' tab-panel--active' : '' ?>"
                 role="tabpanel">

                <!-- Summary: critical stock count -->
                <div class="stat-cards" style="grid-template-columns:repeat(1,1fr);margin-bottom:1.5rem;">
                    <div class="stat-card <?= $total_stok_kritis > 0 ? 'stat-card--danger' : 'stat-card--success' ?>">
                        <div class="stat-card__header">
                            <span class="stat-card__icon"><?= $total_stok_kritis > 0 ? '⚠️' : '✅' ?></span>
                        </div>
                        <div class="stat-card__value"><?= e((string)$total_stok_kritis) ?></div>
                        <div class="stat-card__label">Bahan dengan Stok Kritis</div>
                    </div>
                </div>

                <!-- Stock table -->
                <div class="admin-card">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Daftar Stok Bahan</span>
                    </div>
                    <div class="admin-card__body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Nama Bahan</th>
                                        <th>Satuan</th>
                                        <th>Stok Saat Ini</th>
                                        <th>Stok Minimum</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($stok_list)): ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center;color:#9CA3AF;padding:2rem;">
                                                Belum ada data stok bahan.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($stok_list as $row): ?>
                                            <?php $is_kritis = (int)$row['stok_saat_ini'] < (int)$row['stok_minimum']; ?>
                                            <tr class="<?= $is_kritis ? 'row-kritis' : '' ?>">
                                                <td style="font-weight:500;"><?= e($row['nama_bahan']) ?></td>
                                                <td><?= e($row['satuan']) ?></td>
                                                <td><?= e((string)$row['stok_saat_ini']) ?></td>
                                                <td><?= e((string)$row['stok_minimum']) ?></td>
                                                <td>
                                                    <?php if ($is_kritis): ?>
                                                        <span class="badge badge-kritis">Stok Kritis</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-aktif">Aman</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.admin-content -->
    </main><!-- /.admin-main -->
</div><!-- /.admin-layout -->

<!-- ── Print CSS ──────────────────────────────────────────────────────────── -->
<style>
@media print {
    .wf-sidebar        { display: none; }
    .wf-hamburger      { display: none; }
    .admin-main        { margin-left: 0; }
    .tab-panel         { display: block !important; }
    .page-header__actions { display: none; }
    .tab-nav           { display: none; }
}
</style>

<!-- ── Vanilla JS: tab switching + print button ──────────────────────────── -->
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        // ── Print button ──────────────────────────────────────────────────
        var btnCetak = document.getElementById('btn-cetak');
        if (btnCetak) {
            btnCetak.addEventListener('click', function () {
                window.print();
            });
        }

        // ── Tab switching ─────────────────────────────────────────────────
        var tabBtns   = document.querySelectorAll('.tab-btn[data-tab]');
        var tabPanels = document.querySelectorAll('.tab-panel');

        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-tab');

                // Update buttons
                tabBtns.forEach(function (b) {
                    b.classList.remove('tab-btn--active');
                    b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('tab-btn--active');
                btn.setAttribute('aria-selected', 'true');

                // Update panels
                tabPanels.forEach(function (panel) {
                    panel.classList.remove('tab-panel--active');
                });
                var activePanel = document.getElementById('panel-' + target);
                if (activePanel) {
                    activePanel.classList.add('tab-panel--active');
                }

                // Update URL (preserve dari/sampai, update tab param without reload)
                var url = new URL(window.location.href);
                url.searchParams.set('tab', target);
                window.history.replaceState(null, '', url.toString());
            });
        });

    });
}());
</script>

</body>
</html>
