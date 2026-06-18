<?php
/**
 * components/footer.php
 *
 * Footer publik WanFlorist — ditampilkan di semua halaman publik.
 * Layout 3 kolom di desktop, ditumpuk di mobile.
 *
 * Tidak memerlukan input database.
 */
?>

<footer class="wf-footer" role="contentinfo">
    <div class="wf-footer__inner">

        <div class="wf-footer__col wf-footer__col--brand">
            <a href="/index.php" class="wf-footer__logo" aria-label="WanFlorist — Beranda">
                WanFlorist
            </a>
            <p class="wf-footer__tagline">
                Toko buket bunga terpercaya di Singojuruh, Banyuwangi. Menghadirkan
                keindahan dan kesegaran dalam setiap rangkaian untuk momen spesial Anda.
            </p>

            <div class="wf-footer__socials">
                <a href="https://wa.me/6281234567890"
                   class="wf-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Hubungi WanFlorist via WhatsApp">
                    <span class="wf-footer__social-icon" aria-hidden="true"><i class="bi bi-whatsapp"></i></span>
                    WhatsApp
                </a>
                <a href="https://instagram.com/wanflorist"
                   class="wf-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Ikuti WanFlorist di Instagram">
                    <span class="wf-footer__social-icon" aria-hidden="true"><i class="bi bi-instagram"></i></span>
                    Instagram
                </a>
            </div>
        </div>

        <div class="wf-footer__col">
            <h2 class="wf-footer__heading">Tautan Cepat</h2>
            <nav aria-label="Tautan cepat footer">
                <ul class="wf-footer__link-list">
                    <li>
                        <a href="/index.php" class="wf-footer__link">Beranda</a>
                    </li>
                    <li>
                        <a href="/pages/katalog.php" class="wf-footer__link">Katalog Produk</a>
                    </li>
                    <li>
                        <a href="/pages/pemesanan.php" class="wf-footer__link">Form Pemesanan</a>
                    </li>
                    <li>
                        <a href="/pages/cek-pesanan.php" class="wf-footer__link">Cek Status Pesanan</a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="wf-footer__col">
            <h2 class="wf-footer__heading">Kontak &amp; Lokasi</h2>
            <address class="wf-footer__address">
                <p class="wf-footer__address-line">
                    <span aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                    Singojuruh, Banyuwangi<br>
                    Jawa Timur, Indonesia
                </p>
                <p class="wf-footer__address-line">
                    <span aria-hidden="true"><i class="bi bi-whatsapp"></i></span>
                    <a href="https://wa.me/+6285746606624"
                       class="wf-footer__link"
                       target="_blank"
                       rel="noopener noreferrer">
                        +62 812-3456-7890
                    </a>
                </p>
                <p class="wf-footer__address-line">
                    <span aria-hidden="true"><i class="bi bi-instagram"></i></span>
                    <a href="https://www.instagram.com/wanflorist.id/"
                       class="wf-footer__link"
                       target="_blank"
                       rel="noopener noreferrer">
                        @wanflorist
                    </a>
                </p>
            </address>
        </div>

    </div>

    <div class="wf-footer__bottom">
        <p class="wf-footer__copyright">
            &copy; 2025 WanFlorist. Dibuat dengan <i class="bi bi-flower1" aria-hidden="true"></i> di Singojuruh, Banyuwangi.
        </p>
    </div>
</footer>
