<?php
/**
 * components/sidebar.php
 *
 * Sidebar navigasi admin WanFlorist.
 * — Lebar 240px, latar belakang #1E1040 (dark-bg).
 * — Menerima variabel $active_page (string) untuk menyorot item aktif.
 * — Mobile: tersembunyi secara default, tampil via tombol hamburger.
 * — Logout menggunakan form POST (bukan tautan GET) untuk keamanan.
 * — Semua event listener JavaScript ditambahkan via addEventListener();
 *   TIDAK ada atribut onclick="" inline di HTML.
 *
 * Variabel yang dibutuhkan dari halaman pemanggil:
 *   $active_page  (string) — nama halaman aktif, contoh: 'dashboard', 'pesanan'
 *   $_SESSION['username'] — username admin yang sedang login
 *
 * Requirements: 8.8, 16.7, 16.9
 */

// Pastikan session sudah dimulai oleh config/session.php sebelum sidebar ini di-include.
$admin_username = htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES | ENT_HTML5, 'UTF-8');
$admin_role     = htmlspecialchars(ucfirst($_SESSION['role'] ?? 'staf'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

/**
 * Daftar item menu sidebar.
 * Setiap item: [key, label, ikon emoji, href, is_logout]
 */
$menu_items = [
    ['dashboard',   'Dashboard',    '📊', '/admin/index.php',       false],
    ['pesanan',     'Pesanan',      '📦', '/admin/pesanan.php',      false],
    ['produk',      'Produk',       '🌸', '/admin/produk.php',       false],
    ['stok',        'Stok Bahan',   '🌿', '/admin/stok.php',         false],
    ['pembayaran',  'Pembayaran',   '💳', '/admin/pembayaran.php',   false],
    ['pengeluaran', 'Pengeluaran',  '💰', '/admin/pengeluaran.php',  false],
    ['laporan',     'Laporan',      '📈', '/admin/laporan.php',      false],
];
?>

<button
    id="wf-sidebar-toggle"
    class="wf-hamburger"
    type="button"
    aria-label="Buka navigasi admin"
    aria-expanded="false"
    aria-controls="wf-sidebar"
>
    <span class="wf-hamburger__bar"></span>
    <span class="wf-hamburger__bar"></span>
    <span class="wf-hamburger__bar"></span>
</button>

<div id="wf-sidebar-overlay" class="wf-sidebar-overlay" aria-hidden="true"></div>

<aside id="wf-sidebar" class="wf-sidebar" role="navigation" aria-label="Navigasi admin">

    <div class="wf-sidebar__header">
        <div class="wf-sidebar__brand">
            <span class="wf-sidebar__brand-icon" aria-hidden="true">🌸</span>
            <span class="wf-sidebar__brand-name">WanFlorist</span>
        </div>
        <div class="wf-sidebar__user">
            <div class="wf-sidebar__avatar" aria-hidden="true">
                <?= strtoupper(mb_substr($_SESSION['username'] ?? 'A', 0, 1, 'UTF-8')) ?>
            </div>
            <div class="wf-sidebar__user-info">
                <span class="wf-sidebar__username"><?= $admin_username ?></span>
                <span class="wf-sidebar__role"><?= $admin_role ?></span>
            </div>
        </div>
    </div>

    <nav class="wf-sidebar__nav" aria-label="Menu admin">
        <ul class="wf-sidebar__menu" role="list">
            <?php foreach ($menu_items as [$key, $label, $icon, $href, $is_logout]): ?>
                <?php $is_active = ($active_page ?? '') === $key; ?>
                <li class="wf-sidebar__item" role="listitem">
                    <a
                        href="<?= htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                        class="wf-sidebar__link<?= $is_active ? ' wf-sidebar__link--active' : '' ?>"
                        <?= $is_active ? 'aria-current="page"' : '' ?>
                    >
                        <span class="wf-sidebar__icon" aria-hidden="true"><?= $icon ?></span>
                        <span class="wf-sidebar__label"><?= htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="wf-sidebar__spacer" aria-hidden="true"></div>

    <div class="wf-sidebar__footer">
        <div class="wf-sidebar__divider" aria-hidden="true"></div>
        <form method="POST" action="/admin/logout.php" class="wf-sidebar__logout-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf(), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">
            <button type="submit" class="wf-sidebar__link wf-sidebar__link--logout">
                <span class="wf-sidebar__icon" aria-hidden="true">🚪</span>
                <span class="wf-sidebar__label">Keluar</span>
            </button>
        </form>
    </div>

</aside>

<style>
:root {
    --sidebar-bg:         #1E1040;
    --sidebar-width:      240px;
    --sidebar-text:       #E9D5FF;
    --sidebar-muted:      #A78BFA;
    --sidebar-active-bg:  rgba(107, 33, 168, 0.4);
    --sidebar-active-border: #9333EA;
    --sidebar-hover-bg:   rgba(255, 255, 255, 0.07);
    --sidebar-divider:    rgba(255, 255, 255, 0.08);
    --sidebar-transition: 0.25s ease;
}

.wf-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background-color: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    z-index: 200;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 4px 0 16px rgba(0, 0, 0, 0.3);
    transition: transform var(--sidebar-transition);
    font-family: 'Inter', sans-serif;
}

.wf-sidebar__header {
    padding: 24px 16px 16px 16px;
    flex-shrink: 0;
}

.wf-sidebar__brand {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.wf-sidebar__brand-icon {
    font-size: 20px;
    line-height: 1;
}

.wf-sidebar__brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--sidebar-text);
    letter-spacing: -0.01em;
}

.wf-sidebar__user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.05);
}

