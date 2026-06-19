<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo        = get_pdo();
$csrf_token = generate_csrf();

$stmt = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()");
$pesanan_hari_ini = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status = 'diproses'");
$pesanan_diproses = (int) $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT COALESCE(SUM(jumlah_lunas), 0)
     FROM lunas
     WHERE MONTH(dicatat_pada) = MONTH(CURDATE())
       AND YEAR(dicatat_pada)  = YEAR(CURDATE())"
);
$pemasukan_bulan = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM stok_bahan WHERE stok_saat_ini < stok_minimum");
$stok_kritis = (int) $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT id_pesanan, no_pesanan, nama_pembeli, total_harga,
            status, metode_pengambilan, created_at
     FROM pesanan
     ORDER BY created_at DESC
     LIMIT 5"
);
$pesanan_terbaru = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT status, COUNT(*) AS jumlah
     FROM pesanan
     WHERE MONTH(created_at) = MONTH(CURDATE())
       AND YEAR(created_at)  = YEAR(CURDATE())
     GROUP BY status"
);
$rows_status = $stmt->fetchAll();

$label_map = [
    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
    'diproses'            => 'Diproses',
    'selesai'             => 'Selesai',
    'dibatalkan'          => 'Dibatalkan',
];

// Inisialisasi dengan 0 agar semua label selalu tampil di chart
$distribusi_data = [
    'Menunggu Konfirmasi' => 0,
    'Diproses'            => 0,
    'Selesai'             => 0,
    'Dibatalkan'          => 0,
];

foreach ($rows_status as $row) {
    $key = $label_map[$row['status']] ?? null;
    if ($key !== null) {
        $distribusi_data[$key] = (int) $row['jumlah'];
    }
}

$stmt = $pdo->query(
    "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual
     FROM detail_pesanan dp
     JOIN produk p ON p.id_produk = dp.id_produk
     GROUP BY dp.id_produk
     ORDER BY total_terjual DESC
     LIMIT 3"
);
$produk_terlaris = $stmt->fetchAll();

$stmt = $pdo->query(
    "SELECT keterangan, jumlah, tanggal
     FROM pengeluaran
     ORDER BY created_at DESC
     LIMIT 3"
);
$pengeluaran_terakhir = $stmt->fetchAll();

$stmt       = $pdo->query("SELECT id, status FROM status_toko LIMIT 1");
$status_toko = $stmt->fetch();

// Fallback jika tabel kosong
if (!$status_toko) {
    $status_toko = ['id' => 0, 'status' => 'nonaktif'];
}

