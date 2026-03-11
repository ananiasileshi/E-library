<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/config/db.php';

$config = require __DIR__ . '/config/config.php';

$errors = [];
$name = '';
$email = '';

if (is_post()) {
    if (!csrf_validate($_POST['_csrf'] ?? null)) {
        $errors['form'] = 'Invalid session token. Please refresh and try again.';
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password_confirm'] ?? '');

    if ($name === '' || mb_strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (mb_strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($password !== $password2) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email is already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => (int)$config['security']['bcrypt_cost']]);

        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, \"member\", \"active\")');
        $stmt->execute([$name, $email, $hash]);

        $_SESSION['user_id'] = (int)db()->lastInsertId();
        redirect('/dashboard.php');
    }
}

$title = 'Register';
require __DIR__ . '/partials/layout_top.php';

?>
<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="h4 mb-1">Create account</div>
                <div class="text-muted mb-4">Join the library and start reading</div>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/register.php')) ?>" class="vstack gap-3">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                    <div>
                        <label class="form-label">Name</label>
                        <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= e($name) ?>" autocomplete="name" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Password</label>
                        <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" type="password" name="password" autocomplete="new-password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Confirm Password</label>
                        <input class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" type="password" name="password_confirm" autocomplete="new-password" required>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-success">Create account</button>
                    <div class="small text-muted">Already have an account? <a href="<?= e(url('/login.php')) ?>">Login</a></div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/partials/layout_bottom.php';
