<?php
/**
 * pages/cek-pesanan.php
 *
 * Halaman cek status pesanan publik WanFlorist.
 * Menangani:
 *   - Form input no_pesanan (GET form, bukan POST — tidak perlu CSRF)
 *   - Baca ?no= dari $_GET (setelah PRG redirect dari pemesanan.php)
 *   - Query pesanan + detail_pesanan + dp + lunas via PDO prepared statement
 *   - Progress stepper visual: Pesanan Diterima → Diproses → Siap Kirim → Selesai
 *   - Penanganan status dibatalkan
 *   - Info pembayaran: status DP dan pelunasan
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6
 */

session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';
$pdo = get_pdo();

$no_input   = trim($_GET['no'] ?? '');
$pesanan    = null;
$details    = [];
$dp_row     = null;
$lunas_row  = null;
$not_found  = false;

if ($no_input !== '') {
    $stmt = $pdo->prepare(
        "SELECT id_pesanan, no_pesanan, nama_pembeli, no_hp,
                tanggal_ambil, metode_pengambilan, catatan, status, total_harga, created_at
           FROM pesanan
          WHERE no_pesanan = :no
          LIMIT 1"
    );
    $stmt->execute([':no' => $no_input]);
    $pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pesanan) {
        $id_pesanan = (int) $pesanan['id_pesanan'];

        $stmt_det = $pdo->prepare(
            "SELECT dp.nama_produk, dp.harga_satuan, dp.jumlah, dp.subtotal,
                    pr.foto
               FROM detail_pesanan dp
               LEFT JOIN produk pr ON pr.id_produk = dp.id_produk
              WHERE dp.id_pesanan = :id
              ORDER BY dp.id_detail ASC"
        );
        $stmt_det->execute([':id' => $id_pesanan]);
        $details = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

        $stmt_dp = $pdo->prepare(
            "SELECT jumlah_dp, metode, dicatat_pada
               FROM dp
              WHERE id_pesanan = :id
              LIMIT 1"
        );
        $stmt_dp->execute([':id' => $id_pesanan]);
        $dp_row = $stmt_dp->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt_lunas = $pdo->prepare(
            "SELECT jumlah_lunas, metode, dicatat_pada
               FROM lunas
              WHERE id_pesanan = :id
              LIMIT 1"
        );
        $stmt_lunas->execute([':id' => $id_pesanan]);
        $lunas_row = $stmt_lunas->fetch(PDO::FETCH_ASSOC) ?: null;

    } else {
        $not_found = true;
    }
}

// Status DB: menunggu_konfirmasi | diproses | selesai | dibatalkan
// Stepper stages (index 0–3): Pesanan Diterima, Diproses, Siap Kirim, Selesai
$stepper_stages = [
    ['label' => 'Pesanan Diterima', 'icon' => 'bi bi-check-lg', 'status_trigger' => 'menunggu_konfirmasi'],
    ['label' => 'Diproses',         'icon' => 'bi bi-flower1',   'status_trigger' => 'diproses'],
    ['label' => 'Siap Kirim',       'icon' => 'bi bi-truck',     'status_trigger' => 'siap_kirim'],   // virtual
    ['label' => 'Selesai',          'icon' => 'bi bi-flag',      'status_trigger' => 'selesai'],
];

$status_to_step = [
    'menunggu_konfirmasi' => 0,
    'diproses'            => 1,
    'selesai'             => 3,  // skip to last
    'dibatalkan'          => -1, // special — no active step
];

$current_step = -1;
if ($pesanan) {
    $current_step = $status_to_step[$pesanan['status']] ?? 0;
}

$step_state = static function (int $stepIdx, int $currentStep, string $status): string {
    if ($status === 'dibatalkan') {
        return 'cancelled';
    }
    if ($currentStep === 3) {
        return 'done';
    }
    if ($stepIdx < $currentStep) {
        return 'done';
    }
    if ($stepIdx === $currentStep) {
        return 'active';
    }
    return 'pending';
};

