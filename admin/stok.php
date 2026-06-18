<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo         = get_pdo();
$csrf_token  = generate_csrf();
$errors      = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_bahan') {

    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {

        $nama_bahan  = trim($_POST['nama_bahan'] ?? '');
        $satuan      = trim($_POST['satuan'] ?? '');
        $stok_awal   = $_POST['stok_awal'] ?? '';
        $stok_minimum = $_POST['stok_minimum'] ?? '';

        if ($nama_bahan === '') {
            $errors[] = 'Nama bahan tidak boleh kosong.';
        } elseif (mb_strlen($nama_bahan) > 150) {
            $errors[] = 'Nama bahan maksimal 150 karakter.';
        }

        if ($satuan === '') {
            $errors[] = 'Satuan tidak boleh kosong.';
        } elseif (mb_strlen($satuan) > 30) {
            $errors[] = 'Satuan maksimal 30 karakter.';
        }

        if (!is_numeric($stok_awal) || (int) $stok_awal < 0 || (string)(int)$stok_awal !== (string)(int)$stok_awal) {
            $errors[] = 'Stok awal harus berupa bilangan bulat >= 0.';
        }

        if (!is_numeric($stok_minimum) || (int) $stok_minimum < 0 || (string)(int)$stok_minimum !== (string)(int)$stok_minimum) {
            $errors[] = 'Stok minimum harus berupa bilangan bulat >= 0.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'INSERT INTO stok_bahan (nama_bahan, satuan, stok_saat_ini, stok_minimum)
                 VALUES (:nama_bahan, :satuan, :stok_saat_ini, :stok_minimum)'
            );
            $stmt->execute([
                ':nama_bahan'   => $nama_bahan,
                ':satuan'       => $satuan,
                ':stok_saat_ini' => (int) $stok_awal,
                ':stok_minimum' => (int) $stok_minimum,
            ]);

            $success_msg = 'Bahan berhasil ditambahkan.';

            $csrf_token = generate_csrf();
        }
    }
}

$stmt_bahan = $pdo->query('SELECT * FROM stok_bahan ORDER BY nama_bahan ASC');
$list_bahan = $stmt_bahan->fetchAll();

$stmt_kritis  = $pdo->query('SELECT COUNT(*) FROM stok_bahan WHERE stok_saat_ini < stok_minimum');
$jumlah_kritis = (int) $stmt_kritis->fetchColumn();

