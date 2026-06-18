<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

$pdo = get_pdo();

$stmt_kategori = $pdo->prepare(
    "SELECT * FROM kategori
     WHERE is_active = 1
     ORDER BY nama_kategori ASC"
);
$stmt_kategori->execute();
$categories = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);

$page_title  = 'Beranda';
$active_page = 'beranda';
$css_extra   = '/assets/css/public.css';
?>
<?php include 'components/head.php'; ?>
<body>

<?php include 'components/navbar.php'; ?>

<section class="hero hero--home" aria-label="Selamat datang di WanFlorist">
    <div class="hero__slides" aria-hidden="true">
        <img src="assets/img/hero/hero_1.webp" alt="" class="hero__slide" loading="eager" decoding="async">
        <img src="assets/img/hero/hero_2.webp" alt="" class="hero__slide" loading="lazy" decoding="async">
    </div>
    <div class="hero__overlay" aria-hidden="true"></div>
    <div class="hero__inner">
        <h1 class="hero__title">
            Buket Bunga Artisanal<br>dari Singojuruh, Banyuwangi
        </h1>
        <p class="hero__subtitle">
            Rangkaian bunga segar pilihan, dikemas dengan penuh kasih sayang
            untuk setiap momen istimewa Anda.
        </p>
        <a href="pages/katalog.php" class="hero__btn">
            <i class="bi bi-flower1" aria-hidden="true"></i> Belanja Sekarang
        </a>
    </div>
</section>

<section class="section" aria-label="Koleksi kategori produk">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Koleksi Kami</h2>
            <div class="section__title-underline" aria-hidden="true"></div>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="category-pills" role="list">
            <?php foreach ($categories as $cat): ?>
            <a href="pages/katalog.php?kategori=<?= e($cat['slug']) ?>"
               class="category-pill"
               role="listitem"
               aria-label="Lihat produk kategori <?= e($cat['nama_kategori']) ?>">
                <?= e($cat['nama_kategori']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center text-muted">Belum ada kategori yang tersedia.</p>
        <?php endif; ?>
    </div>
</section>

<section class="section section--primary" aria-label="Hubungi kami">
    <div class="container text-center">
        <h2 class="section__title">Siap Memesan Buket Impian Anda?</h2>
        <p class="section__subtitle">
            Hubungi kami via WhatsApp atau isi form pemesanan online kami.
        </p>
        <div class="public-cta__actions">
                <a href="pages/pemesanan.php"
                   class="hero__btn"
                   aria-label="Isi form pemesanan online">
                    <i class="bi bi-journal-text" aria-hidden="true"></i> Form Pemesanan
                </a>
                <a href="https://wa.me/6281234567890"
                   class="hero__btn hero__btn--outline"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Hubungi WanFlorist via WhatsApp">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp Kami
                </a>
        </div>
    </div>
</section>

<section class="section" aria-label="Testimoni pelanggan">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Kata Mereka</h2>
            <div class="section__title-underline" aria-hidden="true"></div>
            <p class="section__subtitle">Pelanggan yang mempercayakan momen spesialnya kepada kami</p>
        </div>

        <div class="catalog-grid">

            <article class="card testimonial-card" aria-label="Testimoni dari Andi Setiawan">
                <div class="testimonial-rating" aria-label="Rating: 5 bintang">
                    <span class="testimonial-stars" aria-hidden="true">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </span>
                </div>
                <blockquote>
                    <p class="testimonial-text">
                        "Bunganya sangat segar dan rangkaiannya cantik sekali! Pengiriman juga tepat waktu.
                        Pacar saya sangat suka dengan buket mawarnya. Pasti pesan lagi!"
                    </p>
                </blockquote>
                <footer class="testimonial-footer">
                    <div class="testimonial-avatar" aria-hidden="true">A</div>
                    <div>
                        <p class="testimonial-author">Andi Setiawan</p>
                        <p class="testimonial-meta">Pesan: Buket Mawar Merah</p>
                    </div>
                </footer>
            </article>

            <article class="card testimonial-card" aria-label="Testimoni dari Rina Kusuma">
                <div class="testimonial-rating" aria-label="Rating: 5 bintang">
                    <span class="testimonial-stars" aria-hidden="true"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                </div>
                <blockquote>
                    <p class="testimonial-text">
                        "Pelayanannya sangat ramah, bisa custom buket sesuai budget. Hasilnya melebihi ekspektasi.
                        Cocok banget buat hadiah wisuda dan anniversary!"
                    </p>
                </blockquote>
                <footer class="testimonial-footer">
                    <div class="testimonial-avatar" aria-hidden="true">R</div>
                    <div>
                        <p class="testimonial-author">Rina Kusuma</p>
                        <p class="testimonial-meta">Pesan: Buket Mix Pastel</p>
                    </div>
                </footer>
            </article>

            <article class="card testimonial-card" aria-label="Testimoni dari Bagas Pratama">
                <div class="testimonial-rating" aria-label="Rating: 5 bintang">
                    <span class="testimonial-stars" aria-hidden="true"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                </div>
                <blockquote>
                    <p class="testimonial-text">
                        "Saya pesan untuk dekorasi pernikahan adik saya. WanFlorist benar-benar profesional,
                        buket hand bouquet dan standing flower-nya indah banget. Semua tamu takjub!"
                    </p>
                </blockquote>
                <footer class="testimonial-footer">
                    <div class="testimonial-avatar" aria-hidden="true">B</div>
                    <div>
                        <p class="testimonial-author">Bagas Pratama</p>
                        <p class="testimonial-meta">Pesan: Paket Wedding Bouquet</p>
                    </div>
                </footer>
            </article>

            <article class="card testimonial-card" aria-label="Testimoni dari Dewi Anggraini">
                <div class="testimonial-rating" aria-label="Rating: 5 bintang">
                    <span class="testimonial-stars" aria-hidden="true"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span>
                </div>
                <blockquote>
                    <p class="testimonial-text">
                        "Buket sunflower-nya segar dan tahan lama, lebih dari seminggu masih cantik!
                        Harga juga terjangkau untuk kualitas sebagus ini. Rekomended banget!"
                    </p>
                </blockquote>
                <footer class="testimonial-footer">
                    <div class="testimonial-avatar" aria-hidden="true">D</div>
                    <div>
                        <p class="testimonial-author">Dewi Anggraini</p>
                        <p class="testimonial-meta">Pesan: Sunshine Sunflower</p>
                    </div>
                </footer>
            </article>

        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

</body>
</html>
