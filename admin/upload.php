<?php
/**
 * Image Upload Handler (AJAX)
 * Supports: local file upload, drag & drop, clipboard paste
 * Returns: { code: 0, url: "/uploads/xxx.jpg" }
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['code' => 1, 'msg' => 'POST only']);
    exit;
}

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Check for file
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['image']['error'] ?? -1;
    echo json_encode(['code' => 1, 'msg' => 'Upload failed, error code: ' . $errCode]);
    exit;
}

$file = $_FILES['image'];

// Validate size
if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['code' => 1, 'msg' => '文件大小超过限制 (' . (MAX_UPLOAD_SIZE / 1024 / 1024) . 'MB)']);
    exit;
}

// Validate type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, ALLOWED_TYPES)) {
    echo json_encode(['code' => 1, 'msg' => '不支持的文件格式，仅支持: JPG, PNG, GIF, WebP']);
    exit;
}

// Generate unique filename
$ext = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    default => 'jpg',
};
$filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = UPLOAD_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['code' => 1, 'msg' => '保存文件失败']);
    exit;
}

$url = UPLOAD_URL . $filename;
echo json_encode(['code' => 0, 'url' => $url]);
