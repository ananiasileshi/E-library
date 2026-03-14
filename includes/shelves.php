<?php

declare(strict_types=1);

/**
 * Shelf management functions
 */

function get_user_shelves(int $userId): array
{
    static $cache = [];
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    
    $stmt = db()->prepare("
        SELECT s.*, COUNT(bs.book_id) as book_count
        FROM shelves s
        LEFT JOIN book_shelf bs ON bs.shelf_id = s.id
        WHERE s.user_id = ?
        GROUP BY s.id
        ORDER BY s.is_default DESC, s.position ASC, s.created_at ASC
    ");
    $stmt->execute([$userId]);
    $cache[$userId] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    return $cache[$userId];
}

function get_or_create_default_shelves(int $userId): array
{
    $shelves = get_user_shelves($userId);
    
    if (empty($shelves)) {
        // Create default shelves
        $defaults = [
            ['name' => 'Want to Read', 'slug' => 'want-to-read'],
            ['name' => 'Currently Reading', 'slug' => 'currently-reading'],
            ['name' => 'Read', 'slug' => 'read'],
            ['name' => 'Favorites', 'slug' => 'favorites'],
        ];
        
        foreach ($defaults as $i => $def) {
            $stmt = db()->prepare("
                INSERT INTO shelves (user_id, name, slug, is_default, position)
                VALUES (?, ?, ?, 1, ?)
            ");
            $stmt->execute([$userId, $def['name'], $def['slug'], $i]);
        }
        
        // Clear cache and refetch
        $shelves = get_user_shelves($userId);
    }
    
    return $shelves;
}

function add_book_to_shelf(int $bookId, int $shelfId): bool
{
    try {
        $stmt = db()->prepare("
            INSERT IGNORE INTO book_shelf (book_id, shelf_id)
            VALUES (?, ?)
        ");
        return $stmt->execute([$bookId, $shelfId]);
    } catch (PDOException $e) {
        return false;
    }
}

function remove_book_from_shelf(int $bookId, int $shelfId): bool
{
    $stmt = db()->prepare("
        DELETE FROM book_shelf WHERE book_id = ? AND shelf_id = ?
    ");
    return $stmt->execute([$bookId, $shelfId]);
}

function get_book_shelves(int $bookId, int $userId): array
{
    $stmt = db()->prepare("
        SELECT s.* FROM shelves s
        JOIN book_shelf bs ON bs.shelf_id = s.id
        WHERE bs.book_id = ? AND s.user_id = ?
    ");
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function create_custom_shelf(int $userId, string $name): ?int
{
    $slug = slugify($name);
    if ($slug === '') {
        return null;
    }
    
    try {
        $stmt = db()->prepare("
            INSERT INTO shelves (user_id, name, slug, is_default, position)
            VALUES (?, ?, ?, 0, 999)
        ");
        $stmt->execute([$userId, $name, $slug]);
        return (int)db()->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}

function delete_shelf(int $shelfId, int $userId): bool
{
    // Don't delete default shelves
    $stmt = db()->prepare("SELECT is_default FROM shelves WHERE id = ? AND user_id = ?");
    $stmt->execute([$shelfId, $userId]);
    $shelf = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shelf || (int)$shelf['is_default'] === 1) {
        return false;
    }
    
    $stmt = db()->prepare("DELETE FROM shelves WHERE id = ? AND user_id = ?");
    return $stmt->execute([$shelfId, $userId]);
}

/**
 * Reading progress functions
 */

function get_reading_progress(int $bookId, int $userId): ?array
{
    $stmt = db()->prepare("
        SELECT * FROM reading_progress WHERE book_id = ? AND user_id = ?
    ");
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function update_reading_progress(int $bookId, int $userId, int $currentPage, int $totalPages): bool
{
    $percentage = $totalPages > 0 ? min(100, (int)round(($currentPage / $totalPages) * 100)) : 0;
    
    $stmt = db()->prepare("
        INSERT INTO reading_progress (user_id, book_id, current_page, total_pages, percentage, last_read_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            current_page = VALUES(current_page),
            total_pages = VALUES(total_pages),
            percentage = VALUES(percentage),
            last_read_at = NOW()
    ");
    
    return $stmt->execute([$userId, $bookId, $currentPage, $totalPages, $percentage]);
}

function get_user_reading_books(int $userId): array
{
    $stmt = db()->prepare("
        SELECT rp.*, b.title, b.slug, b.cover_path, b.author, b.format,
               s.id as shelf_id
        FROM reading_progress rp
        JOIN books b ON b.id = rp.book_id
        LEFT JOIN shelves s ON s.user_id = rp.user_id AND s.slug = 'currently-reading'
        LEFT JOIN book_shelf bs ON bs.book_id = b.id AND bs.shelf_id = s.id
        WHERE rp.user_id = ? AND rp.percentage < 100
        ORDER BY rp.last_read_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function get_user_finished_books(int $userId): array
{
    $stmt = db()->prepare("
        SELECT rp.*, b.title, b.slug, b.cover_path, b.author, b.format
        FROM reading_progress rp
        JOIN books b ON b.id = rp.book_id
        WHERE rp.user_id = ? AND rp.percentage >= 100
        ORDER BY rp.updated_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Highlights functions
 */

function add_highlight(int $bookId, int $userId, string $content, ?string $note = null, ?int $pageNumber = null, string $color = 'yellow'): int
{
    $stmt = db()->prepare("
        INSERT INTO highlights (user_id, book_id, content, note, page_number, color)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $bookId, $content, $note, $pageNumber, $color]);
    return (int)db()->lastInsertId();
}

function get_book_highlights(int $bookId, int $userId, bool $publicOnly = false): array
{
    $sql = "SELECT * FROM highlights WHERE book_id = ? AND user_id = ?";
    if ($publicOnly) {
        $sql .= " AND is_public = 1";
    }
    $sql .= " ORDER BY page_number ASC, created_at DESC";
    
    $stmt = db()->prepare($sql);
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function delete_highlight(int $highlightId, int $userId): bool
{
    $stmt = db()->prepare("DELETE FROM highlights WHERE id = ? AND user_id = ?");
    return $stmt->execute([$highlightId, $userId]);
}
