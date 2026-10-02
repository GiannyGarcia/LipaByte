<?php
/**
 * LipaByte — Bootstrap
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $appConfig = require __DIR__ . '/../config/app.php';
    session_name($appConfig['session_name']);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

date_default_timezone_set((require __DIR__ . '/../config/app.php')['timezone']);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/listings.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/reviews.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/messages.php';
require_once __DIR__ . '/admin.php';