$metode_label = [
    'ambil_sendiri' => 'Ambil Sendiri',
    'cod'           => 'COD (Diantar ke Lokasi)',
];

$status_label = [
    'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
    'diproses'            => 'Diproses',
    'selesai'             => 'Selesai',
    'dibatalkan'          => 'Dibatalkan',
];
$status_badge_class = [
    'menunggu_konfirmasi' => 'badge badge-info',
    'diproses'            => 'badge badge-warning',
    'selesai'             => 'badge badge-success',
    'dibatalkan'          => 'badge badge-danger',
];

$page_title  = 'Cek Pesanan';
$active_page = 'cek-pesanan';
$css_extra   = '/assets/css/public.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main class="cp-main">
    <div class="container cp-container">

        <header class="cp-header">
            <h1 class="cp-heading">Cek Status Pesanan</h1>
            <p class="cp-subheading">Masukkan nomor pesanan Anda untuk memantau perkembangan pesanan secara real-time.</p>
        </header>

        <section class="cp-search-section" aria-label="Form cek pesanan">
            <form
                id="form-cek-pesanan"
                method="GET"
                action="/pages/cek-pesanan.php"
                class="cp-search-form"
            >
                <div class="cp-search-input-wrap">
                    <label for="no_pesanan" class="cp-search-label">
                        Nomor Pesanan
                    </label>
                    <div class="cp-search-row">
                        <div class="cp-input-icon-wrap">
                            <span class="cp-input-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                            <input
                                type="text"
                                id="no_pesanan"
                                name="no"
                                class="cp-search-input"
                                placeholder="Contoh: WF-20250115-0001"
                                value="<?= e($no_input) ?>"
                                maxlength="20"
                                autocomplete="off"
                                spellcheck="false"
                                aria-label="Masukkan nomor pesanan"
                            >
                        </div>
                        <button type="submit" class="btn btn-primary cp-search-btn">
                            <i class="bi bi-search" aria-hidden="true"></i> Cek Sekarang
                        </button>
                    </div>
                </div>
            </form>
        </section>


        <?php if ($no_input !== '' && $not_found): ?>
        <section class="cp-result" aria-live="polite">
                <div class="empty-state">
                <div class="empty-state__icon" aria-hidden="true"><i class="bi bi-search"></i></div>
                <h2 class="empty-state__title">Pesanan Tidak Ditemukan</h2>
                <p class="empty-state__message">
                    Nomor pesanan tidak ditemukan. Periksa kembali nomor pesanan Anda.
                </p>
                <a href="/pages/cek-pesanan.php" class="btn btn-secondary">
                    Coba Lagi
                </a>
            </div>
        </section>

        <?php elseif ($pesanan): ?>
        <section class="cp-result" aria-live="polite">

            <div class="cp-card">

                <div class="cp-card-header">
                    <div class="cp-card-header__left">
                        <p class="cp-label-small">Nomor Pesanan</p>
                        <h2 class="cp-no-pesanan"><?= e($pesanan['no_pesanan']) ?></h2>
                        <p class="cp-label-small cp-nama-pemesan">
                            atas nama <strong><?= e($pesanan['nama_pembeli']) ?></strong>
                        </p>
                    </div>
                    <div class="cp-card-header__right">
                        <span class="<?= e($status_badge_class[$pesanan['status']] ?? 'badge badge-info') ?>">
                            <?= e($status_label[$pesanan['status']] ?? $pesanan['status']) ?>
                        </span>
                    </div>
                </div>

                <?php if ($pesanan['status'] !== 'dibatalkan'): ?>
                <div class="cp-stepper-section">
                    <div class="order-stepper" role="list" aria-label="Tahapan pesanan">

                        <?php foreach ($stepper_stages as $idx => $stage):
                            $state = $step_state($idx, $current_step, $pesanan['status']);
                            $step_cls = '';
                            if ($state === 'done')   $step_cls = 'stepper-step--done';
                            if ($state === 'active') $step_cls = 'stepper-step--active';
                        ?>
                        <div
                            class="stepper-step <?= e($step_cls) ?>"
                            role="listitem"
                            aria-label="Tahap <?= $idx + 1 ?>: <?= e($stage['label']) ?> — <?= e($state === 'done' ? 'Selesai' : ($state === 'active' ? 'Sedang berlangsung' : 'Belum')) ?>"
                        >
                            <div class="stepper-dot" aria-hidden="true">
                                <?php if ($state === 'done'): ?>
                                ✓
                                <?php elseif ($state === 'active'): ?>
                                <i class="<?= e($stage['icon']) ?>" aria-hidden="true"></i>
                                <?php else: ?>
                                <?= $idx + 1 ?>
                                <?php endif; ?>
                            </div>
                            <span class="stepper-label"><?= e($stage['label']) ?></span>
                        </div>

                        <?php if ($idx < count($stepper_stages) - 1):
                            $line_done = ($step_state($idx, $current_step, $pesanan['status']) === 'done') ? 'stepper-line--done' : '';
                        ?>
                        <div class="stepper-line <?= e($line_done) ?>" aria-hidden="true"></div>
                        <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                    <?php
                    $status_messages = [
                        'menunggu_konfirmasi' => 'Pesanan Anda telah masuk dan sedang menunggu konfirmasi dari toko.',
                        'diproses'            => 'Pesanan Anda sedang dirangkai oleh florist kami dengan penuh kasih.',
                        'selesai'             => 'Pesanan Anda telah selesai dan siap diterima. Terima kasih sudah berbelanja!',
                    ];
                    $status_msg = $status_messages[$pesanan['status']] ?? '';
                    ?>
                    <?php if ($status_msg): ?>
                    <p class="cp-stepper-msg"><?= e($status_msg) ?></p>
                    <?php endif; ?>

                </div>

                <?php else: ?>
                <div class="cp-cancelled-banner" role="alert">
                    <div class="cp-cancelled-banner__icon" aria-hidden="true"><i class="bi bi-x-circle"></i></div>
                    <div>
                        <h3 class="cp-cancelled-banner__title">Pesanan Dibatalkan</h3>
                        <p class="cp-cancelled-banner__msg">
                            Pesanan ini telah dibatalkan. Jika Anda memiliki pertanyaan, silakan hubungi kami melalui WhatsApp.
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="cp-details-grid">

                    <div class="cp-details-col">
                        <h3 class="cp-section-heading">
                            <span aria-hidden="true"><i class="bi bi-bag"></i></span> Detail Produk
                        </h3>

                        <?php if (empty($details)): ?>
                        <p class="cp-text-muted">Tidak ada produk ditemukan.</p>
                        <?php else: ?>
                        <ul class="cp-product-list">
                            <?php foreach ($details as $item): ?>
                            <li class="cp-product-item">
                                <div class="cp-product-img-wrap">
                                    <?php $foto_path = produk_foto_src($item['foto'] ?? null, '/'); ?>
                                    <img
                                        src="<?= e($foto_path) ?>"
                                        alt="<?= e($item['nama_produk']) ?>"
                                        class="cp-product-img"
                                        loading="lazy"
                                        onerror="this.src='<?= e(produk_foto_src(null, '/')) ?>'"
                                    >
                                </div>
                                <div class="cp-product-info">
                                    <h4 class="cp-product-name"><?= e($item['nama_produk']) ?></h4>
                                    <div class="cp-product-meta">
                                        <span class="cp-product-price"><?= format_rupiah((int) $item['harga_satuan']) ?></span>
                                        <span class="cp-product-qty">× <?= (int) $item['jumlah'] ?></span>
                                    </div>
                                    <div class="cp-product-subtotal">
                                        Subtotal: <strong><?= format_rupiah((int) $item['subtotal']) ?></strong>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>

                    <div class="cp-details-col">

                        <h3 class="cp-section-heading">
                            <span aria-hidden="true"><i class="bi bi-card-text"></i></span> Informasi Pesanan
                        </h3>

                        <div class="cp-info-card">
                            <div class="cp-info-row">
                                <span class="cp-info-label">Tanggal Ambil</span>
                                <span class="cp-info-value">
                                    <?php
                                    $dt = DateTime::createFromFormat('Y-m-d', $pesanan['tanggal_ambil']);
                                    echo $dt ? e(format_tanggal_id($dt)) : e($pesanan['tanggal_ambil']);
                                    ?>
                                </span>
                            </div>
                            <div class="cp-info-row">
                                <span class="cp-info-label">Metode Pengambilan</span>
                                <span class="cp-info-value">
                                    <?= e($metode_label[$pesanan['metode_pengambilan']] ?? $pesanan['metode_pengambilan']) ?>
                                </span>
                            </div>
                            <?php if (!empty($pesanan['catatan'])): ?>
                            <div class="cp-info-row">
                                <span class="cp-info-label">Catatan</span>
                                <span class="cp-info-value"><?= e($pesanan['catatan']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="cp-info-row cp-info-row--total">
                                <span class="cp-info-label">Total Harga</span>
                                <span class="cp-info-value cp-total-value">
                                    <?= format_rupiah((int) $pesanan['total_harga']) ?>
                                </span>
                            </div>
                        </div>

                        <h3 class="cp-section-heading cp-section-heading--mt">
                            <span aria-hidden="true"><i class="bi bi-credit-card"></i></span> Informasi Pembayaran
                        </h3>

                        <div class="cp-info-card">

                            <div class="cp-info-row">
                                <span class="cp-info-label">Down Payment (DP)</span>
                                <?php if ($dp_row): ?>
                                <span class="cp-info-value cp-payment-paid">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i> Sudah Dibayar
                                    <span class="cp-payment-sub">
                                        <?= format_rupiah((int) $dp_row['jumlah_dp']) ?>
                                    </span>
                                </span>
                                <?php else: ?>
                                <span class="cp-info-value cp-payment-unpaid">
                                    <?= ($pesanan['metode_pengambilan'] === 'cod') ? '— (COD)' : '⏳ Belum Dibayar' ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <div class="cp-info-row">
                                <span class="cp-info-label">Pelunasan</span>
                                <?php if ($lunas_row): ?>
                                <span class="cp-info-value cp-payment-paid">
                                    <i class="bi bi-check-lg" aria-hidden="true"></i> Sudah Lunas
                                    <span class="cp-payment-sub">
                                        <?= format_rupiah((int) $lunas_row['jumlah_lunas']) ?>
                                    </span>
                                </span>
                                <?php else: ?>
                                <span class="cp-info-value cp-payment-unpaid">
                                    ⏳ Belum Lunas
                                </span>
                                <?php endif; ?>
                            </div>

                            <div class="cp-info-row cp-info-row--payment-status">
                                <span class="cp-info-label">Status Pembayaran</span>
                                <span class="cp-info-value">
                                    <?php if ($lunas_row): ?>
                                    <span class="badge badge-success">Lunas</span>
                                    <?php elseif ($dp_row): ?>
                                    <span class="badge badge-warning">DP Terbayar</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Belum Bayar</span>
                                    <?php endif; ?>
                                </span>
                            </div>

                        </div>

                        <?php if ($pesanan['metode_pengambilan'] === 'ambil_sendiri' && !$lunas_row): ?>
                        <div class="alert alert-info cp-dp-reminder" role="status">
                            <span aria-hidden="true"><i class="bi bi-info-circle"></i></span>
                            <div>
                                <strong>Informasi Pembayaran</strong><br>
                                Silakan lakukan pembayaran
                                <?= $dp_row ? 'pelunasan' : 'Down Payment (DP)' ?>
                                melalui transfer bank. Hubungi kami via WhatsApp untuk konfirmasi pembayaran.
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                </div>

                <div class="cp-contact-cta">
                    <p class="cp-contact-text">Ada pertanyaan tentang pesanan Anda?</p>
                    <a
                        href="https:
                        class="btn btn-secondary"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <i class="bi bi-whatsapp" aria-hidden="true"></i> Hubungi via WhatsApp
                    </a>
                </div>

            </div>

        </section>

        <?php elseif ($no_input === ''): ?>
        <section class="cp-initial-hint">
            <div class="empty-state">
                <div class="empty-state__icon" aria-hidden="true"><i class="bi bi-box-seam"></i></div>
                <p class="empty-state__message">
                    Masukkan nomor pesanan di atas, lalu klik <strong>Cek Sekarang</strong> untuk melihat status pesanan Anda.
                </p>
            </div>
        </section>
        <?php endif; ?>

    </div>
</main>

<?php include '../components/footer.php'; ?>

<style>
.cp-main {
    min-height: calc(100vh - 130px);
    padding: 3rem 0 5rem;
    background-color: #FAFAFA;
}

.cp-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.cp-header {
    text-align: center;
    margin-bottom: 2rem;
}

.cp-heading {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(1.75rem, 4vw, 2.25rem);
    font-weight: 700;
    color: #6B21A8;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.cp-subheading {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 1rem;
    color: #6B7280;
    line-height: 1.6;
}

.cp-search-section {
    background: #ffffff;
    border: 1.5px solid #E9D5FF;
    border-radius: 16px;
    padding: 1.75rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 2px 12px rgba(107, 33, 168, 0.06);
}

.cp-search-form {
    width: 100%;
}

.cp-search-label {
    display: block;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.625rem;
}

.cp-search-row {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
    flex-wrap: wrap;
}

.cp-input-icon-wrap {
    flex: 1;
    min-width: 220px;
    position: relative;
    display: flex;
    align-items: center;
}

.cp-input-icon {
    position: absolute;
    left: 0.875rem;
    font-size: 1.125rem;
    pointer-events: none;
    z-index: 1;
}

.cp-search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: 1.5px solid #E5E7EB;
    border-radius: 9999px;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9375rem;
    color: #1F2937;
    background: #FAFAFA;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.cp-search-input:focus {
    border-color: #9333EA;
    box-shadow: 0 0 0 3px rgba(107, 33, 168, 0.1);
    background: #ffffff;
}

