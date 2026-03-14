<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/shelves.php';
require_once __DIR__ . '/includes/social.php';
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
    <div class="glass p-4">
        <div class="text-center py-4">
            <i class="bi bi-book display-4 text-muted"></i>
            <h4 class="mt-3">Book not available</h4>
            <p class="text-muted">This book might be deleted, archived, or not marked as active.</p>
            <a class="btn btn-primary" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to browse</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/layout_bottom.php';
    exit;
}

db()->prepare('UPDATE books SET views = views + 1 WHERE id = ?')->execute([$id]);
$book['views'] = (int)($book['views'] ?? 0) + 1;

$user = current_user();

// Get rating stats
$ratingStats = get_book_rating_stats($id);

// Get user's shelves and book status
$userShelves = [];
$userReview = null;
$readingProgress = null;
if ($user) {
    $userShelves = get_book_shelves($id, (int)$user['id']);
    $userReview = get_user_review($id, (int)$user['id']);
    $readingProgress = get_reading_progress($id, (int)$user['id']);
}

// Get reviews
$reviews = get_book_reviews($id, 5);

// Handle shelf toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $action = (string)($_POST['action'] ?? '');
    $shelfId = (int)($_POST['shelf_id'] ?? 0);
    
    if ($action === 'add_to_shelf' && $shelfId > 0) {
        add_book_to_shelf($id, $shelfId);
        redirect('/book.php?id=' . $id);
    } elseif ($action === 'remove_from_shelf' && $shelfId > 0) {
        remove_book_from_shelf($id, $shelfId);
        redirect('/book.php?id=' . $id);
    } elseif ($action === 'submit_review') {
        $rating = (int)($_POST['rating'] ?? 0);
        $reviewTitle = trim((string)($_POST['review_title'] ?? ''));
        $reviewContent = trim((string)($_POST['review_content'] ?? ''));
        $hasSpoilers = isset($_POST['has_spoilers']);
        
        if ($rating >= 1 && $rating <= 5) {
            create_or_update_review($id, (int)$user['id'], $rating, $reviewTitle ?: null, $reviewContent ?: null, $hasSpoilers);
            redirect('/book.php?id=' . $id);
        }
    }
}

$title = (string)$book['title'];
require __DIR__ . '/partials/layout_top.php';

