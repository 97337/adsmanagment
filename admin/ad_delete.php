<?php
/**
 * Delete Ad and renumber remaining ads
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$adId = intval($_GET['id'] ?? 0);
$domainId = intval($_GET['domain_id'] ?? 0);

if ($adId < 1 || $domainId < 1) {
    header('Location: index.php');
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Delete uploaded image file
    $stmt = $db->prepare("SELECT image_file FROM ads WHERE id = ? AND domain_id = ?");
    $stmt->execute([$adId, $domainId]);
    $ad = $stmt->fetch();
    if ($ad && !empty($ad['image_file'])) {
        $filePath = __DIR__ . '/../' . ltrim($ad['image_file'], '/');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Delete the ad
    $stmt = $db->prepare("DELETE FROM ads WHERE id = ? AND domain_id = ?");
    $stmt->execute([$adId, $domainId]);

    // Renumber remaining ads to keep 1-N continuous
    $stmt = $db->prepare("SELECT id FROM ads WHERE domain_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$domainId]);
    $remaining = $stmt->fetchAll();

    $updateStmt = $db->prepare("UPDATE ads SET sort_order = ? WHERE id = ?");
    foreach ($remaining as $i => $row) {
        $updateStmt->execute([$i + 1, $row['id']]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}

header('Location: ads.php?domain_id=' . $domainId . '&msg=deleted');
