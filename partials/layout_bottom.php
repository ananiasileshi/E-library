        </div>

        <footer class="border-top app-footer">
            <div class="container-fluid py-4">
                <div class="row g-4">
                    <div class="col-12 col-lg-5">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="brand-mark"></div>
                            <div class="fw-semibold">E-Library</div>
                        </div>
                        <div class="text-muted small app-footer-text">Discover, read, and download ebooks with a clean experience for members and admins.</div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="fw-semibold small mb-2">Quick links</div>
                        <div class="vstack gap-1 small">
                            <a class="app-footer-link" href="<?= e(url('/index.php')) ?>">Home</a>
                            <a class="app-footer-link" href="<?= e(url('/browse.php')) ?>">Browse</a>
                            <a class="app-footer-link" href="<?= e(url('/dashboard.php')) ?>">Dashboard</a>
                            <a class="app-footer-link" href="<?= e(url('/admin/index.php')) ?>">Admin</a>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="fw-semibold small mb-2">Contact</div>
                        <div class="vstack gap-1 small">
                            <div class="text-muted">Email: <a class="app-footer-link" href="mailto:support@example.com">support@example.com</a></div>
                            <div class="text-muted">Phone: <a class="app-footer-link" href="tel:+0000000000">+0000000000</a></div>
                            <div class="d-flex gap-2 mt-2">
                                <a class="app-footer-social" href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                <a class="app-footer-social" href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                                <a class="app-footer-social" href="#" aria-label="GitHub"><i class="bi bi-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-footer-bottom">
                <div class="container-fluid py-2 small d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="text-muted">&copy; <?= date('Y') ?> E-Library</div>
                    <div class="d-flex gap-3">
                        <a class="app-footer-link" href="#">Privacy</a>
                        <a class="app-footer-link" href="#">Terms</a>
                    </div>
                </div>
            </div>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('/assets/js/app.js')) ?>"></script>
</body>
</html>
