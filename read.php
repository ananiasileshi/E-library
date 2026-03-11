<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/browse.php');
}

$stmt = db()->prepare('SELECT id, title, file_path, format, status FROM books WHERE id = ? AND status = "active" LIMIT 1');
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book || (string)($book['file_path'] ?? '') === '') {
    http_response_code(404);
    $title = 'Cannot read book';
    require __DIR__ . '/partials/layout_top.php';
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="h5 mb-1 section-title">Reading unavailable</div>
            <div class="text-muted">This book either does not exist, is not active, or does not have a file attached.</div>
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a class="btn btn-light" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to browse</a>
                <a class="btn btn-outline-primary" href="<?= e(url('/book.php?id=' . $id)) ?>">Open details</a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/layout_bottom.php';
    exit;
}

$format = (string)($book['format'] ?? 'pdf');
$filePath = (string)$book['file_path'];

// EPUB isn't typically readable inline without a reader; fallback to download.
if ($format === 'epub') {
    redirect('/download.php?id=' . $id);
}

// Allow external URLs by redirecting.
if (preg_match('~^https?://~i', $filePath)) {
    header('Location: ' . $filePath);
    exit;
}

$root = realpath(__DIR__);
$requested = $filePath;
if ($requested !== '' && $requested[0] === '/') {
    $requested = ltrim($requested, '/');
}

$abs = realpath(__DIR__ . DIRECTORY_SEPARATOR . $requested);
if ($abs === false || $root === false || strpos($abs, $root) !== 0 || !is_file($abs)) {
    http_response_code(404);
    $title = 'File not found';
    require __DIR__ . '/partials/layout_top.php';
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="h5 mb-1 section-title">File not found</div>
            <div class="text-muted">The file path saved for this book could not be found on the server.</div>
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a class="btn btn-light" href="<?= e(url('/book.php?id=' . $id)) ?>"><i class="bi bi-arrow-left me-1"></i>Back to details</a>
                <a class="btn btn-outline-primary" href="<?= e(url('/admin/book_edit.php?id=' . $id)) ?>">Fix file path (admin)</a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/layout_bottom.php';
    exit;
}

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$filename = preg_replace('~[^a-zA-Z0-9._-]+~', '_', (string)$book['title']);
if ($filename === '') {
    $filename = 'book';
}

$viewName = $filename . ($ext ? ('.' . $ext) : '');

$mime = 'application/octet-stream';
if ($ext === 'pdf') {
    $mime = 'application/pdf';
}

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $viewName . '"');
header('Content-Length: ' . (string)filesize($abs));
header('X-Content-Type-Options: nosniff');

readfile($abs);
exit;