$page_title  = 'Stok Bahan';
$active_page = 'stok';
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
                    <h2 class="page-header__title">Stok Bahan</h2>
                    <p class="page-header__subtitle">
                        Kelola persediaan bahan dan pantau stok kritis untuk operasional toko.
                    </p>
                </div>
            </div>

            <div class="stat-cards stat-cards--stacked">
                <div class="stat-card <?= $jumlah_kritis > 0 ? 'stat-card--danger' : 'stat-card--success' ?>">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value">
                                <?= e((string) $jumlah_kritis) ?>
                            </div>
                            <div class="stat-card__label">
                                Item Stok Kritis
                            </div>
                        </div>

                        <span class="stat-card__icon" aria-hidden="true">
                            <?php if ($jumlah_kritis > 0): ?>
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            <?php else: ?>
                                <i class="bi bi-check-circle-fill"></i>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($success_msg !== ''): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> <?= e($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Terjadi kesalahan:</strong>
                    <ul class="alert-list">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-card__header">
                    <h2 class="admin-card__title"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Bahan Baru</h2>
                </div>
                <div class="admin-card__body">
                    <form method="POST" action="/admin/stok.php" novalidate>
                        <input type="hidden" name="action" value="tambah_bahan">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="form-grid">

                            <div>
                                <label for="nama_bahan" class="form-label">
                                    Nama Bahan <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_bahan"
                                    name="nama_bahan"
                                    maxlength="150"
                                    required
                                    placeholder="Contoh: Mawar Merah"
                                    value="<?= e($_POST['nama_bahan'] ?? '') ?>"
                                    class="form-control"
                                >
                            </div>

                            <div>
                                <label for="satuan" class="form-label">
                                    Satuan <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="satuan"
                                    name="satuan"
                                    maxlength="30"
                                    required
                                    placeholder="pcs / tangkai / kg / lembar"
                                    value="<?= e($_POST['satuan'] ?? 'pcs') ?>"
                                    class="form-control"
                                >
                            </div>

                            <div>
                                <label for="stok_awal" class="form-label">
                                    Stok Awal <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="stok_awal"
                                    name="stok_awal"
                                    min="0"
                                    step="1"
                                    required
                                    placeholder="0"
                                    value="<?= e($_POST['stok_awal'] ?? '0') ?>"
                                    class="form-control"
                                >
                            </div>

                            <div>
                                <label for="stok_minimum" class="form-label">
                                    Stok Minimum <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="stok_minimum"
                                    name="stok_minimum"
                                    min="0"
                                    step="1"
                                    required
                                    placeholder="5"
                                    value="<?= e($_POST['stok_minimum'] ?? '5') ?>"
                                    class="form-control"
                                >
                            </div>

                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn--primary">
                                Tambah Bahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                    <div class="admin-card__header">
                    <h2 class="admin-card__title"><i class="bi bi-card-list"></i> Daftar Bahan</h2>
                    <span class="text-muted">
                        Total: <?= e((string) count($list_bahan)) ?> bahan
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="admin-table" aria-label="Daftar stok bahan">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Bahan</th>
                                <th>Satuan</th>
                                <th>Stok Saat Ini</th>
                                <th>Stok Minimum</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_bahan)): ?>
                                <tr class="table-empty">
                                    <td colspan="7">
                                        Belum ada data bahan. Tambahkan bahan pertama Anda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($list_bahan as $no => $bahan): ?>
                                    <?php $is_kritis = (int)$bahan['stok_saat_ini'] < (int)$bahan['stok_minimum']; ?>
                                    <tr
                                        class="<?= $is_kritis ? 'row-kritis' : '' ?>"
                                        id="bahan-row-<?= e((string)$bahan['id_bahan']) ?>"
                                    >
                                        <td><?= e((string)($no + 1)) ?></td>
                                        <td><strong><?= e($bahan['nama_bahan']) ?></strong></td>
                                        <td><?= e($bahan['satuan']) ?></td>
                                        <td>
                                            <span
                                                id="stok-nilai-<?= e((string)$bahan['id_bahan']) ?>"
                                                class="stok-count <?= $is_kritis ? 'stok-count--kritis' : 'stok-count--safe' ?>"
                                            >
                                                <?= e((string)$bahan['stok_saat_ini']) ?>
                                            </span>
                                        </td>
                                        <td><?= e((string)$bahan['stok_minimum']) ?></td>
                                        <td>
                                            <?php if ($is_kritis): ?>
                                                <span class="badge badge-kritis">⚠ Stok Kritis</span>
                                            <?php else: ?>
                                                <span class="badge badge-aktif">✓ Aman</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn--primary btn--sm btn-update-stok"
                                                data-id="<?= e((string)$bahan['id_bahan']) ?>"
                                                data-nama="<?= e($bahan['nama_bahan']) ?>"
                                                data-stok="<?= e((string)$bahan['stok_saat_ini']) ?>"
                                                aria-label="Update stok <?= e($bahan['nama_bahan']) ?>"
                                            >
                                                Update Stok
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<div
    id="modal-update-stok"
    class="modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-update-title"
    aria-hidden="true"
