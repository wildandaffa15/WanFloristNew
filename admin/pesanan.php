<?php
/**
 * admin/pesanan.php
 * Halaman manajemen pesanan — daftar, filter, cari, ekspor CSV, ubah status.
 *
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.7
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$q             = trim($_GET['q']      ?? '');
$filter_status = trim($_GET['status'] ?? '');
$page          = max(1, (int) ($_GET['page'] ?? 1));

$allowed_status = ['menunggu_konfirmasi', 'diproses', 'selesai', 'dibatalkan'];
if ($filter_status !== '' && !in_array($filter_status, $allowed_status, true)) {
    $filter_status = '';
}

$per_page = 20;
$offset   = ($page - 1) * $per_page;

$pdo = get_pdo();

$where  = [];
$params = [];

if ($q !== '') {
    $where[]        = '(no_pesanan LIKE :q OR nama_pembeli LIKE :q)';
    $params[':q']   = "%{$q}%";
}
if ($filter_status !== '') {
    $where[]           = 'status = :status';
    $params[':status'] = $filter_status;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pesanan-' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM agar Excel membaca karakter Indonesia dengan benar

    $out = fopen('php://output', 'w');
    fputcsv($out, ['No. Pesanan', 'Nama Pembeli', 'Total Harga', 'Metode Pengambilan', 'Status', 'Tanggal Pesan']);

    $stmt_csv = $pdo->prepare("SELECT no_pesanan, nama_pembeli, total_harga, metode_pengambilan, status, created_at FROM pesanan {$where_sql} ORDER BY created_at DESC");
    $stmt_csv->execute($params);

    while ($row = $stmt_csv->fetch()) {
        fputcsv($out, [
            $row['no_pesanan'],
            $row['nama_pembeli'],
            $row['total_harga'],
            $row['metode_pengambilan'],
            $row['status'],
            $row['created_at'],
        ]);
    }

    fclose($out);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan {$where_sql}");
$stmt->execute($params);
$total       = (int) $stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total / $per_page));

$stmt = $pdo->prepare("SELECT * FROM pesanan {$where_sql} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}");
$stmt->execute($params);
$pesanan_list = $stmt->fetchAll();

$csrf_token = generate_csrf();

$page_title  = 'Manajemen Pesanan';
$active_page = 'pesanan';
$css_extra   = '/assets/css/admin.css';

function status_badge_class(string $status): string
{
    return match ($status) {
        'menunggu_konfirmasi' => 'badge-menunggu',
        'diproses'            => 'badge-diproses',
        'selesai'             => 'badge-selesai',
        'dibatalkan'          => 'badge-dibatalkan',
        default               => 'badge-menunggu',
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'diproses'            => 'Diproses',
        'selesai'             => 'Selesai',
        'dibatalkan'          => 'Dibatalkan',
        default               => ucfirst($status),
    };
}

function pagination_url(int $pg, string $q, string $status): string
{
    $qs = http_build_query(array_filter([
        'q'      => $q,
        'status' => $status,
        'page'   => $pg > 1 ? $pg : null,
    ], fn($v) => $v !== null && $v !== ''));
    return '/admin/pesanan.php' . ($qs ? '?' . $qs : '');
}
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
                    <h1 class="page-header__title">Daftar Pesanan</h1>
                    <p class="page-header__subtitle">
                        Total <?= number_format($total) ?> pesanan
                        <?php if ($q !== '' || $filter_status !== ''): ?>
                            — difilter
                        <?php endif; ?>
                    </p>
                </div>
                <div class="page-header__actions">
                    <a
                        href="<?= e(pagination_url(1, $q, $filter_status) . (str_contains(pagination_url(1, $q, $filter_status), '?') ? '&export=csv' : '?export=csv')) ?>"
                        class="btn btn--secondary"
                    >
                        <i class="bi bi-download"></i>
                        Ekspor CSV
                    </a>
                </div>
            </div>

            <div class="admin-card admin-card--no-margin">
                <div class="admin-card__body admin-card__body--compact">
                    <form method="GET" action="/admin/pesanan.php" class="wf-filter-form">
                        <div class="filter-grid">
                            <div class="filter-grid-grow">
                                <label for="wf-search" class="form-label--caps">
                                    Cari Pesanan
                                </label>
                                <div class="admin-search">
                                    <span class="admin-search__icon"><i class="bi bi-search"></i></span>
                                    <input
                                        id="wf-search"
                                        type="search"
                                        name="q"
                                        value="<?= e($q) ?>"
                                        placeholder="No. pesanan atau nama pembeli…"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="wf-status" class="form-label--caps">
                                    Filter Status
                                </label>
                                <select
                                    id="wf-status"
                                    name="status"
                                    class="form-control"
                                >
                                    <option value="" <?= $filter_status === '' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="menunggu_konfirmasi" <?= $filter_status === 'menunggu_konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                    <option value="diproses"            <?= $filter_status === 'diproses'            ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai"             <?= $filter_status === 'selesai'             ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan"          <?= $filter_status === 'dibatalkan'          ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                            </div>

                            <div class="admin-action-row">
                                <button type="submit" class="btn btn--primary btn--sm">
                                    <i class="bi bi-search" aria-hidden="true"></i> Cari
                                </button>
                                <?php if ($q !== '' || $filter_status !== ''): ?>
                                    <a href="/admin/pesanan.php" class="btn btn--secondary btn--sm">
                                        <i class="bi bi-x" aria-hidden="true"></i> Reset
                                    </a>
                                <?php endif; ?>

                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Nama Pembeli</th>
                                <th>Total Harga</th>
                                <th>Metode Pengambilan</th>
                                <th>Status</th>
                                <th>Tanggal Pesan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesanan_list)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="admin-empty">
                                            <div class="admin-empty__icon"><i class="bi bi-box-seam"></i></div>
                                            <div class="admin-empty__title">Tidak Ada Pesanan</div>
                                            <p class="admin-empty__message">
                                                <?php if ($q !== '' || $filter_status !== ''): ?>
                                                    Tidak ditemukan pesanan yang cocok dengan filter yang dipilih.
                                                <?php else: ?>
                                                    Belum ada pesanan masuk saat ini.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pesanan_list as $p): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-strong text-purple text-mono text-sm">
                                                <?= e($p['no_pesanan']) ?>
                                            </strong>
                                        </td>
                                        <td><?= e($p['nama_pembeli']) ?></td>
                                        <td class="text-nowrap">
                                            <strong><?= e(format_rupiah((int) $p['total_harga'])) ?></strong>
                                        </td>
                                        <td class="text-capitalize">
                                            <?= e(str_replace('_', ' ', $p['metode_pengambilan'] ?? '-')) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= e(status_badge_class($p['status'])) ?>">
                                                <?= e(status_label($p['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap text-muted text-sm">
                                            <?php
                                            try {
                                                $dt = new DateTime($p['created_at']);
                                                echo e(format_tanggal_id($dt));
                                            } catch (Exception $e_dt) {
                                                echo e($p['created_at'] ?? '-');
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <button
                                                type="button"
                                                class="btn btn--primary btn--sm btn-ubah-status"
                                                data-id-pesanan="<?= e((string) $p['id_pesanan']) ?>"
                                                data-status-saat-ini="<?= e($p['status']) ?>"
                                            ><i class="bi bi-pencil-square"></i>
                                                Ubah Status
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="admin-card__footer">
                        <span class="text-sm text-muted">
                            Halaman <?= $page ?> dari <?= $total_pages ?>
                            &mdash; <?= number_format($total) ?> pesanan
                        </span>
                        <div class="pagination-list">
                            <?php if ($page > 1): ?>
                                <a href="<?= e(pagination_url($page - 1, $q, $filter_status)) ?>" class="btn btn--secondary btn--sm">
                                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Sebelumnya
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn--secondary btn--sm" disabled>
                                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Sebelumnya
                                </button>
                            <?php endif; ?>

                            <?php
                            // Tampilkan nomor halaman (maks 5 di tengah)
                            $range_start = max(1, min($page - 2, $total_pages - 4));
                            $range_end   = min($total_pages, $range_start + 4);
                            for ($pg = $range_start; $pg <= $range_end; $pg++):
                            ?>
                                <?php if ($pg === $page): ?>
                                    <span class="pagination-item pagination-item--active">
                                        <?= $pg ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= e(pagination_url($pg, $q, $filter_status)) ?>" class="pagination-item">
                                        <?= $pg ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?= e(pagination_url($page + 1, $q, $filter_status)) ?>" class="btn btn--primary btn--sm">
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i> Berikutnya
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn--primary btn--sm" disabled>
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i> Berikutnya
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </main>
</div>

<!-- CSRF token untuk digunakan oleh modal JS -->
<input type="hidden" id="csrf_token_modal" value="<?= e($csrf_token) ?>">

<script src="/assets/js/admin-modal.js"></script>
</body>
</html>
