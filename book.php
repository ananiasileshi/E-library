<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/browse.php');
}

$stmt = db()->prepare('SELECT b.*, c.name AS category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE b.id = ? AND b.status = "active" LIMIT 1');
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    $title = 'Book not found';
    require __DIR__ . '/partials/layout_top.php';
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="h5 mb-1 section-title">Book not available</div>
            <div class="text-muted">This book might be deleted, archived, or not marked as active.</div>
            <div class="mt-3">
                <a class="btn btn-light" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to browse</a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/layout_bottom.php';
    exit;
}

db()->prepare('UPDATE books SET views = views + 1 WHERE id = ?')->execute([$id]);
$book['views'] = (int)($book['views'] ?? 0) + 1;

$title = (string)$book['title'];
require __DIR__ . '/partials/layout_top.php';

?>
<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="book-cover" style="max-width: 320px; margin: 0 auto;">
                    <?php if ((string)($book['cover_path'] ?? '') !== ''): ?>
                        <?php
                        $cover = (string)$book['cover_path'];
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
                <div class="mt-3 d-grid gap-2">
                    <?php if ((string)($book['file_path'] ?? '') !== ''): ?>
                        <a class="btn btn-primary" href="<?= e(url('/read.php?id=' . (int)$book['id'])) ?>" target="_blank"><i class="bi bi-book me-1"></i>Read</a>
                        <a class="btn btn-success" href="<?= e(url('/download.php?id=' . (int)$book['id'])) ?>"><i class="bi bi-download me-1"></i>Download</a>
                    <?php else: ?>
                        <button class="btn btn-primary" disabled><i class="bi bi-book me-1"></i>Read</button>
                        <button class="btn btn-success" disabled><i class="bi bi-download me-1"></i>Download</button>
                    <?php endif; ?>
                    <a class="btn btn-light" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to browse</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="h4 mb-1 section-title"><?= e((string)$book['title']) ?></div>
                <div class="text-muted mb-3"><?= e((string)($book['category_name'] ?? '')) ?></div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-light"><i class="bi bi-file-earmark-text me-1"></i><?= e(strtoupper((string)$book['format'])) ?></span>
                    <?php if ((int)($book['is_free'] ?? 0) === 1): ?>
                        <span class="badge text-bg-success">Free</span>
                    <?php endif; ?>
                    <span class="badge text-bg-light"><i class="bi bi-eye me-1"></i><?= e((string)((int)($book['views'] ?? 0))) ?></span>
                    <span class="badge text-bg-light"><i class="bi bi-download me-1"></i><?= e((string)((int)($book['downloads'] ?? 0))) ?></span>
                </div>

                <?php if ((string)($book['summary'] ?? '') !== ''): ?>
                    <div class="fw-semibold mb-2">Summary</div>
                    <div class="text-muted" style="white-space: pre-wrap"><?= e((string)$book['summary']) ?></div>
                <?php else: ?>
                    <div class="text-muted">No summary available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
