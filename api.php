<?php
/**
 * Public API Endpoint
 * GET /api.php?domain=example.com&seq=1
 * GET /api/example.com/1  (via .htaccess rewrite)
 *
 * Returns JSON: { code: 0, data: { ad_text, ad_image, ad_link } }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/db.php';

$domain = trim($_GET['domain'] ?? '');
$seq = intval($_GET['seq'] ?? 0);

if (empty($domain) || $seq < 1) {
    http_response_code(400);
    echo json_encode(['code' => 1, 'msg' => 'Missing or invalid parameters. Usage: ?domain=example.com&seq=1'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT a.ad_text, a.ad_link, a.image_url, a.image_file
        FROM ads a
        JOIN domains d ON a.domain_id = d.id
        WHERE d.domain = ? AND a.sort_order = ?
        LIMIT 1
    ");
    $stmt->execute([$domain, $seq]);
    $ad = $stmt->fetch();

    if (!$ad) {
        http_response_code(404);
        echo json_encode(['code' => 1, 'msg' => 'Ad not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Image priority: image_url > image_file
    $image = '';
    if (!empty($ad['image_url'])) {
        $image = $ad['image_url'];
    } elseif (!empty($ad['image_file'])) {
        $image = $ad['image_file'];
    }

    echo json_encode([
        'code' => 0,
        'data' => [
            'ad_text' => $ad['ad_text'] ?? '',
            'ad_image' => $image,
            'ad_link' => $ad['ad_link'] ?? '',
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['code' => 1, 'msg' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
