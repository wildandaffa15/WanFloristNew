<?php
/**
 * admin/logout.php
 *
 * Handler logout admin WanFlorist.
 *
 * Alur:
 *   1. Menerima permintaan POST dari tombol "Keluar" di sidebar.
 *   2. Memulai (atau melanjutkan) session yang ada.
 *   3. Memvalidasi CSRF token untuk mencegah logout paksa (CSRF attack).
 *   4. Menghancurkan session sepenuhnya via session_destroy().
 *   5. Redirect ke /login.php dengan HTTP 302.
 *
 * Hanya menerima metode POST. Semua akses GET/non-POST langsung
 * di-redirect ke login.php.
 *
 * Requirements: 7.6
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php', true, 302);
    exit;
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf($csrf_token)) {
    // Token tidak valid — kembalikan ke halaman sebelumnya atau ke login
    header('Location: /login.php', true, 302);
    exit;
}

$_SESSION = [];

// Hapus cookie session dari browser jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: /login.php', true, 302);
exit;
