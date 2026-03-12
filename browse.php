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

        <form method="get" action="<?= e(url('/browse.php')) ?>" class="browse-search mt-3">
            <input class="form-control browse-search-input" name="q" placeholder="Search books, authors..." value="<?= e($q) ?>">
            <button class="btn btn-primary browse-search-btn" type="submit">Search</button>
        </form>

        <form method="get" action="<?= e(url('/browse.php')) ?>" class="browse-filters mt-3">
            <input type="hidden" name="q" value="<?= e($q) ?>">
            <select class="form-select browse-filter-select" name="category">
                <option value="0">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $categoryId ? 'selected' : '' ?>><?= e((string)$c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary" type="submit">Apply</button>
            <?php if ($q !== '' || $categoryId > 0): ?>
                <a class="btn btn-light" href="<?= e(url('/browse.php')) ?>">Reset</a>
            <?php endif; ?>
        </form>
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
                                            <img alt="" src="<?= e((string)$b['cover_path']) ?>">
                                        <?php else: ?>
                                            <div class="text-muted small">No cover</div>
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
                                        <a class="btn btn-sm btn-light w-100" href="<?= e(url('/book.php?id=' . (int)$b['id'])) ?>"><i class="bi bi-eye me-1"></i>Details</a>
                                        <?php if ((string)($b['file_path'] ?? '') !== ''): ?>
                                            <a class="btn btn-sm btn-success w-100" href="<?= e(url('/read.php?id=' . (int)$b['id'])) ?>" target="_blank"><i class="bi bi-book me-1"></i>Read</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success w-100" disabled><i class="bi bi-book me-1"></i>Read</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="book-meta">
                                    <div class="book-title"><?= e((string)$b['title']) ?></div>
                                    <div class="book-author small text-muted"><?= e((string)($b['category_name'] ?? '')) ?></div>
                                    <div class="small text-muted mt-1">
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
