        </div>

        <footer class="border-top app-footer d-none d-lg-block">
            <div class="container-fluid py-4">
                <div class="row g-4">
                    <div class="col-12 col-lg-4">
                        <a class="app-brand" href="<?= e(url('/index.php')) ?>">
                            <span class="app-text-logo" aria-hidden="true">
                                <span class="l1">L</span><span class="l2">I</span><span class="l3">B</span><span class="l4">R</span><span class="l5">A</span><span class="l6">R</span><span class="l7">Y</span>
                            </span>
                        </a>
                        <div class="text-muted small app-footer-text mt-2">Discover, read, and download ebooks with a clean experience for members and admins.</div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <div class="fw-semibold small mb-2">Quick links</div>
                        <div class="vstack gap-1 small">
                            <a class="app-footer-link" href="<?= e(url('/index.php')) ?>">Home</a>
                            <a class="app-footer-link" href="<?= e(url('/browse.php')) ?>">Browse</a>
                            <a class="app-footer-link" href="<?= e(url('/dashboard.php')) ?>">Dashboard</a>
                            <a class="app-footer-link" href="<?= e(url('/admin/index.php')) ?>">Admin</a>
                        </div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <div class="fw-semibold small mb-2">Contact</div>
                        <div class="vstack gap-1 small">
                            <div class="text-muted">Email: <a class="app-footer-link" href="mailto:support@example.com">support@example.com</a></div>
                            <div class="d-flex gap-2 mt-2">
                                <a class="app-footer-social" href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                <a class="app-footer-social" href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                                <a class="app-footer-social" href="#" aria-label="GitHub"><i class="bi bi-github"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="newsletter-box">
                            <div class="fw-semibold mb-1"><i class="bi bi-envelope me-2"></i>Sign up for more from our Library</div>
                            <p class="text-muted small mb-2">Receive our newsletter, free Story of the Week, and more!</p>
                            <form class="newsletter-form" action="#" method="post">
                                <div class="input-group">
                                    <input type="email" name="email" class="form-control" placeholder="name@email.com" required>
                                    <button class="btn btn-primary" type="submit">Sign Up</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-footer-bottom">
                <div class="container-fluid py-2 small d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="text-muted">&copy; <?= date('Y') ?> E-Library</div>
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3">
                        <a class="app-footer-link" href="#">Terms of Use</a>
                        <a class="app-footer-link" href="#">Privacy Statement</a>
                        <a class="app-footer-link" href="#">Accessibility Statement</a>
                        <a class="app-footer-link" href="#">City of AddisAbaba Website</a>
                    </div>
                </div>
            </div>
        </footer>
    </main>

    <!-- Mobile Bottom Tab Bar -->
    <nav class="mobile-tab-bar d-lg-none">
        <a class="mobile-tab-item <?= nav_active('/index.php') ? 'active' : '' ?>" href="<?= e(url('/index.php')) ?>">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        <a class="mobile-tab-item <?= nav_active('/browse.php') ? 'active' : '' ?>" href="<?= e(url('/browse.php')) ?>">
            <i class="bi bi-grid"></i>
            <span>Browse</span>
        </a>
        <?php if ($user): ?>
        <a class="mobile-tab-item <?= nav_active('/dashboard.php') ? 'active' : '' ?>" href="<?= e(url('/dashboard.php')) ?>">
            <i class="bi bi-book"></i>
            <span>Library</span>
        </a>
        <?php else: ?>
        <a class="mobile-tab-item" href="<?= e(url('/login.php')) ?>">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Login</span>
        </a>
        <?php endif; ?>
        <?php if ($user): ?>
        <a class="mobile-tab-item" href="<?= e(url('/profile.php')) ?>">
            <i class="bi bi-person"></i>
            <span>Profile</span>
        </a>
        <?php endif; ?>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('/assets/js/app.js')) ?>"></script>
</body>
</html>
