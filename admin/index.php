<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

require_admin();

$totalBooks = (int)(db()->query('SELECT COUNT(*) AS c FROM books')->fetch()['c'] ?? 0);
$totalUsers = (int)(db()->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'] ?? 0);
$totalDownloads = (int)(db()->query('SELECT COALESCE(SUM(downloads),0) AS s FROM books')->fetch()['s'] ?? 0);

$title = 'Admin Dashboard';
require __DIR__ . '/../partials/layout_top.php';

?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="h4 mb-1 section-title">Admin Dashboard</div>
        <div class="text-muted">Manage books, categories, users</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-success" href="<?= e(url('/admin/book_edit.php')) ?>"><i class="bi bi-plus-lg me-1"></i>New Book</a>
        <a class="btn btn-light" href="<?= e(url('/admin/books.php')) ?>">Books</a>
        <a class="btn btn-light" href="<?= e(url('/admin/categories.php')) ?>">Categories</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">Total Books</div>
                <div class="h4 mb-0"><?= e((string)$totalBooks) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">Users</div>
                <div class="h4 mb-0"><?= e((string)$totalUsers) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">Active Borrows</div>
                <div class="h4 mb-0">0</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="small text-muted">Downloads</div>
                <div class="h4 mb-0"><?= e((string)$totalDownloads) ?></div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../partials/layout_bottom.php';
