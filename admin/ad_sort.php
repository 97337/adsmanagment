<?php
/**
 * Ad Sort - AJAX handler for drag reorder
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$domainId = intval($input['domain_id'] ?? 0);
$ids = $input['ids'] ?? [];

if ($domainId < 1 || empty($ids)) {
    echo json_encode(['code' => 1, 'msg' => 'Invalid params']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Pass 1: set all to temporary high values to avoid UNIQUE constraint conflicts
    $stmt = $db->prepare("UPDATE ads SET sort_order = ? WHERE id = ? AND domain_id = ?");
    foreach ($ids as $i => $id) {
        $stmt->execute([10000 + $i, intval($id), $domainId]);
    }

    // Pass 2: set final sort_order 1-N
    foreach ($ids as $i => $id) {
        $stmt->execute([$i + 1, intval($id), $domainId]);
    }

    $db->commit();
    echo json_encode(['code' => 0, 'msg' => 'ok']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['code' => 1, 'msg' => $e->getMessage()]);
}
