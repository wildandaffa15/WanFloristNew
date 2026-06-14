<?php
/**
 * admin/ajax/update-status-pesanan.php
 * Endpoint AJAX untuk mengubah status pesanan.
 *
 * Method : POST (application/json)
 * Input  : { "csrf_token": "...", "id_pesanan": 1, "status_baru": "diproses" }
 * Output : { "success": true,  "status_baru": "diproses" }
 *        | { "success": false, "message": "..." }
 *
 * Requirements: 8.3, 8.7, 15.3
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

if (!validate_csrf($body['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF tidak valid']);
    exit;
}

$id_pesanan  = (int) ($body['id_pesanan'] ?? 0);
$status_baru = $body['status_baru'] ?? '';
$allowed     = ['menunggu_konfirmasi', 'diproses', 'selesai', 'dibatalkan'];

if ($id_pesanan <= 0 || !in_array($status_baru, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Input tidak valid']);
    exit;
}

$pdo  = get_pdo();
$stmt = $pdo->prepare('UPDATE pesanan SET status = :s WHERE id_pesanan = :id');
$stmt->execute([':s' => $status_baru, ':id' => $id_pesanan]);

echo json_encode(['success' => true, 'status_baru' => $status_baru]);
