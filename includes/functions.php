<?php

declare(strict_types=1);

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }
    return $config;
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(__DIR__ . '/..');

    if ($docRoot && $projectRoot) {
        $docRoot = str_replace('\\', '/', $docRoot);
        $projectRoot = str_replace('\\', '/', $projectRoot);
        if (str_starts_with($projectRoot, $docRoot)) {
            $base = substr($projectRoot, strlen($docRoot));
            return $base === '' ? '' : $base;
        }
    }

    $base = '';
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = base_path();

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    $relative = 'assets/' . ltrim($path, '/');
    $url = url($relative);
    $file = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_readable($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid security token. Please go back and try again.');
    }
}

function verify_csrf_api(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Session expired. Refresh the page and try again.',
        ]);
        exit;
    }
}

function str_preview(string $text, int $length): string
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '…';
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function store_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function format_money(float $amount): string
{
    return app_config()['currency_symbol'] . number_format($amount, 2);
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    return date('M j, Y', strtotime($date));
}

function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    return date('M j, Y g:i A', strtotime($datetime));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper($part[0] ?? '');
    }
    return $letters ?: 'U';
}

function user_display_name(array $user): string
{
    return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

function user_initials(array $user): string
{
    $first = strtoupper($user['first_name'][0] ?? '');
    $last = strtoupper($user['last_name'][0] ?? '');
    $letters = $first . $last;

    return $letters !== '' ? $letters : initials(user_display_name($user));
}

function is_valid_institutional_email(string $email): bool
{
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Local part: letters, numbers, or a combination (e.g. 2120751, juan.mitra).
    // Domain: @{schoolname}.edu.ph (e.g. university.edu.ph, dlsu.edu.ph).
    return (bool) preg_match(
        '/^[a-z0-9][a-z0-9._+-]*@[a-z0-9][a-z0-9-]*\.edu\.ph$/',
        $email
    );
}

function paginate_params(int $page, int $perPage): array
{
    $page = max(1, $page);
    return [$perPage, ($page - 1) * $perPage];
}

function pagination_meta(int $total, int $page, int $perPage): array
{
    $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = min(max(1, $page), $totalPages);

    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'from' => $total === 0 ? 0 : (($page - 1) * $perPage) + 1,
        'to' => min($page * $perPage, $total),
    ];
}

function render_pagination(array $meta, array $queryParams = []): string
{
    if ($meta['total_pages'] <= 1) {
        return '';
    }

    $queryParams = array_filter($queryParams, static fn($v) => $v !== null && $v !== '');
    $page = (int) $meta['page'];
    $totalPages = (int) $meta['total_pages'];

    $html = '<nav class="pagination" aria-label="Marketplace pages"><div class="pagination-inner">';

    if ($page > 1) {
        $html .= '<a class="pagination-link" href="' . e(url('home.php?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])))) . '">← Prev</a>';
    }

    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $page) {
            $html .= '<span class="pagination-link active" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a class="pagination-link" href="' . e(url('home.php?' . http_build_query(array_merge($queryParams, ['page' => $i])))) . '">' . $i . '</a>';
        }
    }

    if ($page < $totalPages) {
        $html .= '<a class="pagination-link" href="' . e(url('home.php?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])))) . '">Next →</a>';
    }

    $html .= '</div></nav>';
    return $html;
}

function status_badge(string $status): string
{
    $map = [
        'Available' => 'badge-success',
        'Pending' => 'badge-warning',
        'Rented' => 'badge-info',
        'Approved' => 'badge-success',
        'Declined' => 'badge-danger',
        'Cancelled' => 'badge-muted',
        'Completed' => 'badge-info',
        'active' => 'badge-success',
        'inactive' => 'badge-muted',
        'verified' => 'badge-success',
        'unverified' => 'badge-warning',
        'admin' => 'badge-info',
        'user' => 'badge-muted',
    ];

    $class = $map[$status] ?? 'badge-muted';
    return '<span class="badge ' . $class . '">' . e($status) . '</span>';
}

function get_campuses(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM campuses';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY university_name, campus_name';
    return db()->query($sql)->fetchAll();
}

function get_campus_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM campuses WHERE campus_id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_user_stats(int $userId): array
{
    $pdo = db();

    $activeRentals = $pdo->prepare(
        "SELECT COUNT(*) FROM rental_requests
         WHERE renter_id = ? AND request_status IN ('Pending', 'Approved')"
    );
    $activeRentals->execute([$userId]);

    $listedItems = $pdo->prepare(
        "SELECT COUNT(*) FROM listings
         WHERE lender_id = ? AND is_deleted = 0"
    );
    $listedItems->execute([$userId]);

    $stmt = $pdo->prepare('SELECT avg_rating FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $rating = (float) ($stmt->fetchColumn() ?: 0);

    return [
        'active_rentals' => (int) $activeRentals->fetchColumn(),
        'listed_items' => (int) $listedItems->fetchColumn(),
        'avg_rating' => $rating,
    ];
}
