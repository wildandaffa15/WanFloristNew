<?php
/**
 * pages/kontak.php — Halaman Kontak WanFlorist
 *
 * Menampilkan info kontak: WhatsApp, alamat, dan cara menghubungi.
 * Layout publik: head + navbar + footer. Tidak ada form pengiriman.
 */

require_once '../config/database.php';
require_once '../config/helpers.php';

$pdo         = get_pdo();
$page_title  = 'Kontak';
$active_page = 'kontak';
$css_extra   = '/assets/css/public.css';
?>
<!DOCTYPE html>
<html lang="id">
<?php include '../components/head.php'; ?>
<body>

<?php include '../components/navbar.php'; ?>

<main>

    <!-- ═══════════════════════════════════════════
         HERO — Kontak
         ═══════════════════════════════════════════ -->
    <section class="hero" style="min-height:40vh;padding:3rem 1.5rem;">
        <div class="hero__inner">
            <p style="
                font-family:'Inter',sans-serif;
                font-size:0.8125rem;
                font-weight:600;
                letter-spacing:0.1em;
                text-transform:uppercase;
                color:rgba(255,255,255,0.65);
                margin-bottom:0.75rem;
            ">📱 Hubungi Kami</p>
            <h1 class="hero__title" style="font-size:clamp(2rem,5vw,2.75rem);">
                Kami Senang Mendengar<br>dari Anda
            </h1>
            <p class="hero__subtitle">
                Punya pertanyaan tentang produk, ingin pesan custom, atau sekadar
                ingin menyapa? Jangan ragu untuk menghubungi kami.
            </p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         INFO KONTAK UTAMA
         ═══════════════════════════════════════════ -->
    <section class="section">
        <div class="container">

            <div style="
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:3rem;
                align-items:start;
            " class="kontak-grid">

                <!-- Kolom kiri: kartu-kartu kontak -->
                <div style="display:flex;flex-direction:column;gap:1.25rem;">

                    <div>
                        <p style="
                            font-family:'Inter',sans-serif;
                            font-size:0.8125rem;
                            font-weight:600;
                            letter-spacing:0.1em;
                            text-transform:uppercase;
                            color:#6B21A8;
                            margin-bottom:0.5rem;
                        ">Informasi Kontak</p>
                        <h2 style="
                            font-family:'Playfair Display',Georgia,serif;
                            font-size:clamp(1.5rem,3vw,2rem);
                            font-weight:700;
                            color:#1A1A2E;
                            line-height:1.25;
                            margin-bottom:0.5rem;
                        ">Cara Menghubungi WanFlorist</h2>
                        <p style="
                            font-family:'Inter',sans-serif;
                            font-size:0.9375rem;
                            color:#6B7280;
                            line-height:1.7;
                        ">
                            Kami tersedia via WhatsApp dan Instagram. Respon biasanya
                            diberikan dalam waktu singkat selama toko aktif beroperasi.
                        </p>
                    </div>

                    <!-- WhatsApp -->
                    <a href="https://wa.me/6281234567890?text=<?= rawurlencode('Halo WanFlorist, saya ingin bertanya tentang produk Anda.') ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       style="
                           display:flex;
                           align-items:center;
                           gap:1.25rem;
                           padding:1.25rem 1.5rem;
                           background:#fff;
                           border:1.5px solid #E9D5FF;
                           border-radius:16px;
                           text-decoration:none;
                           box-shadow:0 2px 12px rgba(107,33,168,0.06);
                           transition:transform 0.2s ease,box-shadow 0.2s ease,border-color 0.2s ease;
                       "
                       class="kontak-card">
                        <div style="
                            width:52px;height:52px;
                            border-radius:50%;
                            background:#D1FAE5;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.5rem;
                            flex-shrink:0;
                        " aria-hidden="true">📱</div>
                        <div>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.75rem;
                                font-weight:600;
                                color:#16A34A;
                                text-transform:uppercase;
                                letter-spacing:0.06em;
                                margin-bottom:0.2rem;
                            ">WhatsApp</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:1.0625rem;
                                font-weight:700;
                                color:#1F2937;
                                margin-bottom:0.2rem;
                            ">+62 812-3456-7890</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.8125rem;
                                color:#6B7280;
                                margin:0;
                            ">Klik untuk chat langsung</p>
                        </div>
                        <span style="margin-left:auto;color:#9CA3AF;font-size:1.25rem;" aria-hidden="true">→</span>
                    </a>

                    <!-- Instagram -->
                    <a href="https://instagram.com/wanflorist"
                       target="_blank"
                       rel="noopener noreferrer"
                       style="
                           display:flex;
                           align-items:center;
                           gap:1.25rem;
                           padding:1.25rem 1.5rem;
                           background:#fff;
                           border:1.5px solid #E9D5FF;
                           border-radius:16px;
                           text-decoration:none;
                           box-shadow:0 2px 12px rgba(107,33,168,0.06);
                           transition:transform 0.2s ease,box-shadow 0.2s ease,border-color 0.2s ease;
                       "
                       class="kontak-card">
                        <div style="
                            width:52px;height:52px;
                            border-radius:50%;
                            background:#FDF2F8;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.5rem;
                            flex-shrink:0;
                        " aria-hidden="true">📸</div>
                        <div>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.75rem;
                                font-weight:600;
                                color:#BE185D;
                                text-transform:uppercase;
                                letter-spacing:0.06em;
                                margin-bottom:0.2rem;
                            ">Instagram</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:1.0625rem;
                                font-weight:700;
                                color:#1F2937;
                                margin-bottom:0.2rem;
                            ">@wanflorist</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.8125rem;
                                color:#6B7280;
                                margin:0;
                            ">Lihat koleksi terbaru kami</p>
                        </div>
                        <span style="margin-left:auto;color:#9CA3AF;font-size:1.25rem;" aria-hidden="true">→</span>
                    </a>

                    <!-- Alamat -->
                    <div style="
                        display:flex;
                        align-items:flex-start;
                        gap:1.25rem;
                        padding:1.25rem 1.5rem;
                        background:#fff;
                        border:1.5px solid #E9D5FF;
                        border-radius:16px;
                        box-shadow:0 2px 12px rgba(107,33,168,0.06);
                    ">
                        <div style="
                            width:52px;height:52px;
                            border-radius:50%;
                            background:#EFF6FF;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.5rem;
                            flex-shrink:0;
                        " aria-hidden="true">📍</div>
                        <div>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.75rem;
                                font-weight:600;
                                color:#2563EB;
                                text-transform:uppercase;
                                letter-spacing:0.06em;
                                margin-bottom:0.2rem;
                            ">Lokasi Homestore</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:1.0625rem;
                                font-weight:700;
                                color:#1F2937;
                                margin-bottom:0.2rem;
                            ">Singojuruh, Banyuwangi</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.8125rem;
                                color:#6B7280;
                                margin:0;
                            ">Jawa Timur, Indonesia</p>
                        </div>
                    </div>

                </div>

                <!-- Kolom kanan: jam operasional + catatan -->
                <div style="display:flex;flex-direction:column;gap:1.5rem;">

                    <!-- Jam operasional -->
                    <div style="
                        background:#F5F0FF;
                        border:1.5px solid #E9D5FF;
                        border-radius:16px;
                        padding:1.75rem;
                    ">
                        <h3 style="
                            font-family:'Playfair Display',Georgia,serif;
                            font-size:1.125rem;
                            color:#1A1A2E;
                            margin-bottom:1.25rem;
                            display:flex;
                            align-items:center;
                            gap:0.5rem;
                        ">
                            <span aria-hidden="true">🕐</span> Jam Operasional
                        </h3>
                        <div style="display:flex;flex-direction:column;gap:0.75rem;">
                            <?php
                            $jadwal = [
                                ['Senin – Jumat', '08.00 – 20.00 WIB'],
                                ['Sabtu',         '08.00 – 18.00 WIB'],
                                ['Minggu',        '10.00 – 16.00 WIB'],
                            ];
                            foreach ($jadwal as $item):
                            ?>
                            <div style="
                                display:flex;
                                justify-content:space-between;
                                align-items:center;
                                font-family:'Inter',sans-serif;
                                font-size:0.9rem;
                                padding-bottom:0.625rem;
                                border-bottom:1px solid #E9D5FF;
                            ">
                                <span style="color:#374151;font-weight:500;">
                                    <?= e($item[0]) ?>
                                </span>
                                <span style="
                                    color:#6B21A8;
                                    font-weight:600;
                                    background:#fff;
                                    padding:0.25rem 0.75rem;
                                    border-radius:9999px;
                                    font-size:0.8125rem;
                                ">
                                    <?= e($item[1]) ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="
                            font-family:'Inter',sans-serif;
                            font-size:0.8125rem;
                            color:#9CA3AF;
                            margin-top:0.875rem;
                            margin-bottom:0;
                        ">
                            * Jadwal dapat berubah. Cek status toko di halaman utama.
                        </p>
                    </div>

                    <!-- Tips menghubungi -->
                    <div style="
                        background:#fff;
                        border:1.5px solid #E5E7EB;
                        border-radius:16px;
                        padding:1.75rem;
                    ">
                        <h3 style="
                            font-family:'Playfair Display',Georgia,serif;
                            font-size:1.125rem;
                            color:#1A1A2E;
                            margin-bottom:1rem;
                            display:flex;
                            align-items:center;
                            gap:0.5rem;
                        ">
                            <span aria-hidden="true">💡</span> Tips Pemesanan
                        </h3>
                        <ul style="
                            font-family:'Inter',sans-serif;
                            font-size:0.9rem;
                            color:#4B5563;
                            line-height:1.7;
                            display:flex;
                            flex-direction:column;
                            gap:0.625rem;
                            list-style:none;
                            margin:0;
                            padding:0;
                        ">
                            <li style="display:flex;align-items:flex-start;gap:0.5rem;">
                                <span style="color:#6B21A8;font-weight:700;flex-shrink:0;">✓</span>
                                Pesan minimal 1 hari sebelum tanggal pengiriman untuk hasil terbaik.
                            </li>
                            <li style="display:flex;align-items:flex-start;gap:0.5rem;">
                                <span style="color:#6B21A8;font-weight:700;flex-shrink:0;">✓</span>
                                Untuk pesanan custom atau acara besar, hubungi kami 3–5 hari lebih awal.
                            </li>
                            <li style="display:flex;align-items:flex-start;gap:0.5rem;">
                                <span style="color:#6B21A8;font-weight:700;flex-shrink:0;">✓</span>
                                Nomor pesanan Anda bisa dicek langsung di halaman
                                <a href="/pages/cek-pesanan.php" style="color:#6B21A8;font-weight:600;text-decoration:underline;">Cek Pesanan</a>.
                            </li>
                            <li style="display:flex;align-items:flex-start;gap:0.5rem;">
                                <span style="color:#6B21A8;font-weight:700;flex-shrink:0;">✓</span>
                                Tersedia layanan antar untuk area Singojuruh dan sekitarnya.
                            </li>
                        </ul>
                    </div>

                </div>

            </div><!-- /.kontak-grid -->

        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         CTA — Pesan Sekarang
         ═══════════════════════════════════════════ -->
    <section class="section section--primary">
        <div class="container" style="text-align:center;">
            <h2 class="section__title" style="color:#fff;margin-bottom:0.75rem;">
                Siap Membuat Seseorang Bahagia?
            </h2>
            <p class="section__subtitle" style="color:rgba(255,255,255,0.8);margin-bottom:2rem;">
                Mulai dari melihat koleksi hingga memesan buket impian Anda — semuanya mudah.
            </p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="/pages/katalog.php" class="hero__btn">
                    🌸 Lihat Katalog
                </a>
                <a href="/pages/pemesanan.php" class="hero__btn hero__btn--outline">
                    📋 Pesan Sekarang
                </a>
            </div>
        </div>
    </section>

</main>

<?php include '../components/footer.php'; ?>

<style>
/* Responsive untuk kontak-grid */
@media (max-width: 767px) {
    .kontak-grid {
        grid-template-columns: 1fr !important;
        gap: 2rem !important;
    }
}
@media (min-width: 768px) and (max-width: 1023px) {
    .kontak-grid {
        grid-template-columns: 1fr !important;
        gap: 2rem !important;
    }
}
/* Hover effect untuk kontak cards */
.kontak-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(107,33,168,0.12) !important;
    border-color: #C4B5FD !important;
}
</style>

</body>
</html>
