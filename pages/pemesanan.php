<?php
/**
 * pages/pemesanan.php
 *
 * Halaman form pemesanan publik WanFlorist.
 * Menangani:
 *   - Pre-fill produk dari query string ?id=
 *   - Daftar semua produk tersedia untuk selector
 *   - Validasi server-side (nama, WA, alamat, tanggal, produk)
 *   - Perlindungan CSRF
 *   - INSERT pesanan + detail_pesanan dalam satu transaksi
 *   - PRG redirect ke cek-pesanan.php setelah berhasil
 *
 * Requirements: 5.1–5.10, 15.1–15.3, 17.2, 17.4, 17.5
 */

session_start(); // needed for CSRF
require_once '../config/database.php';
require_once '../config/helpers.php';
$pdo = get_pdo();

$errors = [];
$form_data = [];
$prefill_produk = null;
$success_no_pesanan = null;

$prefill_id = (int) ($_GET['id'] ?? 0);
if ($prefill_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = :id AND status = 'tersedia'");
    $stmt->execute([':id' => $prefill_id]);
    $prefill_produk = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt_all = $pdo->prepare("SELECT id_produk, nama_produk, harga FROM produk WHERE status = 'tersedia' ORDER BY nama_produk");
$stmt_all->execute();
$all_produk = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

$csrf_token = generate_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Permintaan tidak valid.');
    }

    $items = [];
    $produk_ids = $_POST['produk_id'] ?? [];
    $jumlah_list = $_POST['jumlah'] ?? [];
    foreach ($produk_ids as $idx => $pid) {
        $pid = (int) $pid;
        $qty = (int) ($jumlah_list[$idx] ?? 0);
        if ($pid > 0 && $qty >= 1) {
            $items[] = ['id_produk' => $pid, 'jumlah' => $qty];
        }
    }

    $input = [
        'nama_pemesan'  => $_POST['nama_pemesan'] ?? '',
        'no_whatsapp'   => $_POST['no_whatsapp'] ?? '',
        'alamat'        => $_POST['alamat'] ?? '',
        'tanggal_kirim' => $_POST['tanggal_kirim'] ?? '',
        'produk'        => $items,
    ];

    $errors = validasi_form_pemesanan($input);

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $no_pesanan   = generate_no_pesanan($pdo);
            $metode_bayar = in_array($_POST['metode_bayar'] ?? '', ['transfer', 'cod']) ? $_POST['metode_bayar'] : 'transfer';
            $catatan      = trim($_POST['catatan'] ?? '');

            // Calculate total — fetch prices from DB to prevent tampering
            $total        = 0;
            $detail_items = [];
            foreach ($items as $item) {
                $stmt_p = $pdo->prepare("SELECT nama_produk, harga FROM produk WHERE id_produk = :id AND status = 'tersedia'");
                $stmt_p->execute([':id' => $item['id_produk']]);
                $p_row = $stmt_p->fetch(PDO::FETCH_ASSOC);
                if ($p_row) {
                    $subtotal = (float) $p_row['harga'] * $item['jumlah'];
                    $total   += $subtotal;
                    $detail_items[] = [
                        'id_produk'    => $item['id_produk'],
                        'nama_produk'  => $p_row['nama_produk'],
                        'harga_satuan' => (float) $p_row['harga'],
                        'jumlah'       => $item['jumlah'],
                        'subtotal'     => $subtotal,
                    ];
                }
            }

            $stmt_ins = $pdo->prepare(
                "INSERT INTO pesanan (no_pesanan, nama_pemesan, no_whatsapp, alamat, tanggal_kirim, metode_bayar, catatan, total_harga)
                 VALUES (:no, :nama, :wa, :alamat, :tgl, :metode, :catatan, :total)"
            );
            $stmt_ins->execute([
                ':no'      => $no_pesanan,
                ':nama'    => trim($input['nama_pemesan']),
                ':wa'      => trim($input['no_whatsapp']),
                ':alamat'  => trim($input['alamat']),
                ':tgl'     => $input['tanggal_kirim'],
                ':metode'  => $metode_bayar,
                ':catatan' => $catatan,
                ':total'   => $total,
            ]);
            $id_pesanan = (int) $pdo->lastInsertId();

            $stmt_det = $pdo->prepare(
                "INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga_satuan, jumlah, subtotal)
                 VALUES (:id_pesanan, :id_produk, :nama_produk, :harga_satuan, :jumlah, :subtotal)"
            );
            foreach ($detail_items as $d) {
                $stmt_det->execute([
                    ':id_pesanan'  => $id_pesanan,
                    ':id_produk'   => $d['id_produk'],
                    ':nama_produk' => $d['nama_produk'],
                    ':harga_satuan'=> $d['harga_satuan'],
                    ':jumlah'      => $d['jumlah'],
                    ':subtotal'    => $d['subtotal'],
                ]);
            }

            $pdo->commit();

            // PRG redirect after successful save
            header('Location: /pages/cek-pesanan.php?no=' . urlencode($no_pesanan));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['_global'] = 'Gagal menyimpan pesanan. Silakan coba lagi.';
        }
    }

    $csrf_token = generate_csrf();
    $form_data = $input;
}