.cp-search-input::placeholder {
    color: #9CA3AF;
}

.cp-search-btn {
    white-space: nowrap;
    flex-shrink: 0;
}

.cp-result {
    animation: cp-fade-in 0.35s ease;
}

@keyframes cp-fade-in {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.cp-card {
    background: #ffffff;
    border: 1.5px solid #E9D5FF;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(107, 33, 168, 0.08);
}

.cp-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.5rem 1.75rem;
    background-color: #F9F5FF;
    border-bottom: 1.5px solid #E9D5FF;
    flex-wrap: wrap;
}

.cp-label-small {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.75rem;
    font-weight: 500;
    color: #9CA3AF;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.cp-no-pesanan {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1F2937;
    margin: 0.125rem 0 0.25rem;
}

.cp-nama-pemesan {
    margin-top: 0.25rem;
    color: #6B7280;
}

.cp-stepper-section {
    padding: 2rem 1.75rem;
    border-bottom: 1.5px solid #F3F4F6;
    background: #fff;
}

.cp-stepper-msg {
    text-align: center;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9375rem;
    color: #6B7280;
    margin-top: 1.25rem;
    font-style: italic;
}

.cp-cancelled-banner {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem 1.75rem;
    background-color: #FFF1F1;
    border-left: 4px solid #DC2626;
    border-bottom: 1.5px solid #FECACA;
}

