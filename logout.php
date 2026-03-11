<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';

unset($_SESSION['user_id']);

redirect('/login.php');