$saved_metode  = $form_data['metode_bayar'] ?? $_POST['metode_bayar'] ?? 'transfer';
$saved_catatan = trim($form_data['catatan'] ?? $_POST['catatan'] ?? '');

$page_title = 'Form Pemesanan';
$active_page = 'produk';
$css_extra   = '/assets/css/pemesanan.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main class="pem-main">
    <div class="container pem-container">

        <header class="pem-header">
            <h1 class="pem-heading">Form Pemesanan</h1>
            <p class="pem-subheading">Lengkapi data di bawah ini untuk menyelesaikan pesanan Anda.</p>
        </header>

        <div class="pem-layout">

            <section class="pem-form-col">

                <?php if (!empty($errors['_global'])): ?>
                <div class="alert alert-danger pem-global-error" role="alert">
                    <span>⚠️</span>
                    <span><?= e($errors['_global']) ?></span>
                </div>
                <?php endif; ?>

                <form
                    id="form-pemesanan"
                    method="POST"
                    action="/pages/pemesanan.php<?= $prefill_id > 0 ? '?id=' . $prefill_id : '' ?>"
                    novalidate
                >
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                    <div class="pem-section">
                        <h2 class="pem-section-title">
                            <span class="pem-section-icon" aria-hidden="true">👤</span>
                            Data Pemesan
                        </h2>

                        <div class="pem-grid-2">

                            <div class="form-group">
                                <label for="nama_pemesan" class="form-label">
                                    Nama Pemesan <span class="pem-required" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="nama_pemesan"
                                    name="nama_pemesan"
                                    maxlength="100"
                                    placeholder="Masukkan nama lengkap Anda"
                                    value="<?= e($form_data['nama_pemesan'] ?? '') ?>"
                                    class="<?= !empty($errors['nama_pemesan']) ? 'input-error' : '' ?>"
                                    aria-describedby="<?= !empty($errors['nama_pemesan']) ? 'err-nama' : '' ?>"
                                    autocomplete="name"
                                >
                                <?php if (!empty($errors['nama_pemesan'])): ?>
                                <span id="err-nama" class="form-error" role="alert"><?= e($errors['nama_pemesan']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="no_whatsapp" class="form-label">
                                    Nomor WhatsApp <span class="pem-required" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="tel"
                                    id="no_whatsapp"
                                    name="no_whatsapp"
                                    placeholder="08123456789"
                                    value="<?= e($form_data['no_whatsapp'] ?? '') ?>"
                                    class="<?= !empty($errors['no_whatsapp']) ? 'input-error' : '' ?>"
                                    aria-describedby="<?= !empty($errors['no_whatsapp']) ? 'err-wa' : 'hint-wa' ?>"
                                    autocomplete="tel"
                                >
                                <?php if (!empty($errors['no_whatsapp'])): ?>
                                <span id="err-wa" class="form-error" role="alert"><?= e($errors['no_whatsapp']) ?></span>
                                <?php else: ?>
                                <span id="hint-wa" class="form-hint">8–15 digit angka, tanpa tanda (+) atau spasi.</span>
                                <?php endif; ?>
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="alamat" class="form-label">
                                Alamat Pengiriman <span class="pem-required" aria-hidden="true">*</span>
                            </label>
                            <textarea
                                id="alamat"
                                name="alamat"
                                maxlength="500"
                                rows="3"
                                placeholder="Jl. Contoh No. 1, Kelurahan, Kecamatan, Kota"
                                class="<?= !empty($errors['alamat']) ? 'input-error' : '' ?>"
                                aria-describedby="<?= !empty($errors['alamat']) ? 'err-alamat' : '' ?>"
                            ><?= e($form_data['alamat'] ?? '') ?></textarea>
                            <?php if (!empty($errors['alamat'])): ?>
                            <span id="err-alamat" class="form-error" role="alert"><?= e($errors['alamat']) ?></span>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="pem-section">
                        <h2 class="pem-section-title">
                            <span class="pem-section-icon" aria-hidden="true">📅</span>
                            Jadwal &amp; Pembayaran
                        </h2>

                        <div class="pem-grid-2">

                            <div class="form-group">
                                <label for="tanggal_kirim" class="form-label">
                                    Tanggal Pengiriman <span class="pem-required" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="date"
                                    id="tanggal_kirim"
                                    name="tanggal_kirim"
                                    min="<?= date('Y-m-d') ?>"
                                    value="<?= e($form_data['tanggal_kirim'] ?? '') ?>"
                                    class="<?= !empty($errors['tanggal_kirim']) ? 'input-error' : '' ?>"
                                    aria-describedby="<?= !empty($errors['tanggal_kirim']) ? 'err-tgl' : '' ?>"
                                >
                                <?php if (!empty($errors['tanggal_kirim'])): ?>
                                <span id="err-tgl" class="form-error" role="alert"><?= e($errors['tanggal_kirim']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <fieldset class="pem-fieldset">
                                    <legend class="form-label">
                                        Metode Pembayaran <span class="pem-required" aria-hidden="true">*</span>
                                    </legend>
                                    <div class="pem-radio-group">
                                        <label class="pem-radio-card <?= ($saved_metode === 'transfer') ? 'pem-radio-card--checked' : '' ?>">
                                            <input
                                                type="radio"
                                                name="metode_bayar"
                                                value="transfer"
                                                <?= ($saved_metode === 'transfer') ? 'checked' : '' ?>
                                                class="pem-radio-input"
                                            >
                                            <span class="pem-radio-label">
                                                <span class="pem-radio-title">🏦 Transfer</span>
                                                <span class="pem-radio-desc">BCA / Mandiri / dll.</span>
                                            </span>
                                        </label>
                                        <label class="pem-radio-card <?= ($saved_metode === 'cod') ? 'pem-radio-card--checked' : '' ?>">
                                            <input
                                                type="radio"
                                                name="metode_bayar"
                                                value="cod"
                                                <?= ($saved_metode === 'cod') ? 'checked' : '' ?>
                                                class="pem-radio-input"
                                            >
                                            <span class="pem-radio-label">
                                                <span class="pem-radio-title">🛵 COD</span>
                                                <span class="pem-radio-desc">Bayar di tempat</span>
                                            </span>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>

                        </div>

                    </div>

                    <div class="pem-section">
                        <h2 class="pem-section-title">
                            <span class="pem-section-icon" aria-hidden="true">🌸</span>
                            Pilihan Produk
                        </h2>

                        <?php if (!empty($errors['produk'])): ?>
                        <div id="produk-section">
                            <span class="form-error" role="alert"><?= e($errors['produk']) ?></span>
                        </div>
                        <?php else: ?>
                        <div id="produk-section"></div>
                        <?php endif; ?>

                        <div id="produk-rows" class="pem-produk-rows">

                            <?php
                            $initial_rows = [];
                            if (!empty($form_data['produk'])) {
                                foreach ($form_data['produk'] as $item) {
                                    $initial_rows[] = [
                                        'id_produk' => $item['id_produk'],
                                        'jumlah'    => $item['jumlah'],
                                    ];
                                }
                            } elseif ($prefill_produk) {
                                $initial_rows[] = [
                                    'id_produk' => $prefill_produk['id_produk'],
                                    'jumlah'    => 1,
                                ];
                            } else {
                                $initial_rows[] = ['id_produk' => 0, 'jumlah' => 1];
                            }
                            ?>

                            <?php foreach ($initial_rows as $row_idx => $row): ?>
                            <div class="produk-row">
                                <div class="pem-produk-row-inner">
                                    <div class="pem-produk-select-wrap">
                                        <label class="form-label" for="produk_id_<?= $row_idx ?>">
                                            Produk
                                        </label>
                                        <select
                                            id="produk_id_<?= $row_idx ?>"
                                            name="produk_id[]"
                                            class="pem-produk-select"
                                        >
                                            <option value="">— Pilih Produk —</option>
                                            <?php foreach ($all_produk as $p): ?>
                                            <option
                                                value="<?= e((string)$p['id_produk']) ?>"
                                                data-harga="<?= (int)$p['harga'] ?>"
                                                <?= ((int)$row['id_produk'] === (int)$p['id_produk']) ? 'selected' : '' ?>
                                            >
                                                <?= e($p['nama_produk']) ?> — <?= format_rupiah((int)$p['harga']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="pem-produk-qty-wrap">
                                        <label class="form-label" for="jumlah_<?= $row_idx ?>">
                                            Jumlah
                                        </label>
                                        <input
                                            type="number"
                                            id="jumlah_<?= $row_idx ?>"
                                            name="jumlah[]"
                                            min="1"
                                            value="<?= (int)$row['jumlah'] ?>"
                                            class="pem-produk-qty"
                                        >
                                    </div>

                                    <button
                                        type="button"
                                        class="btn-hapus-produk pem-btn-hapus"
                                        aria-label="Hapus baris produk ini"
                                        title="Hapus"
                                    >✕</button>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>

                        <button
                            type="button"
                            id="btn-tambah-produk"
                            class="btn btn-secondary btn-sm pem-btn-tambah"
                        >
                            + Tambah Produk
                        </button>

                        <template id="produk-row-template">
                            <div class="produk-row">
                                <div class="pem-produk-row-inner">
                                    <div class="pem-produk-select-wrap">
                                        <label class="form-label">Produk</label>
                                        <select name="produk_id[]" class="pem-produk-select">
                                            <option value="">— Pilih Produk —</option>
                                            <?php foreach ($all_produk as $p): ?>
                                            <option
                                                value="<?= e((string)$p['id_produk']) ?>"
                                                data-harga="<?= (int)$p['harga'] ?>"
                                            >
                                                <?= e($p['nama_produk']) ?> — <?= format_rupiah((int)$p['harga']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="pem-produk-qty-wrap">
                                        <label class="form-label">Jumlah</label>
                                        <input
                                            type="number"
                                            name="jumlah[]"
                                            min="1"
                                            value="1"
                                            class="pem-produk-qty"
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-hapus-produk pem-btn-hapus"
                                        aria-label="Hapus baris produk ini"
                                        title="Hapus"
                                    >✕</button>
                                </div>
                            </div>
                        </template>

                    </div>

                    <div class="pem-section">
                        <h2 class="pem-section-title">
                            <span class="pem-section-icon" aria-hidden="true">📝</span>
                            Catatan Tambahan
                        </h2>
                        <div class="form-group">
                            <label for="catatan" class="form-label">
                                Catatan <span class="text-muted">(opsional)</span>
                            </label>
                            <textarea
                                id="catatan"
                                name="catatan"
                                rows="3"
                                placeholder="Tuliskan pesan kartu ucapan, instruksi khusus, atau catatan lainnya..."
                            ><?= e($saved_catatan) ?></textarea>
                        </div>
                    </div>

                    <div class="pem-submit-wrap">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            🌸 Buat Pesanan
                        </button>
                    </div>

                </form>

            </section>

            <aside class="pem-sidebar" aria-label="Ringkasan pesanan">
                <div class="pem-summary-card">
                    <h3 class="pem-summary-title">Ringkasan Pesanan</h3>

                    <div id="pem-summary-list" class="pem-summary-list">
                        <p class="pem-summary-empty text-muted text-sm">
                            Pilih produk untuk melihat ringkasan.
                        </p>
                    </div>

                    <div class="pem-summary-total-row">
                        <span>Total Keseluruhan</span>
                        <span id="pem-summary-total" class="pem-summary-total-value">Rp 0</span>
                    </div>

                    <!-- DP Info box: shown by JS when total > 100000 AND metode = transfer -->
                    <div id="pem-dp-info" class="alert alert-info pem-dp-info" style="display:none;" role="status">
                        <span>ℹ️</span>
                        <div>
                            <strong>Informasi Pembayaran DP</strong><br>
                            Pesanan di atas Rp 100.000 dengan metode Transfer memerlukan Down Payment (DP) minimal 50% sebelum pesanan diproses.
                        </div>
                    </div>

                </div>
            </aside>

        </div>

    </div>
</main>

<?php include '../components/footer.php'; ?>

<script src="/assets/js/pemesanan.js"></script>
</body>
</html>
