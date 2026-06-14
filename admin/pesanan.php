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

        <header class="admin-header">
            <h1 class="admin-header__title">📦 Manajemen Pesanan</h1>
            <div class="admin-header__actions">
                <a
                    href="<?= e(pagination_url(1, $q, $filter_status) . (str_contains(pagination_url(1, $q, $filter_status), '?') ? '&export=csv' : '?export=csv')) ?>"
                    class="btn btn--secondary btn--sm"
                >
                    ⬇ Ekspor CSV
                </a>
            </div>
        </header>

        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h2 class="page-header__title">Daftar Pesanan</h2>
                    <p class="page-header__subtitle">
                        Total <?= number_format($total) ?> pesanan ditemukan
                        <?php if ($q !== '' || $filter_status !== ''): ?>
                            (difilter)
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="admin-card" style="margin-bottom:1.25rem;">
                <div class="admin-card__body" style="padding:1rem 1.5rem;">
                    <form method="GET" action="/admin/pesanan.php" class="wf-filter-form">
                        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:flex-end;">

                            <div style="flex:1;min-width:200px;">
                                <label for="wf-search" style="display:block;font-size:0.75rem;font-weight:600;color:#6B7280;margin-bottom:0.375rem;text-transform:uppercase;letter-spacing:.05em;">
                                    Cari Pesanan
                                </label>
                                <div class="admin-search">
                                    <span class="admin-search__icon">🔍</span>
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

                            <div style="min-width:180px;">
                                <label for="wf-status" style="display:block;font-size:0.75rem;font-weight:600;color:#6B7280;margin-bottom:0.375rem;text-transform:uppercase;letter-spacing:.05em;">
                                    Filter Status
                                </label>
                                <select
                                    id="wf-status"
                                    name="status"
                                    style="width:100%;padding:0.5rem 0.875rem;border:1.5px solid #E5E7EB;border-radius:9999px;font-family:'Inter',sans-serif;font-size:0.875rem;color:#1F2937;background:#F9FAFB;outline:none;cursor:pointer;"
                                >
                                    <option value="" <?= $filter_status === '' ? 'selected' : '' ?>>Semua Status</option>
                                    <option value="menunggu_konfirmasi" <?= $filter_status === 'menunggu_konfirmasi' ? 'selected' : '' ?>>Menunggu Konfirmasi</option>
                                    <option value="diproses"            <?= $filter_status === 'diproses'            ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai"             <?= $filter_status === 'selesai'             ? 'selected' : '' ?>>Selesai</option>
                                    <option value="dibatalkan"          <?= $filter_status === 'dibatalkan'          ? 'selected' : '' ?>>Dibatalkan</option>
                                </select>
                            </div>

                            <div style="display:flex;gap:0.5rem;align-items:flex-end;">
                                <button type="submit" class="btn btn--primary btn--sm">
                                    🔍 Cari
                                </button>
                                <?php if ($q !== '' || $filter_status !== ''): ?>
                                    <a href="/admin/pesanan.php" class="btn btn--secondary btn--sm">
                                        ✕ Reset
                                    </a>
                                <?php endif; ?>
                                <a
                                    href="<?= e(pagination_url(1, $q, $filter_status) . (str_contains(pagination_url(1, $q, $filter_status), '?') ? '&export=csv' : '?export=csv')) ?>"
                                    class="btn btn--secondary btn--sm"
                                >
                                    ⬇ Ekspor CSV
                                </a>
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
                                <th style="text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pesanan_list)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="admin-empty">
                                            <div class="admin-empty__icon">📦</div>
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
                                            <strong style="color:#6B21A8;font-family:'Inter',monospace;font-size:0.85rem;">
                                                <?= e($p['no_pesanan']) ?>
                                            </strong>
                                        </td>
                                        <td><?= e($p['nama_pembeli']) ?></td>
                                        <td style="white-space:nowrap;">
                                            <strong><?= e(format_rupiah((int) $p['total_harga'])) ?></strong>
                                        </td>
                                        <td style="text-transform:capitalize;">
                                            <?= e(str_replace('_', ' ', $p['metode_pengambilan'] ?? '-')) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= e(status_badge_class($p['status'])) ?>">
                                                <?= e(status_label($p['status'])) ?>
                                            </span>
                                        </td>
                                        <td style="white-space:nowrap;color:#6B7280;font-size:0.85rem;">
                                            <?php
                                            try {
                                                $dt = new DateTime($p['created_at']);
                                                echo e(format_tanggal_id($dt));
                                            } catch (Exception $e_dt) {
                                                echo e($p['created_at'] ?? '-');
                                            }
                                            ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <button
                                                type="button"
                                                class="btn btn--primary btn--sm btn-ubah-status"
                                                data-id-pesanan="<?= e((string) $p['id_pesanan']) ?>"
                                                data-status-saat-ini="<?= e($p['status']) ?>"
                                            >
                                                ✏ Ubah Status
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="admin-card__footer" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                        <span style="font-size:0.875rem;color:#6B7280;">
                            Halaman <?= $page ?> dari <?= $total_pages ?>
                            &mdash; <?= number_format($total) ?> pesanan
                        </span>
                        <div style="display:flex;gap:0.375rem;align-items:center;">
                            <?php if ($page > 1): ?>
                                <a href="<?= e(pagination_url($page - 1, $q, $filter_status)) ?>" class="btn btn--secondary btn--sm">
                                    ← Sebelumnya
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn--secondary btn--sm" disabled style="opacity:.4;cursor:not-allowed;">
                                    ← Sebelumnya
                                </button>
                            <?php endif; ?>

                            <?php
                            // Tampilkan nomor halaman (maks 5 di tengah)
                            $range_start = max(1, min($page - 2, $total_pages - 4));
                            $range_end   = min($total_pages, $range_start + 4);
                            for ($pg = $range_start; $pg <= $range_end; $pg++):
                            ?>
                                <?php if ($pg === $page): ?>
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:8px;background:#6B21A8;color:#fff;font-size:0.875rem;font-weight:600;">
                                        <?= $pg ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?= e(pagination_url($pg, $q, $filter_status)) ?>" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:8px;border:1px solid #E5E7EB;color:#374151;font-size:0.875rem;text-decoration:none;font-family:'Inter',sans-serif;transition:background .15s;">
                                        <?= $pg ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?= e(pagination_url($page + 1, $q, $filter_status)) ?>" class="btn btn--primary btn--sm">
                                    Berikutnya →
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn--primary btn--sm" disabled style="opacity:.4;cursor:not-allowed;">
                                    Berikutnya →
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
