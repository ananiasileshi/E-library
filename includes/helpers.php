<?php

declare(strict_types=1);

function app_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }
    $config = require __DIR__ . '/../config/config.php';
    return $config;
}

function base_path(): string
{
    $config = app_config();
    $base = (string)($config['app']['base_path'] ?? '');
    $base = rtrim($base, '/');

    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $auto = str_replace('\\', '/', dirname($script));
    if ($auto === '.' || $auto === '/') {
        $auto = '';
    }
    $auto = rtrim($auto, '/');

    if ($base === '') {
        return $auto;
    }

    if ($auto !== '' && $auto !== $base) {
        return $auto;
    }

    return $base;
}

function url(string $path): string
{
    if ($path === '') {
        return base_path() . '/';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return base_path() . $path;
}

function request_path(): string
{
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string)(parse_url($uri, PHP_URL_PATH) ?? '/');
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base));
    } elseif ($base !== '' && $path === $base) {
        $path = '/';
    }
    return $path;
}

function nav_active(string $targetPath): string
{
    $current = request_path();
    if ($targetPath === '/') {
        $ok = ($current === '/' || $current === '/index.php');
        return $ok ? 'active' : '';
    }
    return $current === $targetPath ? 'active' : '';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = mb_strtolower($value);
    $value = preg_replace('~[^\pL\pN]+~u', '-', $value) ?? '';
    $value = trim($value, '-');
    $value = preg_replace('~-{2,}~', '-', $value) ?? '';

    return $value;
}
