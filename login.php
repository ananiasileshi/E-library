<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$errors = [];
$email = '';

if (is_post()) {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $errors['form'] = 'Invalid session token. Please refresh and try again.';
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Please enter your password.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $ok = $user && password_verify($password, (string)$user['password_hash']);

        if (!$ok) {
            $errors['form'] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors['form'] = 'Your account is disabled. Contact an admin.';
        } else {
            $_SESSION['user_id'] = (int)$user['id'];
            if ($user['role'] === 'admin') {
                redirect('/admin/index.php');
            }
            redirect('/dashboard.php');
        }
    }
}

$title = 'Login';
require __DIR__ . '/partials/layout_top.php';

?>
<div class="container-fluid py-4 app-container centered">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="glass p-4">
                <div class="text-center mb-4">
                    <div class="h3 mb-1 fw-bold">Welcome back</div>
                    <div class="text-muted">Sign in to continue</div>
                </div>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/login.php')) ?>" class="vstack gap-3">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                    <div>
                        <label class="form-label fw-semibold">Email</label>
                        <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" placeholder="you@example.com" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Password</label>
                        <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-success w-100 rounded-pill py-2 fw-bold">Login</button>
                    <div class="text-center small text-muted">No account yet? <a href="<?= e(url('/register.php')) ?>">Register</a></div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
