<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$pdo         = get_pdo();
$csrf_token  = generate_csrf();
$errors      = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

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
            $csrf_token = generate_csrf();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

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

        $stmtGet = $pdo->prepare('SELECT * FROM produk WHERE id_produk = :id LIMIT 1');
        $stmtGet->execute([':id' => $id_produk]);
        $currentProduk = $stmtGet->fetch();

        if (!$currentProduk) {
            $errors[] = 'Produk tidak ditemukan.';
        } else {
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

            if (empty($errors)) {
                $finalFoto = $currentProduk['foto']; // pertahankan foto lama by default

                if ($hasFoto && $newFilename !== '') {
                    $destDir = __DIR__ . '/../assets/img/produk/';
                    move_uploaded_file($_FILES['foto']['tmp_name'], $destDir . $newFilename);
                    $finalFoto = $newFilename;

                    // Hapus file upload lama (bukan foto seed bawaan)
                    $oldFoto    = $currentProduk['foto'];
                    $oldFotoPath = $destDir . $oldFoto;
                    if (
                        $oldFoto !== '' &&
                        str_starts_with($oldFoto, 'produk_') &&
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

$stmtProduk = $pdo->query(
    'SELECT p.*, k.nama_kategori
     FROM produk p
     LEFT JOIN kategori k ON k.id_kategori = p.id_kategori
     ORDER BY p.created_at DESC'
);
$produkList = $stmtProduk->fetchAll();

$stmtKategori = $pdo->query(
    'SELECT id_kategori, nama_kategori FROM kategori WHERE is_active = 1 ORDER BY nama_kategori'
);
$kategoriList = $stmtKategori->fetchAll();

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

    <main class="admin-main">
        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h1 class="page-header__title">Manajemen Produk</h1>
                    <p class="page-header__subtitle">Kelola katalog bunga, harga, dan status produk.</p>
                </div>
                <div class="page-header__actions">
                    <div class="page-header__action-card">
                        <button
                            type="button"
                            id="btn-show-tambah"
                            class="btn btn--primary"
                            aria-expanded="false"
                            aria-controls="panel-tambah"
                        >
                            <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Produk
                        </button>
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
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Terjadi kesalahan:</strong>
                <ul class="alert-list">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div id="panel-tambah" class="admin-card hidden" aria-hidden="true">
                <div class="admin-card__header">
                    <h2 class="admin-card__title"><i class="bi bi-plus-lg"></i> Tambah Produk Baru</h2>
                    <button type="button" id="btn-tutup-tambah" class="btn btn--ghost btn--sm" aria-label="Tutup form tambah"><i class="bi bi-x"></i> Tutup</button>
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

                            <div class="form-group">
                                <label class="form-label" for="tambah-status">Status</label>
                                <select id="tambah-status" name="status" class="form-input">
                                    <option value="tersedia" <?= (($_POST['status'] ?? 'tersedia') === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="nonaktif" <?= (($_POST['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>

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
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary"><i class="bi bi-save"></i> Simpan Produk</button>
                            <button type="reset" class="btn btn--ghost"><i class="bi bi-arrow-repeat"></i> Reset</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="panel-edit" class="admin-card hidden" aria-hidden="true">
                <div class="admin-card__header">
                    <h2 class="admin-card__title"><i class="bi bi-pencil"></i> Edit Produk</h2>
                    <button type="button" id="btn-tutup-edit" class="btn btn--ghost btn--sm" aria-label="Tutup form edit"><i class="bi bi-x"></i> Tutup</button>
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

                            <div class="form-group">
                                <label class="form-label" for="edit-foto">Ganti Foto Produk</label>
                                <div id="edit-foto-preview" class="preview-box"></div>
                                <input
                                    type="file"
                                    id="edit-foto"
                                    name="foto"
                                    class="form-input"
                                    accept="image/jpeg,image/png"
                                >
                                <small class="form-hint">Kosongkan jika tidak ingin mengganti foto. Format: JPG, JPEG, PNG. Maks 2 MB.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="edit-status">Status</label>
                                <select id="edit-status" name="status" class="form-input">
                                    <option value="tersedia">Tersedia</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>

                            <div class="form-group form-group--full">
                                <label class="form-label" for="edit-deskripsi">Deskripsi</label>
                                <textarea
                                    id="edit-deskripsi"
                                    name="deskripsi"
                                    class="form-input"
                                    rows="4"
                                ></textarea>
                            </div>

                            <div class="form-group form-group--full">
                                <label class="form-label--inline" for="edit-is_featured">
                                    <input
                                        type="checkbox"
                                        id="edit-is_featured"
                                        name="is_featured"
                                        value="1"
                                        class="form-checkbox"
                                    >
                                    Tampilkan sebagai produk unggulan di beranda
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary"><i class="bi bi-save"></i> Perbarui Produk</button>
                            <button type="button" id="btn-tutup-edit-2" class="btn btn--ghost">Batal</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card__header">
                    <h2 class="admin-card__title"><i class="bi bi-box-seam"></i> Daftar Produk (<?= count($produkList) ?> produk)</h2>
                </div>
                <div class="table-responsive">
                    <?php if (empty($produkList)): ?>
                    <div class="admin-empty">
                        <div class="admin-empty__icon"><i class="bi bi-flower1"></i></div>
                        <p class="admin-empty__title">Belum ada produk</p>
                        <p class="admin-empty__message">Klik "Tambah Produk" untuk menambahkan produk pertama Anda.</p>
                    </div>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="table-col--photo">Foto</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th class="text-center table-col--status">Status</th>
                                <th class="text-center table-col--actions">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produkList as $p): ?>
                            <tr>
                                <td>
                                    <img
                                        src="<?= e(produk_foto_src($p['foto'] ?? null, '/')) ?>"
                                        alt="Foto <?= e($p['nama_produk']) ?>"
                                        width="48"
                                        height="48"
                                        class="table-thumbnail"
                                        loading="lazy"
                                        onerror="this.src='<?= e(produk_foto_src(null, '/')) ?>'"
                                    >
                                </td>

                                <td>
                                    <strong><?= e($p['nama_produk']) ?></strong>
                                    <?php if (!empty($p['deskripsi'])): ?>
                                    <br><small class="text-muted"><?= e(mb_substr($p['deskripsi'], 0, 60)) ?><?= mb_strlen((string)$p['deskripsi']) > 60 ? '…' : '' ?></small>
                                    <?php endif; ?>
                                </td>

                                <td><?= e($p['nama_kategori'] ?? '—') ?></td>

                                <td><?= format_rupiah((int) $p['harga']) ?></td>

                                <td class="text-center">
                                    <span class="badge <?= $p['status'] === 'tersedia' ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                        <?= $p['status'] === 'tersedia' ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="admin-action-row">
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
                                            <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn--ghost btn--sm btn-toggle-status"
                                            data-id="<?= e((string) $p['id_produk']) ?>"
                                            aria-label="<?= e($p['status'] === 'tersedia' ? 'Nonaktifkan produk ' . $p['nama_produk'] : 'Aktifkan produk ' . $p['nama_produk']) ?>"
                                        >
                                            <i class="bi <?= $p['status'] === 'tersedia' ? 'bi-toggle-on' : 'bi-toggle-off' ?>" aria-hidden="true"></i>
                                            <?= $p['status'] === 'tersedia' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>

                                        <form method="POST" action="/admin/produk.php" class="admin-inline-form hidden" data-product-id="<?= e((string) $p['id_produk']) ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                                            <input type="hidden" name="id_produk" value="<?= e((string) $p['id_produk']) ?>">
                                            <input type="hidden" name="current_status" value="<?= e($p['status']) ?>">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

</div>

<script>
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var btnShowTambah  = document.getElementById('btn-show-tambah');
        var panelTambah    = document.getElementById('panel-tambah');
        var btnTutupTambah = document.getElementById('btn-tutup-tambah');

        var panelEdit      = document.getElementById('panel-edit');
        var btnTutupEdit   = document.getElementById('btn-tutup-edit');
        var btnTutupEdit2  = document.getElementById('btn-tutup-edit-2');

        function showPanel(panel, btn) {
            panel.classList.remove('hidden');
            panel.setAttribute('aria-hidden', 'false');
            if (btn) btn.setAttribute('aria-expanded', 'true');
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function hidePanel(panel, btn) {
            panel.classList.add('hidden');
            panel.setAttribute('aria-hidden', 'true');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        if (btnShowTambah && panelTambah) {
            btnShowTambah.addEventListener('click', function () {
                var isOpen = !panelTambah.classList.contains('hidden');
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

        function tutupEdit() {
            hidePanel(panelEdit, null);
        }
        if (btnTutupEdit)  btnTutupEdit.addEventListener('click', tutupEdit);
        if (btnTutupEdit2) btnTutupEdit2.addEventListener('click', tutupEdit);

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

                document.getElementById('edit-id_produk').value  = id;
                document.getElementById('edit-nama_produk').value = nama;
                document.getElementById('edit-harga').value      = harga;
                document.getElementById('edit-deskripsi').value  = deskripsi;

                var selKat = document.getElementById('edit-id_kategori');
                for (var i = 0; i < selKat.options.length; i++) {
                    selKat.options[i].selected = (selKat.options[i].value === kategori);
                }

                var selStatus = document.getElementById('edit-status');
                for (var j = 0; j < selStatus.options.length; j++) {
                    selStatus.options[j].selected = (selStatus.options[j].value === status);
                }

                document.getElementById('edit-is_featured').checked = (featured === '1');

                var previewDiv = document.getElementById('edit-foto-preview');
                if (foto) {
                    previewDiv.innerHTML =
                        '<div class="preview-box">'
                        + '<img src="/assets/img/produk/' + encodeURIComponent(foto) + '"'
                        + ' alt="Foto saat ini" width="64" height="64" class="preview-image"'
                        + ' onerror="this.src=\'<?= e(produk_foto_src(null, '/')) ?>\'">'
                        + '<small class="preview-meta">Foto saat ini</small>'
                        + '</div>';
                } else {
                    previewDiv.innerHTML = '';
                }

                hidePanel(panelTambah, btnShowTambah);
                showPanel(panelEdit, null);
            });
        });

        var actionInput = document.querySelector('input[name="action"]');
        if (actionInput && document.querySelector('.alert--error')) {
            var act = actionInput.value;
            if (act === 'tambah' && panelTambah) {
                showPanel(panelTambah, btnShowTambah);
            } else if (act === 'edit' && panelEdit) {
                showPanel(panelEdit, null);
            }
        }

        // Toggle status button handler
        var toggleStatusButtons = document.querySelectorAll('.btn-toggle-status');
        toggleStatusButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var productId = btn.getAttribute('data-id');
                var form = document.querySelector('.admin-inline-form[data-product-id="' + productId + '"]');
                if (form) {
                    if (confirm('Ubah status produk?')) {
                        form.submit();
                    }
                }
            });
        });

    });
}());
</script>

</body>
</html>