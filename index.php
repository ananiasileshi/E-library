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
        <div class="browse-title"><span style="color:#E6452B">L</span><span style="color:#8BB746">I</span><span style="color:#2491BF">B</span><span style="color:#000000">R</span><span style="color:#000000">A</span><span style="color:#8BB746">R</span><span style="color:#000000">Y</span></div>
        <div class="browse-sub text-muted">Search by title, author, or topic then start reading in minutes.</div>

        <form method="get" action="<?= e(url('/browse.php')) ?>" class="browse-search hero-search mt-3">
            <div class="hero-search-wrap">
                <i class="bi bi-search hero-search-icon" aria-hidden="true"></i>
                <input class="form-control browse-search-input hero-search-input" name="q" placeholder="Search books, authors..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
            </div>
            <button class="btn btn-primary browse-search-btn hero-search-btn" type="submit">Search</button>
        </form>

        <div class="browse-filters hero-chips mt-3">
            <a class="btn btn-outline-primary chip chip-primary" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-grid me-1" aria-hidden="true"></i>Browse all</a>
            <?php foreach ($topCats as $c): ?>
                <a class="btn btn-light chip" href="<?= e(url('/browse.php?category=' . (int)$c['id'])) ?>"><i class="bi bi-tag me-1" aria-hidden="true"></i><?= e((string)$c['name']) ?></a>
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
                        <div class="no-cover">
                            <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                            <div class="t">No cover</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book-badges">
                    <span class="badge-soft blue"><?= e(strtoupper((string)($b['format'] ?? 'PDF'))) ?></span>
                    <?php if ((int)($b['is_free'] ?? 0) === 1): ?><span class="badge-soft green">Free</span><?php endif; ?>
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
                        <div class="no-cover">
                            <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                            <div class="t">No cover</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book-badges">
                    <span class="badge-soft blue"><?= e(strtoupper((string)($b['format'] ?? 'PDF'))) ?></span>
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
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php

require __DIR__ . '/partials/layout_bottom.php';
