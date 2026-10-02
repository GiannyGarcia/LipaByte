<?php

declare(strict_types=1);

/**
 * Layout variables:
 * @var string $pageTitle
 * @var string $activeNav  home|list|dashboard|account|admin|messages|inquiries|assistant|about
 * @var bool $hideBottomNav
 * @var bool $authLayout
 * @var bool $marketplaceLayout
 */

$pageTitle = $pageTitle ?? app_config()['name'];
$activeNav = $activeNav ?? '';
$hideBottomNav = $hideBottomNav ?? false;
$authLayout = $authLayout ?? false;
$marketplaceLayout = $marketplaceLayout ?? false;
$user = current_user();
$unreadNotifications = $user ? count_unread_notifications((int) $user['user_id']) : 0;
$unreadMessages = $user ? count_unread_chat_messages((int) $user['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(app_config()['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= asset('img/lipabyte-logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?php if ($marketplaceLayout ?? false): ?>
    <link rel="stylesheet" href="<?= asset('css/marketplace.css') ?>">
    <style>
      /* Critical fallback if external CSS fails to load on host */
      .marketplace-page .listing-grid{display:grid!important;grid-template-columns:repeat(2,1fr);gap:.5rem}
      @media(min-width:768px){.marketplace-page .listing-grid{grid-template-columns:repeat(4,1fr)}}
      @media(min-width:1024px){.marketplace-page .listing-grid{grid-template-columns:repeat(5,1fr)}}
      .marketplace-page .listing-card-image{position:relative;width:100%;height:0;padding-bottom:100%;overflow:hidden;background:#e4e6eb center/cover no-repeat}
      .marketplace-page .listing-card-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;max-width:100%;max-height:100%}
      .marketplace-page .category-pill{display:inline-block;padding:.35rem .75rem;border-radius:999px;background:#f0f2f5;color:#050505!important;text-decoration:none;font-size:.8125rem;font-weight:600;margin-right:.25rem}
      .marketplace-page .category-pills{display:flex;flex-wrap:nowrap;overflow-x:auto;gap:.35rem}
    </style>
    <?php endif; ?>
</head>
<body class="<?= $authLayout ? 'auth-body' : 'app-body' ?>">

<?php if (!$authLayout): ?>
<header class="app-header">
    <div class="container header-inner">
        <a href="<?= url('index.html') ?>" class="brand">
            <img src="<?= asset('img/lipabyte-logo.png') ?>" alt="<?= e(app_config()['name']) ?>" class="brand-logo" width="36" height="36">
            <span class="brand-text"><?= e(app_config()['name']) ?></span>
        </a>

        <div class="header-actions">
            <nav class="desktop-nav">
                <a href="<?= url('home.php') ?>" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Marketplace</a>
                <?php if ($user): ?>
                    <a href="<?= url('list.php') ?>" class="<?= $activeNav === 'list' ? 'active' : '' ?>">List</a>
                    <a href="<?= url('messages.php') ?>" class="<?= $activeNav === 'messages' ? 'active' : '' ?>">Messages</a>
                    <a href="<?= url('inquiries.php') ?>" class="<?= $activeNav === 'inquiries' ? 'active' : '' ?>">Inquiries</a>
                    <a href="<?= url('dashboard.php') ?>" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">My Activity</a>
                    <?php if (is_admin()): ?>
                        <a href="<?= url('admin/index.php') ?>" class="<?= $activeNav === 'admin' ? 'active' : '' ?>">Admin</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a href="<?= url('assistant.html') ?>" class="<?= $activeNav === 'assistant' ? 'active' : '' ?>">Assistant</a>
                <a href="<?= url('about.html') ?>" class="<?= $activeNav === 'about' ? 'active' : '' ?>">About</a>
            </nav>

            <?php if ($user): ?>
                <a href="<?= url('messages.php') ?>" class="header-notify" title="Messages" aria-label="Messages<?= $unreadMessages > 0 ? " ($unreadMessages unread)" : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <?php if ($unreadMessages > 0): ?>
                        <span class="header-notify-badge"><?= $unreadMessages > 9 ? '9+' : $unreadMessages ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('account/notifications.php') ?>" class="header-notify" title="Notifications" aria-label="Notifications<?= $unreadNotifications > 0 ? " ($unreadNotifications unread)" : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    <?php if ($unreadNotifications > 0): ?>
                        <span class="header-notify-badge"><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('account/profile.php') ?>" class="avatar" title="<?= e(user_display_name($user)) ?>">
                    <?= e(user_initials($user)) ?>
                </a>
            <?php else: ?>
                <a href="<?= url('auth/register.php') ?>" class="btn btn-sm btn-outline hide-mobile">Register</a>
                <a href="<?= url('auth/login.php') ?>" class="btn btn-sm btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>

<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success container"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="alert alert-error container"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('warning')): ?>
    <div class="alert alert-warning container"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash('info')): ?>
    <div class="alert alert-info container"><?= e($msg) ?></div>
<?php endif; ?>

<main class="<?= $authLayout ? 'auth-main' : ($marketplaceLayout ? 'app-main app-main-flush' : 'app-main') ?>">
