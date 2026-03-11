<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
$errors = [];

$titleValue = '';
$summary = '';
$coverPath = '';
$filePath = '';
$format = 'pdf';
$isFree = 1;
$status = 'active';
$categoryId = 0;

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM books WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if (!$book) {
        redirect('/admin/books.php');
    }

    $titleValue = (string)$book['title'];
    $summary = (string)($book['summary'] ?? '');
    $coverPath = (string)($book['cover_path'] ?? '');
    $filePath = (string)($book['file_path'] ?? '');
    $format = (string)($book['format'] ?? 'pdf');
    $isFree = (int)($book['is_free'] ?? 1);
    $status = (string)($book['status'] ?? 'active');
    $categoryId = (int)($book['category_id'] ?? 0);
}

$catStmt = db()->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $catStmt->fetchAll() ?: [];

if (is_post()) {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $errors['form'] = 'Invalid session token. Please refresh and try again.';
    }

    $titleValue = trim((string)($_POST['title'] ?? ''));
    $summary = trim((string)($_POST['summary'] ?? ''));
    $coverPath = trim((string)($_POST['cover_path'] ?? ''));
    $filePath = trim((string)($_POST['file_path'] ?? ''));
    $format = (string)($_POST['format'] ?? 'pdf');
    $isFree = (int)($_POST['is_free'] ?? 0);
    $status = (string)($_POST['status'] ?? 'active');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if ($titleValue === '' || mb_strlen($titleValue) < 2) {
        $errors['title'] = 'Title must be at least 2 characters.';
    }

    if (!in_array($format, ['pdf', 'epub'], true)) {
        $errors['format'] = 'Invalid format.';
    }

    if (!in_array($status, ['active', 'draft', 'archived'], true)) {
        $errors['status'] = 'Invalid status.';
    }

    if ($categoryId < 0) {
        $errors['category_id'] = 'Invalid category.';
    }

    $slug = slugify($titleValue);
    if ($slug === '') {
        $errors['title'] = 'Title is required.';
    }

    if (!$errors) {
        $baseSlug = $slug;
        $i = 0;
        while (true) {
            $check = db()->prepare('SELECT id FROM books WHERE slug = ? ' . ($id > 0 ? 'AND id <> ?' : '') . ' LIMIT 1');
            $params = [$slug];
            if ($id > 0) {
                $params[] = $id;
            }
            $check->execute($params);
            if (!$check->fetch()) {
                break;
            }
            $i++;
            $slug = $baseSlug . '-' . $i;
        }

        if ($id > 0) {
            $stmt = db()->prepare('UPDATE books SET title = ?, slug = ?, summary = ?, cover_path = ?, file_path = ?, format = ?, is_free = ?, status = ?, category_id = ? WHERE id = ?');
            $stmt->execute([
                $titleValue,
                $slug,
                $summary !== '' ? $summary : null,
                $coverPath !== '' ? $coverPath : null,
                $filePath !== '' ? $filePath : null,
                $format,
                $isFree ? 1 : 0,
                $status,
                $categoryId > 0 ? $categoryId : null,
                $id,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO books (title, slug, summary, cover_path, file_path, format, is_free, status, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $titleValue,
                $slug,
                $summary !== '' ? $summary : null,
                $coverPath !== '' ? $coverPath : null,
                $filePath !== '' ? $filePath : null,
                $format,
                $isFree ? 1 : 0,
                $status,
                $categoryId > 0 ? $categoryId : null,
            ]);
        }

        redirect('/admin/books.php');
    }
}

$pageTitle = $id > 0 ? 'Edit Book' : 'New Book';
$title = $pageTitle;
require __DIR__ . '/../partials/layout_top.php';

?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <div class="h4 mb-1 section-title"><?= e($pageTitle) ?></div>
        <div class="text-muted">Book details</div>
    </div>
    <div>
        <a class="btn btn-light" href="<?= e(url('/admin/books.php')) ?>">Back</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/admin/book_edit.php' . ($id > 0 ? ('?id=' . $id) : ''))) ?>" class="vstack gap-3">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                    <div>
                        <label class="form-label">Title</label>
                        <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" name="title" value="<?= e($titleValue) ?>" required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Summary</label>
                        <textarea class="form-control" name="summary" rows="5"><?= e($summary) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Cover Path (URL or /relative/path)</label>
                            <input class="form-control" name="cover_path" value="<?= e($coverPath) ?>" placeholder="https://... or assets/covers/cover.jpg">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">File Path (URL or /relative/path)</label>
                            <input class="form-control" name="file_path" value="<?= e($filePath) ?>" placeholder="files/book.pdf">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Format</label>
                            <select class="form-select <?= isset($errors['format']) ? 'is-invalid' : '' ?>" name="format">
                                <option value="pdf" <?= $format === 'pdf' ? 'selected' : '' ?>>PDF</option>
                                <option value="epub" <?= $format === 'epub' ? 'selected' : '' ?>>EPUB</option>
                            </select>
                            <?php if (isset($errors['format'])): ?>
                                <div class="invalid-feedback"><?= e($errors['format']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" name="status">
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?= e($errors['status']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" name="category_id">
                                <option value="0">None</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $categoryId ? 'selected' : '' ?>><?= e((string)$c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_free" value="1" id="isFree" <?= $isFree ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isFree">Free to download</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-success"><?= $id > 0 ? 'Save changes' : 'Create book' ?></button>
                        <a class="btn btn-light" href="<?= e(url('/admin/books.php')) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../partials/layout_bottom.php';
