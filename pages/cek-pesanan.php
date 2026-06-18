<?php
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
$css_extra   = '/assets/css/pages/cek-pesanan.css';
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
                            <span class="cp-input-icon" aria-hidden="true"></span>
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
                        href="https://wa.me/6281234567890"
                        class="btn btn-secondary cp-contact-btn"
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

</body>