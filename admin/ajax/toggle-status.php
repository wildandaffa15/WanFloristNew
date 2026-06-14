<?php
/**
 * admin/ajax/toggle-status.php
 * Endpoint AJAX: Toggle status toko (aktif ↔ nonaktif)
 *
 * Menerima POST request dengan body JSON:
 *   { "csrf_token": "<token>" }
 *
 * Mengembalikan JSON:
 *   { "success": true,  "status_baru": "aktif"|"nonaktif" }
 *   { "success": false, "message": "<pesan error>" }
 *
 * Requirements: 8.6, 15.3
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

header('Content-Type: application/json');

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Baca body JSON
$body = json_decode(file_get_contents('php://input'), true) ?? [];

// Validasi CSRF token
if (!validate_csrf($body['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid']);
    exit;
}

$pdo = get_pdo();

// Ambil status toko saat ini
$row = $pdo->query('SELECT id, status FROM status_toko LIMIT 1')->fetch();

if (!$row) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Data status toko tidak ditemukan']);
    exit;
}

// Toggle status
$new_status = ($row['status'] === 'aktif') ? 'nonaktif' : 'aktif';

// Simpan status baru
$stmt = $pdo->prepare('UPDATE status_toko SET status = :s WHERE id = :id');
$stmt->execute([':s' => $new_status, ':id' => $row['id']]);

echo json_encode(['success' => true, 'status_baru' => $new_status]);