.cp-cancelled-banner__icon {
    font-size: 2rem;
    flex-shrink: 0;
    line-height: 1;
}

.cp-cancelled-banner__title {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #DC2626;
    margin-bottom: 0.25rem;
}

.cp-cancelled-banner__msg {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9rem;
    color: #6B7280;
    line-height: 1.5;
    margin: 0;
}

.cp-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border-top: 1px solid #F3F4F6;
}

.cp-details-col {
    padding: 1.75rem;
}

.cp-details-col:first-child {
    border-right: 1px solid #F3F4F6;
}

.cp-section-heading {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.cp-section-heading--mt {
    margin-top: 1.5rem;
}

.cp-product-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.cp-product-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem;
    background: #FAFAFA;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
}

.cp-product-img-wrap {
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    background: #F3F4F6;
}

.cp-product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.cp-product-info {
    flex: 1;
    min-width: 0;
}

.cp-product-name {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cp-product-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.cp-product-price {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6B21A8;
    font-family: 'Inter', system-ui, sans-serif;
}

.cp-product-qty {
    font-size: 0.8125rem;
    color: #9CA3AF;
    font-family: 'Inter', system-ui, sans-serif;
}

.cp-product-subtotal {
    font-size: 0.8rem;
    color: #6B7280;
    font-family: 'Inter', system-ui, sans-serif;
}

.cp-product-subtotal strong {
    color: #374151;
}

.cp-info-card {
    background: #FAFAFA;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    overflow: hidden;
}

.cp-info-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #F3F4F6;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.875rem;
}

