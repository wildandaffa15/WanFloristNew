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
                🌸 WanFlorist
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

<style>
:root {
    --wf-primary:        #6B21A8;
    --wf-primary-hover:  #5B1A90;
    --wf-primary-subtle: #F5F0FF;
    --wf-primary-border: #E9D5FF;
    --wf-success:        #16A34A;
    --wf-gray:           #9CA3AF;
    --wf-surface:        #fff7fe;
    --wf-on-surface:     #1f1a22;
    --wf-muted:          #6B7280;
    --wf-navbar-h:       64px;
}

.wf-navbar-wrapper {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: var(--wf-surface);
    border-bottom: 1px solid var(--wf-primary-border);
    box-shadow: 0 1px 8px rgba(107, 33, 168, 0.06);
}

.wf-navbar {
    width: 100%;
    background: var(--wf-surface);
}

.wf-navbar__container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 2rem;
    height: var(--wf-navbar-h);
    gap: 1.5rem;
}

.wf-navbar__logo {
    font-family: 'Playfair Display', serif;
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--wf-primary);
    text-decoration: none;
    letter-spacing: -0.01em;
    white-space: nowrap;
    flex-shrink: 0;
    transition: color 0.2s ease;
}

.wf-navbar__logo:hover,
.wf-navbar__logo:focus-visible {
    color: var(--wf-primary-hover);
    outline: 2px solid var(--wf-primary);
    outline-offset: 3px;
    border-radius: 4px;
}

.wf-navbar__links {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    list-style: none;
    margin: 0;
    padding: 0;
    flex: 1;
    justify-content: center;
}

.nav-link {
    font-family: 'Inter', sans-serif;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--wf-muted);
    text-decoration: none;
    padding: 0.4rem 0.75rem;
    border-radius: 9999px;
    transition: color 0.2s ease, background 0.2s ease;
    white-space: nowrap;
}

.nav-link:hover,
.nav-link:focus-visible {
    color: var(--wf-primary);
    background: var(--wf-primary-subtle);
    outline: none;
}

.nav-link:focus-visible {
    outline: 2px solid var(--wf-primary);
    outline-offset: 2px;
}

.nav-link--active,
.nav-link--active:hover {
    color: var(--wf-primary);
    font-weight: 700;
    background: var(--wf-primary-subtle);
    position: relative;
}

.wf-navbar__hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 9999px;
    padding: 0.35rem;
    transition: background 0.2s ease;
    flex-shrink: 0;
}

.wf-navbar__hamburger:hover,
.wf-navbar__hamburger:focus-visible {
    background: var(--wf-primary-subtle);
    outline: none;
}

.wf-navbar__hamburger:focus-visible {
    outline: 2px solid var(--wf-primary);
    outline-offset: 2px;
}

.wf-hamburger__bar {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--wf-primary);
    border-radius: 9999px;
    transition: transform 0.3s ease, opacity 0.3s ease, width 0.3s ease;
    transform-origin: center;
}

.wf-navbar__hamburger[aria-expanded="true"] .wf-hamburger__bar:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.wf-navbar__hamburger[aria-expanded="true"] .wf-hamburger__bar:nth-child(2) {
    opacity: 0;
    width: 0;
}
.wf-navbar__hamburger[aria-expanded="true"] .wf-hamburger__bar:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

.wf-mobile-menu {
    display: none;
    overflow: hidden;
    background: var(--wf-surface);
    border-top: 1px solid var(--wf-primary-border);
}

.wf-mobile-menu.wf-mobile-menu--open {
    display: block;
    animation: wf-slide-down 0.25s ease forwards;
}

@keyframes wf-slide-down {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.wf-mobile-menu ul {
    list-style: none;
    margin: 0;
    padding: 0.5rem 1rem 1rem;
    max-width: 1280px;
    margin-left: auto;
    margin-right: auto;
}

.wf-mobile-menu li {
    border-bottom: 1px solid var(--wf-primary-border);
}

.wf-mobile-menu li:last-child {
    border-bottom: none;
}

.wf-mobile-menu .nav-link {
    display: block;
    padding: 0.875rem 0.5rem;
    border-radius: 0;
    font-size: 1rem;
}

.wf-mobile-menu .nav-link:hover,
.wf-mobile-menu .nav-link:focus-visible {
    border-radius: 8px;
    padding-left: 0.75rem;
}

.wf-mobile-menu .nav-link--active {
    background: transparent;
    border-left: 3px solid var(--wf-primary);
    padding-left: 0.75rem;
    border-radius: 0;
}

.wf-status-banner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 1rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.4;
}

.wf-status-banner--aktif {
    background: var(--wf-primary-subtle);
    border-bottom: 1px solid var(--wf-primary-border);
    color: var(--wf-primary);
}

.wf-status-banner--nonaktif {
    background: #F3F4F6;
    border-bottom: 1px solid #E5E7EB;
    color: var(--wf-muted);
}

.wf-status-banner__dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    flex-shrink: 0;
}

.wf-status-banner--aktif .wf-status-banner__dot {
    background: var(--wf-success);
}

.wf-status-banner--nonaktif .wf-status-banner__dot {
    background: var(--wf-gray);
}

.wf-status-banner__dot--pulse {
    animation: wf-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes wf-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.5; transform: scale(1.3); }
}

@media (max-width: 767px) {
    .wf-navbar__container {
        padding: 0 1rem;
    }

    .wf-navbar__links {
        display: none;
    }

    .wf-navbar__hamburger {
        display: flex;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .wf-navbar__container {
        padding: 0 1.5rem;
    }

    .wf-navbar__links {
        gap: 0.125rem;
    }

    .nav-link {
        font-size: 0.875rem;
        padding: 0.375rem 0.625rem;
    }
}
</style>

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
