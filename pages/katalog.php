<?php
/**
 * pages/katalog.php
 *
 * Halaman katalog produk publik WanFlorist.
 * Mendukung pencarian teks, filter kategori, pengurutan, dan paginasi.
 *
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 3.9
 */

declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo = get_pdo();

$q        = trim($_GET['q']        ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$urut     = $_GET['urut']          ?? 'terbaru';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 12;

// Hanya produk dengan status 'tersedia' yang ditampilkan (Req 3.1)
$where_clauses = ["p.status = 'tersedia'"];
$params        = [];

if ($q !== '') {
    $where_clauses[] = "(p.nama_produk LIKE :q OR p.deskripsi LIKE :q)";
    $params[':q']    = '%' . $q . '%';
}

if ($kategori !== '') {
    $where_clauses[] = "k.slug = :kategori";
    $params[':kategori'] = $kategori;
}

$where_sql = implode(' AND ', $where_clauses);

$order_sql = match ($urut) {
    'harga_asc'  => 'p.harga ASC',
    'harga_desc' => 'p.harga DESC',
    default      => 'p.created_at DESC',
};

$count_sql = "
    SELECT COUNT(*)
    FROM   produk p
    JOIN   kategori k ON p.id_kategori = k.id_kategori
    WHERE  {$where_sql}
";
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($params);
$total_produk = (int) $stmt_count->fetchColumn();
$total_pages  = max(1, (int) ceil($total_produk / $per_page));
$page         = min($page, $total_pages);
$offset       = ($page - 1) * $per_page;

$sql = "
    SELECT p.*,
           k.nama_kategori,
           k.slug AS kategori_slug
    FROM   produk p
    JOIN   kategori k ON p.id_kategori = k.id_kategori
    WHERE  {$where_sql}
    ORDER BY {$order_sql}
    LIMIT  :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();
$produk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_kat = $pdo->prepare(
    "SELECT * FROM kategori WHERE is_active = 1 ORDER BY nama_kategori ASC"
);
$stmt_kat->execute();
$all_kategori = $stmt_kat->fetchAll(PDO::FETCH_ASSOC);

function build_url(array $params): string
{
    return '?' . http_build_query(
        array_filter($params, fn($v) => $v !== '' && $v !== null)
    );
}

$page_title  = 'Katalog Produk';
$css_extra   = '/assets/css/public.css';
$active_page = 'produk';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main class="katalog-page" id="main-content">
    <div class="container">

        <div class="katalog-header">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="/index.php" class="breadcrumb__link">Beranda</a>
                <span class="breadcrumb__sep" aria-hidden="true">›</span>
                <span class="breadcrumb__current" aria-current="page">Produk</span>
            </nav>

            <div class="katalog-header__title-row">
                <div>
                    <h1 class="katalog-header__title">Katalog Produk</h1>
                    <p class="katalog-header__subtitle">
                        Temukan koleksi rangkaian bunga terbaik kami untuk setiap momen spesial Anda.
                        Dibuat dengan penuh cinta dan dedikasi.
                    </p>
                </div>
                <p class="katalog-header__count">
                    <?= e((string) $total_produk) ?> produk ditemukan
                </p>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" action="" class="filter-bar__form" role="search">

                <div class="filter-bar__search-wrap">
                    <label for="filter-q" class="sr-only">Cari produk</label>
                    <input
                        type="search"
                        id="filter-q"
                        name="q"
                        class="filter-bar__input filter-bar__input--search"
                        placeholder="Cari produk..."
                        value="<?= e($q) ?>"
                        autocomplete="off"
                    >
                </div>

                <div class="filter-bar__select-wrap">
                    <label for="filter-kategori" class="sr-only">Filter kategori</label>
                    <select id="filter-kategori" name="kategori" class="filter-bar__select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($all_kategori as $kat): ?>
                        <option
                            value="<?= e($kat['slug']) ?>"
                            <?= ($kategori === $kat['slug']) ? 'selected' : '' ?>
                        >
                            <?= e($kat['nama_kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-bar__select-wrap">
                    <label for="filter-urut" class="sr-only">Urutkan berdasarkan</label>
                    <select id="filter-urut" name="urut" class="filter-bar__select">
                        <option value="terbaru"    <?= ($urut === 'terbaru')    ? 'selected' : '' ?>>Terbaru</option>
                        <option value="harga_asc"  <?= ($urut === 'harga_asc')  ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                        <option value="harga_desc" <?= ($urut === 'harga_desc') ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary filter-bar__btn">
                    Cari
                </button>

                <?php if ($q !== '' || $kategori !== '' || $urut !== 'terbaru'): ?>
                <a href="/pages/katalog.php" class="btn btn-ghost filter-bar__btn">
                    Reset
                </a>
                <?php endif; ?>

            </form>
        </div>

        <div class="category-pills" role="navigation" aria-label="Filter kategori cepat">
            <div class="category-pills__scroll">
                <a
                    href="<?= e(build_url(['q' => $q, 'urut' => $urut])) ?>"
                    class="category-pill <?= ($kategori === '') ? 'category-pill--active' : '' ?>"
                    <?= ($kategori === '') ? 'aria-current="true"' : '' ?>
                >
                    Semua
                </a>

                <?php foreach ($all_kategori as $kat): ?>
                <a
                    href="<?= e(build_url(['q' => $q, 'kategori' => $kat['slug'], 'urut' => $urut])) ?>"
                    class="category-pill <?= ($kategori === $kat['slug']) ? 'category-pill--active' : '' ?>"
                    <?= ($kategori === $kat['slug']) ? 'aria-current="true"' : '' ?>
                >
                        <?= e($kat['nama_kategori']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($produk_list)): ?>
        <div class="empty-state" role="status">
            <div class="empty-state__icon" aria-hidden="true"><i class="bi bi-flower1"></i></div>
            <h2 class="empty-state__title">Produk tidak ditemukan.</h2>
            <p class="empty-state__text">
                Coba ubah kata kunci pencarian atau pilih kategori yang berbeda.
            </p>
            <a href="/pages/katalog.php" class="btn btn-primary">
                Lihat Semua Produk
            </a>
        </div>

        <?php else: ?>
        <div class="catalog-grid" aria-label="Daftar produk">

            <?php foreach ($produk_list as $produk): ?>
            <?php $foto_src = produk_foto_src($produk['foto'] ?? null, '../'); ?>
            <article class="product-card" aria-label="<?= e($produk['nama_produk']) ?>">

                <a href="/pages/detail-produk.php?id=<?= e((string) $produk['id_produk']) ?>"
                   class="product-card__image-link"
                   tabindex="-1"
                   aria-hidden="true">
                    <div class="product-card__image-wrap">
                        <img
                            src="<?= e($foto_src) ?>"
                            alt="<?= e($produk['nama_produk']) ?>"
                            class="product-card__image"
                            loading="lazy"
                            onerror="this.src='<?= e(produk_foto_src(null, '../')) ?>'"
                        >
                    </div>
                </a>

                <div class="product-card__body">

                    <span class="product-card__category">
                        <?= e($produk['nama_kategori']) ?>
                    </span>

                    <h2 class="product-card__name">
                        <a href="/pages/detail-produk.php?id=<?= e((string) $produk['id_produk']) ?>"
                           class="product-card__name-link">
                            <?= e($produk['nama_produk']) ?>
                        </a>
                    </h2>

                    <p class="product-card__price">
                        <?= e(format_rupiah((int) $produk['harga'])) ?>
                    </p>

                    <div class="product-card__actions">
                        <a href="/pages/detail-produk.php?id=<?= e((string) $produk['id_produk']) ?>"
                           class="btn btn-secondary btn-sm product-card__btn-detail">
                            Detail
                        </a>

                        <a href="/pages/pemesanan.php?id=<?= e((string) $produk['id_produk']) ?>"
                           class="btn btn-primary btn-sm product-card__btn-order">
                            Pesan
                        </a>
                    </div>

                </div>

            </article>
            <?php endforeach; ?>

        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="pagination-wrapper" aria-label="Navigasi halaman">
            <ul class="pagination">

                <?php if ($page > 1): ?>
                <li>
                    <a href="<?= e(build_url(['q' => $q, 'kategori' => $kategori, 'urut' => $urut, 'page' => $page - 1])) ?>"
                       class="pagination__btn pagination__btn--prev"
                       aria-label="Halaman sebelumnya">
                        &lsaquo;
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <span class="pagination__btn pagination__btn--prev disabled" aria-disabled="true">&lsaquo;</span>
                </li>
                <?php endif; ?>

                <?php
                $window = 2;
                $start  = max(1, $page - $window);
                $end    = min($total_pages, $page + $window);

                if ($start > 1): ?>
                <li>
                    <a href="<?= e(build_url(['q' => $q, 'kategori' => $kategori, 'urut' => $urut, 'page' => 1])) ?>"
                       class="pagination__btn">1</a>
                </li>
                <?php if ($start > 2): ?>
                <li><span class="pagination__ellipsis" aria-hidden="true">…</span></li>
                <?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $start; $p <= $end; $p++): ?>
                <li>
                    <?php if ($p === $page): ?>
                    <span class="pagination__btn active" aria-current="page"
                          aria-label="Halaman <?= $p ?>">
                        <?= $p ?>
                    </span>
                    <?php else: ?>
                    <a href="<?= e(build_url(['q' => $q, 'kategori' => $kategori, 'urut' => $urut, 'page' => $p])) ?>"
                       class="pagination__btn"
                       aria-label="Halaman <?= $p ?>">
                        <?= $p ?>
                    </a>
                    <?php endif; ?>
                </li>
                <?php endfor; ?>

                <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                <li><span class="pagination__ellipsis" aria-hidden="true">…</span></li>
                <?php endif; ?>
                <li>
                    <a href="<?= e(build_url(['q' => $q, 'kategori' => $kategori, 'urut' => $urut, 'page' => $total_pages])) ?>"
                       class="pagination__btn">
                        <?= $total_pages ?>
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                <li>
                    <a href="<?= e(build_url(['q' => $q, 'kategori' => $kategori, 'urut' => $urut, 'page' => $page + 1])) ?>"
                       class="pagination__btn pagination__btn--next"
                       aria-label="Halaman berikutnya">
                        &rsaquo;
                    </a>
                </li>
                <?php else: ?>
                <li>
                    <span class="pagination__btn pagination__btn--next disabled" aria-disabled="true">&rsaquo;</span>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</main>

<?php include '../components/footer.php'; ?>

<style>
.katalog-page {
    padding-top: var(--sp-10);
    padding-bottom: var(--sp-16);
    min-height: 60vh;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    font-size: 0.875rem;
    color: var(--color-text-muted);
    margin-bottom: var(--sp-4);
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

.katalog-header {
    margin-bottom: var(--sp-8);
}

.katalog-header__title-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: var(--sp-4);
    flex-wrap: wrap;
}

.katalog-header__title {
    font-family: var(--font-heading);
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    color: var(--color-primary);
    font-weight: 700;
    margin-bottom: var(--sp-2);
}

.katalog-header__subtitle {
    font-size: 1rem;
    color: var(--color-text-muted);
    max-width: 600px;
    line-height: 1.6;
    margin-bottom: 0;
}

.katalog-header__count {
    font-size: 0.875rem;
    color: var(--color-text-muted);
    white-space: nowrap;
    margin-bottom: 0;
    flex-shrink: 0;
}

.filter-bar {
    background: var(--color-surface-alt, #FAFAFA);
    border: 1px solid var(--color-primary-border);
    border-radius: var(--radius-xl);
    padding: var(--sp-5) var(--sp-6);
    margin-bottom: var(--sp-6);
    box-shadow: var(--shadow-sm);
}

.filter-bar__form {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    flex-wrap: wrap;
}

.filter-bar__search-wrap {
    flex: 1 1 200px;
    min-width: 160px;
    position: relative;
}

.filter-bar__input--search {
    width: 100%;
    padding: 10px 16px 10px 16px;
    border: 1.5px solid var(--color-primary-border);
    border-radius: var(--radius-pill);
    font-size: 0.9375rem;
    background: var(--color-white);
    color: var(--color-text);
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
    appearance: none;
    -webkit-appearance: none;
}

.filter-bar__input--search:focus {
    border-color: var(--color-primary-light);
    box-shadow: 0 0 0 3px rgba(107, 33, 168, .12);
}

.filter-bar__input--search::placeholder {
    color: var(--color-text-muted);
}

.filter-bar__select-wrap {
    flex: 0 1 200px;
    min-width: 140px;
}

.filter-bar__select {
    width: 100%;
    padding: 10px 40px 10px 16px;
    border: 1.5px solid var(--color-primary-border);
    border-radius: var(--radius-pill);
    font-size: 0.9375rem;
    background-color: var(--color-white);
    color: var(--color-text);
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http:
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 14px;
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
}

.filter-bar__select:focus {
    border-color: var(--color-primary-light);
    box-shadow: 0 0 0 3px rgba(107, 33, 168, .12);
}

.filter-bar__btn {
    flex-shrink: 0;
    padding: 10px 22px;
    font-size: 0.9375rem;
}

.category-pills {
    margin-bottom: var(--sp-8);
}

.category-pills__scroll {
    display: flex;
    gap: var(--sp-2);
    overflow-x: auto;
    padding-bottom: var(--sp-2);
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.category-pills__scroll::-webkit-scrollbar {
    display: none;
}

.category-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    padding: 8px 20px;
    border-radius: var(--radius-pill);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border: 1.5px solid var(--color-primary-border);
    background-color: var(--color-primary-subtle);
    color: var(--color-primary);
    transition: background-color var(--transition), color var(--transition), border-color var(--transition);
    cursor: pointer;
}

.category-pill:hover {
    background-color: var(--color-primary);
    color: var(--color-white);
    border-color: var(--color-primary);
}

.category-pill--active {
    background-color: var(--color-primary);
    color: var(--color-white);
    border-color: var(--color-primary);
}

.category-pill--active:hover {
    background-color: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
}

.catalog-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-6);
    margin-bottom: var(--sp-10);
}

@media (max-width: 1023px) {
    .catalog-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767px) {
    .catalog-grid {
        grid-template-columns: 1fr;
        gap: var(--sp-4);
    }
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
    background-color: #F5F0FF;
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

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: var(--sp-16) var(--sp-8);
    gap: var(--sp-4);
}

.empty-state__icon {
    font-size: 4rem;
    line-height: 1;
}

.empty-state__title {
    font-family: var(--font-heading);
    font-size: 1.5rem;
    color: var(--color-text-heading);
    margin: 0;
}

.empty-state__text {
    color: var(--color-text-muted);
    font-size: 0.9375rem;
    max-width: 360px;
    margin: 0;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: var(--sp-10);
}

.pagination {
    display: flex;
    align-items: center;
    gap: var(--sp-1);
    list-style: none;
    padding: 0;
    margin: 0;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 var(--sp-2);
    border-radius: var(--radius-pill);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text);
    border: 1.5px solid var(--color-border);
    text-decoration: none;
    transition: background-color var(--transition), color var(--transition), border-color var(--transition);
    cursor: pointer;
    background: var(--color-white);
    user-select: none;
}

.pagination__btn:hover:not(.disabled):not(.active) {
    background-color: var(--color-primary-subtle);
    border-color: var(--color-primary-border);
    color: var(--color-primary);
}

.pagination__btn.active {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    cursor: default;
}

.pagination__btn.disabled {
    opacity: 0.4;
    pointer-events: none;
    cursor: default;
}

.pagination__ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 40px;
    color: var(--color-text-muted);
    font-size: 0.875rem;
    user-select: none;
}

@media (max-width: 767px) {
    .filter-bar {
        padding: var(--sp-4);
        border-radius: var(--radius-lg);
    }

    .filter-bar__form {
        flex-direction: column;
        gap: var(--sp-3);
        align-items: stretch;
    }

    .filter-bar__search-wrap,
    .filter-bar__select-wrap {
        flex: none;
        width: 100%;
        min-width: unset;
    }

    .filter-bar__btn {
        width: 100%;
        justify-content: center;
    }

    .katalog-header__title-row {
        flex-direction: column;
        align-items: flex-start;
    }

    .katalog-page {
        padding-top: var(--sp-6);
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .filter-bar__form {
        flex-wrap: wrap;
    }

    .filter-bar__search-wrap {
        flex: 1 1 100%;
    }
}
</style>

</body>
</html>
