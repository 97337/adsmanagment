<?php
/**
 * Ad Edit / Add Form
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$db = getDB();
$domainId = intval($_GET['domain_id'] ?? 0);
$adId = intval($_GET['id'] ?? 0);

// Get domain
$stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
$stmt->execute([$domainId]);
$domain = $stmt->fetch();

if (!$domain) {
    header('Location: index.php');
    exit;
}

// Load ad if editing
$ad = null;
if ($adId > 0) {
    $stmt = $db->prepare("SELECT * FROM ads WHERE id = ? AND domain_id = ?");
    $stmt->execute([$adId, $domainId]);
    $ad = $stmt->fetch();
}

$isEdit = $ad !== null;
$title = $isEdit ? '编辑广告 #' . $ad['sort_order'] : '添加广告';

// Get next sort_order for new ads
$defaultSortOrder = 1;
if (!$isEdit) {
    $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM ads WHERE domain_id = ?");
    $stmt->execute([$domainId]);
    $defaultSortOrder = $stmt->fetch()['next_order'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $title ?> -
        <?= htmlspecialchars($domain['domain']) ?>
    </title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <div class="layout">
        <nav class="sidebar">
            <div class="sidebar-header">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path d="M9 9h6v6H9z" />
                </svg>
                <span>ADS Manager</span>
            </div>
            <ul class="sidebar-nav">
                <li><a href="index.php">📋 域名管理</a></li>
                <li class="active"><a href="ads.php?domain_id=<?= $domainId ?>">📢
                        <?= htmlspecialchars($domain['domain']) ?>
                    </a></li>
            </ul>
            <div class="sidebar-footer">
                <span>👤
                    <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?>
                </span>
                <a href="logout.php" class="btn-logout">退出</a>
            </div>
        </nav>

        <main class="content">
            <div class="page-header">
                <div>
                    <a href="ads.php?domain_id=<?= $domainId ?>" class="back-link">← 返回广告列表</a>
                    <h1>
                        <?= $title ?>
                    </h1>
                </div>
            </div>

            <div class="card">
                <form method="POST" action="ad_save.php" id="adForm">
                    <input type="hidden" name="domain_id" value="<?= $domainId ?>">
                    <input type="hidden" name="ad_id" value="<?= $adId ?>">
                    <input type="hidden" name="image_file" id="imageFileInput"
                        value="<?= htmlspecialchars($ad['image_file'] ?? '') ?>">

                    <div class="form-group" style="max-width:200px">
                        <label for="sort_order">广告序号</label>
                        <input type="number" id="sort_order" name="sort_order" min="1"
                            value="<?= $isEdit ? $ad['sort_order'] : $defaultSortOrder ?>" required>
                        <small class="text-muted">可自定义序号，如只需4号广告位则填4</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="alliance_name">联盟名称</label>
                            <input type="text" id="alliance_name" name="alliance_name"
                                value="<?= htmlspecialchars($ad['alliance_name'] ?? '') ?>"
                                placeholder="e.g., Google Adsense">
                        </div>
                        <div class="form-group">
                            <label for="alliance_account">联盟账号</label>
                            <input type="text" id="alliance_account" name="alliance_account"
                                value="<?= htmlspecialchars($ad['alliance_account'] ?? '') ?>"
                                placeholder="e.g., pub-1234567890">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ad_link">广告链接</label>
                        <input type="url" id="ad_link" name="ad_link"
                            value="<?= htmlspecialchars($ad['ad_link'] ?? '') ?>"
                            placeholder="https://example.com/click">
                    </div>

                    <div class="form-group">
                        <label for="ad_text">广告文字</label>
                        <textarea id="ad_text" name="ad_text" rows="3"
                            placeholder="广告文案内容..."><?= htmlspecialchars($ad['ad_text'] ?? '') ?></textarea>
                    </div>

                    <hr class="divider">

                    <h3 class="section-title">广告图片</h3>
                    <p class="section-desc">填写图片URL或使用上传功能。URL优先级高于上传图片。</p>

                    <div class="form-group">
                        <label for="image_url">图片URL（优先使用）</label>
                        <input type="url" id="image_url" name="image_url"
                            value="<?= htmlspecialchars($ad['image_url'] ?? '') ?>"
                            placeholder="https://example.com/image.jpg">
                    </div>

                    <!-- Image Upload Module -->
                    <div class="form-group">
                        <label>图片上传（URL为空时使用）</label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-placeholder" id="uploadPlaceholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                <p>拖拽图片到此处 / 点击选择文件 / Ctrl+V 粘贴图片</p>
                                <span class="upload-hint">支持 JPG, PNG, GIF, WebP，最大 2MB</span>
                            </div>
                            <div class="upload-preview" id="uploadPreview" style="display:none">
                                <img id="previewImage" src="" alt="preview">
                                <button type="button" class="btn btn-sm btn-danger upload-remove" id="removeImage">✕
                                    移除</button>
                            </div>
                            <input type="file" id="fileInput" accept="image/*" style="display:none">
                            <div class="upload-progress" id="uploadProgress" style="display:none">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progressFill"></div>
                                </div>
                                <span id="progressText">上传中...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Current uploaded image preview -->
                    <?php if (!empty($ad['image_file'])): ?>
                        <div class="form-group">
                            <label>当前已上传图片</label>
                            <div class="current-image">
                                <img src="<?= htmlspecialchars($ad['image_file']) ?>" alt="current">
                                <code><?= htmlspecialchars($ad['image_file']) ?></code>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <a href="ads.php?domain_id=<?= $domainId ?>" class="btn btn-secondary">取消</a>
                        <button type="submit" class="btn btn-primary">💾 保存广告</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="assets/app.js"></script>
</body>

</html>