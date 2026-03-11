<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
$errors = [];

$name = '';
$parentId = 0;

if ($id > 0) {
    $stmt = db()->prepare('SELECT id, name, parent_id FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    if (!$cat) {
        redirect('/admin/categories.php');
    }

    $name = (string)$cat['name'];
    $parentId = (int)($cat['parent_id'] ?? 0);
}

$allStmt = db()->query('SELECT id, name FROM categories ORDER BY name ASC');
$allCategories = $allStmt->fetchAll() ?: [];

if (is_post()) {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $errors['form'] = 'Invalid session token. Please refresh and try again.';
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $parentId = (int)($_POST['parent_id'] ?? 0);

    if ($name === '' || mb_strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    }

    if ($parentId === $id) {
        $errors['parent_id'] = 'A category cannot be its own parent.';
    }

    $slug = slugify($name);
    if ($slug === '') {
        $errors['name'] = 'Name is required.';
    }

    if (!$errors) {
        $baseSlug = $slug;
        $i = 0;
        while (true) {
            $check = db()->prepare('SELECT id FROM categories WHERE slug = ? ' . ($id > 0 ? 'AND id <> ?' : '') . ' LIMIT 1');
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
            $stmt = db()->prepare('UPDATE categories SET name = ?, slug = ?, parent_id = ? WHERE id = ?');
            $stmt->execute([$name, $slug, $parentId > 0 ? $parentId : null, $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)');
            $stmt->execute([$name, $slug, $parentId > 0 ? $parentId : null]);
        }

        redirect('/admin/categories.php');
    }
}

$title = $id > 0 ? 'Edit Category' : 'New Category';
require __DIR__ . '/../partials/layout_top.php';

?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <div class="h4 mb-1 section-title"><?= e($title) ?></div>
        <div class="text-muted">Category details</div>
    </div>
    <div>
        <a class="btn btn-light" href="<?= e(url('/admin/categories.php')) ?>">Back</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/admin/category_edit.php' . ($id > 0 ? ('?id=' . $id) : ''))) ?>" class="vstack gap-3">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                    <div>
                        <label class="form-label">Name</label>
                        <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= e($name) ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Parent</label>
                        <select class="form-select <?= isset($errors['parent_id']) ? 'is-invalid' : '' ?>" name="parent_id">
                            <option value="0">None</option>
                            <?php foreach ($allCategories as $c): ?>
                                <?php if ((int)$c['id'] === $id) { continue; } ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $parentId ? 'selected' : '' ?>><?= e((string)$c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['parent_id'])): ?>
                            <div class="invalid-feedback"><?= e($errors['parent_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-success"><?= $id > 0 ? 'Save changes' : 'Create category' ?></button>
                        <a class="btn btn-light" href="<?= e(url('/admin/categories.php')) ?>">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/../partials/layout_bottom.php';