?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="glass p-4">
            <div class="book-cover-preview">
                <?php if ((string)($book['cover_path'] ?? '') !== ''): ?>
                    <?php
                    $cover = (string)$book['cover_path'];
                    $coverSrc = preg_match('~^https?://~i', $cover) ? $cover : url('/' . ltrim($cover, '/'));
                    ?>
                    <img alt="" src="<?= e($coverSrc) ?>">
                <?php else: ?>
                    <div class="no-cover">
                        <i class="bi bi-journal-bookmark"></i>
                        <div class="t">No cover</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-3 d-grid gap-2">
                <?php if ((string)($book['file_path'] ?? '') !== ''): ?>
                    <a class="btn btn-primary btn-lg" href="<?= e(url('/read.php?id=' . (int)$book['id'])) ?>" target="_blank">
                        <i class="bi bi-book me-2"></i>Read Now
                    </a>
                    <a class="btn btn-success" href="<?= e(url('/download.php?id=' . (int)$book['id'])) ?>">
                        <i class="bi bi-download me-2"></i>Download
                    </a>
                <?php else: ?>
                    <button class="btn btn-primary btn-lg" disabled><i class="bi bi-book me-2"></i>Not Available</button>
                <?php endif; ?>
            </div>
            
            <?php if ($user): ?>
            <div class="mt-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-collection me-2"></i>Add to Shelf</h6>
                <form method="post">
                    <input type="hidden" name="action" value="add_to_shelf">
                    <div class="vstack gap-1">
                        <?php 
                        $allShelves = get_or_create_default_shelves((int)$user['id']);
                        $bookShelfIds = array_column($userShelves, 'id');
                        ?>
                        <?php foreach ($allShelves as $shelf): ?>
                            <?php $inShelf = in_array($shelf['id'], $bookShelfIds); ?>
                            <button type="submit" name="shelf_id" value="<?= e($shelf['id']) ?>" 
                                    class="btn btn-sm <?= $inShelf ? 'btn-primary' : 'btn-outline-secondary' ?> text-start">
                                <?php if ($inShelf): ?>
                                    <i class="bi bi-check-circle me-2"></i>
                                <?php else: ?>
                                    <i class="bi bi-plus-circle me-2"></i>
                                <?php endif; ?>
                                <?= e($shelf['name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($readingProgress): ?>
        <div class="glass p-3 mt-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-bar-chart me-2"></i>Your Progress</h6>
            <div class="progress mb-2" style="height:8px">
                <div class="progress-bar bg-success" style="width:<?= e((int)$readingProgress['percentage']) ?>%"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Page <?= e((int)$readingProgress['current_page']) ?> of <?= e((int)$readingProgress['total_pages']) ?></span>
                <span><?= e((int)$readingProgress['percentage']) ?>%</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-8">
        <div class="glass p-4">
            <h1 class="book-detail-title"><?= e((string)$book['title']) ?></h1>
            
            <?php if ((string)($book['author'] ?? '') !== ''): ?>
                <p class="book-author text-muted mb-2">
                    <i class="bi bi-person me-1"></i><?= e((string)$book['author']) ?>
                </p>
            <?php endif; ?>
            
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-primary"><i class="bi bi-file-earmark-text me-1"></i><?= e(strtoupper((string)$book['format'])) ?></span>
                <?php if ((int)($book['is_free'] ?? 0) === 1): ?>
                    <span class="badge bg-success"><i class="bi bi-unlock me-1"></i>Free</span>
                <?php endif; ?>
                <?php if ((string)($book['category_name'] ?? '') !== ''): ?>
                    <a href="<?= e(url('/browse.php?category=' . $book['category_id'])) ?>" class="badge bg-secondary text-decoration-none">
                        <i class="bi bi-tag me-1"></i><?= e((string)$book['category_name']) ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Rating Summary -->
            <?php if ($ratingStats['total'] > 0): ?>
            <div class="rating-summary mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rating-big">
                        <span class="rating-number"><?= e($ratingStats['avg']) ?></span>
                        <span class="rating-out-of">/ 5</span>
                    </div>
                    <div>
                        <?= format_rating_stars($ratingStats['avg']) ?>
                        <div class="small text-muted"><?= e(number_format($ratingStats['total'])) ?> reviews</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="book-stats-row mb-3">
                <span><i class="bi bi-eye"></i> <?= e(number_format((int)$book['views'])) ?> views</span>
                <span><i class="bi bi-download"></i> <?= e(number_format((int)$book['downloads'])) ?> downloads</span>
                <?php if ((int)$book['review_count'] > 0): ?>
                    <span><i class="bi bi-chat-quote"></i> <?= e(number_format((int)$book['review_count'])) ?> reviews</span>
                <?php endif; ?>
            </div>
            
            <?php if ((string)($book['summary'] ?? '') !== ''): ?>
                <div class="book-summary">
                    <h6 class="fw-bold mb-2">Summary</h6>
                    <p class="text-muted mb-0" style="white-space:pre-wrap"><?= e((string)$book['summary']) ?></p>
                </div>
            <?php else: ?>
                <p class="text-muted">No summary available.</p>
            <?php endif; ?>
        </div>
        
        <!-- Reviews Section -->
        <div class="glass p-4 mt-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-chat-quote me-2"></i>Reviews</h5>
            
            <?php if ($user): ?>
            <div class="review-form mb-4">
                <form method="post">
                    <input type="hidden" name="action" value="submit_review">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" 
                                       <?= $userReview && (int)$userReview['rating'] === $i ? 'checked' : '' ?> required>
                                <label for="star<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <input type="text" name="review_title" class="form-control" placeholder="Review title (optional)" 
                               value="<?= e((string)($userReview['title'] ?? '')) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <textarea name="review_content" class="form-control" rows="3" placeholder="Write your review (optional)"><?= e((string)($userReview['content'] ?? '')) ?></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="has_spoilers" class="form-check-input" id="hasSpoilers"
                               <?= $userReview && (int)$userReview['has_spoilers'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="hasSpoilers">This review contains spoilers</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?= $userReview ? 'Update Review' : 'Submit Review' ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (empty($reviews)): ?>
                <p class="text-muted text-center py-3">No reviews yet. Be the first to review!</p>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= e((string)$review['user_name']) ?></strong>
                                    <?= format_rating_stars((float)$review['rating']) ?>
                                </div>
                                <small class="text-muted"><?= e(date('M j, Y', strtotime($review['created_at']))) ?></small>
                            </div>
                            <?php if ((string)($review['title'] ?? '') !== ''): ?>
                                <div class="fw-semibold mt-2"><?= e((string)$review['title']) ?></div>
                            <?php endif; ?>
                            <?php if ((string)($review['content'] ?? '') !== ''): ?>
                                <p class="mb-0 mt-1 <?= (int)$review['has_spoilers'] === 1 ? 'spoiler-content' : '' ?>">
                                    <?= e((string)$review['content']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ((int)$review['helpful_count'] > 0): ?>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-hand-thumbs-up"></i> <?= e((int)$review['helpful_count']) ?> people found this helpful
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($reviews) < $ratingStats['total']): ?>
                    <a href="<?= e(url('/reviews.php?book_id=' . $id)) ?>" class="btn btn-outline-primary btn-sm mt-3">
                        View all <?= e(number_format($ratingStats['total'])) ?> reviews
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
