<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/browse.php');
}

$stmt = db()->prepare('SELECT id, title, file_path, format FROM books WHERE id = ? AND status = "active" LIMIT 1');
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book || (string)($book['file_path'] ?? '') === '') {
    redirect('/book.php?id=' . $id);
}

$filePath = (string)$book['file_path'];

// Allow external URLs by redirecting.
if (preg_match('~^https?://~i', $filePath)) {
    db()->prepare('UPDATE books SET downloads = downloads + 1 WHERE id = ?')->execute([$id]);
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
    redirect('/book.php?id=' . $id);
}

db()->prepare('UPDATE books SET downloads = downloads + 1 WHERE id = ?')->execute([$id]);

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$filename = preg_replace('~[^a-zA-Z0-9._-]+~', '_', (string)$book['title']);
if ($filename === '') {
    $filename = 'book';
}

$downloadName = $filename . ($ext ? ('.' . $ext) : '');

$mime = 'application/octet-stream';
if ($ext === 'pdf') {
    $mime = 'application/pdf';
} elseif ($ext === 'epub') {
    $mime = 'application/epub+zip';
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . (string)filesize($abs));
header('X-Content-Type-Options: nosniff');

readfile($abs);
exit;
