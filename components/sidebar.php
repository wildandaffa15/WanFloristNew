<?php
$menu_items = [
    ['dashboard',   'Dashboard',    'bi bi-speedometer2', '/admin/index.php', false],
    ['pesanan',     'Pesanan',      'bi bi-box-seam', '/admin/pesanan.php', false],
    ['produk',      'Produk',       'bi bi-flower1', '/admin/produk.php', false],
    ['stok',        'Stok Bahan',   'bi bi-archive', '/admin/stok.php', false],
    ['pembayaran',  'Pembayaran',   'bi bi-credit-card', '/admin/pembayaran.php', false],
    ['pengeluaran', 'Pengeluaran',  'bi bi-cash-stack', '/admin/pengeluaran.php', false],
    ['laporan',     'Laporan',      'bi bi-bar-chart-line', '/admin/laporan.php', false],
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

<aside id="wf-sidebar" class="wf-sidebar">
    <div class="wf-sidebar__header">
        <div class="wf-sidebar__brand">
            <div class="wf-sidebar__brand-icon">
                <i class="bi bi-flower1"></i>
            </div>

            <div class="wf-sidebar__brand-text">
                <span class="wf-sidebar__brand-name">WanFlorist</span>
                <span class="wf-sidebar__brand-subtitle">
                    Admin Panel
                </span>
            </div>
        </div>
    </div>

    <nav class="wf-sidebar__nav">
        <ul class="wf-sidebar__menu">
            <?php foreach ($menu_items as $item): ?>
                <?php
                $is_active = ($active_page ?? '') === $item[0];
                ?>
                <li>
                    <a
                        href="<?= e($item[3]) ?>"
                        class="wf-sidebar__link <?= $is_active ? 'wf-sidebar__link--active' : '' ?>"
                    >
                        <span class="wf-sidebar__icon">
                            <i class="<?= e($item[2]) ?>"></i>
                        </span>

                        <span class="wf-sidebar__text">
                            <?= e($item[1]) ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="wf-sidebar__spacer"></div>
    <div class="wf-sidebar__footer">
        <div class="wf-sidebar__divider"></div>

        <form
            method="post"
            action="/admin/logout.php"
            class="wf-sidebar__logout-form"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrf_token) ?>"
            >

            <button
                type="submit"
                class="wf-sidebar__link wf-sidebar__link--logout"
            >
                <span class="wf-sidebar__icon">
                    <i class="bi bi-box-arrow-right"></i>
                </span>

                <span class="wf-sidebar__text">
                    Keluar
                </span>
            </button>
        </form>
    </div>
</aside>

<script>
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
