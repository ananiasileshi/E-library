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

if ($ext === 'pdf') {
    $title = 'Read: ' . (string)$book['title'];
    
    // Read PDF file and encode as base64 to bypass IDM
    $pdfData = base64_encode(file_get_contents($abs));
    
    require __DIR__ . '/partials/layout_top.php';
    ?>
    <div class="reader-shell">
        <div class="reader-topbar">
            <div class="reader-title"><?= e((string)$book['title']) ?></div>
            <div class="reader-actions">
                <button class="btn btn-sm btn-light" id="zoomOut" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                <span class="reader-zoom-level" id="zoomLevel">100%</span>
                <button class="btn btn-sm btn-light" id="zoomIn" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                <button class="btn btn-sm btn-outline-primary" id="fitPage" title="Fit to Page"><i class="bi bi-arrows-fullscreen"></i></button>
                <a class="btn btn-sm btn-light" href="<?= e(url('/book.php?id=' . $id)) ?>"><i class="bi bi-info-circle me-1"></i>Details</a>
            </div>
        </div>

        <div class="reader-stage" id="readerStage">
            <div class="reader-book" id="flipBook" aria-label="Book pages">
                <div class="pdf-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2">Loading PDF...</div>
                </div>
            </div>
        </div>
        
        <div class="reader-nav">
            <button class="btn btn-light" type="button" id="flipPrev" aria-label="Previous page"><i class="bi bi-chevron-left"></i> Prev</button>
            <div class="reader-page" id="flipPage">1 / 1</div>
            <button class="btn btn-light" type="button" id="flipNext" aria-label="Next page">Next <i class="bi bi-chevron-right"></i></button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        (function () {
            // PDF data embedded directly - no HTTP request
            const pdfBase64 = "<?= $pdfData ?>";
            const pdfData = atob(pdfBase64);
            const pdfBytes = new Uint8Array(pdfData.length);
            for (let i = 0; i < pdfData.length; i++) {
                pdfBytes[i] = pdfData.charCodeAt(i);
            }

            const container = document.getElementById('flipBook');
            const stage = document.getElementById('readerStage');
            const prevBtn = document.getElementById('flipPrev');
            const nextBtn = document.getElementById('flipNext');
            const pageEl = document.getElementById('flipPage');
            const zoomInBtn = document.getElementById('zoomIn');
            const zoomOutBtn = document.getElementById('zoomOut');
            const zoomLevelEl = document.getElementById('zoomLevel');
            const fitPageBtn = document.getElementById('fitPage');

            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            let pdfDoc = null;
            let currentPage = 1;
            let currentScale = 1;
            let baseScale = 1;
            const pageCache = {};
            const zoomStep = 0.15;

            function updateZoomDisplay() {
                zoomLevelEl.textContent = Math.round(currentScale / baseScale * 100) + '%';
            }

            function getFitScale() {
                if (!pdfDoc) return 1;
                
                // Get stage dimensions
                const stageWidth = stage.clientWidth - 40;
                const stageHeight = stage.clientHeight - 40;
                
                // Get page dimensions at scale 1
                return pdfDoc.getPage(currentPage).then(function (page) {
                    const viewport = page.getViewport({ scale: 1 });
                    
                    // Calculate scale to fit both width and height
                    const scaleW = stageWidth / viewport.width;
                    const scaleH = stageHeight / viewport.height;
                    
                    // Use smaller scale to ensure page fits entirely
                    return Math.min(scaleW, scaleH);
                });
            }

            function renderPage(num, scale) {
                return new Promise(function (resolve, reject) {
                    pdfDoc.getPage(num).then(function (page) {
                        const scaledViewport = page.getViewport({ scale: scale });

                        const canvas = document.createElement('canvas');
                        canvas.width = Math.floor(scaledViewport.width);
                        canvas.height = Math.floor(scaledViewport.height);

                        const ctx = canvas.getContext('2d', { alpha: false });
                        
                        page.render({
                            canvasContext: ctx,
                            viewport: scaledViewport
                        }).promise.then(function () {
                            resolve({ canvas, width: scaledViewport.width, height: scaledViewport.height });
                        }).catch(reject);
                    }).catch(reject);
                });
            }

            function displayPage(num, scale) {
                setHint('Loading page ' + num + '...');
                
                renderPage(num, scale).then(function (result) {
                    container.innerHTML = '';
                    
                    const wrapper = document.createElement('div');
                    wrapper.className = 'pdf-page-wrapper';
                    
                    result.canvas.style.maxWidth = '100%';
                    result.canvas.style.height = 'auto';
                    
                    wrapper.appendChild(result.canvas);
                    container.appendChild(wrapper);

                    if (pageEl) pageEl.textContent = num + ' / ' + pdfDoc.numPages;
                    setHint('');
                }).catch(function (err) {
                    console.error('Error rendering page:', err);
                    setHint('Error loading page');
                });
            }

            function showPage(num) {
                if (!pdfDoc || num < 1 || num > pdfDoc.numPages) return;
                currentPage = num;
                displayPage(num, currentScale);
            }

            function prevPage() {
                if (currentPage > 1) showPage(currentPage - 1);
            }

            function nextPage() {
                if (currentPage < pdfDoc.numPages) showPage(currentPage + 1);
            }

            function zoomIn() {
                currentScale = Math.min(currentScale + baseScale * zoomStep, baseScale * 3);
                updateZoomDisplay();
                displayPage(currentPage, currentScale);
            }

            function zoomOut() {
                currentScale = Math.max(currentScale - baseScale * zoomStep, baseScale * 0.5);
                updateZoomDisplay();
                displayPage(currentPage, currentScale);
            }

            function fitToPage() {
                getFitScale().then(function (scale) {
                    baseScale = scale;
                    currentScale = scale;
                    updateZoomDisplay();
                    displayPage(currentPage, currentScale);
                });
            }

            function setHint(v) {
                const hint = document.querySelector('.pdf-loading');
                if (hint) hint.querySelector('div:last-child').textContent = v;
            }

            // Load PDF from embedded data
            pdfjsLib.getDocument({ data: pdfBytes }).promise.then(function (pdf) {
                pdfDoc = pdf;
                
                // Calculate initial fit scale
                getFitScale().then(function (scale) {
                    baseScale = scale;
                    currentScale = scale;
                    updateZoomDisplay();
                    showPage(1);
                });
            }).catch(function (err) {
                console.error('PDF load error:', err);
                container.innerHTML = '<div class="alert alert-danger m-3">Could not load PDF.</div>';
            });

            // Event listeners
            if (prevBtn) prevBtn.addEventListener('click', prevPage);
            if (nextBtn) nextBtn.addEventListener('click', nextPage);
            if (zoomInBtn) zoomInBtn.addEventListener('click', zoomIn);
            if (zoomOutBtn) zoomOutBtn.addEventListener('click', zoomOut);
            if (fitPageBtn) fitPageBtn.addEventListener('click', fitToPage);

            window.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') prevPage();
                if (e.key === 'ArrowRight') nextPage();
                if (e.key === '+' || e.key === '=') zoomIn();
                if (e.key === '-') zoomOut();
            });

            // Recalculate on window resize
            let resizeTimeout;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(fitToPage, 250);
            });
        })();
    </script>
    <?php
    require __DIR__ . '/partials/layout_bottom.php';
    exit;
}

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
