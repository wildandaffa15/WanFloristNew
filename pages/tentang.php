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

    <section class="hero" style="min-height:45vh;padding:3.5rem 1.5rem;">
        <div class="hero__inner">
            <p style="
                font-family:'Inter',sans-serif;
                font-size:0.8125rem;
                font-weight:600;
                letter-spacing:0.1em;
                text-transform:uppercase;
                color:rgba(255,255,255,0.65);
                margin-bottom:0.75rem;
            "><i class="bi bi-flower1" aria-hidden="true"></i> Kisah Kami</p>
            <h1 class="hero__title" style="font-size:clamp(2rem,5vw,2.75rem);">
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
            <div style="
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:4rem;
                align-items:center;
            " class="about-grid">

                <div style="text-align:center;">
                    <div style="
                        width:100%;
                        max-width:380px;
                        margin:0 auto;
                        aspect-ratio:1/1;
                        border-radius:24px;
                        background:linear-gradient(135deg,#F5F0FF 0%,#E9D5FF 100%);
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:6rem;
                        box-shadow:0 12px 40px rgba(107,33,168,0.15);
                        position:relative;
                        overflow:hidden;
                    " aria-hidden="true">
                        <i class="bi bi-flower1" aria-hidden="true"></i>
                        <span style="
                            position:absolute;
                            bottom:24px;
                            right:24px;
                            font-size:2.5rem;
                            opacity:0.4;
                        "><i class="bi bi-flower2"></i></span>
                        <span style="
                            position:absolute;
                            top:20px;
                            left:20px;
                            font-size:2rem;
                            opacity:0.35;
                        "><i class="bi bi-flower3"></i></span>
                    </div>
                </div>

                <div>
                    <p style="
                        font-family:'Inter',sans-serif;
                        font-size:0.8125rem;
                        font-weight:600;
                        letter-spacing:0.1em;
                        text-transform:uppercase;
                        color:#6B21A8;
                        margin-bottom:0.75rem;
                    ">Kisah Pemilik</p>

                    <h2 style="
                        font-family:'Playfair Display',Georgia,serif;
                        font-size:clamp(1.625rem,3vw,2.25rem);
                        font-weight:700;
                        color:#1A1A2E;
                        line-height:1.25;
                        margin-bottom:1.25rem;
                    ">
                        Dari Kecintaan pada Bunga,<br>
                        Lahirlah WanFlorist
                    </h2>

                    <p style="font-family:'Inter',sans-serif;color:#4B5563;line-height:1.8;margin-bottom:1rem;">
                        WanFlorist bermula dari passion yang sederhana — kecintaan mendalam terhadap
                        keindahan bunga dan keinginan untuk berbagi kebahagiaan melalui setiap rangkaian
                        yang dibuat dengan tangan. Berawal dari hobi merangkai di rumah, kini WanFlorist
                        hadir untuk melayani berbagai kebutuhan bunga di Banyuwangi dan sekitarnya.
                    </p>

                    <p style="font-family:'Inter',sans-serif;color:#4B5563;line-height:1.8;margin-bottom:1.5rem;">
                        Setiap buket dirancang dengan perhatian penuh terhadap detail — dari pemilihan
                        bunga segar, perpaduan warna yang harmonis, hingga sentuhan pita dan kemasan yang
                        elegan. Kami percaya bahwa sebuah buket bukan sekadar hadiah; ia adalah ungkapan
                        perasaan yang tidak bisa disampaikan hanya dengan kata-kata.
                    </p>

                    <div style="
                        display:inline-flex;
                        align-items:center;
                        gap:0.75rem;
                        padding:0.875rem 1.25rem;
                        background:#F5F0FF;
                        border:1.5px solid #E9D5FF;
                        border-radius:12px;
                    ">
                        <span style="font-size:1.5rem;" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <div>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.875rem;
                                font-weight:600;
                                color:#6B21A8;
                                margin-bottom:0.125rem;
                            ">Lokasi Homestore</p>
                            <p style="
                                font-family:'Inter',sans-serif;
                                font-size:0.875rem;
                                color:#6B7280;
                                margin:0;
                            ">Singojuruh, Banyuwangi, Jawa Timur</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="section section--alt">
        <div class="container">

            <div class="section__header">
                <p style="
                    font-family:'Inter',sans-serif;
                    font-size:0.8125rem;
                    font-weight:600;
                    letter-spacing:0.1em;
                    text-transform:uppercase;
                    color:#6B21A8;
                    margin-bottom:0.5rem;
                ">Mengapa WanFlorist</p>
                <h2 class="section__title">Misi &amp; Nilai Kami</h2>
                <div class="section__title-underline"></div>
                <p class="section__subtitle" style="margin-top:0.75rem;">
                    Kami hadir bukan sekadar untuk menjual bunga —
                    kami hadir untuk menciptakan kenangan yang tak terlupakan.
                </p>
            </div>

            <div style="
                display:grid;
                grid-template-columns:repeat(3,1fr);
                gap:1.5rem;
            " class="values-grid">

                <div style="
                    background:#fff;
                    border:1.5px solid #E9D5FF;
                    border-radius:16px;
                    padding:2rem 1.5rem;
                    text-align:center;
                    box-shadow:0 2px 12px rgba(107,33,168,0.06);
                    transition:transform 0.2s ease,box-shadow 0.2s ease;
                " class="value-card">
                    <div style="
                        width:64px;height:64px;
                        border-radius:50%;
                        background:#F5F0FF;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.75rem;
                        margin:0 auto 1.25rem;
                    " aria-hidden="true"><i class="bi bi-flower1"></i></div>
                    <h3 style="
                        font-family:'Playfair Display',Georgia,serif;
                        font-size:1.125rem;
                        color:#1A1A2E;
                        margin-bottom:0.625rem;
                    ">Kualitas Terjaga</h3>
                    <p style="
                        font-family:'Inter',sans-serif;
                        font-size:0.9rem;
                        color:#6B7280;
                        line-height:1.7;
                        margin:0;
                    ">
                        Hanya bunga segar pilihan yang digunakan dalam setiap rangkaian.
                        Kami selektif dalam memilih bahan agar hasil selalu memuaskan.
                    </p>
                </div>

                <div style="
                    background:#fff;
                    border:1.5px solid #E9D5FF;
                    border-radius:16px;
                    padding:2rem 1.5rem;
                    text-align:center;
                    box-shadow:0 2px 12px rgba(107,33,168,0.06);
                    transition:transform 0.2s ease,box-shadow 0.2s ease;
                " class="value-card">
                    <div style="
                        width:64px;height:64px;
                        border-radius:50%;
                        background:#F5F0FF;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.75rem;
                        margin:0 auto 1.25rem;
                    " aria-hidden="true"><i class="bi bi-heart-fill"></i></div>
                    <h3 style="
                        font-family:'Playfair Display',Georgia,serif;
                        font-size:1.125rem;
                        color:#1A1A2E;
                        margin-bottom:0.625rem;
                    ">Sentuhan Personal</h3>
                    <p style="
                        font-family:'Inter',sans-serif;
                        font-size:0.9rem;
                        color:#6B7280;
                        line-height:1.7;
                        margin:0;
                    ">
                        Setiap pesanan dibuat khusus sesuai keinginan Anda. Tidak ada buket
                        yang identik — semuanya dirancang untuk momen unik Anda.
                    </p>
                </div>

                <div style="
                    background:#fff;
                    border:1.5px solid #E9D5FF;
                    border-radius:16px;
                    padding:2rem 1.5rem;
                    text-align:center;
                    box-shadow:0 2px 12px rgba(107,33,168,0.06);
                    transition:transform 0.2s ease,box-shadow 0.2s ease;
                " class="value-card">
                    <div style="
                        width:64px;height:64px;
                        border-radius:50%;
                        background:#F5F0FF;
                        display:flex;align-items:center;justify-content:center;
                        font-size:1.75rem;
                        margin:0 auto 1.25rem;
                    " aria-hidden="true"><i class="bi bi-house-fill"></i></div>
                    <h3 style="
                        font-family:'Playfair Display',Georgia,serif;
                        font-size:1.125rem;
                        color:#1A1A2E;
                        margin-bottom:0.625rem;
                    ">Lokal &amp; Terpercaya</h3>
                    <p style="
                        font-family:'Inter',sans-serif;
                        font-size:0.9rem;
                        color:#6B7280;
                        line-height:1.7;
                        margin:0;
                    ">
                        Kami adalah usaha lokal yang bangga melayani masyarakat Banyuwangi.
                        Kepercayaan pelanggan adalah prioritas utama kami.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <section class="section section--primary">
        <div class="container" style="text-align:center;">
            <h2 class="section__title" style="color:#fff;margin-bottom:0.75rem;">
                Siap Memesan Buket Impian Anda?
            </h2>
            <p class="section__subtitle" style="color:rgba(255,255,255,0.8);margin-bottom:2rem;">
                Jelajahi koleksi kami atau hubungi langsung untuk pesanan custom.
            </p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
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

<style>
@media (max-width: 767px) {
    .about-grid {
        grid-template-columns: 1fr !important;
        gap: 2rem !important;
    }
    .values-grid {
        grid-template-columns: 1fr !important;
    }
}
@media (min-width: 768px) and (max-width: 1023px) {
    .about-grid {
        grid-template-columns: 1fr !important;
        gap: 2.5rem !important;
    }
    .values-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
.value-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(107,33,168,0.14) !important;
}
</style>

</body>
</html>
