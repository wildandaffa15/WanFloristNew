<?php
/**
 * admin/pengeluaran.php
 *
 * Halaman pencatatan pengeluaran WanFlorist.
 * — Menampilkan ringkasan total pengeluaran bulan ini.
 * — Form tambah pengeluaran baru (POST).
 * — Filter rentang tanggal (GET).
 * — Tabel daftar pengeluaran dengan total baris terbawah.
 *
 * Requirements: 13.1, 13.2, 13.3
 */

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

        <div class="admin-header">
            <h1 class="admin-header__title">Pengeluaran</h1>
            <div class="admin-header__actions">
                <span style="font-size:0.85rem;color:#6B7280;">
                    Halo, <?= e($_SESSION['username'] ?? 'Admin') ?>
                </span>
            </div>
        </div>

        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h2 class="page-header__title">Pencatatan Pengeluaran</h2>
                    <p class="page-header__subtitle">Catat dan pantau semua pengeluaran operasional toko.</p>
                </div>
            </div>

            <div class="stat-cards" style="grid-template-columns:repeat(1,minmax(0,320px));">
                <div class="stat-card stat-card--danger">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value">
                                <?= e(format_rupiah($total_bulan_ini)) ?>
                            </div>
                            <div class="stat-card__label">Total Pengeluaran Bulan Ini</div>
                        </div>
                        <span class="stat-card__icon" aria-hidden="true">💸</span>
                    </div>
                </div>
            </div>

            <?php if ($success_msg !== ''): ?>
            <div
                role="alert"
                style="background:#D1FAE5;border:1px solid #6EE7B7;color:#065F46;
                       border-radius:10px;padding:0.875rem 1.25rem;margin-bottom:1.25rem;
                       font-size:0.9rem;display:flex;align-items:center;gap:0.5rem;"
            >
                <span aria-hidden="true">✅</span>
                <?= e($success_msg) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div
                role="alert"
                style="background:#FEE2E2;border:1px solid #FECACA;color:#991B1B;
                       border-radius:10px;padding:0.875rem 1.25rem;margin-bottom:1.25rem;
                       font-size:0.9rem;"
            >
                <strong>Terjadi kesalahan:</strong>
                <ul style="margin:0.5rem 0 0 1.25rem;padding:0;">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:340px 1fr;gap:1.5rem;align-items:start;">

                <div class="admin-card" style="position:sticky;top:80px;">
                    <div class="admin-card__header">
                        <h3 class="admin-card__title">➕ Tambah Pengeluaran</h3>
                    </div>
                    <div class="admin-card__body">
                        <form method="POST" action="/admin/pengeluaran.php" novalidate>
                            <input type="hidden" name="action"     value="tambah_pengeluaran">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                            <div style="margin-bottom:1rem;">
                                <label
                                    for="keterangan"
                                    style="display:block;font-size:0.875rem;font-weight:600;
                                           color:#374151;margin-bottom:0.375rem;"
                                >
                                    Keterangan <span style="color:#DC2626;" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="keterangan"
                                    name="keterangan"
                                    maxlength="255"
                                    required
                                    placeholder="Contoh: Beli bunga mawar merah"
                                    value="<?= e($_POST['keterangan'] ?? '') ?>"
                                    style="width:100%;padding:0.625rem 0.875rem;border:1.5px solid #E5E7EB;
                                           border-radius:8px;font-size:0.9rem;color:#1F2937;
                                           font-family:inherit;transition:border-color .15s ease;
                                           box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#9333EA'"
                                    onblur="this.style.borderColor='#E5E7EB'"
                                >
                            </div>

                            <div style="margin-bottom:1rem;">
                                <label
                                    for="jumlah"
                                    style="display:block;font-size:0.875rem;font-weight:600;
                                           color:#374151;margin-bottom:0.375rem;"
                                >
                                    Jumlah (Rp) <span style="color:#DC2626;" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="jumlah"
                                    name="jumlah"
                                    min="1"
                                    step="1"
                                    required
                                    placeholder="Contoh: 150000"
                                    value="<?= e($_POST['jumlah'] ?? '') ?>"
                                    style="width:100%;padding:0.625rem 0.875rem;border:1.5px solid #E5E7EB;
                                           border-radius:8px;font-size:0.9rem;color:#1F2937;
                                           font-family:inherit;transition:border-color .15s ease;
                                           box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#9333EA'"
                                    onblur="this.style.borderColor='#E5E7EB'"
                                >
                            </div>

                            <div style="margin-bottom:1.5rem;">
                                <label
                                    for="tanggal"
                                    style="display:block;font-size:0.875rem;font-weight:600;
                                           color:#374151;margin-bottom:0.375rem;"
                                >
                                    Tanggal <span style="color:#DC2626;" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="tanggal"
                                    name="tanggal"
                                    required
                                    value="<?= e($_POST['tanggal'] ?? date('Y-m-d')) ?>"
                                    style="width:100%;padding:0.625rem 0.875rem;border:1.5px solid #E5E7EB;
                                           border-radius:8px;font-size:0.9rem;color:#1F2937;
                                           font-family:inherit;transition:border-color .15s ease;
                                           box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#9333EA'"
                                    onblur="this.style.borderColor='#E5E7EB'"
                                >
                            </div>

                            <button
                                type="submit"
                                style="width:100%;padding:0.75rem 1rem;background:#6B21A8;color:#fff;
                                       border:none;border-radius:8px;font-size:0.95rem;font-weight:600;
                                       cursor:pointer;font-family:inherit;transition:background-color .15s ease;"
                                onmouseover="this.style.backgroundColor='#7C3AED'"
                                onmouseout="this.style.backgroundColor='#6B21A8'"
                            >
                                💾 Simpan Pengeluaran
                            </button>
                        </form>
                    </div>
                </div>

                <div>

                    <div class="admin-card" style="margin-bottom:1.25rem;">
                        <div class="admin-card__header">
                            <h3 class="admin-card__title">🔍 Filter Tanggal</h3>
                        </div>
                        <div class="admin-card__body" style="padding-top:1rem;">
                            <form method="GET" action="/admin/pengeluaran.php">
                                <div style="display:flex;align-items:flex-end;gap:0.75rem;flex-wrap:wrap;">

                                    <div style="flex:1;min-width:140px;">
                                        <label
                                            for="dari"
                                            style="display:block;font-size:0.8rem;font-weight:600;
                                                   color:#6B7280;margin-bottom:0.3rem;"
                                        >
                                            Dari
                                        </label>
                                        <input
                                            type="date"
                                            id="dari"
                                            name="dari"
                                            value="<?= e($dari) ?>"
                                            style="width:100%;padding:0.5rem 0.75rem;border:1.5px solid #E5E7EB;
                                                   border-radius:8px;font-size:0.875rem;color:#1F2937;
                                                   font-family:inherit;box-sizing:border-box;"
                                        >
                                    </div>

                                    <div style="flex:1;min-width:140px;">
                                        <label
                                            for="sampai"
                                            style="display:block;font-size:0.8rem;font-weight:600;
                                                   color:#6B7280;margin-bottom:0.3rem;"
                                        >
                                            Sampai
                                        </label>
                                        <input
                                            type="date"
                                            id="sampai"
                                            name="sampai"
                                            value="<?= e($sampai) ?>"
                                            style="width:100%;padding:0.5rem 0.75rem;border:1.5px solid #E5E7EB;
                                                   border-radius:8px;font-size:0.875rem;color:#1F2937;
                                                   font-family:inherit;box-sizing:border-box;"
                                        >
                                    </div>

                                    <div style="display:flex;gap:0.5rem;padding-bottom:0;">
                                        <button
                                            type="submit"
                                            style="padding:0.5rem 1.25rem;background:#6B21A8;color:#fff;
                                                   border:none;border-radius:8px;font-size:0.875rem;
                                                   font-weight:600;cursor:pointer;font-family:inherit;
                                                   white-space:nowrap;transition:background-color .15s ease;"
                                            onmouseover="this.style.backgroundColor='#7C3AED'"
                                            onmouseout="this.style.backgroundColor='#6B21A8'"
                                        >
                                            Filter
                                        </button>
                                        <a
                                            href="/admin/pengeluaran.php"
                                            style="padding:0.5rem 1rem;background:#F3F4F6;color:#374151;
                                                   border-radius:8px;font-size:0.875rem;font-weight:600;
                                                   text-decoration:none;display:inline-flex;
                                                   align-items:center;white-space:nowrap;
                                                   transition:background-color .15s ease;"
                                            onmouseover="this.style.backgroundColor='#E5E7EB'"
                                            onmouseout="this.style.backgroundColor='#F3F4F6'"
                                        >
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
                                📋 Daftar Pengeluaran
                                <?php if ($dari !== '' || $sampai !== ''): ?>
                                    <span style="font-size:0.8rem;font-weight:400;color:#6B7280;margin-left:0.5rem;">
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
                            <div class="admin-empty__icon" aria-hidden="true">💸</div>
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
                                        <th style="width:3rem;">No.</th>
                                        <th>Keterangan</th>
                                        <th style="text-align:right;white-space:nowrap;">Jumlah</th>
                                        <th style="white-space:nowrap;">Tanggal</th>
                                        <th style="white-space:nowrap;">Tanggal Input</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($list as $i => $row): ?>
                                    <tr>
                                        <td style="color:#9CA3AF;text-align:center;">
                                            <?= e((string)($i + 1)) ?>
                                        </td>
                                        <td><?= e($row['keterangan']) ?></td>
                                        <td style="text-align:right;font-weight:600;color:#DC2626;white-space:nowrap;">
                                            <?= e(format_rupiah((int) $row['jumlah'])) ?>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <?php
                                                $tgl_obj = DateTime::createFromFormat('Y-m-d', $row['tanggal']);
                                                echo $tgl_obj ? e(format_tanggal_id($tgl_obj)) : e($row['tanggal']);
                                            ?>
                                        </td>
                                        <td style="color:#6B7280;font-size:0.8rem;white-space:nowrap;">
                                            <?= e($row['created_at']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td
                                            colspan="2"
                                            style="font-weight:700;font-size:0.9rem;
                                                   color:#1F2937;padding:0.875rem 1rem;
                                                   border-top:2px solid #E5E7EB;"
                                        >
                                            Total
                                        </td>
                                        <td
                                            style="text-align:right;font-weight:700;font-size:0.95rem;
                                                   color:#DC2626;white-space:nowrap;
                                                   border-top:2px solid #E5E7EB;padding:0.875rem 1rem;"
                                        >
                                            <?= e(format_rupiah($total_range)) ?>
                                        </td>
                                        <td
                                            colspan="2"
                                            style="border-top:2px solid #E5E7EB;"
                                        ></td>
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
