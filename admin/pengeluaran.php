<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo          = get_pdo();
$csrf_token   = generate_csrf();
$errors       = [];
$success_msg  = '';

$dari   = trim($_GET['dari']   ?? '');
$sampai = trim($_GET['sampai'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_pengeluaran') {

    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $keterangan = trim($_POST['keterangan'] ?? '');
        $jumlah_raw = trim($_POST['jumlah']     ?? '');
        $tanggal    = trim($_POST['tanggal']    ?? '');

        if ($keterangan === '') {
            $errors[] = 'Keterangan tidak boleh kosong.';
        } elseif (mb_strlen($keterangan) > 255) {
            $errors[] = 'Keterangan maksimal 255 karakter.';
        }

        if ($jumlah_raw === '') {
            $errors[] = 'Jumlah tidak boleh kosong.';
        } elseif (!ctype_digit($jumlah_raw) || (int)$jumlah_raw <= 0) {
            $errors[] = 'Jumlah harus berupa bilangan bulat positif.';
        }

        if ($tanggal === '') {
            $errors[] = 'Tanggal tidak boleh kosong.';
        } else {
            $tgl_dt = DateTime::createFromFormat('Y-m-d', $tanggal);
            if (!$tgl_dt || $tgl_dt->format('Y-m-d') !== $tanggal) {
                $errors[] = 'Format tanggal tidak valid.';
            }
        }

        if (empty($errors)) {
            $jumlah = (int) $jumlah_raw;
            $stmt = $pdo->prepare(
                'INSERT INTO pengeluaran (keterangan, jumlah, tanggal) VALUES (:keterangan, :jumlah, :tanggal)'
            );
            $stmt->execute([
                ':keterangan' => $keterangan,
                ':jumlah'     => $jumlah,
                ':tanggal'    => $tanggal,
            ]);

            $success_msg = 'Pengeluaran berhasil dicatat.';
            $csrf_token  = generate_csrf();
        }
    }
}

$where  = [];
$params = [];

if ($dari !== '') {
    $where[]           = 'tanggal >= :dari';
    $params[':dari']   = $dari;
}
if ($sampai !== '') {
    $where[]             = 'tanggal <= :sampai';
    $params[':sampai']   = $sampai;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT * FROM pengeluaran $where_sql ORDER BY tanggal DESC, created_at DESC"
);
$stmt->execute($params);
$list = $stmt->fetchAll();

$stmt2 = $pdo->prepare(
    "SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran $where_sql"
);
$stmt2->execute($params);
$total_range = (int) $stmt2->fetchColumn();

$stmt3 = $pdo->query(
    'SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran
     WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())'
);
$total_bulan_ini = (int) $stmt3->fetchColumn();

