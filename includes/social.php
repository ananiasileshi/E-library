<?php

declare(strict_types=1);

/**
 * Reviews and ratings functions
 */

function get_book_reviews(int $bookId, int $limit = 10, int $offset = 0, bool $approvedOnly = true): array
{
    $sql = "
        SELECT r.*, u.name as user_name, u.avatar_path
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        WHERE r.book_id = ?
    ";
    if ($approvedOnly) {
        $sql .= " AND r.is_approved = 1";
    }
    $sql .= " ORDER BY r.helpful_count DESC, r.created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = db()->prepare($sql);
    $stmt->execute([$bookId, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_book_rating_stats(int $bookId): array
{
    $stmt = db()->prepare("
        SELECT 
            COUNT(*) as total,
            AVG(rating) as avg,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one
        FROM reviews WHERE book_id = ? AND is_approved = 1
    ");
    $stmt->execute([$bookId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || (int)$result['total'] === 0) {
        return ['total' => 0, 'avg' => 0, 'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]];
    }
    
    return [
        'total' => (int)$result['total'],
        'avg' => round((float)$result['avg'], 1),
        'distribution' => [
            5 => (int)$result['five'],
            4 => (int)$result['four'],
            3 => (int)$result['three'],
            2 => (int)$result['two'],
            1 => (int)$result['one'],
        ]
    ];
}

function get_user_review(int $bookId, int $userId): ?array
{
    $stmt = db()->prepare("
        SELECT * FROM reviews WHERE book_id = ? AND user_id = ?
    ");
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function create_or_update_review(int $bookId, int $userId, int $rating, ?string $title = null, ?string $content = null, bool $hasSpoilers = false): bool
{
    $stmt = db()->prepare("
        INSERT INTO reviews (user_id, book_id, rating, title, content, has_spoilers)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            title = VALUES(title),
            content = VALUES(content),
            has_spoilers = VALUES(has_spoilers),
            updated_at = NOW()
    ");
    
    $result = $stmt->execute([$userId, $bookId, $rating, $title, $content, $hasSpoilers]);
    
    // Update book's average rating
    if ($result) {
        update_book_rating_stats($bookId);
    }
    
    return $result;
}

function delete_review(int $bookId, int $userId): bool
{
    $stmt = db()->prepare("DELETE FROM reviews WHERE book_id = ? AND user_id = ?");
    $result = $stmt->execute([$bookId, $userId]);
    
    if ($result) {
        update_book_rating_stats($bookId);
    }
    
    return $result;
}

function update_book_rating_stats(int $bookId): void
{
    $stats = get_book_rating_stats($bookId);
    
    $stmt = db()->prepare("
        UPDATE books SET avg_rating = ?, review_count = ? WHERE id = ?
    ");
    $stmt->execute([$stats['avg'], $stats['total'], $bookId]);
}

function vote_review_helpful(int $reviewId, int $userId, bool $isHelpful = true): bool
{
    try {
        $stmt = db()->prepare("
            INSERT INTO review_votes (review_id, user_id, is_helpful)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_helpful = VALUES(is_helpful)
        ");
        $result = $stmt->execute([$reviewId, $userId, $isHelpful]);
        
        if ($result) {
            // Update helpful count
            db()->prepare("
                UPDATE reviews SET helpful_count = (
                    SELECT COUNT(*) FROM review_votes WHERE review_id = ? AND is_helpful = 1
                ) WHERE id = ?
            ")->execute([$reviewId, $reviewId]);
        }
        
        return $result;
    } catch (PDOException $e) {
        return false;
    }
}

function format_rating_stars(float $rating, int $maxStars = 5): string
{
    $html = '<span class="stars">';
    $fullStars = (int)floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="bi bi-star-fill"></i>';
    }
    if ($halfStar) {
        $html .= '<i class="bi bi-star-half"></i>';
    }
    for ($i = $fullStars + ($halfStar ? 1 : 0); $i < $maxStars; $i++) {
        $html .= '<i class="bi bi-star muted"></i>';
    }
    
    $html .= '</span>';
    return $html;
}

/**
 * Social functions
 */

function follow_user(int $followerId, int $followingId): bool
{
    if ($followerId === $followingId) {
        return false;
    }
    
    try {
        $stmt = db()->prepare("
            INSERT IGNORE INTO user_follows (follower_id, following_id)
            VALUES (?, ?)
        ");
        return $stmt->execute([$followerId, $followingId]);
    } catch (PDOException $e) {
        return false;
    }
}

function unfollow_user(int $followerId, int $followingId): bool
{
    $stmt = db()->prepare("
        DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?
    ");
    return $stmt->execute([$followerId, $followingId]);
}

function is_following(int $followerId, int $followingId): bool
{
    $stmt = db()->prepare("
        SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$followerId, $followingId]);
    return $stmt->fetch() !== false;
}

function get_user_followers(int $userId, int $limit = 50): array
{
    $stmt = db()->prepare("
        SELECT u.id, u.name, u.avatar_path
        FROM user_follows uf
        JOIN users u ON u.id = uf.follower_id
        WHERE uf.following_id = ?
        ORDER BY uf.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_user_following(int $userId, int $limit = 50): array
{
    $stmt = db()->prepare("
        SELECT u.id, u.name, u.avatar_path
        FROM user_follows uf
        JOIN users u ON u.id = uf.following_id
        WHERE uf.follower_id = ?
        ORDER BY uf.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_user_stats(int $userId): array
{
    $stmt = db()->prepare("
        SELECT 
            (SELECT COUNT(*) FROM user_follows WHERE following_id = ?) as followers,
            (SELECT COUNT(*) FROM user_follows WHERE follower_id = ?) as following,
            (SELECT COUNT(*) FROM reviews WHERE user_id = ?) as reviews,
            (SELECT COUNT(*) FROM reading_progress WHERE user_id = ? AND percentage >= 100) as books_read
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['followers' => 0, 'following' => 0, 'reviews' => 0, 'books_read' => 0];
}

/**
 * Activity feed
 */

function create_activity(int $userId, string $type, ?int $bookId = null, ?int $targetUserId = null, ?array $data = null): bool
{
    $stmt = db()->prepare("
        INSERT INTO activities (user_id, type, book_id, target_user_id, data)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $type, $bookId, $targetUserId, $data ? json_encode($data) : null]);
}

function get_activity_feed(int $userId, int $limit = 20): array
{
    // Get activities from followed users + own activities
    $stmt = db()->prepare("
        SELECT a.*, u.name as user_name, u.avatar_path,
               b.title as book_title, b.cover_path as book_cover,
               tu.name as target_user_name
        FROM activities a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN books b ON b.id = a.book_id
        LEFT JOIN users tu ON tu.id = a.target_user_id
        WHERE a.user_id = ? 
           OR a.user_id IN (SELECT following_id FROM user_follows WHERE follower_id = ?)
        ORDER BY a.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $userId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
