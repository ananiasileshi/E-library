<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_validate($_POST['_csrf'] ?? null)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad CSRF token']);
    exit;
}

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

    if (!is_dir($coversDir)) {
        if (!mkdir($coversDir, 0775, true) && !is_dir($coversDir)) {
            $zip->close();
            return '';
        }
    }

    $destName = $baseName . '-' . date('YmdHis') . '.' . $ext;
    $destAbs = rtrim($coversDir, '/\\') . '/' . $destName;
    $zip->close();

    if (@file_put_contents($destAbs, $coverData) === false) {
        return '';
    }

    return 'uploads/covers/' . $destName;
}

function try_extract_pdf_cover(string $pdfAbsPath, string $coversDir, string $baseName): string
{
    if (!class_exists('Imagick')) {
        return '';
    }

    if (!is_dir($coversDir)) {
        if (!mkdir($coversDir, 0775, true) && !is_dir($coversDir)) {
            return '';
        }
    }

    try {
        $img = new Imagick();
        $img->setResolution(150, 150);
        $img->readImage($pdfAbsPath . '[0]');
        $img->setImageFormat('jpeg');
        $img->setImageCompressionQuality(82);
        if (method_exists($img, 'thumbnailImage')) {
            $img->thumbnailImage(900, 0);
        }

        $destName = $baseName . '-' . date('YmdHis') . '.jpg';
        $destAbs = rtrim($coversDir, '/\\') . '/' . $destName;
        $img->writeImage($destAbs);
        $img->clear();
        $img->destroy();

        return 'uploads/covers/' . $destName;
    } catch (Throwable $e) {
        return '';
    }
}

$title = trim((string)($_POST['title'] ?? ''));

$coverPath = '';
$coverUrl = '';

if (isset($_FILES['book_upload']) && is_array($_FILES['book_upload']) && (int)($_FILES['book_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $tmp = (string)$_FILES['book_upload']['tmp_name'];
    $name = (string)$_FILES['book_upload']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($ext === 'epub') {
        $coversDir = __DIR__ . '/../uploads/covers';
        $baseName = slugify($title);
        if ($baseName === '') {
            $baseName = 'cover';
        }

        $coverPath = try_extract_epub_cover($tmp, $coversDir, $baseName);
    } elseif ($ext === 'pdf') {
        $coversDir = __DIR__ . '/../uploads/covers';
        $baseName = slugify($title);
        if ($baseName === '') {
            $baseName = 'cover';
        }

        $coverPath = try_extract_pdf_cover($tmp, $coversDir, $baseName);
    }
}

if ($coverPath !== '') {
    $coverUrl = url('/' . ltrim($coverPath, '/'));
} else {
    $ol = fetch_openlibrary_cover_url($title);
    if ($ol !== '') {
        $coverUrl = $ol;
    }
}

echo json_encode([
    'ok' => true,
    'cover_path' => $coverPath,
    'cover_url' => $coverUrl,
]);
