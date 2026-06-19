<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

if (!validate_csrf($body['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid']);
    exit;
}

$pdo = get_pdo();

$row = $pdo->query('SELECT id, status FROM status_toko LIMIT 1')->fetch();

if (!$row) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Data status toko tidak ditemukan']);
    exit;
}

$new_status = ($row['status'] === 'aktif') ? 'nonaktif' : 'aktif';

$stmt = $pdo->prepare('UPDATE status_toko SET status = :s WHERE id = :id');
$stmt->execute([':s' => $new_status, ':id' => $row['id']]);

echo json_encode(['success' => true, 'status_baru' => $new_status]);
