<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$params = $_GET;
unset($params['page']);
$query = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== null));
redirect('home.php' . ($query ? '?' . $query : ''));