.cp-info-row:last-child {
    border-bottom: none;
}

.cp-info-row--total {
    background: #F9F5FF;
}

.cp-info-row--payment-status {
    background: #F9FAFB;
}

.cp-info-label {
    color: #9CA3AF;
    font-weight: 500;
    flex-shrink: 0;
    line-height: 1.5;
}

.cp-info-value {
    color: #1F2937;
    font-weight: 500;
    text-align: right;
    line-height: 1.5;
}

.cp-total-value {
    font-size: 1rem;
    font-weight: 700;
    color: #6B21A8;
}

.cp-payment-paid {
    color: #16A34A;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.125rem;
}

.cp-payment-unpaid {
    color: #D97706;
    font-weight: 500;
}

.cp-payment-sub {
    font-size: 0.8rem;
    color: #6B7280;
    font-weight: 400;
}

.cp-dp-reminder {
    margin-top: 0.875rem;
    font-size: 0.875rem;
}

.cp-contact-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1.25rem 1.75rem;
    background: #F9F5FF;
    border-top: 1.5px solid #E9D5FF;
    flex-wrap: wrap;
}

.cp-contact-text {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9rem;
    color: #6B7280;
    margin: 0;
}

.cp-initial-hint {
    padding: 1rem 0;
}

.cp-text-muted {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.875rem;
    color: #9CA3AF;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
}

