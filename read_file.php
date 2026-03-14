<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit;
}

$stmt = db()->prepare('SELECT id, title, file_path, format, status FROM books WHERE id = ? AND status = "active" LIMIT 1');
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book || (string)($book['file_path'] ?? '') === '') {
    http_response_code(404);
    exit;
}

$format = (string)($book['format'] ?? 'pdf');
$filePath = (string)$book['file_path'];

if ($format === 'epub') {
    http_response_code(404);
    exit;
}

if (preg_match('~^https?://~i', $filePath)) {
    http_response_code(404);
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
    exit;
}

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    http_response_code(404);
    exit;
}

$filename = preg_replace('~[^a-zA-Z0-9._-]+~', '_', (string)$book['title']);
if ($filename === '') {
    $filename = 'book';
}

$viewName = $filename . '.bin';

// Use application/octet-stream to prevent IDM from intercepting
header('Content-Type: application/octet-stream');
header('Content-Disposition: inline; filename="' . $viewName . '"');
header('Content-Length: ' . (string)filesize($abs));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($abs);
exit;
