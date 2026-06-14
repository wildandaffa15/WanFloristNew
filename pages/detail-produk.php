<?php
/**
 * pages/detail-produk.php
 *
 * Halaman detail produk publik WanFlorist.
 * Menampilkan informasi lengkap satu produk dan seksif produk terkait.
 *
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
 */

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo = get_pdo();

// ─── Baca ?id= dari GET ──────────────────────────────────────────────────────
$id = (int) ($_GET['id'] ?? 0);

// ─── Query produk + nama kategori via JOIN (Req 4.1) ─────────────────────────
$stmt = $pdo->prepare(
    "SELECT p.*, k.nama_kategori
     FROM   produk p
     JOIN   kategori k ON p.id_kategori = k.id_kategori
     WHERE  p.id_produk = :id"
);
$stmt->execute([':id' => $id]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

// ─── Produk terkait: kategori sama, bukan produk ini, max 4 (Req 4.3) ────────
$related_products = [];
if ($produk) {
    $stmt_related = $pdo->prepare(
        "SELECT p.*, k.nama_kategori
         FROM   produk p
         JOIN   kategori k ON p.id_kategori = k.id_kategori
         WHERE  p.id_kategori = :kat
           AND  p.id_produk  != :id
           AND  p.status      = 'tersedia'
         ORDER BY RAND()
         LIMIT 4"
    );
    $stmt_related->execute([
        ':kat' => $produk['id_kategori'],
        ':id'  => $id,
    ]);
    $related_products = $stmt_related->fetchAll(PDO::FETCH_ASSOC);
}

// ─── Variabel halaman ─────────────────────────────────────────────────────────
$page_title  = $produk ? $produk['nama_produk'] : 'Produk Tidak Ditemukan';
$css_extra   = '/assets/css/public.css';
$active_page = 'produk';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     DETAIL PRODUK — Konten Utama
     ═══════════════════════════════════════════════════════════════════════════ -->
<main class="detail-page" id="main-content">
    <div class="container">

        <?php if (!$produk): ?>
        <!-- ── Produk tidak ditemukan (Req 4.2) ─────────────────────────────── -->
        <div class="detail-not-found" role="alert">
            <div class="detail-not-found__icon" aria-hidden="true">🌸</div>
            <h1 class="detail-not-found__title">Produk Tidak Ditemukan</h1>
            <p class="detail-not-found__text">
                Produk yang Anda cari tidak tersedia atau telah dihapus.
            </p>
            <a href="/pages/katalog.php" class="btn btn-primary">
                Kembali ke Katalog
            </a>
        </div>

        <?php else: ?>
        <?php
            // Tentukan path foto utama
            $foto = $produk['foto'] ?? '';
            if ($foto === '' || $foto === null || $foto === 'placeholder.jpg') {
                $foto_src = '../assets/img/placeholder.jpg';
            } else {
                $foto_src = '../assets/img/produk/' . $foto;
            }
        ?>

        <!-- ── Breadcrumb ─────────────────────────────────────────────────── -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/index.php" class="breadcrumb__link">Beranda</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <a href="/pages/katalog.php" class="breadcrumb__link">Produk</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current" aria-current="page">
                <?= e($produk['nama_produk']) ?>
            </span>
        </nav>

        <!-- ── Layout 2 kolom: foto (kiri) + info (kanan) ────────────────── -->
        <div class="detail-layout">

            <!-- Kolom kiri: foto produk -->
            <div class="detail-layout__image-col">
                <div class="detail-image-wrap">
                    <img
                        src="<?= e($foto_src) ?>"
                        alt="<?= e($produk['nama_produk']) ?>"
                        class="detail-image"
                        onerror="this.src='../assets/img/placeholder.jpg'"
                    >
                </div>
            </div>
            <!-- /.detail-layout__image-col -->

            <!-- Kolom kanan: info produk -->
            <div class="detail-layout__info-col">

                <!-- Badge kategori -->
                <span class="detail-badge-category">
                    <?= e($produk['nama_kategori']) ?>
                </span>

                <!-- Nama produk (Req 4.1) -->
                <h1 class="detail-product-name">
                    <?= e($produk['nama_produk']) ?>
                </h1>

                <!-- Harga (Req 4.5) -->
                <p class="detail-price">
                    <?= e(format_rupiah((int) $produk['harga'])) ?>
                </p>

                <!-- Badge status -->
                <?php if (($produk['status'] ?? '') === 'tersedia'): ?>
                <span class="badge badge-hijau detail-status-badge">
                    ● Tersedia
                </span>
                <?php else: ?>
                <span class="badge badge-secondary detail-status-badge">
                    ● Tidak Tersedia
                </span>
                <?php endif; ?>

                <!-- Deskripsi -->
                <?php if (!empty($produk['deskripsi'])): ?>
                <div class="detail-description">
                    <p><?= e($produk['deskripsi']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Tombol aksi -->
                <div class="detail-actions">
                    <!-- Pesan Sekarang (Req 4.4) -->
                    <a
                        href="/pages/pemesanan.php?id=<?= e((string) $produk['id_produk']) ?>"
                        class="btn btn-primary btn-lg detail-btn-order"
                    >
                        Pesan Sekarang
                    </a>

                    <!-- Kembali ke Katalog -->
                    <a href="/pages/katalog.php" class="btn btn-secondary btn-lg detail-btn-back">
                        Kembali ke Katalog
                    </a>
                </div>
                <!-- /.detail-actions -->

            </div>
            <!-- /.detail-layout__info-col -->

        </div>
        <!-- /.detail-layout -->

        <!-- ── Produk Terkait (Req 4.3) ──────────────────────────────────── -->
        <?php if (!empty($related_products)): ?>
        <section class="related-section" aria-labelledby="related-heading">
            <h2 class="related-section__title" id="related-heading">
                Produk Terkait
            </h2>

            <div class="catalog-grid related-grid">
                <?php foreach ($related_products as $rel): ?>
                <?php
                    $rel_foto = $rel['foto'] ?? '';
                    if ($rel_foto === '' || $rel_foto === null || $rel_foto === 'placeholder.jpg') {
                        $rel_foto_src = '../assets/img/placeholder.jpg';
                    } else {
                        $rel_foto_src = '../assets/img/produk/' . $rel_foto;
                    }
                ?>
                <article class="product-card" aria-label="<?= e($rel['nama_produk']) ?>">

                    <!-- Foto produk -->
                    <a href="/pages/detail-produk.php?id=<?= e((string) $rel['id_produk']) ?>"
                       class="product-card__image-link"
                       tabindex="-1"
                       aria-hidden="true">
                        <div class="product-card__image-wrap">
                            <img
                                src="<?= e($rel_foto_src) ?>"
                                alt="<?= e($rel['nama_produk']) ?>"
                                class="product-card__image"
                                loading="lazy"
                                onerror="this.src='../assets/img/placeholder.jpg'"
                            >
                        </div>
                    </a>

                    <!-- Konten kartu -->
                    <div class="product-card__body">

                        <!-- Badge kategori -->
                        <span class="product-card__category">
                            <?= e($rel['nama_kategori']) ?>
                        </span>

                        <!-- Nama produk -->
                        <h3 class="product-card__name">
                            <a href="/pages/detail-produk.php?id=<?= e((string) $rel['id_produk']) ?>"
                               class="product-card__name-link">
                                <?= e($rel['nama_produk']) ?>
                            </a>
                        </h3>

                        <!-- Harga -->
                        <p class="product-card__price">
                            <?= e(format_rupiah((int) $rel['harga'])) ?>
                        </p>

                        <!-- Tombol aksi -->
                        <div class="product-card__actions">
                            <a href="/pages/detail-produk.php?id=<?= e((string) $rel['id_produk']) ?>"
                               class="btn btn-secondary btn-sm product-card__btn-detail">
                                Detail
                            </a>
                            <a href="/pages/pemesanan.php?id=<?= e((string) $rel['id_produk']) ?>"
                               class="btn btn-primary btn-sm product-card__btn-order">
                                Pesan
                            </a>
                        </div>

                    </div>
                    <!-- /.product-card__body -->

                </article>
                <?php endforeach; ?>
            </div>
            <!-- /.catalog-grid -->
        </section>
        <?php endif; ?>
        <!-- /.related-section -->

        <?php endif; ?>
        <!-- /if produk -->

    </div>
    <!-- /.container -->
</main>
<!-- /.detail-page -->

<?php include '../components/footer.php'; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     DETAIL PRODUK PAGE STYLES
     Pure CSS, no framework. Reuses product-card classes from katalog.php.
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Halaman ────────────────────────────────────────────────────────────────── */
.detail-page {
    padding-top: var(--sp-10);
    padding-bottom: var(--sp-16);
    min-height: 60vh;
}

/* ── Breadcrumb ─────────────────────────────────────────────────────────────── */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    font-size: 0.875rem;
    color: var(--color-text-muted);
    margin-bottom: var(--sp-8);
    flex-wrap: wrap;
}

.breadcrumb__link {
    color: var(--color-text-muted);
    text-decoration: none;
    transition: color var(--transition);
}

.breadcrumb__link:hover {
    color: var(--color-primary);
}

.breadcrumb__sep {
    color: var(--color-text-muted);
    font-size: 1rem;
}

.breadcrumb__current {
    color: var(--color-primary);
    font-weight: 500;
}

/* ── Layout 2 kolom ─────────────────────────────────────────────────────────── */
.detail-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-16);
    margin-bottom: var(--sp-16);
    align-items: start;
}

