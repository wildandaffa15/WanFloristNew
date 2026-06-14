<?php
/**
 * components/footer.php
 *
 * Footer publik WanFlorist — ditampilkan di semua halaman publik.
 * Layout 3 kolom di desktop, ditumpuk di mobile.
 *
 * Tidak memerlukan input database.
 *
 * Requirements: 2.6, 17.1
 */
?>

<footer class="wf-footer" role="contentinfo">
    <div class="wf-footer__inner">

        <!-- Kolom 1: Logo + Deskripsi + Kontak Sosial -->
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
                    <span class="wf-footer__social-icon" aria-hidden="true">📱</span>
                    WhatsApp
                </a>
                <a href="https://instagram.com/wanflorist"
                   class="wf-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Ikuti WanFlorist di Instagram">
                    <span class="wf-footer__social-icon" aria-hidden="true">📸</span>
                    Instagram
                </a>
            </div>
        </div>

        <!-- Kolom 2: Tautan Cepat -->
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

        <!-- Kolom 3: Informasi Kontak -->
        <div class="wf-footer__col">
            <h2 class="wf-footer__heading">Kontak &amp; Lokasi</h2>
            <address class="wf-footer__address">
                <p class="wf-footer__address-line">
                    <span aria-hidden="true">📍</span>
                    Singojuruh, Banyuwangi<br>
                    Jawa Timur, Indonesia
                </p>
                <p class="wf-footer__address-line">
                    <span aria-hidden="true">📱</span>
                    <a href="https://wa.me/6281234567890"
                       class="wf-footer__link"
                       target="_blank"
                       rel="noopener noreferrer">
                        +62 812-3456-7890
                    </a>
                </p>
                <p class="wf-footer__address-line">
                    <span aria-hidden="true">📸</span>
                    <a href="https://instagram.com/wanflorist"
                       class="wf-footer__link"
                       target="_blank"
                       rel="noopener noreferrer">
                        @wanflorist
                    </a>
                </p>
            </address>
        </div>

    </div><!-- /.wf-footer__inner -->

    <!-- Bottom bar: Copyright -->
    <div class="wf-footer__bottom">
        <p class="wf-footer__copyright">
            &copy; 2025 WanFlorist. Dibuat dengan 🌸 di Singojuruh, Banyuwangi.
        </p>
    </div>
</footer>

<style>
/* ============================================================
   Footer Component — WanFlorist
   Pure CSS, no framework. Responsive via media queries.
   ============================================================ */

.wf-footer {
    width: 100%;
    background-color: #1E1040;
    color: #E9D5FF;           /* dark-text */
    font-family: 'Inter', sans-serif;
    padding-top: 64px;
}

.wf-footer__inner {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr; /* 3 kolom desktop */
    gap: 32px;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 32px 48px 32px;
}

/* --- Kolom 1: Brand ----------------------------------------- */
.wf-footer__logo {
    display: inline-block;
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    color: #E9D5FF;
    text-decoration: none;
    letter-spacing: -0.02em;
    margin-bottom: 12px;
    transition: opacity 0.2s ease;
}

.wf-footer__logo:hover,
.wf-footer__logo:focus {
    opacity: 0.85;
    outline: 2px solid #9333EA;
    outline-offset: 2px;
    border-radius: 4px;
}

.wf-footer__tagline {
    font-size: 14px;
    line-height: 1.65;
    color: #A78BFA;           /* dark-muted */
    max-width: 340px;
    margin: 0 0 20px 0;
}

.wf-footer__socials {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wf-footer__social-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #A78BFA;
    text-decoration: none;
    transition: color 0.2s ease;
}

.wf-footer__social-link:hover,
.wf-footer__social-link:focus {
    color: #E9D5FF;
    outline: none;
    text-decoration: underline;
}

.wf-footer__social-icon {
    font-size: 16px;
    line-height: 1;
}

/* --- Kolom 2 & 3: Nav / Kontak ----------------------------- */
.wf-footer__col {
    display: flex;
    flex-direction: column;
}

.wf-footer__heading {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #E9D5FF;
    margin: 0 0 16px 0;
}

.wf-footer__link-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wf-footer__link {
    font-size: 14px;
    color: #A78BFA;
    text-decoration: none;
    transition: color 0.2s ease;
}

.wf-footer__link:hover,
.wf-footer__link:focus {
    color: #E9D5FF;
    text-decoration: underline;
    outline: none;
}

/* --- Adress ------------------------------------------------- */
.wf-footer__address {
    font-style: normal;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.wf-footer__address-line {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 14px;
    color: #A78BFA;
    line-height: 1.5;
    margin: 0;
}

.wf-footer__address-line span[aria-hidden] {
    flex-shrink: 0;
    margin-top: 1px;
}

/* --- Bottom bar -------------------------------------------- */
.wf-footer__bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    max-width: 1280px;
    margin: 0 auto;
    padding: 20px 32px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.wf-footer__copyright {
    font-size: 13px;
    color: #A78BFA;
    margin: 0;
    text-align: center;
}

/* --- Responsive: tablet (≤1023px) -------------------------- */
@media (max-width: 1023px) {
    .wf-footer__inner {
        grid-template-columns: 1fr 1fr;
        gap: 28px;
    }

    .wf-footer__col--brand {
        grid-column: 1 / -1; /* ocupa toda a largura */
    }
}

/* --- Responsive: mobile (<768px) --------------------------- */
@media (max-width: 767px) {
    .wf-footer {
        padding-top: 48px;
    }

    .wf-footer__inner {
        grid-template-columns: 1fr;
        padding: 0 20px 40px 20px;
        gap: 32px;
    }

    .wf-footer__col--brand {
        grid-column: unset;
    }

    .wf-footer__bottom {
        padding: 16px 20px;
    }
}
</style>
