<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$user = current_user();

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'E-Library') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar border-end d-none d-lg-block">
        <div class="p-3 d-flex align-items-center gap-2">
            <div class="brand-mark"></div>
            <div class="fw-semibold">E-Library</div>
        </div>
        <div class="px-3 pb-3">
            <div class="small text-uppercase text-muted mb-2">Menu</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link app-navlink <?= e(nav_active('/index.php')) ?>" href="<?= e(url('/index.php')) ?>"><i class="bi bi-house me-2"></i>Home</a>
                <a class="nav-link app-navlink <?= e(nav_active('/browse.php')) ?>" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-grid me-2"></i>Browse</a>
                <?php if ($user): ?>
                    <a class="nav-link app-navlink <?= e(nav_active('/dashboard.php')) ?>" href="<?= e(url('/dashboard.php')) ?>"><i class="bi bi-book me-2"></i>My Library</a>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <a class="nav-link app-navlink <?= e(nav_active('/admin/index.php')) ?>" href="<?= e(url('/admin/index.php')) ?>"><i class="bi bi-shield-lock me-2"></i>Admin</a>
                <?php endif; ?>
            </nav>

            <div class="small text-uppercase text-muted mt-4 mb-2">Account</div>
            <nav class="nav flex-column gap-1">
                <?php if (!$user): ?>
                    <a class="nav-link app-navlink <?= e(nav_active('/login.php')) ?>" href="<?= e(url('/login.php')) ?>"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
                    <a class="nav-link app-navlink <?= e(nav_active('/register.php')) ?>" href="<?= e(url('/register.php')) ?>"><i class="bi bi-person-plus me-2"></i>Register</a>
                <?php else: ?>
                    <div class="px-3 py-2 small text-muted">Signed in as <span class="text-dark fw-semibold"><?= e($user['name']) ?></span></div>
                    <a class="nav-link app-navlink" href="<?= e(url('/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </aside>

    <div class="offcanvas offcanvas-start app-offcanvas" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2" id="sidebarOffcanvasLabel">
                <div class="brand-mark"></div>
                <div class="fw-semibold">E-Library</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body px-3 pb-3">
            <div class="small text-uppercase text-muted mb-2">Menu</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link app-navlink <?= e(nav_active('/index.php')) ?>" href="<?= e(url('/index.php')) ?>"><i class="bi bi-house me-2"></i>Home</a>
                <a class="nav-link app-navlink <?= e(nav_active('/browse.php')) ?>" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-grid me-2"></i>Browse</a>
                <?php if ($user): ?>
                    <a class="nav-link app-navlink <?= e(nav_active('/dashboard.php')) ?>" href="<?= e(url('/dashboard.php')) ?>"><i class="bi bi-book me-2"></i>My Library</a>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <a class="nav-link app-navlink <?= e(nav_active('/admin/index.php')) ?>" href="<?= e(url('/admin/index.php')) ?>"><i class="bi bi-shield-lock me-2"></i>Admin</a>
                <?php endif; ?>
            </nav>

            <div class="small text-uppercase text-muted mt-4 mb-2">Account</div>
            <nav class="nav flex-column gap-1">
                <?php if (!$user): ?>
                    <a class="nav-link app-navlink <?= e(nav_active('/login.php')) ?>" href="<?= e(url('/login.php')) ?>"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
                    <a class="nav-link app-navlink <?= e(nav_active('/register.php')) ?>" href="<?= e(url('/register.php')) ?>"><i class="bi bi-person-plus me-2"></i>Register</a>
                <?php else: ?>
                    <div class="px-3 py-2 small text-muted">Signed in as <span class="text-dark fw-semibold"><?= e($user['name']) ?></span></div>
                    <a class="nav-link app-navlink" href="<?= e(url('/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <main class="app-main">
        <header class="app-topbar border-bottom">
            <div class="container-fluid py-2">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="flex-grow-1">
                        <form class="d-flex" action="<?= e(url('/browse.php')) ?>" method="get">
                            <input class="form-control app-search" name="q" placeholder="Search books, authors..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
                        </form>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($user): ?>
                            <a class="btn btn-sm btn-success" href="<?= e(url('/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-success" href="<?= e(url('/register.php')) ?>"><i class="bi bi-rocket-takeoff me-1"></i>Get Started</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="container-fluid py-4 app-container">
