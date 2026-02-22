<?php
/**
 * Admin Dashboard - Domain List
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$db = getDB();

// Handle quick add domain
$msg = $_GET['msg'] ?? '';

// Get all domains with ad count
$domains = $db->query("
    SELECT d.*, COUNT(a.id) AS ad_count
    FROM domains d
    LEFT JOIN ads a ON a.domain_id = d.id
    GROUP BY d.id
    ORDER BY d.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>域名管理 - 广告管理系统</title>
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
                <li class="active"><a href="index.php">📋 域名管理</a></li>
            </ul>
            <div class="sidebar-footer">
                <span>👤
                    <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?>
                </span>
                <a href="logout.php" class="btn-logout" title="退出">退出</a>
            </div>
        </nav>

        <main class="content">
            <div class="page-header">
                <h1>域名管理 (广告组)</h1>
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')">
                    + 添加域名
                </button>
            </div>

            <?php if ($msg === 'added'): ?>
                <div class="alert alert-success">域名添加成功</div>
            <?php elseif ($msg === 'deleted'): ?>
                <div class="alert alert-success">域名已删除</div>
            <?php elseif ($msg === 'exists'): ?>
                <div class="alert alert-error">该域名已存在</div>
            <?php endif; ?>

            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>域名</th>
                            <th>广告数量</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($domains)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">暂无域名，请先添加</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($domains as $d): ?>
                                <tr>
                                    <td>
                                        <a href="ads.php?domain_id=<?= $d['id'] ?>" class="domain-link">
                                            🌐
                                            <?= htmlspecialchars($d['domain']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge">
                                            <?= $d['ad_count'] ?>
                                        </span></td>
                                    <td class="text-muted">
                                        <?= $d['created_at'] ?>
                                    </td>
                                    <td>
                                        <a href="ads.php?domain_id=<?= $d['id'] ?>" class="btn btn-sm btn-info">管理广告</a>
                                        <a href="domain_delete.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('删除将同时删除该域名下所有广告，确定？')">删除</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- API Usage Guide -->
            <div class="card" style="margin-top:24px">
                <h3 style="margin-bottom:12px">📡 API 调用说明</h3>
                <div class="code-block">
                    <p><strong>请求方式:</strong> GET</p>
                    <p><strong>接口地址:</strong></p>
                    <code>/api.php?domain=域名&amp;seq=广告序号</code><br>
                    <code>/api/域名/广告序号</code>
                    <p style="margin-top:8px"><strong>返回示例:</strong></p>
                    <pre>{
  "code": 0,
  "data": {
    "ad_text": "广告文字",
    "ad_image": "https://...",
    "ad_link": "https://..."
  }
}</pre>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Domain Modal -->
    <div class="modal" id="addModal">
        <div class="modal-overlay" onclick="this.parentElement.classList.remove('show')"></div>
        <div class="modal-content">
            <h2>添加域名</h2>
            <form method="POST" action="domain_save.php">
                <div class="form-group">
                    <label for="domain">域名</label>
                    <input type="text" id="domain" name="domain" placeholder="example.com" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary"
                        onclick="this.closest('.modal').classList.remove('show')">取消</button>
                    <button type="submit" class="btn btn-primary">确定添加</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>