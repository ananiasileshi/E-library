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
    <script>window.APP_BASE_PATH = <?= json_encode(base_path()) ?>;</script>
</head>
<body>
<svg style="position:absolute;width:0;height:0" aria-hidden="true" focusable="false">
    <filter id="liquid-distort">
        <feTurbulence type="fractalNoise" baseFrequency="0.02" numOctaves="3" result="noise" />
        <feDisplacementMap in="SourceGraphic" in2="noise" scale="10" xChannelSelector="R" yChannelSelector="G" />
    </filter>
</svg>
<div class="app-shell">
    <header class="app-header">
        <div class="app-header-inner">
            <a class="app-brand" href="<?= e(url('/index.php')) ?>" aria-label="Library home">
                <span class="app-text-logo" aria-hidden="true">
                    <span class="l1">L</span><span class="l2">I</span><span class="l3">B</span><span class="l4">R</span><span class="l5">A</span><span class="l6">R</span><span class="l7">Y</span>
                </span>
            </a>

            <nav class="app-nav d-none d-md-flex">
                <a class="app-nav-link <?= e(nav_active('/index.php')) ?>" href="<?= e(url('/index.php')) ?>"><i class="bi bi-house"></i><span>Home</span></a>
                <a class="app-nav-link <?= e(nav_active('/browse.php')) ?>" href="<?= e(url('/browse.php')) ?>"><i class="bi bi-grid"></i><span>Browse</span></a>
                <?php if ($user): ?>
                    <a class="app-nav-link <?= e(nav_active('/dashboard.php')) ?>" href="<?= e(url('/dashboard.php')) ?>"><i class="bi bi-book"></i><span>My Library</span></a>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <a class="app-nav-link <?= e(nav_active('/admin/index.php')) ?>" href="<?= e(url('/admin/index.php')) ?>"><i class="bi bi-shield-lock"></i><span>Admin</span></a>
                <?php endif; ?>
            </nav>

            <div class="app-header-right">
                <form class="app-search-form d-none d-md-flex" action="<?= e(url('/browse.php')) ?>" method="get">
                    <input class="form-control app-search-input" name="q" placeholder="Search books..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
                </form>

                <?php if ($user): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i><?= e($user['name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e(url('/dashboard.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= e(url('/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/login.php')) ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                    <a class="btn btn-sm btn-success" href="<?= e(url('/register.php')) ?>"><i class="bi bi-rocket-takeoff me-1"></i>Sign Up</a>
                <?php endif; ?>

                <button class="btn btn-sm btn-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-end app-offcanvas" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
        <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2" id="mobileNavLabel">
                <span class="app-text-logo" aria-hidden="true">
                    <span class="l1">L</span><span class="l2">I</span><span class="l3">B</span><span class="l4">R</span><span class="l5">A</span><span class="l6">R</span><span class="l7">Y</span>
                </span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form class="mb-3" action="<?= e(url('/browse.php')) ?>" method="get">
                <input class="form-control" name="q" placeholder="Search books..." value="<?= e((string)($_GET['q'] ?? '')) ?>">
            </form>
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
            <hr>
            <?php if ($user): ?>
                <div class="px-2 small text-muted mb-2">Signed in as <strong><?= e($user['name']) ?></strong></div>
                <a class="nav-link app-navlink text-danger" href="<?= e(url('/logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            <?php else: ?>
                <a class="nav-link app-navlink" href="<?= e(url('/login.php')) ?>"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
                <a class="nav-link app-navlink" href="<?= e(url('/register.php')) ?>"><i class="bi bi-person-plus me-2"></i>Register</a>
            <?php endif; ?>
        </div>
    </div>

    <main class="app-main">
        <div class="container-fluid py-4 app-container">
