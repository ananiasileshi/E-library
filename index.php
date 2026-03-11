<?php

declare(strict_types=1);

$title = 'Home';

$recent = [
    ['title' => 'Steve Jobs', 'author' => 'Walter Isaacson', 'rating' => 4.5, 'cover' => 'https://covers.openlibrary.org/b/id/8231856-L.jpg'],
    ['title' => 'Radical', 'author' => 'David Platt', 'rating' => 4.2, 'cover' => 'https://covers.openlibrary.org/b/id/8235116-L.jpg'],
    ['title' => "Ender\'s Game", 'author' => 'Orson Scott Card', 'rating' => 4.6, 'cover' => 'https://covers.openlibrary.org/b/id/8235081-L.jpg'],
    ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'rating' => 4.7, 'cover' => 'https://covers.openlibrary.org/b/id/6979861-L.jpg'],
    ['title' => 'Holbein', 'author' => 'Norbert Wolf', 'rating' => 4.1, 'cover' => 'https://covers.openlibrary.org/b/id/8244151-L.jpg'],
    ['title' => 'The Coral Island', 'author' => 'R.M. Ballantyne', 'rating' => 3.9, 'cover' => 'https://covers.openlibrary.org/b/id/8231998-L.jpg'],
];

$recommended = [
    ['title' => 'An American Life', 'author' => 'Ronald Reagan', 'rating' => 4.0, 'cover' => 'https://covers.openlibrary.org/b/id/8232140-L.jpg'],
    ['title' => 'Sherlock Holmes', 'author' => 'Arthur Conan Doyle', 'rating' => 4.6, 'cover' => 'https://covers.openlibrary.org/b/id/8232405-L.jpg'],
    ['title' => "The Sound of Things Falling", 'author' => 'Juan Gabriel Vásquez', 'rating' => 4.2, 'cover' => 'https://covers.openlibrary.org/b/id/8235030-L.jpg'],
    ['title' => 'The Fault in Our Stars', 'author' => 'John Green', 'rating' => 4.4, 'cover' => 'https://covers.openlibrary.org/b/id/8231999-L.jpg'],
    ['title' => 'Just My Type', 'author' => 'Simon Garfield', 'rating' => 4.1, 'cover' => 'https://covers.openlibrary.org/b/id/8232795-L.jpg'],
    ['title' => 'Wake', 'author' => 'Lisa McMann', 'rating' => 3.8, 'cover' => 'https://covers.openlibrary.org/b/id/8232874-L.jpg'],
    ['title' => 'Fearless Captain', 'author' => 'A.L. Kline', 'rating' => 3.7, 'cover' => 'https://covers.openlibrary.org/b/id/8231820-L.jpg'],
    ['title' => 'Execute', 'author' => 'S. J. Scott', 'rating' => 3.9, 'cover' => 'https://covers.openlibrary.org/b/id/8231876-L.jpg'],
    ['title' => 'Harry Potter', 'author' => 'J.K. Rowling', 'rating' => 4.8, 'cover' => 'https://covers.openlibrary.org/b/id/7884866-L.jpg'],
    ['title' => 'I Kissed Dating Goodbye', 'author' => 'Joshua Harris', 'rating' => 3.2, 'cover' => 'https://covers.openlibrary.org/b/id/8232147-L.jpg'],
    ['title' => 'White Fang', 'author' => 'Jack London', 'rating' => 4.0, 'cover' => 'https://covers.openlibrary.org/b/id/8231771-L.jpg'],
    ['title' => 'The Harbinger', 'author' => 'Jonathan Cahn', 'rating' => 4.1, 'cover' => 'https://covers.openlibrary.org/b/id/8232947-L.jpg'],
];

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
            <a class="btn btn-light" href="<?= e(url('/browse.php?category=1')) ?>">Fiction</a>
            <a class="btn btn-light" href="<?= e(url('/browse.php?category=2')) ?>">Non-Fiction</a>
            <a class="btn btn-light" href="<?= e(url('/browse.php?category=3')) ?>">Science</a>
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
    <?php foreach ($recent as $b): ?>
        <div class="book-card">
            <div class="book-tile">
                <div class="book-cover">
                    <img alt="" src="<?= e($b['cover']) ?>">
                </div>
                <div class="book-badges">
                    <span class="badge-soft blue">PDF</span>
                    <span class="badge-soft green">Free</span>
                </div>
                <div class="book-overlay"></div>
                <div class="book-actions">
                    <a class="btn btn-sm btn-light w-100" href="<?= e(url('/browse.php?q=' . urlencode((string)$b['title']))) ?>"><i class="bi bi-eye me-1"></i>Details</a>
                    <a class="btn btn-sm btn-success w-100" href="<?= e(url('/browse.php?q=' . urlencode((string)$b['title']))) ?>"><i class="bi bi-book me-1"></i>Read</a>
                </div>
            </div>
            <div class="book-meta">
                <div class="book-title"><?= e($b['title']) ?></div>
                <div class="book-author"><?= e($b['author']) ?></div>
                <div class="mt-1">
                    <span class="stars" aria-label="Rating">
                        <?php $r = (float)$b['rating']; $full = (int)floor($r); $empty = 5 - $full; ?>
                        <?php for ($i = 0; $i < $full; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                        <?php for ($i = 0; $i < $empty; $i++): ?><i class="bi bi-star-fill muted"></i><?php endfor; ?>
                    </span>
                    <span class="small text-muted ms-1"><?= e(number_format((float)$b['rating'], 1)) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
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
    <?php foreach ($recommended as $b): ?>
        <div>
            <div class="book-tile">
                <div class="book-cover">
                    <img alt="" src="<?= e($b['cover']) ?>">
                </div>
                <div class="book-badges">
                    <span class="badge-soft blue">PDF</span>
                </div>
                <div class="book-overlay"></div>
                <div class="book-actions">
                    <a class="btn btn-sm btn-light w-100" href="<?= e(url('/browse.php?q=' . urlencode((string)$b['title']))) ?>"><i class="bi bi-eye me-1"></i>Details</a>
                    <a class="btn btn-sm btn-success w-100" href="<?= e(url('/browse.php?q=' . urlencode((string)$b['title']))) ?>"><i class="bi bi-book me-1"></i>Read</a>
                </div>
            </div>
            <div class="book-meta">
                <div class="book-title"><?= e($b['title']) ?></div>
                <div class="book-author"><?= e($b['author']) ?></div>
                <div class="mt-1">
                    <span class="stars" aria-label="Rating">
                        <?php $r = (float)$b['rating']; $full = (int)floor($r); $empty = 5 - $full; ?>
                        <?php for ($i = 0; $i < $full; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                        <?php for ($i = 0; $i < $empty; $i++): ?><i class="bi bi-star-fill muted"></i><?php endfor; ?>
                    </span>
                    <span class="small text-muted ms-1"><?= e(number_format((float)$b['rating'], 1)) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php

require __DIR__ . '/partials/layout_bottom.php';
