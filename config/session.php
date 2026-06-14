<?php
/**
 * config/session.php
 * Inisialisasi session dan validasi autentikasi admin.
 *
 * File ini di-require_once oleh SEMUA halaman di direktori admin/.
 * Jika session tidak valid, pengguna di-redirect ke login.php (HTTP 302).
 *
 * Penggunaan (di setiap halaman admin):
 *   require_once __DIR__ . '/../config/session.php';
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

if (empty($_SESSION['id_pengguna'])) {
    // Simpan URL yang dituju untuk redirect kembali setelah login (opsional)
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';

    header('Location: /login.php', true, 302);
    exit;
}
