<?php
/**
 * Delete Domain (cascades to ads via FK)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$id = intval($_GET['id'] ?? 0);
if ($id < 1) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// Delete uploaded images for this domain's ads first
$stmt = $db->prepare("SELECT image_file FROM ads WHERE domain_id = ? AND image_file != ''");
$stmt->execute([$id]);
while ($row = $stmt->fetch()) {
    $filePath = __DIR__ . '/../' . ltrim($row['image_file'], '/');
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
}

// Delete domain (ads cascade)
$stmt = $db->prepare("DELETE FROM domains WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php?msg=deleted');