.badge-info {
    background: #EFF6FF;
    color: #2563EB;
    border: 1px solid #BFDBFE;
}

.badge-warning {
    background: #FFFBEB;
    color: #D97706;
    border: 1px solid #FDE68A;
}

.badge-success {
    background: #F0FDF4;
    color: #16A34A;
    border: 1px solid #BBF7D0;
}

.badge-danger {
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FECACA;
}

.alert {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem 1.125rem;
    border-radius: 10px;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.875rem;
    line-height: 1.55;
}

.alert-info {
    background: #EFF6FF;
    color: #1E40AF;
    border: 1px solid #BFDBFE;
}

.container {
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.65rem 1.5rem;
    border-radius: 9999px;
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: 2px solid transparent;
    transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
    white-space: nowrap;
}

.btn-primary {
    background-color: #6B21A8;
    color: #ffffff;
}

.btn-primary:hover {
    background-color: #5B1A90;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: transparent;
    color: #6B21A8;
    border-color: #E9D5FF;
}

.btn-secondary:hover {
    background-color: #F5F0FF;
    border-color: #6B21A8;
}

@media (max-width: 767px) {
    .cp-main {
        padding: 2rem 0 4rem;
    }

    .cp-details-grid {
        grid-template-columns: 1fr;
    }

    .cp-details-col:first-child {
        border-right: none;
        border-bottom: 1px solid #F3F4F6;
    }

    .cp-card-header {
        flex-direction: column;
    }

    .cp-search-row {
        flex-direction: column;
    }

    .cp-search-btn {
        width: 100%;
    }

    .cp-contact-cta {
        flex-direction: column;
        text-align: center;
    }

    .cp-no-pesanan {
        font-size: 1.25rem;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .cp-container {
        max-width: 720px;
    }
}
</style>

</body>
</html>
