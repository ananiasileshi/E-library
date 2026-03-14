<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$title = 'Browse';
require __DIR__ . '/partials/layout_top.php';

$q = trim((string)($_GET['q'] ?? ''));
$categorySlug = trim((string)($_GET['category'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'newest');
$format = (string)($_GET['format'] ?? '');
$isFree = isset($_GET['free']) && $_GET['free'] === '1';
$minRating = (int)($_GET['rating'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categories = [];
$books = [];
$total = 0;

$categoriesStmt = db()->query('SELECT id, name, slug FROM categories ORDER BY name ASC');
$categories = $categoriesStmt->fetchAll() ?: [];

$where = ['b.status = "active"'];
$params = [];

// Category filter by slug
if ($categorySlug !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

// Format filter
if ($format !== '') {
    $where[] = 'b.format = ?';
    $params[] = $format;
}

// Free filter
if ($isFree) {
    $where[] = 'b.is_free = 1';
}

// Rating filter
if ($minRating > 0 && $minRating <= 5) {
    $where[] = 'b.avg_rating >= ?';
    $params[] = $minRating;
}

// Search - using LIKE for compatibility
if ($q !== '') {
    $where[] = '(b.title LIKE ? OR b.summary LIKE ? OR b.author LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count
$countSql = "SELECT COUNT(*) AS c FROM books b LEFT JOIN categories c ON c.id = b.category_id {$whereSql}";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$total = (int)($countStmt->fetch()['c'] ?? 0);

// Sort
$orderBy = match ($sort) {
    'trending' => 'b.views DESC, b.downloads DESC',
    'rating' => 'b.avg_rating DESC, b.review_count DESC',
    'title' => 'b.title ASC',
    default => 'b.created_at DESC',
};

$sql = "
    SELECT b.id, b.title, b.slug, b.author, b.summary, b.cover_path, b.file_path, b.format, b.is_free, b.views, b.downloads, b.avg_rating, b.review_count,
           c.name AS category_name, c.slug AS category_slug
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    {$whereSql}
    ORDER BY {$orderBy}
    LIMIT {$perPage} OFFSET {$offset}
";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll() ?: [];

$pages = (int)max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);

// Get current category name
$currentCategoryName = '';
if ($categorySlug !== '') {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $categorySlug) {
            $currentCategoryName = $cat['name'];
            break;
        }
    }
}

?>

<div class="section-header mb-3">
    <div>
        <h1 class="section-title"><i class="bi bi-grid me-2"></i>Browse Library</h1>
        <p class="section-subtitle">
            <?php if ($q !== ''): ?>
                Search results for "<?= e($q) ?>"
            <?php elseif ($currentCategoryName !== ''): ?>
                <?= e($currentCategoryName) ?>
            <?php else: ?>
                Discover your next favorite book
            <?php endif; ?>
            <span class="text-muted ms-2">(<?= e(number_format($total)) ?> books)</span>
        </p>
    </div>
</div>

<div class="row g-4">
    <!-- Filters Sidebar -->
    <div class="col-lg-3">
        <div class="glass p-3">
            <form method="get" id="filterForm">
                <?php if ($q !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                <?php endif; ?>
                
                <!-- Search -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-search me-1"></i>Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Title, author..." value="<?= e($q) ?>">
                </div>
                
                <!-- Sort -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-sort-down me-1"></i>Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="trending" <?= $sort === 'trending' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                        <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                    </select>
                </div>
                
                <!-- Categories -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-tag me-1"></i>Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Format -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-file-earmark me-1"></i>Format</label>
                    <select name="format" class="form-select">
                        <option value="">All Formats</option>
                        <option value="pdf" <?= $format === 'pdf' ? 'selected' : '' ?>>PDF</option>
                        <option value="epub" <?= $format === 'epub' ? 'selected' : '' ?>>EPUB</option>
                        <option value="mobi" <?= $format === 'mobi' ? 'selected' : '' ?>>MOBI</option>
                    </select>
                </div>
                
                <!-- Rating -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-star me-1"></i>Min Rating</label>
                    <select name="rating" class="form-select">
                        <option value="">Any Rating</option>
                        <option value="4" <?= $minRating === 4 ? 'selected' : '' ?>>4+ Stars</option>
                        <option value="3" <?= $minRating === 3 ? 'selected' : '' ?>>3+ Stars</option>
                        <option value="2" <?= $minRating === 2 ? 'selected' : '' ?>>2+ Stars</option>
                    </select>
                </div>
                
                <!-- Free toggle -->
                <div class="form-check mb-3">
                    <input type="checkbox" name="free" value="1" class="form-check-input" id="freeOnly" <?= $isFree ? 'checked' : '' ?>>
                    <label class="form-check-label" for="freeOnly">Free books only</label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply Filters</button>
                    <a href="<?= e(url('/browse.php')) ?>" class="btn btn-outline-secondary btn-sm">Clear All</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results -->
    <div class="col-lg-9">
        <div class="glass p-4">
            <?php if (!$books): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <h5 class="mt-3">No books found</h5>
                    <p class="text-muted">Try adjusting your filters or search terms</p>
                    <a href="<?= e(url('/browse.php')) ?>" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="grid-books">
                    <?php foreach ($books as $b): ?>
                        <?php
                        $cover = (string)($b['cover_path'] ?? '');
                        $coverSrc = $cover !== '' ? (preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'))) : '';
                        ?>
                        <div>
                            <div class="book-tile">
                                <div class="book-cover">
                                    <?php if ($coverSrc !== ''): ?>
                                        <img alt="" src="<?= e($coverSrc) ?>">
                                    <?php else: ?>
                                        <div class="no-cover">
                                            <i class="bi bi-journal-bookmark"></i>
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
                                <?php if ((float)$b['avg_rating'] > 0): ?>
                                    <div class="book-rating-badge">
                                        <i class="bi bi-star-fill"></i> <?= e(number_format((float)$b['avg_rating'], 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="book-overlay"></div>
                                <div class="book-actions">
                                    <a class="tile-action" href="<?= e(url('/book.php?id=' . (int)$b['id'])) ?>" data-tip="Details"><i class="bi bi-eye"></i></a>
                                    <?php if ((string)($b['file_path'] ?? '') !== ''): ?>
                                        <a class="tile-action action-read" href="<?= e(url('/read.php?id=' . (int)$b['id'])) ?>" target="_blank" data-tip="Read"><i class="bi bi-book"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="book-meta">
                                <div class="book-title"><?= e((string)$b['title']) ?></div>
                                <?php if ((string)($b['author'] ?? '') !== ''): ?>
                                    <div class="book-author small text-muted"><?= e((string)$b['author']) ?></div>
                                <?php elseif ((string)($b['category_name'] ?? '') !== ''): ?>
                                    <div class="book-author small text-muted"><?= e((string)$b['category_name']) ?></div>
                                <?php endif; ?>
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
                                if ($categorySlug !== '') { $baseParams['category'] = $categorySlug; }
                                if ($sort !== 'newest') { $baseParams['sort'] = $sort; }
                                if ($format !== '') { $baseParams['format'] = $format; }
                                if ($isFree) { $baseParams['free'] = '1'; }
                                if ($minRating > 0) { $baseParams['rating'] = $minRating; }
                                
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
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
