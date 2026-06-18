<?php
require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo         = get_pdo();
$page_title  = 'Kontak';
$active_page = 'kontak';
$css_extra   = '/assets/css/pages/kontak.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main>

    <section class="hero hero--short">
        <div class="hero__inner">
            <p class="hero__eyebrow"><i class="bi bi-whatsapp" aria-hidden="true"></i> Hubungi Kami</p>
            <h1 class="hero__title hero__title--clamp">
                Kami Senang Mendengar<br>dari Anda
            </h1>
            <p class="hero__subtitle">
                Punya pertanyaan tentang produk, ingin pesan custom, atau sekadar
                ingin menyapa? Jangan ragu untuk menghubungi kami.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <div class="kontak-grid">

                <div class="stack stack--lg">

                    <div>
                        <p class="eyebrow">Informasi Kontak</p>
                        <h2 class="section__title section__title--small">Cara Menghubungi WanFlorist</h2>
                        <p class="section__copy">
                            Kami tersedia via WhatsApp dan Instagram. Respon biasanya
                            diberikan dalam waktu singkat selama toko aktif beroperasi.
                        </p>
                    </div>

                    <a href="https://wa.me/6285746606624?text=<?= rawurlencode('Halo WanFlorist, saya ingin bertanya tentang produk Anda.') ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="kontak-card kontak-card--action">
                        <div class="kontak-card__icon kontak-card__icon--whatsapp" aria-hidden="true"><i class="bi bi-whatsapp"></i></div>
                        <div class="kontak-card__content">
                            <p class="contact-label contact-label--success">WhatsApp</p>
                            <p class="contact-title">+62 857-4660-6624</p>
                            <p class="contact-text">Klik untuk chat langsung</p>
                        </div>
                        <span class="event-link-icon" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                    </a>

                    <a href="https://instagram.com/wanflorist.id"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="kontak-card kontak-card--action">
                        <div class="kontak-card__icon kontak-card__icon--instagram" aria-hidden="true"><i class="bi bi-instagram"></i></div>
                        <div class="kontak-card__content">
                            <p class="contact-label contact-label--danger">Instagram</p>
                            <p class="contact-title">@wanflorist</p>
                            <p class="contact-text">Lihat koleksi terbaru kami</p>
                        </div>
                        <span class="event-link-icon" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                    </a>

                    <div class="kontak-card">
                        <div class="kontak-card__icon kontak-card__icon--location" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
                        <div class="kontak-card__content">
                            <p class="contact-label contact-label--info">Lokasi Homestore</p>
                            <p class="contact-title">Singojuruh, Banyuwangi</p>
                            <p class="contact-text">Jawa Timur, Indonesia</p>
                        </div>
                    </div>

                </div>

                <div class="stack stack--xl">

                    <div class="panel-card panel-card--info">
                        <h3 class="panel-card__title"><span aria-hidden="true">🕐</span> Jam Operasional</h3>
                        <div class="schedule-list">
                            <?php
                            $jadwal = [
                                ['Senin – Jumat', '08.00 – 20.00 WIB'],
                                ['Sabtu',         '08.00 – 18.00 WIB'],
                                ['Minggu',        '10.00 – 16.00 WIB'],
                            ];
                            foreach ($jadwal as $item):
                            ?>
                            <div class="schedule-item">
                                <span class="schedule-item__day"><?= e($item[0]) ?></span>
                                <span class="schedule-item__time"><?= e($item[1]) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="panel-note">
                            * Jadwal dapat berubah. Cek status toko di halaman utama.
                        </p>
                    </div>

                    <div class="panel-card panel-card--white">
                        <h3 class="panel-card__title"><span aria-hidden="true">💡</span> Tips Pemesanan</h3>
                        <ul class="tips-list">
                            <li class="tips-item">
                                <span class="tips-item__icon" aria-hidden="true">✓</span>
                                Pesan minimal 1 hari sebelum tanggal pengiriman untuk hasil terbaik.
                            </li>
                            <li class="tips-item">
                                <span class="tips-item__icon" aria-hidden="true">✓</span>
                                Untuk pesanan custom atau acara besar, hubungi kami 3–5 hari lebih awal.
                            </li>
                            <li class="tips-item">
                                <span class="tips-item__icon" aria-hidden="true">✓</span>
                                Nomor pesanan Anda bisa dicek langsung di halaman
                                <a href="/pages/cek-pesanan.php" class="link-underline">Cek Pesanan</a>.
                            </li>
                            <li class="tips-item">
                                <span class="tips-item__icon" aria-hidden="true">✓</span>
                                Tersedia layanan antar untuk area Singojuruh dan sekitarnya.
                            </li>
                        </ul>
                    </div>

                </div>

            </div>

        </div>
    </section>

    <section class="section section--primary section--centered">
        <div class="container">
            <h2 class="section__title section__title--compact">
                Siap Membuat Seseorang Bahagia?
            </h2>
            <p class="section__subtitle section__subtitle--wide">
                Mulai dari melihat koleksi hingga memesan buket impian Anda — semuanya mudah.
            </p>
            <div class="btn-group">
                <a href="/pages/katalog.php" class="hero__btn">
                    <i class="bi bi-flower1" aria-hidden="true"></i> Lihat Katalog
                </a>
                <a href="/pages/pemesanan.php" class="hero__btn hero__btn--outline">
                    <i class="bi bi-card-list" aria-hidden="true"></i> Pesan Sekarang
                </a>
            </div>
        </div>
    </section>

</main>

<?php include '../components/footer.php'; ?>

</body>
</html>