$page_title  = 'Pengeluaran';
$active_page = 'pengeluaran';
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
                    <h2 class="page-header__title">Pencatatan Pengeluaran</h2>
                    <p class="page-header__subtitle">Catat dan pantau semua pengeluaran operasional toko.</p>
                </div>
            </div>

            <div class="stat-cards stat-cards--compact">
                <div class="stat-card stat-card--danger">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value">
                                <?= e(format_rupiah($total_bulan_ini)) ?>
                            </div>
                            <div class="stat-card__label">Total Pengeluaran Bulan Ini</div>
                        </div>
                        <span class="stat-card__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                    </div>
                </div>
            </div>

            <?php if ($success_msg !== ''): ?>
                <div class="alert alert--success" role="alert">
                <span aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                <?= e($success_msg) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert--error" role="alert">
                <strong>Terjadi kesalahan:</strong>
                <ul class="compact-list">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="form-layout">

                <div class="admin-card form-sticky">
                    <div class="admin-card__header">
                        <h3 class="admin-card__title"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Pengeluaran</h3>
                    </div>
                    <div class="admin-card__body">
                        <form method="POST" action="/admin/pengeluaran.php" novalidate>
                            <input type="hidden" name="action"     value="tambah_pengeluaran">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                            <div class="form-group">
                                <label class="form-label" for="keterangan">
                                    Keterangan <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="keterangan"
                                    name="keterangan"
                                    class="form-input"
                                    maxlength="255"
                                    required
                                    placeholder="Contoh: Beli bunga mawar merah"
                                    value="<?= e($_POST['keterangan'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="jumlah">
                                    Jumlah (Rp) <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="jumlah"
                                    name="jumlah"
                                    class="form-input"
                                    min="1"
                                    step="1"
                                    required
                                    placeholder="Contoh: 150000"
                                    value="<?= e($_POST['jumlah'] ?? '') ?>"
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tanggal">
                                    Tanggal <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="tanggal"
                                    name="tanggal"
                                    class="form-input"
                                    required
                                    value="<?= e($_POST['tanggal'] ?? date('Y-m-d')) ?>"
                                >
                            </div>

                            <button type="submit" class="btn btn--primary btn-block">
                                <i class="bi bi-save" aria-hidden="true"></i> Simpan Pengeluaran
                            </button>
                        </form>
                    </div>
                </div>

                <div>

                    <div class="admin-card admin-card--spaced">
                        <div class="admin-card__header">
                            <h3 class="admin-card__title"><i class="bi bi-search"></i> Filter Tanggal</h3>
                        </div>
                        <div class="admin-card__body">
                            <form method="GET" action="/admin/pengeluaran.php">
                                <div class="form-row">

                                    <div>
                                        <label class="form-label form-label--small" for="dari">
                                            Dari
                                        </label>
                                        <input
                                            type="date"
                                            id="dari"
                                            name="dari"
                                            class="form-input input--small"
                                            value="<?= e($dari) ?>"
                                        >
                                    </div>

                                    <div>
                                        <label class="form-label form-label--small" for="sampai">
                                            Sampai
                                        </label>
                                        <input
                                            type="date"
                                            id="sampai"
                                            name="sampai"
                                            class="form-input input--small"
                                            value="<?= e($sampai) ?>"
                                        >
                                    </div>

                                    <div class="form-row__actions">
                                        <button type="submit" class="btn btn--primary btn--sm">
                                            <i class="bi bi-funnel" aria-hidden="true"></i> Filter
                                        </button>
                                        <a href="/admin/pengeluaran.php" class="btn btn--ghost btn--sm">
                                            Reset
                                        </a>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="admin-card">
                        <div class="admin-card__header">
                            <h3 class="admin-card__title">
                                <i class="bi bi-card-list"></i> Daftar Pengeluaran
                                <?php if ($dari !== '' || $sampai !== ''): ?>
                                    <span class="form-note">
                                        (<?php
                                            $label_filter = [];
                                            if ($dari    !== '') $label_filter[] = 'dari ' . e($dari);
                                            if ($sampai  !== '') $label_filter[] = 'sampai ' . e($sampai);
                                            echo implode(' ', $label_filter);
                                        ?>)
                                    </span>
                                <?php endif; ?>
                            </h3>
                        </div>

                        <?php if (empty($list)): ?>
                        <div class="admin-empty">
                            <div class="admin-empty__icon" aria-hidden="true"><i class="bi bi-wallet2"></i></div>
                            <div class="admin-empty__title">Belum ada data pengeluaran</div>
                            <div class="admin-empty__message">
                                <?php if ($dari !== '' || $sampai !== ''): ?>
                                    Tidak ada pengeluaran pada rentang tanggal yang dipilih.
                                <?php else: ?>
                                    Gunakan form di samping untuk mencatat pengeluaran pertama.
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table" aria-label="Tabel pengeluaran">
                                <thead>
                                    <tr>
                                        <th class="table-col--narrow">No.</th>
                                        <th>Keterangan</th>
                                        <th class="text-right-nowrap">Jumlah</th>
                                        <th class="text-nowrap">Tanggal</th>
                                        <th class="text-nowrap">Tanggal Input</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($list as $i => $row): ?>
                                    <tr>
                                        <td class="table-row__number">
                                            <?= e((string)($i + 1)) ?>
                                        </td>
                                        <td><?= e($row['keterangan']) ?></td>
                                        <td class="table-row__amount">
                                            <?= e(format_rupiah((int) $row['jumlah'])) ?>
                                        </td>
                                        <td class="table-row__date">
                                            <?php
                                                $tgl_obj = DateTime::createFromFormat('Y-m-d', $row['tanggal']);
                                                echo $tgl_obj ? e(format_tanggal_id($tgl_obj)) : e($row['tanggal']);
                                            ?>
                                        </td>
                                        <td class="table-row__meta">
                                            <?= e($row['created_at']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="table-row__total" colspan="2">
                                            Total
                                        </td>
                                        <td class="table-row__total--amount">
                                            <?= e(format_rupiah($total_range)) ?>
                                        </td>
                                        <td colspan="2" class="table-divider"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>
    </main>

</div>
</body>
</html>
