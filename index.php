<?php

declare(strict_types=1);

$title = 'Home';

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$catsStmt = db()->query('SELECT id, name FROM categories ORDER BY name ASC LIMIT 3');
$topCats = $catsStmt->fetchAll() ?: [];

$recentStmt = db()->query('SELECT id, title, cover_path, file_path, format, is_free, created_at FROM books WHERE status = "active" ORDER BY created_at DESC LIMIT 12');
$recent = $recentStmt->fetchAll() ?: [];

$recStmt = db()->query('SELECT id, title, cover_path, file_path, format, is_free, views, downloads FROM books WHERE status = "active" ORDER BY downloads DESC, views DESC, created_at DESC LIMIT 12');
$recommended = $recStmt->fetchAll() ?: [];

require __DIR__ . '/partials/layout_top.php';

?>
<div class="browse-head mb-4">
    <div class="browse-head-inner">
        <div class="browse-title">Discover ebooks you’ll love</div>
        <div class="browse-sub text-muted">Search, explore categories, and download files in seconds.</div>

        <form method="get" action="<?= e(url('/browse.php')) ?>" class="browse-search mt-3">
            <input class="form-control browse-search-input" name="q" placeholder="Search books, authors..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
            <button class="btn btn-primary browse-search-btn" type="submit">Search</button>
        </form>

        <div class="browse-filters mt-3">
            <a class="btn btn-outline-primary" href="<?= e(url('/browse.php')) ?>">Browse all</a>
            <?php foreach ($topCats as $c): ?>
                <a class="btn btn-light" href="<?= e(url('/browse.php?category=' . (int)$c['id'])) ?>"><?= e((string)$c['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
    <div>
        <div class="h4 mb-1 section-title">Recently Added</div>
        <div class="text-muted">New books added to the library</div>
    </div>
    <div>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-arrow-right"></i> See all</a>
    </div>
</div>

<div class="book-row mb-4">
    <?php if (!$recent): ?>
        <div class="text-muted">No books yet. <?php if ($user && $user['role'] === 'admin'): ?><a href="<?= e(url('/admin/book_edit.php')) ?>">Add a book</a><?php else: ?><a href="<?= e(url('/browse.php')) ?>">Browse</a><?php endif; ?></div>
    <?php else: ?>
    <?php foreach ($recent as $b): ?>
        <div class="book-card">
            <div class="book-tile">
                <div class="book-cover">
                    <?php
                    $cover = (string)($b['cover_path'] ?? '');
                    $coverSrc = $cover !== '' ? (preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'))) : '';
                    ?>
                    <?php if ($coverSrc !== ''): ?>
                        <img alt="" src="<?= e($coverSrc) ?>">
                    <?php else: ?>
                        <div class="text-muted small">No cover</div>
                    <?php endif; ?>
                </div>
                <div class="book-badges">
                    <span class="badge-soft blue"><?= e(strtoupper((string)($b['format'] ?? 'PDF'))) ?></span>
                    <?php if ((int)($b['is_free'] ?? 0) === 1): ?><span class="badge-soft green">Free</span><?php endif; ?>
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
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
    <div>
        <div class="h4 mb-1 section-title">Recommended For You</div>
        <div class="text-muted">Books you might like</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-light" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-funnel"></i></a>
        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/browse.php')) ?>">Explore</a>
    </div>
</div>

<div class="grid-books">
    <?php if (!$recommended): ?>
        <div class="text-muted">No books yet.</div>
    <?php else: ?>
    <?php foreach ($recommended as $b): ?>
        <div>
            <div class="book-tile">
                <div class="book-cover">
                    <?php
                    $cover = (string)($b['cover_path'] ?? '');
                    $coverSrc = $cover !== '' ? (preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'))) : '';
                    ?>
                    <?php if ($coverSrc !== ''): ?>
                        <img alt="" src="<?= e($coverSrc) ?>">
                    <?php else: ?>
                        <div class="text-muted small">No cover</div>
                    <?php endif; ?>
                </div>
                <div class="book-badges">
                    <span class="badge-soft blue"><?= e(strtoupper((string)($b['format'] ?? 'PDF'))) ?></span>
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
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php

require __DIR__ . '/partials/layout_bottom.php';
