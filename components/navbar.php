<?php
/**
 * components/navbar.php
 *
 * Navbar publik sticky untuk semua halaman publik WanFlorist.
 * Membaca status toko dari database untuk menampilkan banner ketersediaan.
 *
 * Variabel yang dibutuhkan dari halaman pemanggil:
 *   $pdo          — instance PDO (dari config/database.php)
 *   $active_page  — string: 'beranda' | 'produk' | 'cek-pesanan' | 'tentang' | 'kontak'
 *                   (opsional, default kosong = tidak ada yang aktif)
 *
 * Requirements: 1.4, 1.5, 1.6, 2.5, 16.7
 */

if (!function_exists('e')) {
    require_once __DIR__ . '/../config/helpers.php';
}

$status_toko = 'nonaktif'; // fallback default
try {
    $stmt_status = $pdo->query("SELECT status FROM status_toko LIMIT 1");
    $row_status  = $stmt_status ? $stmt_status->fetch(PDO::FETCH_ASSOC) : null;
    if ($row_status && isset($row_status['status'])) {
        // Validasi nilai enum sebelum digunakan (Req 1.1)
        $status_toko = ($row_status['status'] === 'aktif') ? 'aktif' : 'nonaktif';
    }
} catch (PDOException $e) {
    // Gagal query tidak boleh merusak halaman — gunakan fallback
    error_log('navbar.php: gagal membaca status_toko — ' . $e->getMessage());
}

$active_page = $active_page ?? '';

$nav_link_class = function (string $page) use ($active_page): string {
    if ($active_page === $page) {
        return 'nav-link nav-link--active';
    }
    return 'nav-link';
};
?>
<header class="wf-navbar-wrapper">
    <nav class="wf-navbar" role="navigation" aria-label="Navigasi utama">
        <div class="wf-navbar__container">

            <a href="<?= e(dirname($_SERVER['SCRIPT_NAME']) === '/' ? '/' : rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/') . '/') ?>index.php"
               class="wf-navbar__logo"
               aria-label="WanFlorist — Halaman Beranda">
                <i class="bi bi-flower1" aria-hidden="true"></i> WanFlorist
            </a>

            <ul class="wf-navbar__links" role="list">
                <li>
                    <a href="/index.php"
                       class="<?= e($nav_link_class('beranda')) ?>"
                       <?= $active_page === 'beranda' ? 'aria-current="page"' : '' ?>>
                        Beranda
                    </a>
                </li>
                <li>
                    <a href="/pages/katalog.php"
                       class="<?= e($nav_link_class('produk')) ?>"
                       <?= $active_page === 'produk' ? 'aria-current="page"' : '' ?>>
                        Produk
                    </a>
                </li>
                <li>
                    <a href="/pages/cek-pesanan.php"
                       class="<?= e($nav_link_class('cek-pesanan')) ?>"
                       <?= $active_page === 'cek-pesanan' ? 'aria-current="page"' : '' ?>>
                        Cek Pesanan
                    </a>
                </li>
                <li>
                    <a href="/pages/tentang.php"
                       class="<?= e($nav_link_class('tentang')) ?>"
                       <?= $active_page === 'tentang' ? 'aria-current="page"' : '' ?>>
                        Tentang Kami
                    </a>
                </li>
                <li>
                    <a href="/pages/kontak.php"
                       class="<?= e($nav_link_class('kontak')) ?>"
                       <?= $active_page === 'kontak' ? 'aria-current="page"' : '' ?>>
                        Kontak
                    </a>
                </li>
            </ul>

            <button
                id="wf-hamburger"
                class="wf-navbar__hamburger"
                type="button"
                aria-controls="wf-mobile-menu"
                aria-expanded="false"
                aria-label="Buka menu navigasi">
                <span class="wf-hamburger__bar"></span>
                <span class="wf-hamburger__bar"></span>
                <span class="wf-hamburger__bar"></span>
            </button>

        </div>

        <div id="wf-mobile-menu" class="wf-mobile-menu" aria-hidden="true" role="menu">
            <ul role="list">
                <li>
                    <a href="/index.php"
                       class="<?= e($nav_link_class('beranda')) ?>"
                       <?= $active_page === 'beranda' ? 'aria-current="page"' : '' ?>
                       role="menuitem">
                        Beranda
                    </a>
                </li>
                <li>
                    <a href="/pages/katalog.php"
                       class="<?= e($nav_link_class('produk')) ?>"
                       <?= $active_page === 'produk' ? 'aria-current="page"' : '' ?>
                       role="menuitem">
                        Produk
                    </a>
                </li>
                <li>
                    <a href="/pages/cek-pesanan.php"
                       class="<?= e($nav_link_class('cek-pesanan')) ?>"
                       <?= $active_page === 'cek-pesanan' ? 'aria-current="page"' : '' ?>
                       role="menuitem">
                        Cek Pesanan
                    </a>
                </li>
                <li>
                    <a href="/pages/tentang.php"
                       class="<?= e($nav_link_class('tentang')) ?>"
                       <?= $active_page === 'tentang' ? 'aria-current="page"' : '' ?>
                       role="menuitem">
                        Tentang Kami
                    </a>
                </li>
                <li>
                    <a href="/pages/kontak.php"
                       class="<?= e($nav_link_class('kontak')) ?>"
                       <?= $active_page === 'kontak' ? 'aria-current="page"' : '' ?>
                       role="menuitem">
                        Kontak
                    </a>
                </li>
            </ul>
        </div>

    </nav>

    <?php if ($status_toko === 'aktif'): ?>
    <div class="wf-status-banner wf-status-banner--aktif" role="status" aria-live="polite">
        <span class="wf-status-banner__dot wf-status-banner__dot--pulse" aria-hidden="true"></span>
        <span class="wf-status-banner__text">
            Owner sedang tersedia — Homestore buka hari ini
        </span>
    </div>
    <?php else: ?>
    <div class="wf-status-banner wf-status-banner--nonaktif" role="status" aria-live="polite">
        <span class="wf-status-banner__dot" aria-hidden="true"></span>
        <span class="wf-status-banner__text">
            Owner sedang tidak tersedia
        </span>
    </div>
    <?php endif; ?>

</header>

<script>
(function () {
    'use strict';

    /**
     * Initialise the hamburger / mobile-menu toggle.
     * Uses addEventListener exclusively — no inline onclick handlers (Req 17.5).
     */
    function initNavbar() {
        var hamburger  = document.getElementById('wf-hamburger');
        var mobileMenu = document.getElementById('wf-mobile-menu');

        if (!hamburger || !mobileMenu) {
            return;
        }

        hamburger.addEventListener('click', function () {
            var isOpen = hamburger.getAttribute('aria-expanded') === 'true';

            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        var menuLinks = mobileMenu.querySelectorAll('.nav-link');
        menuLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                closeMenu();
            });
        });

        document.addEventListener('click', function (event) {
            var wrapper = document.querySelector('.wf-navbar-wrapper');
            if (wrapper && !wrapper.contains(event.target)) {
                closeMenu();
            }
        });

        // Close menu on Escape key press (accessibility)
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
                hamburger.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                closeMenu();
            }
        });

        function openMenu() {
            hamburger.setAttribute('aria-expanded', 'true');
            mobileMenu.setAttribute('aria-hidden', 'false');
            mobileMenu.classList.add('wf-mobile-menu--open');
        }

        function closeMenu() {
            hamburger.setAttribute('aria-expanded', 'false');
            mobileMenu.setAttribute('aria-hidden', 'true');
            mobileMenu.classList.remove('wf-mobile-menu--open');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNavbar);
    } else {
        initNavbar();
    }
}());
</script>