$page_title  = 'Dashboard';
$active_page = 'dashboard';
$css_extra   = '/assets/css/admin.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php require_once __DIR__ . '/../components/head.php'; ?>
<body>
<div class="admin-layout">

    <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h1 class="page-header__title">Dashboard</h1>
                    <p class="page-header__subtitle">Selamat datang kembali, Admin WanFlorist!</p>
                </div>

                <div class="admin-card admin-card--compact admin-card--row-wrap">
                    <span class="text-sm text-strong">Status Toko:</span>
                    <label class="toggle-switch" for="ownerToggle">
                        <input
                            type="checkbox"
                            id="ownerToggle"
                            <?= $status_toko['status'] === 'aktif' ? 'checked' : '' ?>
                        >
                        <span class="toggle-slider"></span>
                    </label>
                    <label for="ownerToggle" class="text-sm text-muted">
                        Status Toko:
                        <span id="ownerStatusText"><?= e($status_toko['status'] === 'aktif' ? 'Aktif' : 'Nonaktif') ?></span>
                    </label>
                    <input type="hidden" id="csrf_token_endpoint" value="<?= e($csrf_token) ?>">
                </div>
            </div>

            <div class="stat-cards">

                <div class="stat-card stat-card--info">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value"><?= e((string) $pesanan_hari_ini) ?></div>
                            <div class="stat-card__label">Pesanan Hari Ini</div>
                        </div>

                        <div class="stat-card__icon">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card__change">Hari ini</div>
                </div>

                <div class="stat-card stat-card--warning">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value"><?= e((string) $pesanan_diproses) ?></div>
                            <div class="stat-card__label">Pesanan Diproses</div>
                        </div>
                        <div class="stat-card__icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                    </div>
                    <div class="stat-card__change">Perlu perhatian</div>
                </div>

                <div class="stat-card stat-card--success stat-card--large-value">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value"><?= e(format_rupiah($pemasukan_bulan)) ?></div>
                            <div class="stat-card__label">Pemasukan Bulan Ini</div>
                        </div>
                        <div class="stat-card__icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="stat-card__change">Bulan berjalan</div>
                </div>

                <div class="stat-card stat-card--danger">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value"><?= e((string) $stok_kritis) ?></div>
                            <div class="stat-card__label">Stok Bahan Kritis</div>
                        </div>
                        <div class="stat-card__icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="stat-card__change">Di bawah minimum</div>
                </div>

            </div>

            <div class="grid grid-2 gap-3 mb-6">

                <div class="admin-card admin-card--no-margin">
                    <div class="admin-card__header">
                        <h2 class="admin-card__title">Pesanan Terbaru</h2>
                        <a href="/admin/pesanan.php" class="link-underline text-sm">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pesanan_terbaru)): ?>
                                    <tr class="table-empty">
                                        <td colspan="5">
                                            Belum ada pesanan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pesanan_terbaru as $p): ?>
                                        <?php
                                        $badge_class = match ($p['status']) {
                                            'menunggu_konfirmasi' => 'badge-menunggu',
                                            'diproses'            => 'badge-diproses',
                                            'selesai'             => 'badge-selesai',
                                            'dibatalkan'          => 'badge-dibatalkan',
                                            default               => 'badge-menunggu',
                                        };
                                        $label_status = $label_map[$p['status']] ?? e($p['status']);

                                        $tgl_obj = new DateTime($p['created_at']);
                                        $tgl_fmt = $tgl_obj->format('d/m/Y H:i');
                                        ?>
                                        <tr>
                                            <td><?= e($p['no_pesanan']) ?></td>
                                            <td><?= e($p['nama_pembeli']) ?></td>
                                            <td><?= e(format_rupiah((int) $p['total_harga'])) ?></td>
                                            <td>
                                                <span class="badge <?= e($badge_class) ?>">
                                                    <?= e($label_status) ?>
                                                </span>
                                            </td>
                                            <td><?= e($tgl_fmt) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-card admin-card--no-margin">
                    <div class="admin-card__header">
                        <h2 class="admin-card__title">Pesanan per Status (Bulan Ini)</h2>
                    </div>
                    <div class="admin-card__body admin-card__body--centered">
                        <?php echo generate_svg_donat($distribusi_data); ?>
                    </div>
                </div>

            </div>

            <div class="grid grid-2 gap-3 mb-6">

                <div class="admin-card admin-card--no-margin">
                    <div class="admin-card__header">
                        <h2 class="admin-card__title">Produk Terlaris</h2>
                    </div>
                    <div class="admin-card__body admin-card__body--no-padding">
                        <?php if (empty($produk_terlaris)): ?>
                            <div class="admin-empty">
                                <div class="admin-empty__title">Belum ada data penjualan</div>
                            </div>
                        <?php else: ?>
                            <ul class="list-reset">
                                <?php foreach ($produk_terlaris as $idx => $prod): ?>
                                    <li class="list-item-horizontal">
                                        <div class="stat-badge"><?= e((string) ($idx + 1)) ?></div>
                                        <div class="list-item-content">
                                            <div class="text-strong text-ellipsis">
                                                <?= e($prod['nama_produk']) ?>
                                            </div>
                                            <div class="text-muted text-xs mt-1">
                                                Terjual: <?= e((string) $prod['total_terjual']) ?> item
                                            </div>
                                        </div>
                                        <div class="badge badge-selesai badge-pill">#<?= e((string) ($idx + 1)) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="admin-card admin-card--no-margin">
                    <div class="admin-card__header">
                        <h2 class="admin-card__title">Pengeluaran Terakhir</h2>
                        <a href="/admin/pengeluaran.php" class="link-underline text-sm">Detail</a>
                    </div>
                    <div class="admin-card__body admin-card__body--no-padding">
                        <?php if (empty($pengeluaran_terakhir)): ?>
                            <div class="admin-empty">
                                <div class="admin-empty__icon">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="admin-empty__title">Belum ada pengeluaran</div>
                            </div>
                        <?php else: ?>
                            <ul class="list-reset">
                                <?php foreach ($pengeluaran_terakhir as $pen): ?>
                                    <li class="list-item-space-between">
                                        <div class="list-item-content">
                                            <div class="text-strong text-ellipsis">
                                                <?= e($pen['keterangan']) ?>
                                            </div>
                                            <div class="text-muted text-xs mt-1">
                                                <?= e($pen['tanggal']) ?>
                                            </div>
                                        </div>
                                        <div class="text-danger text-strong text-nowrap">
                                            - <?= e(format_rupiah((int) $pen['jumlah'])) ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>

</div>

<script src="/assets/js/toggle-status.js"></script>
</body>
</html>
