<?php

declare(strict_types=1);

$title = 'Home';

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$stats = db()->query('SELECT (SELECT COUNT(*) FROM books WHERE status = "active") as total_books, (SELECT COUNT(*) FROM users WHERE status = "active") as total_users')->fetch(PDO::FETCH_ASSOC);

$catsStmt = db()->query('SELECT id, name, slug FROM categories ORDER BY name ASC LIMIT 12');
$topCats = $catsStmt->fetchAll() ?: [];

$recentStmt = db()->query('SELECT id, title, slug, cover_path, file_path, format, is_free, created_at FROM books WHERE status = "active" ORDER BY created_at DESC LIMIT 12');
$recent = $recentStmt->fetchAll() ?: [];

$trendingStmt = db()->query('SELECT id, title, slug, cover_path, file_path, format, is_free, views, downloads FROM books WHERE status = "active" ORDER BY views DESC, downloads DESC LIMIT 12');
$trending = $trendingStmt->fetchAll() ?: [];

$recommendedStmt = db()->query('SELECT id, title, slug, cover_path, file_path, format, is_free, views, downloads FROM books WHERE status = "active" ORDER BY downloads DESC, views DESC, created_at DESC LIMIT 12');
$recommended = $recommendedStmt->fetchAll() ?: [];

require __DIR__ . '/partials/layout_top.php';

?>
<!-- Hero Carousel -->
<div class="hero-carousel mb-4">
    <div class="hero-carousel-inner">
        <div class="hero-slide active" style="background: linear-gradient(135deg, rgba(46,144,250,.15), rgba(155,81,224,.12))">
            <div class="hero-slide-content">
                <div class="hero-badge"><i class="bi bi-stars"></i> Welcome to the Vault</div>
                <h1 class="hero-title">Enter the Vault of <span class="text-gradient">Forbidden Knowledge</span></h1>
                <p class="hero-subtitle">Discover <?= e(number_format((int)($stats['total_books'] ?? 0))) ?>+ eBooks waiting for you. Free for members.</p>
                <form method="get" action="<?= e(url('/browse.php')) ?>" class="hero-search mt-4">
                    <div class="hero-search-wrap">
                        <i class="bi bi-search hero-search-icon" aria-hidden="true"></i>
                        <input class="form-control hero-search-input" name="q" placeholder="Search books, authors, topics..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
                    </div>
                    <button class="btn btn-primary hero-search-btn" type="submit">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                </form>
                <div class="hero-chips mt-3">
                    <a class="btn btn-light chip" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-grid me-1"></i>Browse All</a>
                    <?php foreach (array_slice($topCats, 0, 4) as $c): ?>
                        <a class="btn btn-light chip" href="<?= e(url('/browse.php?category=' . $c['slug'])) ?>"><?= e($c['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <i class="bi bi-journal-bookmark"></i>
            <div class="hero-stat-value"><?= e(number_format((int)($stats['total_books'] ?? 0))) ?></div>
            <div class="hero-stat-label">Books</div>
        </div>
        <div class="hero-stat">
            <i class="bi bi-people"></i>
            <div class="hero-stat-value"><?= e(number_format((int)($stats['total_users'] ?? 0))) ?></div>
            <div class="hero-stat-label">Readers</div>
        </div>
        <div class="hero-stat">
            <i class="bi bi-download"></i>
            <div class="hero-stat-value">Free</div>
            <div class="hero-stat-label">Downloads</div>
        </div>
    </div>
</div>

<!-- Trending Section -->
<div class="section-header">
    <div>
        <h2 class="section-title"><i class="bi bi-fire text-danger me-2"></i>Trending Now</h2>
        <p class="section-subtitle">Most popular books this week</p>
    </div>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/browse.php?sort=trending')) ?>">
        <i class="bi bi-arrow-right"></i> See all
    </a>
</div>

<div class="book-row mb-5">
    <?php if (!$trending): ?>
        <div class="text-muted py-4">No trending books yet.</div>
    <?php else: ?>
        <?php foreach ($trending as $b): ?>
            <?php
            $cover = (string)($b['cover_path'] ?? '');
            $coverSrc = $cover !== '' ? (preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'))) : '';
            ?>
            <div class="book-card">
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
                        <span class="badge-soft blue"><?= e(strtoupper((string)($b['format'] ?? 'PDF'))) ?></span>
                        <?php if ((int)($b['is_free'] ?? 0) === 1): ?><span class="badge-soft green">Free</span><?php endif; ?>
                    </div>
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
                    <div class="book-stats">
                        <span><i class="bi bi-eye"></i> <?= e(number_format((int)$b['views'])) ?></span>
                        <span><i class="bi bi-download"></i> <?= e(number_format((int)$b['downloads'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- New Arrivals Section -->
<div class="section-header">
    <div>
        <h2 class="section-title"><i class="bi bi-clock-history text-primary me-2"></i>New Arrivals</h2>
        <p class="section-subtitle">Fresh books added recently</p>
    </div>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/browse.php?sort=newest')) ?>">
        <i class="bi bi-arrow-right"></i> See all
    </a>
</div>

<div class="book-row mb-5">
    <?php if (!$recent): ?>
        <div class="text-muted py-4">No new books yet. Check back soon!</div>
    <?php else: ?>
        <?php foreach ($recent as $b): ?>
            <?php
            $cover = (string)($b['cover_path'] ?? '');
            $coverSrc = $cover !== '' ? (preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'))) : '';
            ?>
            <div class="book-card">
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
                        <span class="badge-soft amber"><i class="bi bi-stars"></i> New</span>
                    </div>
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
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Categories Grid -->
<div class="section-header">
    <div>
        <h2 class="section-title"><i class="bi bi-grid-3x3-gap text-success me-2"></i>Browse by Genre</h2>
        <p class="section-subtitle">Find your next favorite category</p>
    </div>
</div>

<div class="category-grid mb-5">
    <?php foreach ($topCats as $cat): ?>
        <a class="category-card" href="<?= e(url('/browse.php?category=' . $cat['slug'])) ?>">
            <i class="bi bi-bookmark"></i>
            <span><?= e($cat['name']) ?></span>
        </a>
    <?php endforeach; ?>
    <a class="category-card all-categories" href="<?= e(url('/browse.php')) ?>">
        <i class="bi bi-grid"></i>
        <span>All Categories</span>
    </a>
</div>

<!-- Recommended Section -->
<div class="section-header">
    <div>
        <h2 class="section-title"><i class="bi bi-hand-thumbs-up text-info me-2"></i>Recommended For You</h2>
        <p class="section-subtitle">Books you might enjoy</p>
    </div>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/browse.php')) ?>">
        <i class="bi bi-arrow-right"></i> Explore
    </a>
</div>

<div class="grid-books mb-4">
    <?php if (!$recommended): ?>
        <div class="text-muted py-4">No recommendations yet. Start reading!</div>
    <?php else: ?>
        <?php foreach ($recommended as $b): ?>
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
                        <span class="badge-soft blue"><?= e(strtoupper((string)($b['format'] ?? 'PDF'))) ?></span>
                    </div>
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
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php

require __DIR__ . '/partials/layout_bottom.php';
