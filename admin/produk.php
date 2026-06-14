<?php
/**
 * admin/produk.php
 *
 * Halaman manajemen produk untuk Admin WanFlorist.
 * Menangani tambah, edit, dan toggle status produk.
 *
 * Requirements: 10.1 – 10.7, 15.1 – 15.6
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo         = get_pdo();
$csrf_token  = generate_csrf();
$errors      = [];
$success_msg = '';

/* ============================================================
   Handler: POST action=tambah
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    // 1. Validasi CSRF
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $nama_produk  = trim($_POST['nama_produk']  ?? '');
        $harga_raw    = trim($_POST['harga']         ?? '');
        $id_kategori  = (int) ($_POST['id_kategori'] ?? 0);
        $deskripsi    = trim($_POST['deskripsi']     ?? '');
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $status       = in_array($_POST['status'] ?? '', ['tersedia', 'nonaktif'], true)
                        ? $_POST['status']
                        : 'tersedia';

        // 2. Validasi field teks
        if ($nama_produk === '') {
            $errors[] = 'Nama produk tidak boleh kosong.';
        } elseif (mb_strlen($nama_produk) > 200) {
            $errors[] = 'Nama produk maksimal 200 karakter.';
        }

        if ($harga_raw === '' || !ctype_digit($harga_raw) || (int) $harga_raw <= 0) {
            $errors[] = 'Harga harus berupa angka positif.';
        }

        if ($id_kategori <= 0) {
            $errors[] = 'Kategori harus dipilih.';
        } else {
            $stmtKat = $pdo->prepare('SELECT id_kategori FROM kategori WHERE id_kategori = :id LIMIT 1');
            $stmtKat->execute([':id' => $id_kategori]);
            if (!$stmtKat->fetch()) {
                $errors[] = 'Kategori tidak ditemukan.';
            }
        }

        // 3. Validasi file foto (wajib untuk produk baru)
        $filename = '';
        if (empty($_FILES['foto']['tmp_name']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Foto produk wajib diunggah.';
        } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah foto. Kode error: ' . (int) $_FILES['foto']['error'];
        } else {
            $fotoTmp  = $_FILES['foto']['tmp_name'];
            $fotoName = $_FILES['foto']['name'];
            $fotoSize = $_FILES['foto']['size'];

            // Validasi MIME via finfo (bukan $_FILES['type'])
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fotoTmp);
            finfo_close($finfo);

            $allowedMime = ['image/jpeg', 'image/png'];
            $ext         = strtolower(pathinfo($fotoName, PATHINFO_EXTENSION));
            $allowedExt  = ['jpg', 'jpeg', 'png'];

            if (!in_array($mimeType, $allowedMime, true)) {
                $errors[] = 'Foto harus berformat JPEG atau PNG.';
            } elseif (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Ekstensi file foto harus jpg, jpeg, atau png.';
            } elseif ($fotoSize > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran foto maksimal 2 MB.';
            } else {
                $filename = uniqid('produk_', true) . '.' . $ext;
            }
        }

        // 4. Simpan ke DB jika tidak ada error
        if (empty($errors)) {
            $destDir = __DIR__ . '/../assets/img/produk/';
            move_uploaded_file($_FILES['foto']['tmp_name'], $destDir . $filename);

            $stmt = $pdo->prepare(
                'INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, foto, status, is_featured)
                 VALUES (:id_kategori, :nama_produk, :deskripsi, :harga, :foto, :status, :is_featured)'
            );
            $stmt->execute([
                ':id_kategori' => $id_kategori,
                ':nama_produk' => $nama_produk,
                ':deskripsi'   => $deskripsi ?: null,
                ':harga'       => (int) $harga_raw,
                ':foto'        => $filename,
                ':status'      => $status,
                ':is_featured' => $is_featured,
            ]);

            $success_msg = 'Produk berhasil ditambahkan.';
            // Regenerate CSRF token after successful action
            $csrf_token = generate_csrf();
        }
    }
}

/* ============================================================
   Handler: POST action=edit
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    // 1. Validasi CSRF
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $id_produk    = (int) ($_POST['id_produk']    ?? 0);
        $nama_produk  = trim($_POST['nama_produk']    ?? '');
        $harga_raw    = trim($_POST['harga']           ?? '');
        $id_kategori  = (int) ($_POST['id_kategori']  ?? 0);
        $deskripsi    = trim($_POST['deskripsi']       ?? '');
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $status       = in_array($_POST['status'] ?? '', ['tersedia', 'nonaktif'], true)
                        ? $_POST['status']
                        : 'tersedia';

        // 2. Ambil data produk saat ini
        $stmtGet = $pdo->prepare('SELECT * FROM produk WHERE id_produk = :id LIMIT 1');
        $stmtGet->execute([':id' => $id_produk]);
        $currentProduk = $stmtGet->fetch();

        if (!$currentProduk) {
            $errors[] = 'Produk tidak ditemukan.';
        } else {
            // 3. Validasi field teks (sama seperti tambah)
            if ($nama_produk === '') {
                $errors[] = 'Nama produk tidak boleh kosong.';
            } elseif (mb_strlen($nama_produk) > 200) {
                $errors[] = 'Nama produk maksimal 200 karakter.';
            }

            if ($harga_raw === '' || !ctype_digit($harga_raw) || (int) $harga_raw <= 0) {
                $errors[] = 'Harga harus berupa angka positif.';
            }

            if ($id_kategori <= 0) {
                $errors[] = 'Kategori harus dipilih.';
            } else {
                $stmtKat = $pdo->prepare('SELECT id_kategori FROM kategori WHERE id_kategori = :id LIMIT 1');
                $stmtKat->execute([':id' => $id_kategori]);
                if (!$stmtKat->fetch()) {
                    $errors[] = 'Kategori tidak ditemukan.';
                }
            }

            // 4. Validasi foto baru jika diunggah
            $newFilename = '';
            $hasFoto     = !empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE;

            if ($hasFoto) {
                if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Gagal mengunggah foto. Kode error: ' . (int) $_FILES['foto']['error'];
                } else {
                    $fotoTmp  = $_FILES['foto']['tmp_name'];
                    $fotoName = $_FILES['foto']['name'];
                    $fotoSize = $_FILES['foto']['size'];

                    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fotoTmp);
                    finfo_close($finfo);

                    $allowedMime = ['image/jpeg', 'image/png'];
                    $ext         = strtolower(pathinfo($fotoName, PATHINFO_EXTENSION));
                    $allowedExt  = ['jpg', 'jpeg', 'png'];

                    if (!in_array($mimeType, $allowedMime, true)) {
                        $errors[] = 'Foto harus berformat JPEG atau PNG.';
                    } elseif (!in_array($ext, $allowedExt, true)) {
                        $errors[] = 'Ekstensi file foto harus jpg, jpeg, atau png.';
                    } elseif ($fotoSize > 2 * 1024 * 1024) {
                        $errors[] = 'Ukuran foto maksimal 2 MB.';
                    } else {
                        $newFilename = uniqid('produk_', true) . '.' . $ext;
                    }
                }
            }

            // 5. Update jika tidak ada error
            if (empty($errors)) {
                $finalFoto = $currentProduk['foto']; // pertahankan foto lama by default

                if ($hasFoto && $newFilename !== '') {
                    $destDir = __DIR__ . '/../assets/img/produk/';
                    move_uploaded_file($_FILES['foto']['tmp_name'], $destDir . $newFilename);
                    $finalFoto = $newFilename;

                    // Hapus file lama (kecuali placeholder)
                    $oldFoto    = $currentProduk['foto'];
                    $oldFotoPath = $destDir . $oldFoto;
                    if (
                        $oldFoto !== '' &&
                        $oldFoto !== 'placeholder.jpg' &&
                        file_exists($oldFotoPath)
                    ) {
                        unlink($oldFotoPath);
                    }
                }

                $stmtUpd = $pdo->prepare(
                    'UPDATE produk
                     SET id_kategori = :id_kategori,
                         nama_produk = :nama_produk,
                         deskripsi   = :deskripsi,
                         harga       = :harga,
                         foto        = :foto,
                         status      = :status,
                         is_featured = :is_featured
                     WHERE id_produk = :id_produk'
                );
                $stmtUpd->execute([
                    ':id_kategori' => $id_kategori,
                    ':nama_produk' => $nama_produk,
                    ':deskripsi'   => $deskripsi ?: null,
                    ':harga'       => (int) $harga_raw,
                    ':foto'        => $finalFoto,
                    ':status'      => $status,
                    ':is_featured' => $is_featured,
                    ':id_produk'   => $id_produk,
                ]);

                $success_msg = 'Produk berhasil diperbarui.';
                $csrf_token  = generate_csrf();
            }
        }
    }
}

/* ============================================================
   Handler: POST action=toggle_status
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {

    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $id_produk     = (int) ($_POST['id_produk']      ?? 0);
        $current_status = $_POST['current_status'] ?? '';

        $newStatus = ($current_status === 'tersedia') ? 'nonaktif' : 'tersedia';

        $stmtToggle = $pdo->prepare(
            'UPDATE produk SET status = :status WHERE id_produk = :id'
        );
        $stmtToggle->execute([':status' => $newStatus, ':id' => $id_produk]);

        $success_msg = 'Status produk diperbarui.';
        $csrf_token  = generate_csrf();
    }
}

/* ============================================================
   Query: Semua produk untuk tabel
   ============================================================ */
