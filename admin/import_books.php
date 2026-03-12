<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

http_response_code(404);
$title = 'Not found';
require __DIR__ . '/../partials/layout_top.php';
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="h5 mb-1 section-title">Page not available</div>
        <div class="text-muted">This feature has been removed.</div>
        <div class="mt-3">
            <a class="btn btn-light" href="<?= e(url('/admin/books.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to books</a>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/../partials/layout_bottom.php';
