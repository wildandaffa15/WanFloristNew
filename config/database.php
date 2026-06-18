<?php
declare(strict_types=1);

/**
 * Mengembalikan instance PDO singleton.
 * Koneksi hanya dibuat sekali per siklus request.
 *
 * @throws PDOException Jika koneksi database gagal.
 * @return PDO
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host    = 'localhost';
    $dbname  = 'wanflorist_new_db';
    $charset = 'utf8mb4';
    $user    = 'root';
    $pass    = '';

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Jangan membocorkan detail koneksi ke browser
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(503);
        die('Koneksi database gagal. Silakan coba beberapa saat lagi.');
    }

    return $pdo;
}