@media (max-width: 1023px) {
    .detail-layout {
        grid-template-columns: 1fr;
        gap: var(--sp-8);
    }
}

/* ── Kolom foto ─────────────────────────────────────────────────────────────── */
.detail-image-wrap {
    width: 100%;
    aspect-ratio: 4 / 5;
    border-radius: var(--radius-xl);
    overflow: hidden;
    background-color: var(--color-primary-subtle);
    box-shadow: var(--shadow-md);
    position: relative;
}

.detail-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* ── Kolom info ─────────────────────────────────────────────────────────────── */
.detail-layout__info-col {
    display: flex;
    flex-direction: column;
    gap: var(--sp-4);
}

/* Badge kategori */
.detail-badge-category {
    display: inline-block;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-primary);
    background-color: var(--color-primary-subtle);
    border-radius: var(--radius-pill);
    padding: 4px 14px;
    align-self: flex-start;
}

/* Nama produk — Playfair Display (Req 4.1) */
.detail-product-name {
    font-family: var(--font-heading);
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 700;
    color: var(--color-text-heading);
    line-height: 1.2;
    margin: 0;
}

/* Harga — besar, ungu (Req 4.5) */
.detail-price {
    font-family: var(--font-heading);
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    color: var(--color-primary);
    margin: 0;
}

