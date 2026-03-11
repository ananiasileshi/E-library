<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$user = current_user();

$title = 'My Library';
require __DIR__ . '/partials/layout_top.php';

?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <div class="h4 mb-1 section-title">My Library</div>
        <div class="text-muted">Your reading in one place</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="fw-semibold">Profile</div>
                <div class="small text-muted mt-2">Name</div>
                <div><?= e((string)($user['name'] ?? '')) ?></div>
                <div class="small text-muted mt-3">Email</div>
                <div><?= e((string)($user['email'] ?? '')) ?></div>
                <div class="small text-muted mt-3">Role</div>
                <div><?= e((string)($user['role'] ?? '')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="fw-semibold mb-2">Coming next</div>
                <div class="text-muted">Borrowed books, reading history, bookmarks, lists, and downloads will appear here once we connect the full schema.</div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
