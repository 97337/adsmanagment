<?php
/**
 * Save Ad (Add or Update)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$domainId = intval($_POST['domain_id'] ?? 0);
$adId = intval($_POST['ad_id'] ?? 0);

if ($domainId < 1) {
    header('Location: index.php');
    exit;
}

$db = getDB();

$data = [
    'alliance_name' => trim($_POST['alliance_name'] ?? ''),
    'alliance_account' => trim($_POST['alliance_account'] ?? ''),
    'ad_link' => trim($_POST['ad_link'] ?? ''),
    'ad_text' => trim($_POST['ad_text'] ?? ''),
    'image_url' => trim($_POST['image_url'] ?? ''),
    'image_file' => trim($_POST['image_file'] ?? ''),
];

try {
    if ($adId > 0) {
        // Update existing ad
        $stmt = $db->prepare("
            UPDATE ads SET
                alliance_name = ?, alliance_account = ?, ad_link = ?,
                ad_text = ?, image_url = ?, image_file = ?
            WHERE id = ? AND domain_id = ?
        ");
        $stmt->execute([
            $data['alliance_name'],
            $data['alliance_account'],
            $data['ad_link'],
            $data['ad_text'],
            $data['image_url'],
            $data['image_file'],
            $adId,
            $domainId
        ]);
    } else {
        // Add new ad - get next sort_order
        $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM ads WHERE domain_id = ?");
        $stmt->execute([$domainId]);
        $nextOrder = $stmt->fetch()['next_order'];

        $stmt = $db->prepare("
            INSERT INTO ads (domain_id, sort_order, alliance_name, alliance_account, ad_link, ad_text, image_url, image_file)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $domainId,
            $nextOrder,
            $data['alliance_name'],
            $data['alliance_account'],
            $data['ad_link'],
            $data['ad_text'],
            $data['image_url'],
            $data['image_file']
        ]);
    }

    header('Location: ads.php?domain_id=' . $domainId . '&msg=saved');
} catch (PDOException $e) {
    header('Location: ad_edit.php?domain_id=' . $domainId . '&id=' . $adId . '&msg=error');
}
