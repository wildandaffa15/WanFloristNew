<?php
declare(strict_types=1);

require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo = get_pdo();

$q        = trim($_GET['q']        ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$urut     = $_GET['urut']          ?? 'terbaru';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 12;

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
$css_extra   = '/assets/css/pages/katalog.css';
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

</body>
</html>