$stmtProduk = $pdo->query(
    'SELECT p.*, k.nama_kategori
     FROM produk p
     LEFT JOIN kategori k ON k.id_kategori = p.id_kategori
     ORDER BY p.created_at DESC'
);
$produkList = $stmtProduk->fetchAll();

/* ============================================================
   Query: Semua kategori aktif untuk dropdown form
   ============================================================ */
$stmtKategori = $pdo->query(
    'SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori'
);
$kategoriList = $stmtKategori->fetchAll();

/* ============================================================
   Variabel template
   ============================================================ */
$page_title  = 'Manajemen Produk';
$active_page = 'produk';
$css_extra   = '/assets/css/admin.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php require_once __DIR__ . '/../components/head.php'; ?>
<body>
<div class="admin-layout">

    <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

    <!-- ======================================================
         Konten Utama
         ====================================================== -->
    <main class="admin-main">
        <div class="admin-content">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-header__title">Manajemen Produk</h1>
                    <p class="page-header__subtitle">Kelola katalog bunga, harga, dan status produk.</p>
                </div>
                <div class="page-header__actions">
                    <button
                        type="button"
                        id="btn-show-tambah"
                        class="btn btn--primary"
                        aria-expanded="false"
                        aria-controls="panel-tambah"
                    >
                        + Tambah Produk
                    </button>
                </div>
            </div>

            <!-- Pesan Sukses -->
            <?php if ($success_msg !== ''): ?>
            <div class="alert alert--success" role="alert" style="
                background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;
                border-radius:12px;padding:12px 16px;margin-bottom:1.25rem;
                font-family:'Inter',sans-serif;font-size:.9rem;display:flex;
                align-items:center;gap:8px;">
                <span aria-hidden="true">✅</span>
                <?= e($success_msg) ?>
            </div>
            <?php endif; ?>

            <!-- Pesan Error -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert--error" role="alert" style="
                background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;
                border-radius:12px;padding:12px 16px;margin-bottom:1.25rem;
                font-family:'Inter',sans-serif;font-size:.9rem;">
                <strong>⚠️ Terjadi kesalahan:</strong>
                <ul style="margin:8px 0 0 16px;padding:0;">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- ============================================
                 Panel Tambah Produk (tersembunyi secara default)
                 ============================================ -->
            <div id="panel-tambah" class="admin-card" style="display:none;margin-bottom:1.5rem;" aria-hidden="true">
                <div class="admin-card__header">
                    <h2 class="admin-card__title">➕ Tambah Produk Baru</h2>
                    <button type="button" id="btn-tutup-tambah" class="btn btn--ghost btn--sm" aria-label="Tutup form tambah">✕ Tutup</button>
                </div>
                <div class="admin-card__body">
                    <form
                        method="POST"
                        action="/admin/produk.php"
                        enctype="multipart/form-data"
                        id="form-tambah"
                        novalidate
                    >
                        <input type="hidden" name="action"     value="tambah">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="form-grid">
                            <!-- Nama Produk -->
                            <div class="form-group">
                                <label class="form-label" for="tambah-nama_produk">Nama Produk <span class="req" aria-hidden="true">*</span></label>
                                <input
                                    type="text"
                                    id="tambah-nama_produk"
                                    name="nama_produk"
                                    class="form-input"
                                    maxlength="200"
                                    placeholder="Contoh: Buket Mawar Merah Premium"
                                    value="<?= e($_POST['nama_produk'] ?? '') ?>"
                                    required
                                >
                            </div>

                            <!-- Harga -->
                            <div class="form-group">
                                <label class="form-label" for="tambah-harga">Harga (Rp) <span class="req" aria-hidden="true">*</span></label>
                                <input
                                    type="number"
                                    id="tambah-harga"
                                    name="harga"
                                    class="form-input"
                                    min="1"
                                    placeholder="Contoh: 250000"
                                    value="<?= e($_POST['harga'] ?? '') ?>"
                                    required
                                >
                            </div>

                            <!-- Kategori -->
                            <div class="form-group">
                                <label class="form-label" for="tambah-id_kategori">Kategori <span class="req" aria-hidden="true">*</span></label>
                                <select id="tambah-id_kategori" name="id_kategori" class="form-input" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategoriList as $kat): ?>
                                    <option
                                        value="<?= e((string) $kat['id_kategori']) ?>"
                                        <?= (isset($_POST['id_kategori']) && (int) $_POST['id_kategori'] === (int) $kat['id_kategori']) ? 'selected' : '' ?>
                                    >
                                        <?= e($kat['nama_kategori']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Foto -->
                            <div class="form-group">
                                <label class="form-label" for="tambah-foto">Foto Produk <span class="req" aria-hidden="true">*</span></label>
                                <input
                                    type="file"
                                    id="tambah-foto"
                                    name="foto"
                                    class="form-input"
                                    accept="image/jpeg,image/png"
                                    required
                                >
                                <small class="form-hint">Format: JPG, JPEG, PNG. Ukuran maks: 2 MB.</small>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="form-label" for="tambah-status">Status</label>
                                <select id="tambah-status" name="status" class="form-input">
                                    <option value="tersedia" <?= (($_POST['status'] ?? 'tersedia') === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="nonaktif" <?= (($_POST['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>

                            <!-- Deskripsi (lebar penuh) -->
                            <div class="form-group form-group--full">
                                <label class="form-label" for="tambah-deskripsi">Deskripsi</label>
                                <textarea
                                    id="tambah-deskripsi"
                                    name="deskripsi"
                                    class="form-input"
                                    rows="4"
                                    placeholder="Tuliskan deskripsi produk..."
                                ><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                            </div>

                            <!-- Is Featured -->
                            <div class="form-group form-group--full">
                                <label class="form-label--inline" for="tambah-is_featured">
                                    <input
                                        type="checkbox"
                                        id="tambah-is_featured"
                                        name="is_featured"
                                        value="1"
                                        <?= isset($_POST['is_featured']) ? 'checked' : '' ?>
                                        style="margin-right:8px;width:16px;height:16px;vertical-align:middle;"
                                    >
                                    Tampilkan sebagai produk unggulan di beranda
                                </label>
                            </div>
                        </div><!-- /.form-grid -->

                        <div style="margin-top:1.25rem;display:flex;gap:.75rem;flex-wrap:wrap;">
                            <button type="submit" class="btn btn--primary">💾 Simpan Produk</button>
                            <button type="reset"  class="btn btn--ghost">🔄 Reset</button>
                        </div>
                    </form>
                </div>
            </div><!-- /#panel-tambah -->

            <!-- ============================================
                 Panel Edit Produk (tersembunyi secara default)
                 ============================================ -->
            <div id="panel-edit" class="admin-card" style="display:none;margin-bottom:1.5rem;" aria-hidden="true">
                <div class="admin-card__header">
                    <h2 class="admin-card__title">✏️ Edit Produk</h2>
                    <button type="button" id="btn-tutup-edit" class="btn btn--ghost btn--sm" aria-label="Tutup form edit">✕ Tutup</button>
                </div>
                <div class="admin-card__body">
                    <form
                        method="POST"
                        action="/admin/produk.php"
                        enctype="multipart/form-data"
                        id="form-edit"
                        novalidate
                    >
                        <input type="hidden" name="action"     value="edit">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                        <input type="hidden" name="id_produk"  id="edit-id_produk" value="">

                        <div class="form-grid">
                            <!-- Nama Produk -->
                            <div class="form-group">
                                <label class="form-label" for="edit-nama_produk">Nama Produk <span class="req" aria-hidden="true">*</span></label>
                                <input
                                    type="text"
                                    id="edit-nama_produk"
                                    name="nama_produk"
                                    class="form-input"
                                    maxlength="200"
                                    required
                                >
                            </div>

                            <!-- Harga -->
                            <div class="form-group">
                                <label class="form-label" for="edit-harga">Harga (Rp) <span class="req" aria-hidden="true">*</span></label>
                                <input
                                    type="number"
                                    id="edit-harga"
                                    name="harga"
                                    class="form-input"
                                    min="1"
                                    required
                                >
                            </div>

                            <!-- Kategori -->
                            <div class="form-group">
                                <label class="form-label" for="edit-id_kategori">Kategori <span class="req" aria-hidden="true">*</span></label>
                                <select id="edit-id_kategori" name="id_kategori" class="form-input" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?= e((string) $kat['id_kategori']) ?>">
                                        <?= e($kat['nama_kategori']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Foto (opsional saat edit) -->
                            <div class="form-group">
                                <label class="form-label" for="edit-foto">Ganti Foto Produk</label>
                                <div id="edit-foto-preview" style="margin-bottom:8px;"></div>
                                <input
                                    type="file"
                                    id="edit-foto"
                                    name="foto"
                                    class="form-input"
                                    accept="image/jpeg,image/png"
                                >
                                <small class="form-hint">Kosongkan jika tidak ingin mengganti foto. Format: JPG, JPEG, PNG. Maks 2 MB.</small>
                            </div>

                            <!-- Status -->
                            <div class="form-group">
                                <label class="form-label" for="edit-status">Status</label>
                                <select id="edit-status" name="status" class="form-input">
                                    <option value="tersedia">Tersedia</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>

                            <!-- Deskripsi -->
                            <div class="form-group form-group--full">
                                <label class="form-label" for="edit-deskripsi">Deskripsi</label>
                                <textarea
                                    id="edit-deskripsi"
                                    name="deskripsi"
                                    class="form-input"
                                    rows="4"
                                ></textarea>
                            </div>

                            <!-- Is Featured -->
                            <div class="form-group form-group--full">
                                <label class="form-label--inline" for="edit-is_featured">
                                    <input
                                        type="checkbox"
                                        id="edit-is_featured"
                                        name="is_featured"
                                        value="1"
                                        style="margin-right:8px;width:16px;height:16px;vertical-align:middle;"
                                    >
                                    Tampilkan sebagai produk unggulan di beranda
                                </label>
                            </div>
                        </div><!-- /.form-grid -->

                        <div style="margin-top:1.25rem;display:flex;gap:.75rem;flex-wrap:wrap;">
                            <button type="submit" class="btn btn--primary">💾 Perbarui Produk</button>
                            <button type="button" id="btn-tutup-edit-2" class="btn btn--ghost">Batal</button>
                        </div>
                    </form>
                </div>
            </div><!-- /#panel-edit -->

            <!-- ============================================
                 Tabel Produk
                 ============================================ -->
            <div class="admin-card">
                <div class="admin-card__header">
                    <h2 class="admin-card__title">📦 Daftar Produk (<?= count($produkList) ?> produk)</h2>
                </div>
                <div class="table-responsive">
                    <?php if (empty($produkList)): ?>
                    <div class="admin-empty">
                        <div class="admin-empty__icon">🌸</div>
                        <p class="admin-empty__title">Belum ada produk</p>
                        <p class="admin-empty__message">Klik "Tambah Produk" untuk menambahkan produk pertama Anda.</p>
                    </div>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Unggulan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produkList as $p): ?>
                            <tr>
                                <!-- Foto thumbnail -->
                                <td>
                                    <img
                                        src="/assets/img/produk/<?= e($p['foto'] ?: 'placeholder.jpg') ?>"
                                        alt="Foto <?= e($p['nama_produk']) ?>"
                                        width="48"
                                        height="48"
                                        class="table-thumbnail"
                                        loading="lazy"
                                        onerror="this.src='/assets/img/placeholder.jpg'"
                                    >
                                </td>

                                <!-- Nama -->
                                <td>
                                    <strong><?= e($p['nama_produk']) ?></strong>
                                    <?php if (!empty($p['deskripsi'])): ?>
                                    <br><small style="color:#9CA3AF;font-size:.75rem;"><?= e(mb_substr($p['deskripsi'], 0, 60)) ?><?= mb_strlen((string)$p['deskripsi']) > 60 ? '…' : '' ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- Kategori -->
                                <td><?= e($p['nama_kategori'] ?? '—') ?></td>

                                <!-- Harga -->
                                <td style="white-space:nowrap;font-weight:600;color:#6B21A8;">
                                    <?= format_rupiah((int) $p['harga']) ?>
                                </td>

                                <!-- Status badge -->
                                <td>
                                    <?php if ($p['status'] === 'tersedia'): ?>
                                    <span class="badge badge-aktif">Tersedia</span>
                                    <?php else: ?>
                                    <span class="badge badge-nonaktif">Nonaktif</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Is Featured -->
                                <td style="text-align:center;">
                                    <?= $p['is_featured'] ? '⭐' : '—' ?>
                                </td>

                                <!-- Aksi -->
                                <td>
                                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                                        <!-- Tombol Edit -->
                                        <button
                                            type="button"
                                            class="btn btn--ghost btn--sm btn-edit-produk"
                                            data-id="<?= e((string) $p['id_produk']) ?>"
                                            data-nama="<?= e($p['nama_produk']) ?>"
                                            data-harga="<?= e((string) (int) $p['harga']) ?>"
                                            data-kategori="<?= e((string) $p['id_kategori']) ?>"
                                            data-deskripsi="<?= e($p['deskripsi'] ?? '') ?>"
                                            data-status="<?= e($p['status']) ?>"
                                            data-featured="<?= e((string) $p['is_featured']) ?>"
                                            data-foto="<?= e($p['foto'] ?? '') ?>"
                                            aria-label="Edit produk <?= e($p['nama_produk']) ?>"
                                        >
                                            ✏️ Edit
                                        </button>

                                        <!-- Toggle Status -->
                                        <form method="POST" action="/admin/produk.php" style="margin:0;">
                                            <input type="hidden" name="action"         value="toggle_status">
                                            <input type="hidden" name="csrf_token"     value="<?= e($csrf_token) ?>">
                                            <input type="hidden" name="id_produk"      value="<?= e((string) $p['id_produk']) ?>">
                                            <input type="hidden" name="current_status" value="<?= e($p['status']) ?>">
                                            <button
                                                type="submit"
                                                class="btn btn--sm <?= $p['status'] === 'tersedia' ? 'btn--danger-ghost' : 'btn--success-ghost' ?>"
                                                aria-label="<?= $p['status'] === 'tersedia' ? 'Nonaktifkan' : 'Aktifkan' ?> produk <?= e($p['nama_produk']) ?>"
                                            >
                                                <?= $p['status'] === 'tersedia' ? '🚫 Nonaktifkan' : '✅ Aktifkan' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div><!-- /.table-responsive -->
            </div><!-- /.admin-card -->

        </div><!-- /.admin-content -->
    </main>

</div><!-- /.admin-layout -->

<!-- ============================================================
     Inline CSS — form grid + button variants + form helpers
     ============================================================ -->
<style>
/* Form grid: 2 kolom di desktop, 1 di mobile */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem 1.25rem;
}
.form-group--full {
    grid-column: 1 / -1;
}
@media (max-width: 640px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-label {
    display: block;
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: .375rem;
}
.form-label--inline {
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    color: #374151;
    cursor: pointer;
    display: flex;
    align-items: center;
}
.form-label .req { color: #DC2626; }

.form-input {
    display: block;
    width: 100%;
    padding: .5rem .75rem;
    border: 1.5px solid #D1D5DB;
    border-radius: 9999px;
    font-family: 'Inter', sans-serif;
    font-size: .9rem;
    color: #1F2937;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
}
.form-input:focus {
    outline: none;
    border-color: #9333EA;
    box-shadow: 0 0 0 3px rgba(147,51,234,.12);
}
textarea.form-input {
    border-radius: 12px;
    resize: vertical;
}
select.form-input {
    border-radius: 9999px;
    cursor: pointer;
}
input[type="file"].form-input {
    border-radius: 12px;
    padding: .375rem .75rem;
    cursor: pointer;
}

.form-hint {
    font-family: 'Inter', sans-serif;
    font-size: .75rem;
    color: #9CA3AF;
    margin-top: .25rem;
    display: block;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    padding: .5rem 1.125rem;
    border-radius: 9999px;
    font-family: 'Inter', sans-serif;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: background-color .15s, color .15s, border-color .15s, box-shadow .15s;
    text-decoration: none;
    line-height: 1.4;
    white-space: nowrap;
}
.btn--primary {
    background: #6B21A8;
    color: #fff;
    border-color: #6B21A8;
}
.btn--primary:hover, .btn--primary:focus {
    background: #5B1A90;
    border-color: #5B1A90;
}
.btn--ghost {
    background: transparent;
    color: #6B7280;
    border-color: #D1D5DB;
}
.btn--ghost:hover, .btn--ghost:focus {
    background: #F3F4F6;
    color: #374151;
    border-color: #9CA3AF;
}
.btn--danger-ghost {
    background: transparent;
    color: #DC2626;
    border-color: #FCA5A5;
}
.btn--danger-ghost:hover, .btn--danger-ghost:focus {
    background: #FEE2E2;
}
.btn--success-ghost {
    background: transparent;
    color: #16A34A;
    border-color: #86EFAC;
}
.btn--success-ghost:hover, .btn--success-ghost:focus {
    background: #DCFCE7;
}
.btn--sm {
    padding: .325rem .75rem;
    font-size: .8rem;
}
</style>

<!-- ============================================================
     Vanilla JS — toggle panel tambah/edit, pre-fill form edit
     ============================================================ -->
<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ---- Referensi elemen ---- */
        var btnShowTambah  = document.getElementById('btn-show-tambah');
        var panelTambah    = document.getElementById('panel-tambah');
        var btnTutupTambah = document.getElementById('btn-tutup-tambah');

        var panelEdit      = document.getElementById('panel-edit');
        var btnTutupEdit   = document.getElementById('btn-tutup-edit');
        var btnTutupEdit2  = document.getElementById('btn-tutup-edit-2');

        /* ---- Helper: tampilkan panel ---- */
        function showPanel(panel, btn) {
            panel.style.display = 'block';
            panel.setAttribute('aria-hidden', 'false');
            if (btn) btn.setAttribute('aria-expanded', 'true');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function hidePanel(panel, btn) {
            panel.style.display = 'none';
            panel.setAttribute('aria-hidden', 'true');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        /* ---- Tambah produk ---- */
        if (btnShowTambah && panelTambah) {
            btnShowTambah.addEventListener('click', function () {
                var isOpen = panelTambah.style.display !== 'none';
                if (isOpen) {
                    hidePanel(panelTambah, btnShowTambah);
                } else {
                    hidePanel(panelEdit, null);
                    showPanel(panelTambah, btnShowTambah);
                }
            });
        }

        if (btnTutupTambah && panelTambah) {
            btnTutupTambah.addEventListener('click', function () {
                hidePanel(panelTambah, btnShowTambah);
            });
        }

        /* ---- Tutup form edit ---- */
        function tutupEdit() {
            hidePanel(panelEdit, null);
        }
        if (btnTutupEdit)  btnTutupEdit.addEventListener('click', tutupEdit);
        if (btnTutupEdit2) btnTutupEdit2.addEventListener('click', tutupEdit);

        /* ---- Tombol Edit: isi ulang form edit ---- */
        var editButtons = document.querySelectorAll('.btn-edit-produk');
        editButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id        = btn.getAttribute('data-id');
                var nama      = btn.getAttribute('data-nama');
                var harga     = btn.getAttribute('data-harga');
                var kategori  = btn.getAttribute('data-kategori');
                var deskripsi = btn.getAttribute('data-deskripsi');
                var status    = btn.getAttribute('data-status');
                var featured  = btn.getAttribute('data-featured');
                var foto      = btn.getAttribute('data-foto');

                /* Pre-fill hidden & text fields */
                document.getElementById('edit-id_produk').value  = id;
                document.getElementById('edit-nama_produk').value = nama;
                document.getElementById('edit-harga').value      = harga;
                document.getElementById('edit-deskripsi').value  = deskripsi;

                /* Pilih kategori */
                var selKat = document.getElementById('edit-id_kategori');
                for (var i = 0; i < selKat.options.length; i++) {
                    selKat.options[i].selected = (selKat.options[i].value === kategori);
                }

                /* Pilih status */
                var selStatus = document.getElementById('edit-status');
                for (var j = 0; j < selStatus.options.length; j++) {
                    selStatus.options[j].selected = (selStatus.options[j].value === status);
                }

                /* Checkbox is_featured */
                document.getElementById('edit-is_featured').checked = (featured === '1');

                /* Tampilkan preview foto saat ini */
                var previewDiv = document.getElementById('edit-foto-preview');
                if (foto) {
                    previewDiv.innerHTML =
                        '<img src="/assets/img/produk/' + encodeURIComponent(foto) + '"'
                        + ' alt="Foto saat ini" width="64" height="64"'
                        + ' style="object-fit:cover;border-radius:8px;border:1px solid #E5E7EB;"'
                        + ' onerror="this.src=\'/assets/img/placeholder.jpg\'">'
                        + '<small style="display:block;color:#9CA3AF;margin-top:4px;font-size:.75rem;">Foto saat ini</small>';
                } else {
                    previewDiv.innerHTML = '';
                }

                /* Sembunyikan panel tambah, tampilkan panel edit */
                hidePanel(panelTambah, btnShowTambah);
                showPanel(panelEdit, null);
            });
        });

        /* ---- Jika ada error POST, tampilkan kembali panel yang relevan ---- */
        var actionInput = document.querySelector('input[name="action"]');
        if (actionInput && document.querySelector('.alert--error')) {
            var act = actionInput.value;
            if (act === 'tambah' && panelTambah) {
                showPanel(panelTambah, btnShowTambah);
            } else if (act === 'edit' && panelEdit) {
                showPanel(panelEdit, null);
            }
        }

    });
}());
</script>

</body>
</html>
