<?php
/**
 * admin/pembayaran.php
 *
 * Halaman Pencatatan Pembayaran — Panel Admin WanFlorist.
 * Mengelola pencatatan DP dan pembayaran lunas dari pembeli.
 *
 * Requirements: 12.1 – 12.8, 15.1 – 15.3, 15.5
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo         = get_pdo();
$csrf_token  = generate_csrf();
$errors      = [];
$success_msg = '';

$active_tab = in_array($_GET['tab'] ?? '', ['dp', 'lunas'], true)
    ? $_GET['tab']
    : 'dp';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'catat_dp') {

    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Token CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $id_pesanan  = (int) ($_POST['id_pesanan'] ?? 0);
        $jumlah_dp   = (int) ($_POST['jumlah_dp'] ?? 0);
        $metode      = trim($_POST['metode'] ?? '');
        $metode_valid = ['transfer_bca', 'transfer_mandiri', 'transfer_bni', 'lainnya'];

        if ($id_pesanan <= 0) {
            $errors['id_pesanan'] = 'Pilih pesanan yang valid.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id_pesanan FROM pesanan
                  WHERE id_pesanan = :id AND status = 'menunggu_konfirmasi'
                  LIMIT 1"
            );
            $stmt->execute([':id' => $id_pesanan]);
            if (!$stmt->fetch()) {
                $errors['id_pesanan'] = 'Pesanan tidak ditemukan atau statusnya bukan Menunggu Konfirmasi.';
            }
        }

        if ($jumlah_dp <= 0) {
            $errors['jumlah_dp'] = 'Jumlah DP harus berupa angka positif.';
        }

        if (!in_array($metode, $metode_valid, true)) {
            $errors['metode'] = 'Pilih metode pembayaran yang valid.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO dp (id_pesanan, jumlah_dp, metode)
                     VALUES (:id_pesanan, :jumlah_dp, :metode)"
                );
                $stmt->execute([
                    ':id_pesanan' => $id_pesanan,
                    ':jumlah_dp'  => $jumlah_dp,
                    ':metode'     => $metode,
                ]);

                $stmt = $pdo->prepare(
                    "UPDATE pesanan SET status = 'diproses' WHERE id_pesanan = :id"
                );
                $stmt->execute([':id' => $id_pesanan]);

                $pdo->commit();

                $success_msg = 'DP berhasil dicatat. Status pesanan diperbarui menjadi Diproses.';
                $csrf_token = generate_csrf();
                $active_tab = 'dp';

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('catat_dp error: ' . $e->getMessage());
                $errors['db'] = 'Gagal menyimpan data. Silakan coba lagi.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'catat_lunas') {

    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Token CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $id_pesanan   = (int) ($_POST['id_pesanan'] ?? 0);
        $metode_lunas = trim($_POST['metode_lunas'] ?? '');
        $jumlah_lunas = (int) ($_POST['jumlah_lunas'] ?? 0);
        $metode_valid = ['transfer_bca', 'transfer_mandiri', 'transfer_bni', 'cod', 'lainnya'];

        if ($id_pesanan <= 0) {
            $errors['id_pesanan'] = 'Pilih pesanan yang valid.';
        }
        if ($jumlah_lunas <= 0) {
            $errors['jumlah_lunas'] = 'Jumlah pelunasan harus berupa angka positif.';
        }
        if (!in_array($metode_lunas, $metode_valid, true)) {
            $errors['metode_lunas'] = 'Pilih metode pembayaran yang valid.';
        }

        $pesanan_row = null;
        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "SELECT id_pesanan, total_harga, metode_pengambilan, status
                   FROM pesanan
                  WHERE id_pesanan = :id AND status = 'diproses'
                  LIMIT 1"
            );
            $stmt->execute([':id' => $id_pesanan]);
            $pesanan_row = $stmt->fetch();

            if (!$pesanan_row) {
                $errors['id_pesanan'] = 'Pesanan tidak ditemukan atau belum berstatus Diproses.';
            }
        }

        if (empty($errors) && $pesanan_row) {
            $total_harga  = (int) $pesanan_row['total_harga'];
            $metode_pengambilan = $pesanan_row['metode_pengambilan'];

            if ($metode_pengambilan === 'ambil_sendiri') {
                $stmt = $pdo->prepare(
                    "SELECT jumlah_dp FROM dp WHERE id_pesanan = :id LIMIT 1"
                );
                $stmt->execute([':id' => $id_pesanan]);
                $dp_row = $stmt->fetch();

                if (!$dp_row) {
                    $errors['id_pesanan'] = 'Belum ada catatan DP untuk pesanan ini.';
                } else {
                    $jumlah_dp_terbayar = (int) $dp_row['jumlah_dp'];

                    if (!validasi_pelunasan_transfer($total_harga, $jumlah_dp_terbayar, $jumlah_lunas)) {
                        $sisa = $total_harga - $jumlah_dp_terbayar;
                        $errors['jumlah_lunas'] = 'Jumlah pelunasan kurang. Sisa yang harus dibayar: '
                            . format_rupiah($sisa) . '.';
                    }
                }
            }
            // COD: tidak ada pemeriksaan DP
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO lunas (id_pesanan, jumlah_lunas, metode)
                     VALUES (:id_pesanan, :jumlah_lunas, :metode)"
                );
                $stmt->execute([
                    ':id_pesanan'   => $id_pesanan,
                    ':jumlah_lunas' => $jumlah_lunas,
                    ':metode'       => $metode_lunas,
                ]);

                $stmt = $pdo->prepare(
                    "UPDATE pesanan SET status = 'selesai' WHERE id_pesanan = :id"
                );
                $stmt->execute([':id' => $id_pesanan]);

                $pdo->commit();

                $success_msg = 'Pembayaran lunas berhasil dicatat.';
                $csrf_token  = generate_csrf();
                $active_tab  = 'lunas';

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('catat_lunas error: ' . $e->getMessage());
                $errors['db'] = 'Gagal menyimpan data. Silakan coba lagi.';
            }
        }
    }
}

$total_dp_bulan = (int) $pdo->query(
    "SELECT COALESCE(SUM(jumlah_dp), 0)
       FROM dp
      WHERE MONTH(dicatat_pada) = MONTH(CURDATE())
        AND YEAR(dicatat_pada)  = YEAR(CURDATE())"
)->fetchColumn();

$total_lunas_bulan = (int) $pdo->query(
    "SELECT COALESCE(SUM(jumlah_lunas), 0)
       FROM lunas
      WHERE MONTH(dicatat_pada) = MONTH(CURDATE())
        AND YEAR(dicatat_pada)  = YEAR(CURDATE())"
)->fetchColumn();

$menunggu_bayar = (int) $pdo->query(
    "SELECT COUNT(*)
       FROM pesanan
      WHERE status = 'menunggu_konfirmasi'"
)->fetchColumn();

$stmt_dp_pending = $pdo->query(
    "SELECT p.id_pesanan, p.no_pesanan, p.nama_pembeli,
            p.total_harga, p.metode_pengambilan, p.created_at
       FROM pesanan p
      WHERE p.status = 'menunggu_konfirmasi'
        AND NOT EXISTS (
            SELECT 1 FROM dp d WHERE d.id_pesanan = p.id_pesanan
        )
      ORDER BY p.created_at ASC"
);
$orders_dp = $stmt_dp_pending->fetchAll();

$stmt_lunas_pending = $pdo->query(
    "SELECT p.id_pesanan, p.no_pesanan, p.nama_pembeli,
            p.total_harga, p.metode_pengambilan,
            COALESCE(d.jumlah_dp, 0) AS jumlah_dp_terbayar
       FROM pesanan p
       LEFT JOIN dp d ON d.id_pesanan = p.id_pesanan
      WHERE p.status = 'diproses'
        AND NOT EXISTS (
            SELECT 1 FROM lunas l WHERE l.id_pesanan = p.id_pesanan
        )
      ORDER BY p.created_at ASC"
);
$orders_lunas = $stmt_lunas_pending->fetchAll();

$page_title  = 'Pencatatan Pembayaran';
$active_page = 'pembayaran';
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
                    <h2 class="page-header__title">Pencatatan Pembayaran</h2>
                    <p class="page-header__subtitle">
                        Kelola transaksi DP dan pelunasan pesanan pembeli.
                    </p>
                </div>
            </div>

            <?php if ($success_msg !== ''): ?>
            <div class="alert alert-success" role="alert" style="
                    background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;
                    border-radius:9999px;padding:0.75rem 1.25rem;margin-bottom:1.25rem;
                    font-family:'Inter',sans-serif;font-size:0.9rem;">
                <i class="bi bi-check-lg" aria-hidden="true"></i> <?= e($success_msg) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors['csrf']) || !empty($errors['db'])): ?>
            <div class="alert alert-danger" role="alert" style="
                    background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;
                    border-radius:9999px;padding:0.75rem 1.25rem;margin-bottom:1.25rem;
                    font-family:'Inter',sans-serif;font-size:0.9rem;">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> <?= e($errors['csrf'] ?? $errors['db'] ?? '') ?>
            </div>
            <?php endif; ?>

            <div class="stat-cards" style="grid-template-columns:repeat(3,1fr);">

                <div class="stat-card stat-card--success">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value">
                                <?= e(format_rupiah($total_dp_bulan)) ?>
                            </div>
                            <div class="stat-card__label">Total DP Bulan Ini</div>
                        </div>
                        <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
                    </div>
                    <div class="stat-card__change">Bulan berjalan</div>
                </div>

                <div class="stat-card">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value">
                                <?= e(format_rupiah($total_lunas_bulan)) ?>
                            </div>
                            <div class="stat-card__label">Total Lunas Bulan Ini</div>
                        </div>
                        <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-credit-card"></i></span>
                    </div>
                    <div class="stat-card__change">Bulan berjalan</div>
                </div>

                <div class="stat-card stat-card--warning">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value">
                                <?= e((string) $menunggu_bayar) ?>
                            </div>
                            <div class="stat-card__label">Menunggu Pembayaran</div>
                        </div>
                        <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="stat-card__change">Pesanan belum dikonfirmasi</div>
                </div>

            </div>

            <nav class="tab-nav" role="tablist" aria-label="Tab pencatatan pembayaran">
                <button
                    type="button"
                    role="tab"
                    id="tab-btn-dp"
                    aria-controls="tab-panel-dp"
                    aria-selected="<?= $active_tab === 'dp' ? 'true' : 'false' ?>"
                    class="tab-btn<?= $active_tab === 'dp' ? ' tab-btn--active' : '' ?>"
                    data-tab="dp"
                >
                    Catat DP
                    <span class="tab-btn__count"><?= e((string) count($orders_dp)) ?></span>
                </button>
                <button
                    type="button"
                    role="tab"
                    id="tab-btn-lunas"
                    aria-controls="tab-panel-lunas"
                    aria-selected="<?= $active_tab === 'lunas' ? 'true' : 'false' ?>"
                    class="tab-btn<?= $active_tab === 'lunas' ? ' tab-btn--active' : '' ?>"
                    data-tab="lunas"
                >
                    Catat Lunas
                    <span class="tab-btn__count"><?= e((string) count($orders_lunas)) ?></span>
                </button>
            </nav>

            <div
                id="tab-panel-dp"
                role="tabpanel"
                aria-labelledby="tab-btn-dp"
                class="tab-panel<?= $active_tab === 'dp' ? ' tab-panel--active' : '' ?>"
            >
                <div class="admin-card" style="margin-bottom:1.5rem;">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Pesanan Menunggu DP</span>
                        <span style="font-size:0.8125rem;color:#6B7280;">
                            Status: menunggu_konfirmasi &amp; belum ada DP
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Nama Pembeli</th>
                                    <th>Total Harga</th>
                                    <th>Metode Pengambilan</th>
                                    <th>Tanggal Pesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders_dp)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="admin-empty">
                                            <div class="admin-empty__icon" aria-hidden="true"><i class="bi bi-emoji-smile"></i></div>
                                            <div class="admin-empty__title">Tidak ada pesanan menunggu DP</div>
                                            <div class="admin-empty__message">
                                                Semua pesanan transfer sudah memiliki DP yang tercatat.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($orders_dp as $row): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight:600;color:#6B21A8;">
                                            <?= e($row['no_pesanan']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($row['nama_pembeli']) ?></td>
                                    <td><?= e(format_rupiah((int) $row['total_harga'])) ?></td>
                                    <td>
                                        <?php if ($row['metode_pengambilan'] === 'ambil_sendiri'): ?>
                                        <span class="badge badge-diproses">Transfer</span>
                                        <?php else: ?>
                                        <span class="badge badge-menunggu">COD</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        try {
                                            $tgl = new DateTime($row['created_at']);
                                            echo e(format_tanggal_id($tgl));
                                        } catch (Exception $ex) {
                                            echo e($row['created_at']);
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card__header">
                        <span class="admin-card__title"><i class="bi bi-cash-stack" aria-hidden="true"></i> Catat Down Payment (DP)</span>
                    </div>
                    <div class="admin-card__body">

                        <?php if (!empty($errors) && $active_tab === 'dp'
                            && !isset($errors['csrf']) && !isset($errors['db'])): ?>
                        <div style="
                                background:#FEF3C7;color:#92400E;
                                border:1px solid #FCD34D;border-radius:12px;
                                padding:0.875rem 1rem;margin-bottom:1.25rem;
                                font-family:'Inter',sans-serif;font-size:0.875rem;">
                            Terdapat kesalahan pada formulir. Silakan periksa kembali isian Anda.
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="/admin/pembayaran.php?tab=dp" novalidate>
                            <input type="hidden" name="action" value="catat_dp">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                            <div style="display:grid;gap:1.25rem;">

                                <div class="form-group">
                                    <label for="dp_id_pesanan" class="form-label">
                                        Pilih Pesanan <span style="color:#DC2626;">*</span>
                                    </label>
                                    <select
                                        id="dp_id_pesanan"
                                        name="id_pesanan"
                                        class="form-control<?= !empty($errors['id_pesanan']) ? ' is-invalid' : '' ?>"
                                        required
                                    >
                                        <option value="">— Pilih pesanan —</option>
                                        <?php foreach ($orders_dp as $row): ?>
                                        <option
                                            value="<?= e((string) $row['id_pesanan']) ?>"
                                            <?= isset($_POST['id_pesanan']) && (int) $_POST['id_pesanan'] === $row['id_pesanan'] ? 'selected' : '' ?>
                                            data-total="<?= e((string) (int) $row['total_harga']) ?>"
                                        >
                                            <?= e($row['no_pesanan']) ?> —
                                            <?= e($row['nama_pembeli']) ?> —
                                            <?= e(format_rupiah((int) $row['total_harga'])) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['id_pesanan'])): ?>
                                    <div class="form-error"><?= e($errors['id_pesanan']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="dp_jumlah" class="form-label">
                                        Jumlah DP (Rp) <span style="color:#DC2626;">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="dp_jumlah"
                                        name="jumlah_dp"
                                        class="form-control<?= !empty($errors['jumlah_dp']) ? ' is-invalid' : '' ?>"
                                        min="1"
                                        step="1"
                                        placeholder="Contoh: 150000"
                                        value="<?= e((string) ((int) ($_POST['jumlah_dp'] ?? 0) ?: '')) ?>"
                                        required
                                    >
                                    <?php if (!empty($errors['jumlah_dp'])): ?>
                                    <div class="form-error"><?= e($errors['jumlah_dp']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="dp_metode" class="form-label">
                                        Metode Pembayaran <span style="color:#DC2626;">*</span>
                                    </label>
                                    <select
                                        id="dp_metode"
                                        name="metode"
                                        class="form-control<?= !empty($errors['metode']) ? ' is-invalid' : '' ?>"
                                        required
                                    >
                                        <option value="">— Pilih metode —</option>
                                        <option value="transfer_bca"
                                            <?= ($_POST['metode'] ?? '') === 'transfer_bca' ? 'selected' : '' ?>>
                                            Transfer BCA
                                        </option>
                                        <option value="transfer_mandiri"
                                            <?= ($_POST['metode'] ?? '') === 'transfer_mandiri' ? 'selected' : '' ?>>
                                            Transfer Mandiri
                                        </option>
                                        <option value="transfer_bni"
                                            <?= ($_POST['metode'] ?? '') === 'transfer_bni' ? 'selected' : '' ?>>
                                            Transfer BNI
                                        </option>
                                        <option value="lainnya"
                                            <?= ($_POST['metode'] ?? '') === 'lainnya' ? 'selected' : '' ?>>
                                            Lainnya
                                        </option>
                                    </select>
                                    <?php if (!empty($errors['metode'])): ?>
                                    <div class="form-error"><?= e($errors['metode']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save" aria-hidden="true"></i> Catat DP
                                                </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <div
                id="tab-panel-lunas"
                role="tabpanel"
                aria-labelledby="tab-btn-lunas"
                class="tab-panel<?= $active_tab === 'lunas' ? ' tab-panel--active' : '' ?>"
            >
                <div class="admin-card" style="margin-bottom:1.5rem;">
                    <div class="admin-card__header">
                        <span class="admin-card__title">Pesanan Menunggu Pelunasan</span>
                        <span style="font-size:0.8125rem;color:#6B7280;">
                            Status: diproses &amp; belum lunas
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Nama Pembeli</th>
                                    <th>Total Harga</th>
                                    <th>DP Terbayar</th>
                                    <th>Sisa Bayar</th>
                                    <th>Metode Pengambilan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders_lunas)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="admin-empty">
                                            <div class="admin-empty__icon" aria-hidden="true"><i class="bi bi-emoji-smile"></i></div>
                                            <div class="admin-empty__title">Tidak ada pesanan menunggu pelunasan</div>
                                            <div class="admin-empty__message">
                                                Semua pesanan yang diproses sudah lunas.
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($orders_lunas as $row): ?>
                                <?php
                                    $total_h   = (int) $row['total_harga'];
                                    $dp_bayar  = (int) $row['jumlah_dp_terbayar'];
                                    $sisa_bayar = $row['metode_pengambilan'] === 'cod'
                                        ? $total_h
                                        : max(0, $total_h - $dp_bayar);
                                ?>
                                <tr>
                                    <td>
                                        <span style="font-weight:600;color:#6B21A8;">
                                            <?= e($row['no_pesanan']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($row['nama_pembeli']) ?></td>
                                    <td><?= e(format_rupiah($total_h)) ?></td>
                                    <td>
                                        <?php if ($row['metode_pengambilan'] === 'cod'): ?>
                                        <span style="color:#9CA3AF;font-style:italic;">—</span>
                                        <?php else: ?>
                                        <?= e(format_rupiah($dp_bayar)) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:600;color:#D97706;">
                                        <?= e(format_rupiah($sisa_bayar)) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['metode_pengambilan'] === 'ambil_sendiri'): ?>
                                        <span class="badge badge-diproses">Transfer</span>
                                        <?php else: ?>
                                        <span class="badge badge-menunggu">COD</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card__header">
                        <span class="admin-card__title"><i class="bi bi-check-lg" aria-hidden="true"></i> Catat Pembayaran Lunas</span>
                    </div>
                    <div class="admin-card__body">

                        <?php if (!empty($errors) && $active_tab === 'lunas'
                            && !isset($errors['csrf']) && !isset($errors['db'])): ?>
                        <div style="
                                background:#FEF3C7;color:#92400E;
                                border:1px solid #FCD34D;border-radius:12px;
                                padding:0.875rem 1rem;margin-bottom:1.25rem;
                                font-family:'Inter',sans-serif;font-size:0.875rem;">
                            Terdapat kesalahan pada formulir. Silakan periksa kembali isian Anda.
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="/admin/pembayaran.php?tab=lunas" novalidate>
                            <input type="hidden" name="action" value="catat_lunas">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                            <div style="display:grid;gap:1.25rem;">

                                <div class="form-group">
                                    <label for="lunas_id_pesanan" class="form-label">
                                        Pilih Pesanan <span style="color:#DC2626;">*</span>
                                    </label>
                                    <select
                                        id="lunas_id_pesanan"
                                        name="id_pesanan"
                                        class="form-control<?= !empty($errors['id_pesanan']) ? ' is-invalid' : '' ?>"
                                        required
                                    >
                                        <option value="">— Pilih pesanan —</option>
                                        <?php foreach ($orders_lunas as $row): ?>
                                        <option
                                            value="<?= e((string) $row['id_pesanan']) ?>"
                                            <?= isset($_POST['id_pesanan']) && (int) $_POST['id_pesanan'] === $row['id_pesanan'] ? 'selected' : '' ?>
                                            data-metode="<?= e($row['metode_pengambilan']) ?>"
                                            data-total="<?= e((string) (int) $row['total_harga']) ?>"
                                            data-dp="<?= e((string) (int) $row['jumlah_dp_terbayar']) ?>"
                                        >
                                            <?= e($row['no_pesanan']) ?> —
                                            <?= e($row['nama_pembeli']) ?> —
                                            <?= e(format_rupiah((int) $row['total_harga'])) ?>
                                            (<?= e(strtoupper($row['metode_pengambilan'])) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['id_pesanan'])): ?>
                                    <div class="form-error"><?= e($errors['id_pesanan']) ?></div>
                                    <?php endif; ?>
                                    <div id="lunas-info" style="
                                            margin-top:0.5rem;font-size:0.8125rem;
                                            color:#6B7280;font-family:'Inter',sans-serif;
                                            display:none;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="lunas_jumlah" class="form-label">
                                        Jumlah Pelunasan (Rp) <span style="color:#DC2626;">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="lunas_jumlah"
                                        name="jumlah_lunas"
                                        class="form-control<?= !empty($errors['jumlah_lunas']) ? ' is-invalid' : '' ?>"
                                        min="1"
                                        step="1"
                                        placeholder="Contoh: 300000"
                                        value="<?= e((string) ((int) ($_POST['jumlah_lunas'] ?? 0) ?: '')) ?>"
                                        required
                                    >
                                    <?php if (!empty($errors['jumlah_lunas'])): ?>
                                    <div class="form-error"><?= e($errors['jumlah_lunas']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="lunas_metode" class="form-label">
                                        Metode Pembayaran <span style="color:#DC2626;">*</span>
                                    </label>
                                    <select
                                        id="lunas_metode"
                                        name="metode_lunas"
                                        class="form-control<?= !empty($errors['metode_lunas']) ? ' is-invalid' : '' ?>"
                                        required
                                    >
                                        <option value="">— Pilih metode —</option>
                                        <option value="transfer_bca"
                                            <?= ($_POST['metode_lunas'] ?? '') === 'transfer_bca' ? 'selected' : '' ?>>
                                            Transfer BCA
                                        </option>
                                        <option value="transfer_mandiri"
                                            <?= ($_POST['metode_lunas'] ?? '') === 'transfer_mandiri' ? 'selected' : '' ?>>
                                            Transfer Mandiri
                                        </option>
                                        <option value="transfer_bni"
                                            <?= ($_POST['metode_lunas'] ?? '') === 'transfer_bni' ? 'selected' : '' ?>>
                                            Transfer BNI
                                        </option>
                                        <option value="cod"
                                            <?= ($_POST['metode_lunas'] ?? '') === 'cod' ? 'selected' : '' ?>>
                                            COD (Tunai saat terima)
                                        </option>
                                        <option value="lainnya"
                                            <?= ($_POST['metode_lunas'] ?? '') === 'lainnya' ? 'selected' : '' ?>>
                                            Lainnya
                                        </option>
                                    </select>
                                    <?php if (!empty($errors['metode_lunas'])): ?>
                                    <div class="form-error"><?= e($errors['metode_lunas']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i> Catat Pelunasan
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<style>
.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.form-control {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9rem;
    color: #1F2937;
    background: #F9FAFB;
    border: 1.5px solid #E5E7EB;
    border-radius: 10px;
    padding: 0.625rem 0.875rem;
    width: 100%;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-sizing: border-box;
    appearance: auto;
}

.form-control:focus {
    outline: none;
    border-color: #9333EA;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(107, 33, 168, 0.1);
}

.form-control.is-invalid {
    border-color: #DC2626;
    background: #FFF5F5;
}

.form-control.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.form-error {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.8125rem;
    color: #DC2626;
    margin-top: 0.125rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.5rem;
    border-radius: 9999px;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: background-color 0.2s ease, transform 0.1s ease;
    text-decoration: none;
}

.btn-primary {
    background: #6B21A8;
    color: #ffffff;
}

.btn-primary:hover {
    background: #5B1A90;
}

.btn-primary:active {
    transform: scale(0.98);
}
</style>

<script>
/**
 * Vanilla JS — tab switching + info hint lunas.
 * addEventListener only, no inline onclick.
 * Requirements: 17.5
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var tabBtns   = document.querySelectorAll('.tab-btn[data-tab]');
        var tabPanels = {
            dp:    document.getElementById('tab-panel-dp'),
            lunas: document.getElementById('tab-panel-lunas'),
        };

        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-tab');

                tabBtns.forEach(function (b) {
                    b.classList.remove('tab-btn--active');
                    b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('tab-btn--active');
                btn.setAttribute('aria-selected', 'true');

                Object.keys(tabPanels).forEach(function (key) {
                    if (tabPanels[key]) {
                        tabPanels[key].classList.remove('tab-panel--active');
                    }
                });
                if (tabPanels[target]) {
                    tabPanels[target].classList.add('tab-panel--active');
                }
            });
        });

        var lunasSelect = document.getElementById('lunas_id_pesanan');
        var lunasInfo   = document.getElementById('lunas-info');
        var lunasJumlah = document.getElementById('lunas_jumlah');

        if (lunasSelect && lunasInfo) {
            lunasSelect.addEventListener('change', function () {
                var opt = lunasSelect.options[lunasSelect.selectedIndex];
                if (!opt || opt.value === '') {
                    lunasInfo.style.display = 'none';
                    return;
                }

                var total  = parseInt(opt.getAttribute('data-total') || '0', 10);
                var dp     = parseInt(opt.getAttribute('data-dp')    || '0', 10);
                var metode = opt.getAttribute('data-metode') || '';
                var sisa   = metode === 'cod' ? total : Math.max(0, total - dp);

                function rupiah(n) {
                    return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }

                var infoText = 'Total: <strong>' + rupiah(total) + '</strong>';
                if (metode === 'ambil_sendiri') {
                    infoText += ' | DP terbayar: <strong>' + rupiah(dp) + '</strong>';
                    infoText += ' | Sisa: <strong style="color:#D97706;">' + rupiah(sisa) + '</strong>';
                } else {
                    infoText += ' | Metode: <strong>COD</strong>';
                    infoText += ' | Jumlah: <strong style="color:#D97706;">' + rupiah(sisa) + '</strong>';
                }
                lunasInfo.innerHTML = infoText;
                lunasInfo.style.display = 'block';

                // Isi otomatis jumlah pelunasan dengan nilai sisa
                if (lunasJumlah && sisa > 0) {
                    lunasJumlah.value = sisa;
                }
            });
        }

    });
}());
</script>

</body>
</html>