>
    <div class="modal-dialog">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-update-title">Update Stok Bahan</h2>
            <button
                type="button"
                id="modal-close-btn"
                class="modal-close"
                aria-label="Tutup modal"
            >
                &times;
            </button>
        </div>
        <div class="modal-body">
            <p id="modal-bahan-nama" class="modal-note"></p>

            <label for="input-stok-baru" class="form-label">
                Stok Baru <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <input
                type="number"
                id="input-stok-baru"
                min="0"
                step="1"
                placeholder="Masukkan jumlah stok baru"
                class="form-control"
            >

            <p id="modal-error-msg" role="alert" class="form-error hidden"></p>
        </div>
        <div class="modal-footer">
            <button
                type="button"
                id="modal-cancel-btn"
                class="btn btn--ghost btn--sm"
            >
                Batal
            </button>
            <button
                type="button"
                id="modal-confirm-btn"
                class="btn btn--primary btn--sm"
            >
                Simpan
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var modal        = document.getElementById('modal-update-stok');
    var modalNama    = document.getElementById('modal-bahan-nama');
    var inputStok    = document.getElementById('input-stok-baru');
    var closeBtn     = document.getElementById('modal-close-btn');
    var cancelBtn    = document.getElementById('modal-cancel-btn');
    var confirmBtn   = document.getElementById('modal-confirm-btn');
    var errorMsg     = document.getElementById('modal-error-msg');

    var currentId    = null;
    var csrfToken    = <?= json_encode($csrf_token) ?>;

    function openModal(id, nama, stok) {
        currentId = id;
        modalNama.textContent = 'Bahan: ' + nama;
        inputStok.value = stok;
        errorMsg.classList.add('hidden');
        errorMsg.textContent = '';
        modal.classList.add('modal-overlay--open');
        modal.setAttribute('aria-hidden', 'false');
        inputStok.focus();
    }

    function closeModal() {
        modal.classList.remove('modal-overlay--open');
        modal.setAttribute('aria-hidden', 'true');
        currentId = null;
        inputStok.value = '';
        errorMsg.classList.add('hidden');
        errorMsg.textContent = '';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-update-stok');
        if (!btn) return;
        openModal(
            btn.getAttribute('data-id'),
            btn.getAttribute('data-nama'),
            btn.getAttribute('data-stok')
        );
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('modal-overlay--open')) {
            closeModal();
        }
    });

    confirmBtn.addEventListener('click', function () {
        var stokBaru = inputStok.value.trim();

        if (stokBaru === '' || isNaN(parseInt(stokBaru, 10)) || parseInt(stokBaru, 10) < 0) {
            errorMsg.textContent = 'Stok baru harus berupa bilangan bulat >= 0.';
                errorMsg.classList.remove('hidden');
                inputStok.focus();
                return;
        fetch('/admin/ajax/update-stok.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token : csrfToken,
                id_bahan   : parseInt(currentId, 10),
                stok_baru  : parseInt(stokBaru, 10)
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                var nilaiEl = document.getElementById('stok-nilai-' + currentId);
                var row     = document.getElementById('bahan-row-' + currentId);
                var badge   = row ? row.querySelector('.badge') : null;

                if (nilaiEl) {
                    nilaiEl.textContent = data.stok_baru;
                }

                var updateBtn = row ? row.querySelector('.btn-update-stok') : null;
                if (updateBtn) {
                    updateBtn.setAttribute('data-stok', data.stok_baru);
                }

                if (data.is_kritis !== undefined && row) {
                    if (nilaiEl) {
                        nilaiEl.classList.remove('stok-count--kritis', 'stok-count--safe');
                    }

                    if (data.is_kritis) {
                        row.classList.add('row-kritis');
                        if (nilaiEl) nilaiEl.classList.add('stok-count--kritis');
                        if (badge) {
                            badge.className = 'badge badge-kritis';
                            badge.textContent = '⚠ Stok Kritis';
                        }
                    } else {
                        row.classList.remove('row-kritis');
                        if (nilaiEl) nilaiEl.classList.add('stok-count--safe');
                        if (badge) {
                            badge.className = 'badge badge-aktif';
                            badge.textContent = '✓ Aman';
                        }
                    }
                }

                closeModal();
            } else {
                errorMsg.textContent = data.message || 'Gagal memperbarui stok. Silakan coba lagi.';
                errorMsg.style.display = 'block';
            }
        })
        .catch(function () {
            errorMsg.textContent = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
            errorMsg.style.display = 'block';
        })
        .finally(function () {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Simpan';
        });
    });

}());
</script>

</body>
</html>
