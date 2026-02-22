<?php
/**
 * Toggle Ad Active Status (AJAX)
 * POST: { id, domain_id }
 * Returns JSON: { code: 0, is_active: 0|1 }
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 1, 'msg' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$adId = intval($input['id'] ?? 0);
$domainId = intval($input['domain_id'] ?? 0);

if ($adId < 1 || $domainId < 1) {
    echo json_encode(['code' => 1, 'msg' => 'Missing parameters']);
    exit;
}

try {
    $db = getDB();

    // Get current status
    $stmt = $db->prepare("SELECT is_active FROM ads WHERE id = ? AND domain_id = ?");
    $stmt->execute([$adId, $domainId]);
    $ad = $stmt->fetch();

    if (!$ad) {
        echo json_encode(['code' => 1, 'msg' => 'Ad not found']);
        exit;
    }

    // Toggle
    $newStatus = $ad['is_active'] ? 0 : 1;
    $stmt = $db->prepare("UPDATE ads SET is_active = ? WHERE id = ? AND domain_id = ?");
    $stmt->execute([$newStatus, $adId, $domainId]);

    echo json_encode(['code' => 0, 'is_active' => $newStatus]);
} catch (PDOException $e) {
    echo json_encode(['code' => 1, 'msg' => 'Server error']);
}
