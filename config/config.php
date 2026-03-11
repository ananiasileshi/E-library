<?php

declare(strict_types=1);

return [
    'app' => [
        'base_path' => '/E-library',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'elibrary',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'bcrypt_cost' => 12,
    ],
];
