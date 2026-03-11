<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

require_admin();

$deleteId = (int)($_POST['delete_id'] ?? 0);
if (is_post() && $deleteId > 0) {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        redirect('/admin/categories.php');
    }

    $stmt = db()->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$deleteId]);

    redirect('/admin/categories.php');
}

$stmt = db()->query('SELECT id, name, slug, parent_id, created_at FROM categories ORDER BY name ASC');
$categories = $stmt->fetchAll() ?: [];

$title = 'Manage Categories';
require __DIR__ . '/../partials/layout_top.php';

?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <div class="h4 mb-1 section-title">Categories</div>
        <div class="text-muted">Create and manage categories</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-success" href="<?= e(url('/admin/category_edit.php')) ?>"><i class="bi bi-plus-lg me-1"></i>New Category</a>
        <a class="btn btn-light" href="<?= e(url('/admin/index.php')) ?>">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (!$categories): ?>
            <div class="text-muted">No categories yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $byId = [];
                    foreach ($categories as $c) {
                        $byId[(int)$c['id']] = (string)$c['name'];
                    }
                    ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string)$c['name']) ?></td>
                            <td><code><?= e((string)$c['slug']) ?></code></td>
                            <td class="text-muted"><?= e($c['parent_id'] ? ($byId[(int)$c['parent_id']] ?? '') : '') ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/category_edit.php?id=' . (int)$c['id'])) ?>">Edit</a>
                                <form method="post" action="<?= e(url('/admin/categories.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
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
