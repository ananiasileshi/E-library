<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

require_admin();

$deleteId = (int)($_POST['delete_id'] ?? 0);
if (is_post() && $deleteId > 0) {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        redirect('/admin/books.php');
    }

    $stmt = db()->prepare('DELETE FROM books WHERE id = ?');
    $stmt->execute([$deleteId]);

    redirect('/admin/books.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$categoryId = (int)($_GET['category'] ?? 0);

$where = ['1=1'];
$params = [];

if ($categoryId > 0) {
    $where[] = 'b.category_id = ?';
    $params[] = $categoryId;
}

if ($q !== '') {
    $where[] = '(b.title LIKE ? OR b.summary LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$catStmt = db()->query('SELECT id, name FROM categories ORDER BY name ASC');
$categories = $catStmt->fetchAll() ?: [];

$sql = "
    SELECT b.id, b.title, b.slug, b.status, b.format, b.is_free, b.views, b.downloads, b.created_at,
           c.name AS category_name
    FROM books b
    LEFT JOIN categories c ON c.id = b.category_id
    {$whereSql}
    ORDER BY b.created_at DESC
";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll() ?: [];

$title = 'Manage Books';
require __DIR__ . '/../partials/layout_top.php';

?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <div class="h4 mb-1 section-title">Books</div>
        <div class="text-muted">Add and manage books</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-success" href="<?= e(url('/admin/book_edit.php')) ?>"><i class="bi bi-plus-lg me-1"></i>New Book</a>
        <a class="btn btn-light" href="<?= e(url('/admin/index.php')) ?>">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="<?= e(url('/admin/books.php')) ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small text-muted">Search</label>
                <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Title or summary">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Category</label>
                <select class="form-select" name="category">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $categoryId ? 'selected' : '' ?>><?= e((string)$c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button class="btn btn-outline-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!$books): ?>
            <div class="text-muted">No books yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th class="text-end">Stats</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($books as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string)$b['title']) ?></div>
                                <div class="small text-muted"><code><?= e((string)$b['slug']) ?></code></div>
                            </td>
                            <td class="text-muted"><?= e((string)($b['category_name'] ?? '')) ?></td>
                            <td>
                                <span class="badge text-bg-light"><?= e((string)$b['status']) ?></span>
                                <span class="badge text-bg-light"><?= e(strtoupper((string)$b['format'])) ?></span>
                                <?php if ((int)$b['is_free'] === 1): ?><span class="badge text-bg-success">Free</span><?php endif; ?>
                            </td>
                            <td class="text-end small text-muted">
                                <div><i class="bi bi-eye"></i> <?= e((string)((int)($b['views'] ?? 0))) ?></div>
                                <div><i class="bi bi-download"></i> <?= e((string)((int)($b['downloads'] ?? 0))) ?></div>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/book_edit.php?id=' . (int)$b['id'])) ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/book.php?id=' . (int)$b['id'])) ?>" target="_blank">View</a>
                                <form method="post" action="<?= e(url('/admin/books.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this book?');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="delete_id" value="<?= (int)$b['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require __DIR__ . '/../partials/layout_bottom.php';
