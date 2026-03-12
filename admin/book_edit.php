<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

require_admin();

function fetch_openlibrary_cover_url(string $title): string
{
    $title = trim($title);
    if ($title === '') {
        return '';
    }

    $url = 'https://openlibrary.org/search.json?title=' . rawurlencode($title) . '&limit=1';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 3,
            'header' => "User-Agent: E-Library/1.0\r\n",
        ],
        'https' => [
            'timeout' => 3,
            'header' => "User-Agent: E-Library/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if (!is_string($json) || $json === '') {
        return '';
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return '';
    }
    $docs = $data['docs'] ?? null;
    if (!is_array($docs) || !$docs) {
        return '';
    }
    $first = $docs[0] ?? null;
    if (!is_array($first)) {
        return '';
    }
    $coverId = $first['cover_i'] ?? null;
    if (!is_int($coverId) && !is_string($coverId)) {
        return '';
    }

    $coverId = (string)$coverId;
    if ($coverId === '') {
        return '';
    }

    return 'https://covers.openlibrary.org/b/id/' . rawurlencode($coverId) . '-L.jpg';
}

function try_extract_epub_cover(string $epubAbsPath, string $coversDir, string $baseName): string
{
    if (!class_exists('ZipArchive')) {
        return '';
    }

    $zip = new ZipArchive();
    if ($zip->open($epubAbsPath) !== true) {
        return '';
    }

    $containerXml = $zip->getFromName('META-INF/container.xml');
    if (!is_string($containerXml) || $containerXml === '') {
        $zip->close();
        return '';
    }

    $container = @simplexml_load_string($containerXml);
    if ($container === false) {
        $zip->close();
        return '';
    }

    $opfPath = (string)($container->rootfiles->rootfile['full-path'] ?? '');
    if ($opfPath === '') {
        $zip->close();
        return '';
    }

    $opfXml = $zip->getFromName($opfPath);
    if (!is_string($opfXml) || $opfXml === '') {
        $zip->close();
        return '';
    }

    $opf = @simplexml_load_string($opfXml);
    if ($opf === false) {
        $zip->close();
        return '';
    }

    $opf->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
    $opf->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

    $coverId = '';
    $metaNodes = $opf->xpath('//opf:metadata/opf:meta');
    if (is_array($metaNodes)) {
        foreach ($metaNodes as $meta) {
            $name = (string)($meta['name'] ?? '');
            if ($name === 'cover') {
                $coverId = (string)($meta['content'] ?? '');
                break;
            }
        }
    }

    $coverHref = '';
    if ($coverId !== '') {
        $itemNodes = $opf->xpath('//opf:manifest/opf:item');
        if (is_array($itemNodes)) {
            foreach ($itemNodes as $item) {
                $id = (string)($item['id'] ?? '');
                if ($id === $coverId) {
                    $coverHref = (string)($item['href'] ?? '');
                    break;
                }
            }
        }
    }

    if ($coverHref === '') {
        $itemNodes = $opf->xpath('//opf:manifest/opf:item');
        if (is_array($itemNodes)) {
            foreach ($itemNodes as $item) {
                $media = strtolower((string)($item['media-type'] ?? ''));
                $props = strtolower((string)($item['properties'] ?? ''));
                if ($media === 'image/jpeg' || $media === 'image/png' || $media === 'image/webp') {
                    if (str_contains($props, 'cover-image')) {
                        $coverHref = (string)($item['href'] ?? '');
                        break;
                    }
                }
            }
        }
    }

    if ($coverHref === '') {
        $zip->close();
        return '';
    }

    $opfDir = str_replace('\\', '/', dirname($opfPath));
    if ($opfDir === '.' || $opfDir === '/') {
        $opfDir = '';
    }
    $coverEntry = ($opfDir !== '' ? ($opfDir . '/') : '') . ltrim($coverHref, '/');
    $coverData = $zip->getFromName($coverEntry);
    if (!is_string($coverData) || $coverData === '') {
        $zip->close();
        return '';
    }

    $ext = strtolower(pathinfo($coverEntry, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $ext = 'jpg';
    }
    $destName = $baseName . '-' . date('YmdHis') . '.' . $ext;
    $destAbs = rtrim($coversDir, '/\\') . '/' . $destName;
    $zip->close();

    if (@file_put_contents($destAbs, $coverData) === false) {
        return '';
    }

    return 'uploads/covers/' . $destName;
}

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

    $uploadedBookAbs = '';
    $uploadedBookExt = '';

    if (!isset($errors['form'])) {
        $uploadRoot = __DIR__ . '/../uploads';
        if (!is_dir($uploadRoot)) {
            if (!mkdir($uploadRoot, 0775, true) && !is_dir($uploadRoot)) {
                $errors['form'] = 'Upload folder is not writable. Create /uploads and ensure Apache can write to it.';
            }
        }

        if (isset($_FILES['cover_upload']) && is_array($_FILES['cover_upload']) && (int)($_FILES['cover_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ((int)$_FILES['cover_upload']['error'] !== UPLOAD_ERR_OK) {
                $errors['cover_upload'] = 'Cover upload failed.';
            } else {
                $coverTmp = (string)$_FILES['cover_upload']['tmp_name'];
                $coverName = (string)$_FILES['cover_upload']['name'];
                $coverSize = (int)($_FILES['cover_upload']['size'] ?? 0);
                $coverExt = strtolower(pathinfo($coverName, PATHINFO_EXTENSION));
                if ($coverSize > 5 * 1024 * 1024) {
                    $errors['cover_upload'] = 'Cover must be 5MB or less.';
                }
                if (!in_array($coverExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $errors['cover_upload'] = 'Cover must be JPG, PNG, or WEBP.';
                } else {
                    $coversDir = $uploadRoot . '/covers';
                    if (!is_dir($coversDir)) {
                        if (!mkdir($coversDir, 0775, true) && !is_dir($coversDir)) {
                            $errors['cover_upload'] = 'Cover upload folder is not writable.';
                        }
                    }
                    $safeBase = slugify($titleValue);
                    if ($safeBase === '') {
                        $safeBase = 'cover';
                    }
                    $destName = $safeBase . '-' . date('YmdHis') . '.' . $coverExt;
                    $destAbs = $coversDir . '/' . $destName;
                    if (!isset($errors['cover_upload']) && !move_uploaded_file($coverTmp, $destAbs)) {
                        $errors['cover_upload'] = 'Could not save cover file.';
                    } else {
                        $coverPath = 'uploads/covers/' . $destName;
                    }
                }
            }
        }

        if (isset($_FILES['book_upload']) && is_array($_FILES['book_upload']) && (int)($_FILES['book_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ((int)$_FILES['book_upload']['error'] !== UPLOAD_ERR_OK) {
                $errors['book_upload'] = 'Book file upload failed.';
            } else {
                $bookTmp = (string)$_FILES['book_upload']['tmp_name'];
                $bookName = (string)$_FILES['book_upload']['name'];
                $bookSize = (int)($_FILES['book_upload']['size'] ?? 0);
                $bookExt = strtolower(pathinfo($bookName, PATHINFO_EXTENSION));
                if ($bookSize > 50 * 1024 * 1024) {
                    $errors['book_upload'] = 'Book file must be 50MB or less.';
                }
                if (!in_array($bookExt, ['pdf', 'epub'], true)) {
                    $errors['book_upload'] = 'Book file must be PDF or EPUB.';
                } else {
                    $booksDir = $uploadRoot . '/books';
                    if (!is_dir($booksDir)) {
                        if (!mkdir($booksDir, 0775, true) && !is_dir($booksDir)) {
                            $errors['book_upload'] = 'Book upload folder is not writable.';
                        }
                    }
                    $safeBase = slugify($titleValue);
                    if ($safeBase === '') {
                        $safeBase = 'book';
                    }
                    $destName = $safeBase . '-' . date('YmdHis') . '.' . $bookExt;
                    $destAbs = $booksDir . '/' . $destName;
                    if (!isset($errors['book_upload']) && !move_uploaded_file($bookTmp, $destAbs)) {
                        $errors['book_upload'] = 'Could not save book file.';
                    } else {
                        $filePath = 'uploads/books/' . $destName;
                        $format = $bookExt;
                        $uploadedBookAbs = $destAbs;
                        $uploadedBookExt = $bookExt;
                    }
                }
            }
        }
    }

    if ($coverPath === '' && !isset($errors['cover_upload']) && !isset($errors['book_upload']) && !isset($errors['form'])) {
        $uploadRoot = __DIR__ . '/../uploads';
        $coversDir = $uploadRoot . '/covers';
        if (!is_dir($coversDir)) {
            if (!mkdir($coversDir, 0775, true) && !is_dir($coversDir)) {
                $errors['cover_upload'] = 'Cover upload folder is not writable.';
            }
        }

        if (!isset($errors['cover_upload'])) {
            $baseName = slugify($titleValue);
            if ($baseName === '') {
                $baseName = 'cover';
            }

            if ($uploadedBookAbs !== '' && $uploadedBookExt === 'epub') {
                $autoCover = try_extract_epub_cover($uploadedBookAbs, $coversDir, $baseName);
                if ($autoCover !== '') {
                    $coverPath = $autoCover;
                }
            }
        }

        if ($coverPath === '' && !isset($errors['cover_upload'])) {
            $auto = fetch_openlibrary_cover_url($titleValue);
            if ($auto !== '') {
                $coverPath = $auto;
            }
        }
    }

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

                <form method="post" enctype="multipart/form-data" action="<?= e(url('/admin/book_edit.php' . ($id > 0 ? ('?id=' . $id) : ''))) ?>" class="vstack gap-3">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="_csrf_ajax" value="<?= e(csrf_token()) ?>">

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
                            <div class="mt-2 app-cover-preview" data-cover-preview>
                                <div class="small text-muted mb-1">Cover preview</div>
                                <div class="app-cover-preview-box">
                                    <?php
                                    $coverPreview = '';
                                    if ($coverPath !== '') {
                                        $coverPreview = preg_match('~^https?://~i', $coverPath) ? $coverPath : url('/' . ltrim($coverPath, '/'));
                                    }
                                    ?>
                                    <img alt="" data-cover-preview-img src="<?= e($coverPreview) ?>" style="<?= $coverPreview !== '' ? '' : 'display:none' ?>">
                                    <div class="text-muted small" data-cover-preview-empty style="<?= $coverPreview === '' ? '' : 'display:none' ?>">No cover yet</div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Or upload cover (JPG/PNG/WEBP)</label>
                                <div class="d-flex gap-2">
                                    <input class="form-control <?= isset($errors['cover_upload']) ? 'is-invalid' : '' ?>" type="file" name="cover_upload" data-cover-input accept="image/jpeg,image/png,image/webp">
                                    <button class="btn btn-light" type="button" data-clear-cover>Cancel</button>
                                </div>
                                <?php if (isset($errors['cover_upload'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['cover_upload']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">File Path (URL or /relative/path)</label>
                            <input class="form-control" name="file_path" value="<?= e($filePath) ?>" placeholder="files/book.pdf">
                            <div class="mt-2">
                                <label class="form-label">Or upload book (PDF/EPUB)</label>
                                <div class="d-flex gap-2">
                                    <input class="form-control <?= isset($errors['book_upload']) ? 'is-invalid' : '' ?>" type="file" name="book_upload" data-book-input accept="application/pdf,application/epub+zip,.pdf,.epub">
                                    <button class="btn btn-light" type="button" data-clear-book>Cancel</button>
                                </div>
                                <?php if (isset($errors['book_upload'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['book_upload']) ?></div>
                                <?php endif; ?>
                            </div>
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
