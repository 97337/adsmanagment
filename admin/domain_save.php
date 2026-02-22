<?php
/**
 * Save Domain (Add/Edit)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$domain = trim($_POST['domain'] ?? '');
if (empty($domain)) {
    header('Location: index.php?msg=error');
    exit;
}

$db = getDB();

// Check duplicate
$stmt = $db->prepare("SELECT id FROM domains WHERE domain = ?");
$stmt->execute([$domain]);
if ($stmt->fetch()) {
    header('Location: index.php?msg=exists');
    exit;
}

$stmt = $db->prepare("INSERT INTO domains (domain) VALUES (?)");
$stmt->execute([$domain]);

header('Location: index.php?msg=added');
