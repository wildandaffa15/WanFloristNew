<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

if (empty($_SESSION['id_pengguna'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Session login berakhir.'
    ]);

    exit;
}
