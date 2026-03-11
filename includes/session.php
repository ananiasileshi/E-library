<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

function csrf_token(): string
{
    return (string)($_SESSION['_csrf'] ?? '');
}

function csrf_validate(?string $token): bool
{
    $expected = (string)($_SESSION['_csrf'] ?? '');
    if ($expected === '' || $token === null) {
        return false;
    }
    return hash_equals($expected, $token);
}
