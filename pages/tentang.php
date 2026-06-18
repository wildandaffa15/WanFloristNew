<?php
/**
 * pages/tentang.php — Halaman Tentang Kami WanFlorist
 *
 * Menampilkan kisah pemilik, lokasi, dan misi toko.
 * Layout publik: head + navbar + footer.
 */

require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo         = get_pdo();
$page_title  = 'Tentang Kami';
$active_page = 'tentang';
$css_extra   = '/assets/css/public.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main>

    <section class="hero hero--medium">
        <div class="hero__inner">
            <p class="hero__eyebrow"><i class="bi bi-flower1" aria-hidden="true"></i> Kisah Kami</p>
            <h1 class="hero__title">
                Tentang WanFlorist
            </h1>
            <p class="hero__subtitle">
                Buket bunga artisanal dari hati, untuk setiap momen spesial Anda.
                Dibuat dengan penuh cinta di Singojuruh, Banyuwangi.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="about-grid">

                <div class="about-figure">
                    <div class="about-illustration" aria-hidden="true">
                        <i class="bi bi-flower1" aria-hidden="true"></i>
                        <span class="decor-small"><i class="bi bi-flower2"></i></span>
                        <span class="decor-top"><i class="bi bi-flower3"></i></span>
                    </div>
                </div>

                <div>
                    <p class="eyebrow">Kisah Pemilik</p>

                    <h2 class="section__title">
                        Dari Kecintaan pada Bunga,<br>
                        Lahirlah WanFlorist
                    </h2>

                    <p>
                        WanFlorist bermula dari passion yang sederhana — kecintaan mendalam terhadap
                        keindahan bunga dan keinginan untuk berbagi kebahagiaan melalui setiap rangkaian
                        yang dibuat dengan tangan. Berawal dari hobi merangkai di rumah, kini WanFlorist
                        hadir untuk melayani berbagai kebutuhan bunga di Banyuwangi dan sekitarnya.
                    </p>

                    <p>
                        Setiap buket dirancang dengan perhatian penuh terhadap detail — dari pemilihan
                        bunga segar, perpaduan warna yang harmonis, hingga sentuhan pita dan kemasan yang
                        elegan. Kami percaya bahwa sebuah buket bukan sekadar hadiah; ia adalah ungkapan
                        perasaan yang tidak bisa disampaikan hanya dengan kata-kata.
                    </p>

                    <div class="about-card">
                        <span class="about-card__icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <div>
                            <p class="about-card__title">Lokasi Homestore</p>
                            <p class="about-card__text">Singojuruh, Banyuwangi, Jawa Timur</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="section section--alt">
        <div class="container">

            <div class="section__header">
                <p class="eyebrow">Mengapa WanFlorist</p>
                <h2 class="section__title">Misi &amp; Nilai Kami</h2>
                <div class="section__title-underline"></div>
                <p class="section__subtitle section__subtitle--spaced">
                    Kami hadir bukan sekadar untuk menjual bunga —
                    kami hadir untuk menciptakan kenangan yang tak terlupakan.
                </p>
            </div>

            <div class="values-grid">

                <div class="value-card">
                    <div class="value-icon" aria-hidden="true"><i class="bi bi-flower1"></i></div>
                    <h3>Kualitas Terjaga</h3>
                    <p>
                        Hanya bunga segar pilihan yang digunakan dalam setiap rangkaian.
                        Kami selektif dalam memilih bahan agar hasil selalu memuaskan.
                    </p>
                </div>

                <div class="value-card">
                    <div class="value-icon" aria-hidden="true"><i class="bi bi-heart-fill"></i></div>
                    <h3>Sentuhan Personal</h3>
                    <p>
                        Setiap pesanan dibuat khusus sesuai keinginan Anda. Tidak ada buket
                        yang identik — semuanya dirancang untuk momen unik Anda.
                    </p>
                </div>

                <div class="value-card">
                    <div class="value-icon" aria-hidden="true"><i class="bi bi-house-fill"></i></div>
                    <h3>Lokal &amp; Terpercaya</h3>
                    <p>
                        Kami adalah usaha lokal yang bangga melayani masyarakat Banyuwangi.
                        Kepercayaan pelanggan adalah prioritas utama kami.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section class="section section--primary section--centered">
        <div class="container">
            <h2 class="section__title section__title--compact">
                Siap Memesan Buket Impian Anda?
            </h2>
            <p class="section__subtitle section__subtitle--wide">
                Jelajahi koleksi kami atau hubungi langsung untuk pesanan custom.
            </p>
            <div class="btn-group">
                <a href="/pages/katalog.php" class="hero__btn">
                    <i class="bi bi-flower1" aria-hidden="true"></i> Lihat Katalog
                </a>
                <a href="/pages/kontak.php" class="hero__btn hero__btn--outline">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i> Hubungi Kami
                </a>
            </div>
        </div>
    </section>

</main>

<?php include '../components/footer.php'; ?>

</body>
</html>
