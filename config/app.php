<?php
/**
 * LipaByte — Application configuration
 */

declare(strict_types=1);

return [
    'name' => 'LipaByte',
    'tagline' => 'Tech rental hub for Lipa & Batangas students',
    'timezone' => 'Asia/Manila',
    'session_name' => 'lipabyte_session',
    'upload_max_images' => 5,
    'upload_max_bytes' => 5 * 1024 * 1024,
    'listings_per_page' => 24,
    'mail_from' => 'noreply@lipabyte.edu.ph',
    'show_reset_link_local' => true,
    'currency_symbol' => '₱',
    'chat_poll_ms' => 3000,
];