.wf-sidebar__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(147, 51, 234, 0.4);
    color: var(--sidebar-text);
    font-size: 15px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 2px solid rgba(147, 51, 234, 0.6);
}

.wf-sidebar__user-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.wf-sidebar__username {
    font-size: 13px;
    font-weight: 600;
    color: var(--sidebar-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.wf-sidebar__role {
    font-size: 11px;
    color: var(--sidebar-muted);
    line-height: 1.3;
}

.wf-sidebar__nav {
    padding: 8px 8px 0 8px;
    flex-shrink: 0;
}

.wf-sidebar__menu {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.wf-sidebar__item {
    display: block;
}

.wf-sidebar__link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--sidebar-muted);
    text-decoration: none;
    border-left: 3px solid transparent;
    transition:
        background-color var(--sidebar-transition),
        color var(--sidebar-transition),
        border-left-color var(--sidebar-transition);
    cursor: pointer;
    width: 100%;
    background: none;
    border-top: none;
    border-right: none;
    border-bottom: none;
    text-align: left;
    font-family: 'Inter', sans-serif;
}

.wf-sidebar__link:hover,
.wf-sidebar__link:focus {
    background-color: var(--sidebar-hover-bg);
    color: var(--sidebar-text);
    outline: none;
}

.wf-sidebar__link:focus-visible {
    outline: 2px solid var(--sidebar-active-border);
    outline-offset: -2px;
}

.wf-sidebar__link--active {
    background-color: var(--sidebar-active-bg);
    color: #ffffff;
    border-left-color: var(--sidebar-active-border);
    font-weight: 600;
}

.wf-sidebar__link--active:hover,
.wf-sidebar__link--active:focus {
    background-color: var(--sidebar-active-bg);
    color: #ffffff;
}

.wf-sidebar__icon {
    font-size: 18px;
    line-height: 1;
    flex-shrink: 0;
    width: 22px;
    text-align: center;
}

.wf-sidebar__label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wf-sidebar__spacer {
    flex: 1;
    min-height: 16px;
}

.wf-sidebar__footer {
    padding: 0 8px 24px 8px;
    flex-shrink: 0;
}

.wf-sidebar__divider {
    height: 1px;
    background: var(--sidebar-divider);
    margin: 0 6px 8px 6px;
}

.wf-sidebar__logout-form {
    display: block;
    margin: 0;
    padding: 0;
}

.wf-sidebar__link--logout {
    color: #FDA4AF; /* merah muda lembut agar kentara di bg gelap */
}

.wf-sidebar__link--logout:hover,
.wf-sidebar__link--logout:focus {
    background-color: rgba(220, 38, 38, 0.15);
    color: #FCA5A5;
}

.wf-hamburger {
    display: none;
    position: fixed;
    top: 14px;
    left: 14px;
    z-index: 300;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: #1E1040;
    border: none;
    cursor: pointer;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
    transition: background-color var(--sidebar-transition);
}

.wf-hamburger:hover,
.wf-hamburger:focus {
    background-color: rgba(107, 33, 168, 0.6);
    outline: 2px solid #9333EA;
    outline-offset: 2px;
}

.wf-hamburger__bar {
    display: block;
    width: 20px;
    height: 2px;
    background-color: #E9D5FF;
    border-radius: 2px;
    transition:
        transform var(--sidebar-transition),
        opacity var(--sidebar-transition);
}

.wf-hamburger[aria-expanded="true"] .wf-hamburger__bar:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.wf-hamburger[aria-expanded="true"] .wf-hamburger__bar:nth-child(2) {
    opacity: 0;
}
.wf-hamburger[aria-expanded="true"] .wf-hamburger__bar:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

.wf-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 190;
    opacity: 0;
    transition: opacity var(--sidebar-transition);
}

.wf-sidebar-overlay.wf-sidebar-overlay--visible {
    display: block;
    opacity: 1;
}

@media (max-width: 1023px) {
    .wf-hamburger {
        display: flex;
    }

    .wf-sidebar {
        transform: translateX(calc(-1 * var(--sidebar-width)));
        box-shadow: none;
    }

    .wf-sidebar--open {
        transform: translateX(0);
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.45);
    }
}

@media (min-width: 1024px) {
    .wf-hamburger {
        display: none;
    }

    .wf-sidebar {
        transform: translateX(0) !important;
    }

    .wf-sidebar-overlay {
        display: none !important;
    }
}
</style>

<script>
/**
 * Sidebar toggle — vanilla JS, addEventListener only.
 * Tidak ada inline onclick="".
 *
 * Requirements: 16.9, 17.5
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle   = document.getElementById('wf-sidebar-toggle');
        var sidebar  = document.getElementById('wf-sidebar');
        var overlay  = document.getElementById('wf-sidebar-overlay');

        if (!toggle || !sidebar || !overlay) return;

        /**
         * Buka sidebar
         */
        function openSidebar() {
            sidebar.classList.add('wf-sidebar--open');
            overlay.classList.add('wf-sidebar-overlay--visible');
            overlay.removeAttribute('aria-hidden');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Tutup navigasi admin');
            // Pindahkan fokus ke sidebar agar screen reader tahu konteks berganti
            sidebar.focus();
        }

        /**
         * Tutup sidebar
         */
        function closeSidebar() {
            sidebar.classList.remove('wf-sidebar--open');
            overlay.classList.remove('wf-sidebar-overlay--visible');
            overlay.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Buka navigasi admin');
            toggle.focus();
        }

        toggle.addEventListener('click', function () {
            var isOpen = sidebar.classList.contains('wf-sidebar--open');
            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        overlay.addEventListener('click', function () {
            closeSidebar();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('wf-sidebar--open')) {
                closeSidebar();
            }
        });
    });
}());
</script>
