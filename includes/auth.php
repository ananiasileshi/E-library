<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

function current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if ($id === null) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect('/login.php');
    }
}

function require_admin(): void
{
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        redirect('/dashboard.php');
    }
}

function auth_login(int $userId): void
{
    $_SESSION['user_id'] = $userId;
}

function auth_logout(): void
{
    unset($_SESSION['user_id']);
}
