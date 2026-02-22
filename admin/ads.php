<?php
/**
 * Ad List for a Domain
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

$db = getDB();
$domainId = intval($_GET['domain_id'] ?? 0);

// Get domain info
$stmt = $db->prepare("SELECT * FROM domains WHERE id = ?");
$stmt->execute([$domainId]);
$domain = $stmt->fetch();

if (!$domain) {
    header('Location: index.php');
    exit;
}

$msg = $_GET['msg'] ?? '';

// Get ads ordered by sort_order
$stmt = $db->prepare("SELECT * FROM ads WHERE domain_id = ? ORDER BY sort_order ASC");
$stmt->execute([$domainId]);
$ads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($domain['domain']) ?> - 广告管理
    </title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
                    <a href="index.php" class="back-link">← 返回域名列表</a>
                    <h1>🌐
                        <?= htmlspecialchars($domain['domain']) ?>
                    </h1>
                </div>
                <a href="ad_edit.php?domain_id=<?= $domainId ?>" class="btn btn-primary">+ 添加广告</a>
            </div>

            <?php if ($msg === 'saved'): ?>
                <div class="alert alert-success">广告保存成功</div>
            <?php elseif ($msg === 'deleted'): ?>
                <div class="alert alert-success">广告已删除，序号已重排</div>
            <?php endif; ?>

            <!-- API Quick Reference -->
            <div class="api-hint">
                📡 API 调用: <code>/api/<?= htmlspecialchars($domain['domain']) ?>/{序号}</code>
            </div>

            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="50">序号</th>
                            <th>联盟名称</th>
                            <th>联盟账号</th>
                            <th>广告文字</th>
                            <th>广告图片</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="adList">
                        <?php if (empty($ads)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">暂无广告</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ads as $ad): ?>
                                <tr data-id="<?= $ad['id'] ?>">
                                    <td>
                                        <span class="sort-handle" title="拖拽排序">⠿</span>
                                        <span class="seq-num">
                                            <?= $ad['sort_order'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($ad['alliance_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($ad['alliance_account']) ?>
                                    </td>
                                    <td class="text-ellipsis">
                                        <?= htmlspecialchars(mb_substr($ad['ad_text'] ?? '', 0, 30)) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $img = !empty($ad['image_url']) ? $ad['image_url'] : $ad['image_file'];
                                        if ($img): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" class="thumb" alt="ad">
                                        <?php else: ?>
                                            <span class="text-muted">无图片</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-cell">
                                        <a href="ad_edit.php?id=<?= $ad['id'] ?>&domain_id=<?= $domainId ?>"
                                            class="btn btn-sm btn-info">编辑</a>
                                        <a href="ad_delete.php?id=<?= $ad['id'] ?>&domain_id=<?= $domainId ?>"
                                            class="btn btn-sm btn-danger" onclick="return confirm('确定删除该广告？序号将自动重排。')">删除</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function showToast(msg, isError) {
            const t = document.createElement('div');
            t.className = 'alert ' + (isError ? 'alert-error' : 'alert-success');
            t.textContent = msg;
            Object.assign(t.style, { position: 'fixed', top: '20px', right: '20px', zIndex: '9999', minWidth: '200px' });
            document.body.appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2000);
        }

        // Drag to reorder
        const tbody = document.getElementById('adList');
        if (tbody && tbody.children.length > 0 && tbody.children[0].dataset.id) {
            new Sortable(tbody, {
                handle: '.sort-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    const ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                    fetch('ad_sort.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ domain_id: <?= $domainId ?>, ids: ids })
                    }).then(r => r.json()).then(data => {
                        if (data.code === 0) {
                            // Update sequence numbers in UI
                            tbody.querySelectorAll('tr[data-id]').forEach((tr, i) => {
                                tr.querySelector('.seq-num').textContent = i + 1;
                            });
                            showToast('✅ 排序已更新');
                        } else {
                            showToast('❌ 排序失败: ' + (data.msg || ''), true);
                            console.error('Sort error:', data);
                        }
                    }).catch(err => {
                        showToast('❌ 网络错误', true);
                        console.error('Sort fetch error:', err);
                    });
                }
            });
        }
    </script>
</body>

</html>