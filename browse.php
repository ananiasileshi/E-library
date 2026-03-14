<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$title = 'Browse';
require __DIR__ . '/partials/layout_top.php';

$q = trim((string)($_GET['q'] ?? ''));
$categoryId = (int)($_GET['category'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categories = [];
$books = [];
$total = 0;

$categoriesStmt = db()->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $categoriesStmt->fetchAll() ?: [];

$where = ['b.status = "active"'];
$params = [];

if ($categoryId > 0) {
    $where[] = 'b.category_id = ?';
    $params[] = $categoryId;
}

$useFulltext = false;
if ($q !== '') {
    $useFulltext = mb_strlen($q) >= 3;
    if ($useFulltext) {
        $where[] = 'MATCH(b.title, b.summary) AGAINST (? IN BOOLEAN MODE)';
        $params[] = $q . '*';
    } else {
        $where[] = '(b.title LIKE ? OR b.summary LIKE ?)';
        $params[] = '%' . $q . '%';
        $params[] = '%' . $q . '%';
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countSql = "SELECT COUNT(*) AS c FROM books b {$whereSql}";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$total = (int)($countStmt->fetch()['c'] ?? 0);

$orderSql = $useFulltext ? 'ORDER BY b.created_at DESC' : 'ORDER BY b.created_at DESC';
$sql = "
    SELECT b.id, b.title, b.slug, b.summary, b.cover_path, b.file_path, b.format, b.is_free, b.views, b.downloads,
           c.name AS category_name
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    {$whereSql}
    {$orderSql}
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll() ?: [];

$pages = (int)max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

?>
<div class="browse-head mb-4">
    <div class="browse-head-inner">
        <div class="browse-title">Browse Library</div>
        <div class="browse-sub text-muted"><?= $q !== '' ? 'Search: ' . e($q) : 'Search books and filter by category' ?></div>

        <form method="get" action="<?= e(url('/browse.php')) ?>" class="browse-search hero-search mt-3">
            <div class="hero-search-wrap">
                <i class="bi bi-search hero-search-icon" aria-hidden="true"></i>
                <input class="form-control browse-search-input hero-search-input" name="q" placeholder="Search books, authors..." value="<?= e($q) ?>">
            </div>
            <button class="btn btn-primary browse-search-btn hero-search-btn" type="submit">Search</button>
        </form>

        <div class="browse-filters hero-chips mt-3">
            <?php
            $base = [];
            if ($q !== '') { $base['q'] = $q; }
            ?>
            <a class="btn btn-outline-primary chip <?= $categoryId === 0 ? 'chip-primary' : '' ?>" href="<?= e(url('/browse.php' . ($q !== '' ? ('?' . http_build_query($base)) : ''))) ?>"><i class="bi bi-grid me-1" aria-hidden="true"></i>Browse all</a>
            <?php foreach ($categories as $c): ?>
                <?php
                $cid = (int)$c['id'];
                $params = $base;
                $params['category'] = $cid;
                $isActive = $cid === $categoryId;
                ?>
                <a class="btn btn-light chip <?= $isActive ? 'chip-primary' : '' ?>" href="<?= e(url('/browse.php?' . http_build_query($params))) ?>"><i class="bi bi-tag me-1" aria-hidden="true"></i><?= e((string)$c['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="small text-muted">
                Showing <?= e((string)count($books)) ?> of <?= e((string)$total) ?>
            </div>
        </div>

                <?php if (!$books): ?>
                    <div class="text-muted">No books found.</div>
                <?php else: ?>
                    <div class="grid-books">
                        <?php foreach ($books as $b): ?>
                            <div>
                                <div class="book-tile">
                                    <div class="book-cover">
                                        <?php if ((string)($b['cover_path'] ?? '') !== ''): ?>
                                            <?php
                                            $cover = (string)$b['cover_path'];
                                            $coverSrc = preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'));
                                            ?>
                                            <img alt="" src="<?= e($coverSrc) ?>">
                                        <?php else: ?>
                                            <div class="no-cover">
                                                <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                                                <div class="t">No cover</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-badges">
                                        <span class="badge-soft blue"><?= e(strtoupper((string)$b['format'])) ?></span>
                                        <?php if ((int)$b['is_free'] === 1): ?>
                                            <span class="badge-soft green">Free</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-overlay"></div>
                                    <div class="book-actions">
                                        <a class="tile-action action-details" href="<?= e(url('/book.php?id=' . (int)$b['id'])) ?>" aria-label="Details" data-tip="Details"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <?php if ((string)($b['file_path'] ?? '') !== ''): ?>
                                            <a class="tile-action action-read" href="<?= e(url('/read.php?id=' . (int)$b['id'])) ?>" target="_blank" aria-label="Read" data-tip="Read"><i class="bi bi-book" aria-hidden="true"></i></a>
                                        <?php else: ?>
                                            <span class="tile-action action-read disabled" aria-label="Read" data-tip="Read" aria-disabled="true"><i class="bi bi-book" aria-hidden="true"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="book-meta">
                                    <div class="book-title"><?= e((string)$b['title']) ?></div>
                                    <div class="book-author small text-muted"><?= e((string)($b['category_name'] ?? '')) ?></div>
                                    <div class="book-stats">
                                        <i class="bi bi-eye"></i> <?= e((string)((int)($b['views'] ?? 0))) ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-download"></i> <?= e((string)((int)($b['downloads'] ?? 0))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Pagination">
                                <ul class="pagination mb-0">
                                    <?php
                                    $baseParams = [];
                                    if ($q !== '') { $baseParams['q'] = $q; }
                                    if ($categoryId > 0) { $baseParams['category'] = $categoryId; }

                                    $window = 2;
                                    $start = max(1, $page - $window);
                                    $end = min($pages, $page + $window);
                                    ?>
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <?php $p = max(1, $page - 1); $href = url('/browse.php?' . http_build_query(array_merge($baseParams, ['page' => $p]))); ?>
                                        <a class="page-link" href="<?= e($href) ?>">Prev</a>
                                    </li>

                                    <?php if ($start > 1): ?>
                                        <?php $href = url('/browse.php?' . http_build_query(array_merge($baseParams, ['page' => 1]))); ?>
                                        <li class="page-item <?= $page === 1 ? 'active' : '' ?>"><a class="page-link" href="<?= e($href) ?>">1</a></li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <?php $href = url('/browse.php?' . http_build_query(array_merge($baseParams, ['page' => $i]))); ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($href) ?>"><?= e((string)$i) ?></a></li>
                                    <?php endfor; ?>

                                    <?php if ($end < $pages): ?>
                                        <?php if ($end < $pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <?php $href = url('/browse.php?' . http_build_query(array_merge($baseParams, ['page' => $pages]))); ?>
                                        <li class="page-item <?= $page === $pages ? 'active' : '' ?>"><a class="page-link" href="<?= e($href) ?>"><?= e((string)$pages) ?></a></li>
                                    <?php endif; ?>

                                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                                        <?php $p = min($pages, $page + 1); $href = url('/browse.php?' . http_build_query(array_merge($baseParams, ['page' => $p]))); ?>
                                        <a class="page-link" href="<?= e($href) ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