/* Badge status */
.detail-status-badge {
    align-self: flex-start;
    font-size: 0.875rem;
    padding: 5px 14px;
}

/* Deskripsi */
.detail-description {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--color-text-muted);
    border-top: 1px solid var(--color-border);
    padding-top: var(--sp-4);
}

.detail-description p {
    margin: 0;
    color: var(--color-text-muted);
}

/* Tombol aksi */
.detail-actions {
    display: flex;
    gap: var(--sp-3);
    flex-wrap: wrap;
    margin-top: var(--sp-2);
}

.detail-btn-order,
.detail-btn-back {
    flex: 1 1 auto;
    min-width: 140px;
    justify-content: center;
    text-align: center;
}

@media (max-width: 480px) {
    .detail-actions {
        flex-direction: column;
    }

    .detail-btn-order,
    .detail-btn-back {
        width: 100%;
    }
}

/* ── Produk tidak ditemukan (Req 4.2) ───────────────────────────────────────── */
.detail-not-found {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: var(--sp-16) var(--sp-8);
    gap: var(--sp-4);
    min-height: 50vh;
}

.detail-not-found__icon {
    font-size: 4rem;
    line-height: 1;
}

.detail-not-found__title {
    font-family: var(--font-heading);
    font-size: 1.75rem;
    color: var(--color-text-heading);
    margin: 0;
}

.detail-not-found__text {
    color: var(--color-text-muted);
    font-size: 1rem;
    max-width: 400px;
    margin: 0;
}

/* ── Seksi produk terkait (Req 4.3) ─────────────────────────────────────────── */
.related-section {
    border-top: 1px solid var(--color-border);
    padding-top: var(--sp-12);
}

.related-section__title {
    font-family: var(--font-heading);
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    color: var(--color-text-heading);
    text-align: center;
    margin-bottom: var(--sp-8);
}

/* Grid produk terkait: 4 kolom desktop, 2 tablet, 1 mobile */
.related-grid {
    grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 1023px) {
    .related-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767px) {
    .related-grid {
        grid-template-columns: 1fr;
    }

    .detail-page {
        padding-top: var(--sp-6);
    }
}

/* ── Kartu produk (dibagi dengan katalog.php) ───────────────────────────────── */
.catalog-grid {
    display: grid;
    gap: var(--sp-6);
    margin-bottom: var(--sp-8);
}

.product-card {
    background: var(--color-white);
    border: 1px solid var(--color-primary-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow var(--transition), transform var(--transition);
}

.product-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.product-card__image-link {
    display: block;
    text-decoration: none;
}

.product-card__image-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background-color: var(--color-primary-subtle);
}

.product-card__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.product-card:hover .product-card__image {
    transform: scale(1.05);
}

.product-card__body {
    padding: var(--sp-4);
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: var(--sp-2);
}

.product-card__category {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--color-primary);
    background-color: var(--color-primary-subtle);
    border-radius: var(--radius-pill);
    padding: 2px 10px;
    align-self: flex-start;
}

.product-card__name {
    font-family: var(--font-heading);
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text-heading);
    line-height: 1.3;
    margin: 0;
}

.product-card__name-link {
    color: inherit;
    text-decoration: none;
    transition: color var(--transition);
}

.product-card__name-link:hover {
    color: var(--color-primary);
}

.product-card__price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
}

.product-card__actions {
    display: flex;
    gap: var(--sp-2);
    margin-top: auto;
    padding-top: var(--sp-2);
}

.product-card__btn-detail,
.product-card__btn-order {
    flex: 1;
    text-align: center;
    font-size: 0.8125rem;
    padding: 7px 12px;
}
</style>

</body>
</html>
