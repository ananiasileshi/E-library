<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/shelves.php';
require_once __DIR__ . '/includes/social.php';

require_login();
$user = current_user();

$title = 'My Library';

// Get user's shelves
$shelves = get_or_create_default_shelves((int)$user['id']);

// Get current shelf from query
$currentShelfSlug = (string)($_GET['shelf'] ?? 'reading');
$currentShelf = null;
foreach ($shelves as $s) {
    if ($s['slug'] === $currentShelfSlug) {
        $currentShelf = $s;
        break;
    }
}

// Get books for current shelf
$books = [];
if ($currentShelf) {
    $stmt = db()->prepare("
        SELECT b.*, rp.percentage, rp.current_page, rp.total_pages
        FROM books b
        JOIN book_shelf bs ON bs.book_id = b.id
        LEFT JOIN reading_progress rp ON rp.book_id = b.id AND rp.user_id = ?
        WHERE bs.shelf_id = ? AND b.status = 'active'
        ORDER BY bs.added_at DESC
    ");
    $stmt->execute([$user['id'], $currentShelf['id']]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Get currently reading books
$readingBooks = get_user_reading_books((int)$user['id']);

// Get user stats
$stats = get_user_stats((int)$user['id']);

require __DIR__ . '/partials/layout_top.php';

?>

<div class="section-header">
    <div>
        <h1 class="section-title"><i class="bi bi-book me-2"></i>My Library</h1>
        <p class="section-subtitle">Your reading journey in one place</p>
    </div>
    <a href="<?= e(url('/profile.php')) ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-person me-1"></i>Profile
    </a>
</div>

<!-- Quick Stats -->
<div class="stat-grid mb-4">
    <div class="stat green">
        <div class="d-flex align-items-center gap-2">
            <div class="i"><i class="bi bi-book"></i></div>
            <div>
                <div class="v"><?= e(number_format((int)$stats['books_read'])) ?></div>
                <div class="k">Books Read</div>
            </div>
        </div>
    </div>
    <div class="stat">
        <div class="d-flex align-items-center gap-2">
            <div class="i"><i class="bi bi-chat-quote"></i></div>
            <div>
                <div class="v"><?= e(number_format((int)$stats['reviews'])) ?></div>
                <div class="k">Reviews</div>
            </div>
        </div>
    </div>
    <div class="stat purple">
        <div class="d-flex align-items-center gap-2">
            <div class="i"><i class="bi bi-people"></i></div>
            <div>
                <div class="v"><?= e(number_format((int)$stats['followers'])) ?></div>
                <div class="k">Followers</div>
            </div>
        </div>
    </div>
    <div class="stat amber">
        <div class="d-flex align-items-center gap-2">
            <div class="i"><i class="bi bi-person-plus"></i></div>
            <div>
                <div class="v"><?= e(number_format((int)$stats['following'])) ?></div>
                <div class="k">Following</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Shelves Sidebar -->
    <div class="col-lg-3">
        <div class="glass p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-collection me-2"></i>My Shelves</h6>
            <div class="vstack gap-1">
                <?php foreach ($shelves as $shelf): ?>
                    <a href="?shelf=<?= e($shelf['slug']) ?>" 
                       class="shelf-link <?= $currentShelfSlug === $shelf['slug'] ? 'active' : '' ?>">
                        <span>
                            <?php if ($shelf['slug'] === 'want-to-read'): ?>
                                <i class="bi bi-bookmark-star me-2"></i>
                            <?php elseif ($shelf['slug'] === 'currently-reading'): ?>
                                <i class="bi bi-book-half me-2"></i>
                            <?php elseif ($shelf['slug'] === 'read'): ?>
                                <i class="bi bi-check-circle me-2"></i>
                            <?php elseif ($shelf['slug'] === 'favorites'): ?>
                                <i class="bi bi-heart me-2"></i>
                            <?php else: ?>
                                <i class="bi bi-folder me-2"></i>
                            <?php endif; ?>
                            <?= e($shelf['name']) ?>
                        </span>
                        <span class="badge bg-secondary"><?= e((string)$shelf['book_count']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($readingBooks)): ?>
        <div class="glass p-3 mt-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Continue Reading</h6>
            <?php foreach (array_slice($readingBooks, 0, 3) as $book): ?>
                <a href="<?= e(url('/read.php?id=' . $book['book_id'])) ?>" class="continue-reading-item">
                    <div class="d-flex gap-2">
                        <?php
                        $cover = (string)($book['cover_path'] ?? '');
                        $coverSrc = $cover !== '' ? (preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'))) : '';
                        ?>
                        <?php if ($coverSrc): ?>
                            <img src="<?= e($coverSrc) ?>" alt="" class="continue-cover">
                        <?php else: ?>
                            <div class="continue-cover continue-cover-placeholder">
                                <i class="bi bi-journal-bookmark"></i>
                            </div>
                        <?php endif; ?>
                        <div class="flex-grow-1 min-w-0">
                            <div class="continue-title"><?= e($book['title']) ?></div>
                            <div class="progress mt-1" style="height:4px">
                                <div class="progress-bar" style="width:<?= e((int)$book['percentage']) ?>%"></div>
                            </div>
                            <div class="small text-muted mt-1"><?= e((int)$book['percentage']) ?>% complete</div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Main Content -->
    <div class="col-lg-9">
        <div class="glass p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold mb-0">
                    <?php if ($currentShelf): ?>
                        <?= e($currentShelf['name']) ?>
                        <span class="badge bg-secondary ms-2"><?= e(count($books)) ?></span>
                    <?php else: ?>
                        All Books
                    <?php endif; ?>
                </h5>
            </div>
            
            <?php if (empty($books)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-3">No books in this shelf yet.</p>
                    <a href="<?= e(url('/browse.php')) ?>" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Browse Books
                    </a>
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
                                <?php if (isset($b['percentage']) && (int)$b['percentage'] > 0): ?>
                                    <div class="book-progress-bar">
                                        <div class="book-progress-fill" style="width:<?= e((int)$b['percentage']) ?>%"></div>
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
                                <?php if (isset($b['percentage']) && (int)$b['percentage'] > 0): ?>
                                    <div class="book-stats">
                                        <span><?= e((int)$b['percentage']) ?>% read</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
