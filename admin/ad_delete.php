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

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}

header('Location: ads.php?domain_id=' . $domainId . '&msg=deleted');
