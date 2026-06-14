<?php
/**
 * admin/ajax/update-stok.php
 *
 * Endpoint AJAX: update stok_saat_ini pada tabel stok_bahan.
 * Menerima POST dengan body JSON, mengembalikan JSON.
 *
 * Request body:
 *   { "csrf_token": "...", "id_bahan": 1, "stok_baru": 25 }
 *
 * Response sukses:
 *   { "success": true, "stok_baru": 25, "is_kritis": false }
 *
 * Response error:
 *   { "success": false, "message": "Pesan error" }
 *
 * Requirements: 16.1, 16.4
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

// Selalu kembalikan JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$csrf_token = $body['csrf_token'] ?? '';
if (!validate_csrf($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid.']);
    exit;
}

$id_bahan = isset($body['id_bahan']) ? filter_var($body['id_bahan'], FILTER_VALIDATE_INT) : false;
if ($id_bahan === false || $id_bahan <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID bahan tidak valid.']);
    exit;
}

if (!isset($body['stok_baru'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nilai stok baru wajib diisi.']);
    exit;
}
$stok_baru = filter_var($body['stok_baru'], FILTER_VALIDATE_INT);
if ($stok_baru === false || $stok_baru < 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Stok baru harus berupa bilangan bulat >= 0.']);
    exit;
}

try {
    $pdo = get_pdo();

    $cek = $pdo->prepare('SELECT id_bahan, stok_minimum FROM stok_bahan WHERE id_bahan = :id');
    $cek->execute([':id' => $id_bahan]);
    $bahan = $cek->fetch();

    if (!$bahan) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bahan tidak ditemukan.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'UPDATE stok_bahan SET stok_saat_ini = :stok WHERE id_bahan = :id'
    );
    $stmt->execute([
        ':stok' => $stok_baru,
        ':id'   => $id_bahan,
    ]);

    $is_kritis = $stok_baru < (int) $bahan['stok_minimum'];

    echo json_encode([
        'success'   => true,
        'stok_baru' => $stok_baru,
        'is_kritis' => $is_kritis,
    ]);

} catch (PDOException $e) {
    error_log('update-stok.php PDOException: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui stok. Silakan coba lagi.']);
}
