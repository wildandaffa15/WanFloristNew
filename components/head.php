<?php
/**
 * components/head.php
 *
 * Komponen <head> HTML standar untuk semua halaman WanFlorist.
 *
 * Variabel yang harus di-set sebelum meng-include file ini:
 *   - $page_title  (string, wajib)  — judul halaman yang ditampilkan di tab browser.
 *   - $css_extra   (string, opsional) — path tambahan CSS, misalnya:
 *                                       '/assets/css/public.css' atau '/assets/css/admin.css'
 *
 * Requirements: 16.2, 17.1
 */
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'WanFlorist') ?> — WanFlorist</title>

    <!-- Google Fonts preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Playfair Display (headings) + Inter (body) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- CSS utama: variabel, reset, typografi, komponen global -->
    <link rel="stylesheet" href="/assets/css/main.css">

    <?php if (!empty($css_extra)): ?>
    <!-- CSS tambahan spesifik halaman -->
    <link rel="stylesheet" href="<?= e($css_extra) ?>">
    <?php endif; ?>
</head>
