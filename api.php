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

if (empty($domain)) {
    http_response_code(400);
    echo json_encode(['code' => 1, 'msg' => 'Missing domain parameter. Usage: ?domain=example.com or ?domain=example.com&seq=1'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Build base URL for uploaded files
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = defined('BASE_URL') && BASE_URL !== '' ? rtrim(BASE_URL, '/') : $scheme . '://' . $host;

/**
 * Resolve image: image_url takes priority, fallback to image_file with full URL
 */
function resolveImage($ad, $baseUrl)
{
    if (!empty($ad['image_url'])) {
        return $ad['image_url'];
    } elseif (!empty($ad['image_file'])) {
        return $baseUrl . '/' . ltrim($ad['image_file'], '/');
    }
    return '';
}

try {
    $db = getDB();

    if ($seq > 0) {
        // Single ad by sequence number
        $stmt = $db->prepare("
            SELECT a.sort_order, a.ad_text, a.ad_link, a.image_url, a.image_file
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

        echo json_encode([
            'code' => 0,
            'data' => [
                'seq' => (int) $ad['sort_order'],
                'ad_text' => $ad['ad_text'] ?? '',
                'ad_image' => resolveImage($ad, $baseUrl),
                'ad_link' => $ad['ad_link'] ?? '',
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } else {
        // All ads for the domain
        $stmt = $db->prepare("
            SELECT a.sort_order, a.ad_text, a.ad_link, a.image_url, a.image_file
            FROM ads a
            JOIN domains d ON a.domain_id = d.id
            WHERE d.domain = ?
            ORDER BY a.sort_order ASC
        ");
        $stmt->execute([$domain]);
        $ads = $stmt->fetchAll();

        if (empty($ads)) {
            http_response_code(404);
            echo json_encode(['code' => 1, 'msg' => 'No ads found for this domain'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $list = [];
        foreach ($ads as $ad) {
            $list[] = [
                'seq' => (int) $ad['sort_order'],
                'ad_text' => $ad['ad_text'] ?? '',
                'ad_image' => resolveImage($ad, $baseUrl),
                'ad_link' => $ad['ad_link'] ?? '',
            ];
        }

        echo json_encode([
            'code' => 0,
            'total' => count($list),
            'data' => $list,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['code' => 1, 'msg' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
