<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo = get_pdo();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT p.*, k.nama_kategori
     FROM   produk p
     JOIN   kategori k ON p.id_kategori = k.id_kategori
     WHERE  p.id_produk = :id"
);
$stmt->execute([':id' => $id]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

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

$page_title  = $produk ? $produk['nama_produk'] : 'Produk Tidak Ditemukan';
$css_extra   = '/assets/css/pages/detail-produk.css';
$active_page = 'produk';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main class="detail-page" id="main-content">
    <div class="container">

        <?php if (!$produk): ?>
        <div class="detail-not-found" role="alert">
            <div class="detail-not-found__icon" aria-hidden="true"><i class="bi bi-flower1"></i></div>
            <h1 class="detail-not-found__title">Produk Tidak Ditemukan</h1>
            <p class="detail-not-found__text">
                Produk yang Anda cari tidak tersedia atau telah dihapus.
            </p>
            <a href="/pages/katalog.php" class="btn btn-primary">
                Kembali ke Katalog
            </a>
        </div>

        <?php else: ?>
        <?php $foto_src = produk_foto_src($produk['foto'] ?? null, '../'); ?>

        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/index.php" class="breadcrumb__link">Beranda</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <a href="/pages/katalog.php" class="breadcrumb__link">Produk</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current" aria-current="page">
                <?= e($produk['nama_produk']) ?>
            </span>
        </nav>

        <div class="detail-layout">

            <div class="detail-layout__image-col">
                <div class="detail-image-wrap">
                    <img
                        src="<?= e($foto_src) ?>"
                        alt="<?= e($produk['nama_produk']) ?>"
                        class="detail-image"
                        onerror="this.src='<?= e(produk_foto_src(null, '../')) ?>'"
                    >
                </div>
            </div>

            <div class="detail-layout__info-col">

                <span class="detail-badge-category">
                    <?= e($produk['nama_kategori']) ?>
                </span>

                <h1 class="detail-product-name">
                    <?= e($produk['nama_produk']) ?>
                </h1>

                <p class="detail-price">
                    <?= e(format_rupiah((int) $produk['harga'])) ?>
                </p>

                <?php if (($produk['status'] ?? '') === 'tersedia'): ?>
                <span class="badge badge-hijau detail-status-badge">
                    ● Tersedia
                </span>
                <?php else: ?>
                <span class="badge badge-secondary detail-status-badge">
                    ● Tidak Tersedia
                </span>
                <?php endif; ?>

                <?php if (!empty($produk['deskripsi'])): ?>
                <div class="detail-description">
                    <p><?= e($produk['deskripsi']) ?></p>
                </div>
                <?php endif; ?>

                <div class="detail-actions">
                    <a
                        href="/pages/pemesanan.php?id=<?= e((string) $produk['id_produk']) ?>"
                        class="btn btn-primary btn-lg detail-btn-order"
                    >
                        Pesan Sekarang
                    </a>

                    <a href="/pages/katalog.php" class="btn btn-secondary btn-lg detail-btn-back">
                        Kembali ke Katalog
                    </a>
                </div>

            </div>

        </div>

        <?php if (!empty($related_products)): ?>
        <section class="related-section" aria-labelledby="related-heading">
            <h2 class="related-section__title" id="related-heading">
                Produk Terkait
            </h2>

            <div class="catalog-grid related-grid">
                <?php foreach ($related_products as $rel): ?>
                <?php $rel_foto_src = produk_foto_src($rel['foto'] ?? null, '../'); ?>
                <article class="product-card" aria-label="<?= e($rel['nama_produk']) ?>">

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
                                onerror="this.src='<?= e(produk_foto_src(null, '../')) ?>'"
                            >
                        </div>
                    </a>

                    <div class="product-card__body">

                        <span class="product-card__category">
                            <?= e($rel['nama_kategori']) ?>
                        </span>

                        <h3 class="product-card__name">
                            <a href="/pages/detail-produk.php?id=<?= e((string) $rel['id_produk']) ?>"
                               class="product-card__name-link">
                                <?= e($rel['nama_produk']) ?>
                            </a>
                        </h3>

                        <p class="product-card__price">
                            <?= e(format_rupiah((int) $rel['harga'])) ?>
                        </p>

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

                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php include '../components/footer.php'; ?>

</body>
</html>
