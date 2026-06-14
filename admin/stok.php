<?php
/**
 * admin/stok.php
 *
 * Halaman manajemen stok bahan WanFlorist.
 * Fitur: tambah bahan baru, lihat semua bahan, update stok via AJAX.
 *
 * Requirements: 16.1, 16.4
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo         = get_pdo();
$csrf_token  = generate_csrf();
$errors      = [];
$success_msg = '';

// ============================================================
// POST handler: tambah_bahan
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah_bahan') {

    // 1. Validasi CSRF
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {

        // 2. Validasi input
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

        // 3. INSERT jika tidak ada error
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

            // Regenerate CSRF token setelah POST sukses
            $csrf_token = generate_csrf();
        }
    }
}

// ============================================================
// Query: semua bahan
// ============================================================
$stmt_bahan = $pdo->query('SELECT * FROM stok_bahan ORDER BY nama_bahan ASC');
$list_bahan = $stmt_bahan->fetchAll();

// ============================================================
// Query: jumlah item stok kritis
// ============================================================
$stmt_kritis  = $pdo->query('SELECT COUNT(*) FROM stok_bahan WHERE stok_saat_ini < stok_minimum');
$jumlah_kritis = (int) $stmt_kritis->fetchColumn();

// ============================================================
// Setup variabel template
// ============================================================
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

    <!-- ============================================================
         Konten utama
         ============================================================ -->
    <main class="admin-main" id="admin-main-content">

        <!-- Header halaman -->
        <div class="admin-header">
            <h1 class="admin-header__title">🌿 Stok Bahan</h1>
        </div>

        <div class="admin-content">

            <!-- ── Summary card: stok kritis ─────────────────────── -->
            <div class="stat-cards" style="grid-template-columns: repeat(1, minmax(0, 320px));">
                <div class="stat-card <?= $jumlah_kritis > 0 ? 'stat-card--danger' : 'stat-card--success' ?>">
                    <div class="stat-card__header">
                        <div>
                            <div class="stat-card__value"><?= e((string) $jumlah_kritis) ?></div>
                            <div class="stat-card__label">item stok kritis</div>
                        </div>
                        <span class="stat-card__icon" aria-hidden="true">
                            <?= $jumlah_kritis > 0 ? '⚠️' : '✅' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Pesan sukses / error ───────────────────────────── -->
            <?php if ($success_msg !== ''): ?>
                <div class="alert alert-success" role="alert" style="
                    background:#D1FAE5; color:#065F46; border:1px solid #6EE7B7;
                    border-radius:8px; padding:0.875rem 1rem; margin-bottom:1.25rem;
                    font-family:'Inter',sans-serif; font-size:0.9rem;
                ">
                    ✅ <?= e($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert" style="
                    background:#FEE2E2; color:#991B1B; border:1px solid #FECACA;
                    border-radius:8px; padding:0.875rem 1rem; margin-bottom:1.25rem;
                    font-family:'Inter',sans-serif; font-size:0.9rem;
                ">
                    <strong>Terjadi kesalahan:</strong>
                    <ul style="margin:0.5rem 0 0 1.25rem; padding:0;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- ── Form tambah bahan ──────────────────────────────── -->
            <div class="admin-card" style="margin-bottom:1.5rem;">
                <div class="admin-card__header">
                    <h2 class="admin-card__title">➕ Tambah Bahan Baru</h2>
                </div>
                <div class="admin-card__body">
                    <form method="POST" action="/admin/stok.php" novalidate>
                        <input type="hidden" name="action" value="tambah_bahan">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">

                            <!-- Nama Bahan -->
                            <div>
                                <label for="nama_bahan" style="display:block; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:0.375rem;">
                                    Nama Bahan <span style="color:#DC2626;" aria-hidden="true">*</span>
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
                                    style="width:100%; padding:0.625rem 0.875rem; border:1.5px solid #D1D5DB; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.9rem; color:#1F2937; box-sizing:border-box;"
                                >
                            </div>

                            <!-- Satuan -->
                            <div>
                                <label for="satuan" style="display:block; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:0.375rem;">
                                    Satuan <span style="color:#DC2626;" aria-hidden="true">*</span>
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
                                    style="width:100%; padding:0.625rem 0.875rem; border:1.5px solid #D1D5DB; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.9rem; color:#1F2937; box-sizing:border-box;"
                                >
                            </div>

                            <!-- Stok Awal -->
                            <div>
                                <label for="stok_awal" style="display:block; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:0.375rem;">
                                    Stok Awal <span style="color:#DC2626;" aria-hidden="true">*</span>
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
                                    style="width:100%; padding:0.625rem 0.875rem; border:1.5px solid #D1D5DB; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.9rem; color:#1F2937; box-sizing:border-box;"
                                >
                            </div>

                            <!-- Stok Minimum -->
                            <div>
                                <label for="stok_minimum" style="display:block; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:0.375rem;">
                                    Stok Minimum <span style="color:#DC2626;" aria-hidden="true">*</span>
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
                                    style="width:100%; padding:0.625rem 0.875rem; border:1.5px solid #D1D5DB; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.9rem; color:#1F2937; box-sizing:border-box;"
                                >
                            </div>

                        </div>

                        <div style="text-align:right;">
                            <button type="submit" class="btn btn-primary" style="
                                background:#6B21A8; color:#fff; border:none; border-radius:8px;
                                padding:0.625rem 1.5rem; font-family:'Inter',sans-serif;
                                font-size:0.9rem; font-weight:600; cursor:pointer;
                                transition:background-color 0.15s ease;
                            ">
                                Tambah Bahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Tabel daftar bahan ─────────────────────────────── -->
            <div class="admin-card">
                <div class="admin-card__header">
                    <h2 class="admin-card__title">📋 Daftar Bahan</h2>
                    <span style="font-family:'Inter',sans-serif; font-size:0.875rem; color:#6B7280;">
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
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:2rem; color:#9CA3AF;">
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
                                        <td style="font-weight:500;"><?= e($bahan['nama_bahan']) ?></td>
                                        <td><?= e($bahan['satuan']) ?></td>
                                        <td>
                                            <span
                                                id="stok-nilai-<?= e((string)$bahan['id_bahan']) ?>"
                                                style="font-weight:600; color:<?= $is_kritis ? '#DC2626' : '#065F46' ?>;"
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
                                                class="btn-update-stok"
                                                data-id="<?= e((string)$bahan['id_bahan']) ?>"
                                                data-nama="<?= e($bahan['nama_bahan']) ?>"
                                                data-stok="<?= e((string)$bahan['stok_saat_ini']) ?>"
                                                aria-label="Update stok <?= e($bahan['nama_bahan']) ?>"
                                                style="
                                                    background:#6B21A8; color:#fff; border:none;
                                                    border-radius:6px; padding:0.4rem 0.875rem;
                                                    font-family:'Inter',sans-serif; font-size:0.8125rem;
                                                    font-weight:600; cursor:pointer;
                                                    transition:background-color 0.15s ease;
                                                "
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

        </div><!-- /.admin-content -->
    </main>
</div><!-- /.admin-layout -->

<!-- ============================================================
     Modal: Update Stok
     ============================================================ -->
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
            <p id="modal-bahan-nama" style="font-family:'Inter',sans-serif; font-size:0.9rem; color:#6B7280; margin-bottom:1rem;"></p>

            <label for="input-stok-baru" style="display:block; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:0.375rem;">
                Stok Baru <span style="color:#DC2626;" aria-hidden="true">*</span>
            </label>
            <input
                type="number"
                id="input-stok-baru"
                min="0"
                step="1"
                placeholder="Masukkan jumlah stok baru"
                style="
                    width:100%; padding:0.625rem 0.875rem;
                    border:1.5px solid #D1D5DB; border-radius:8px;
                    font-family:'Inter',sans-serif; font-size:0.9rem;
                    color:#1F2937; box-sizing:border-box;
                "
            >

            <p id="modal-error-msg" role="alert" style="
                display:none; margin-top:0.5rem;
                font-family:'Inter',sans-serif; font-size:0.8125rem;
                color:#DC2626;
            "></p>
        </div>
        <div class="modal-footer">
            <button
                type="button"
                id="modal-cancel-btn"
                style="
                    background:#F3F4F6; color:#374151; border:none; border-radius:8px;
                    padding:0.625rem 1.25rem; font-family:'Inter',sans-serif;
                    font-size:0.9rem; font-weight:500; cursor:pointer;
                "
            >
                Batal
            </button>
            <button
                type="button"
                id="modal-confirm-btn"
                style="
                    background:#6B21A8; color:#fff; border:none; border-radius:8px;
                    padding:0.625rem 1.5rem; font-family:'Inter',sans-serif;
                    font-size:0.9rem; font-weight:600; cursor:pointer;
                    transition:background-color 0.15s ease;
                "
            >
                Simpan
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     Inline JavaScript — addEventListener only (no onclick="")
     ============================================================ -->
<script>
(function () {
    'use strict';

    // ── Referensi elemen ────────────────────────────────────────
    var modal        = document.getElementById('modal-update-stok');
    var modalNama    = document.getElementById('modal-bahan-nama');
    var inputStok    = document.getElementById('input-stok-baru');
    var closeBtn     = document.getElementById('modal-close-btn');
    var cancelBtn    = document.getElementById('modal-cancel-btn');
    var confirmBtn   = document.getElementById('modal-confirm-btn');
    var errorMsg     = document.getElementById('modal-error-msg');

    // ── State modal ─────────────────────────────────────────────
    var currentId    = null;
    var csrfToken    = <?= json_encode($csrf_token) ?>;

    // ── Fungsi buka modal ───────────────────────────────────────
    function openModal(id, nama, stok) {
        currentId = id;
        modalNama.textContent = 'Bahan: ' + nama;
        inputStok.value = stok;
        errorMsg.style.display = 'none';
        errorMsg.textContent = '';
        modal.classList.add('modal-overlay--open');
        modal.setAttribute('aria-hidden', 'false');
        inputStok.focus();
    }

    // ── Fungsi tutup modal ──────────────────────────────────────
    function closeModal() {
        modal.classList.remove('modal-overlay--open');
        modal.setAttribute('aria-hidden', 'true');
        currentId = null;
        inputStok.value = '';
        errorMsg.style.display = 'none';
        errorMsg.textContent = '';
    }

    // ── Buka modal saat tombol "Update Stok" diklik ─────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-update-stok');
        if (!btn) return;
        openModal(
            btn.getAttribute('data-id'),
            btn.getAttribute('data-nama'),
            btn.getAttribute('data-stok')
        );
    });

    // ── Tutup via tombol × dan Batal ───────────────────────────
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // ── Tutup via klik overlay (di luar dialog) ─────────────────
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // ── Tutup via Escape ────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('modal-overlay--open')) {
            closeModal();
        }
    });

    // ── Submit via fetch() (AJAX) ───────────────────────────────
    confirmBtn.addEventListener('click', function () {
        var stokBaru = inputStok.value.trim();

        // Validasi sisi klien
        if (stokBaru === '' || isNaN(parseInt(stokBaru, 10)) || parseInt(stokBaru, 10) < 0) {
            errorMsg.textContent = 'Stok baru harus berupa bilangan bulat >= 0.';
            errorMsg.style.display = 'block';
            inputStok.focus();
            return;
        }

        // Nonaktifkan tombol sementara
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Menyimpan…';

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
                // ── Update nilai di DOM ──────────────────────
                var nilaiEl = document.getElementById('stok-nilai-' + currentId);
                var row     = document.getElementById('bahan-row-' + currentId);
                var badge   = row ? row.querySelector('.badge') : null;

                if (nilaiEl) {
                    nilaiEl.textContent = data.stok_baru;
                }

                // Perbarui data-stok pada tombol
                var updateBtn = row ? row.querySelector('.btn-update-stok') : null;
                if (updateBtn) {
                    updateBtn.setAttribute('data-stok', data.stok_baru);
                }

                // Tandai/hapus kritis berdasarkan nilai baru
                if (data.is_kritis !== undefined && row) {
                    if (data.is_kritis) {
                        row.classList.add('row-kritis');
                        if (nilaiEl) nilaiEl.style.color = '#DC2626';
                        if (badge) {
                            badge.className = 'badge badge-kritis';
                            badge.textContent = '⚠ Stok Kritis';
                        }
                    } else {
                        row.classList.remove('row-kritis');
                        if (nilaiEl) nilaiEl.style.color = '#065F46';
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
