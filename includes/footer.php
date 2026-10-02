</main>

<?php if (!$authLayout && !$hideBottomNav): ?>
<nav class="bottom-nav" aria-label="Main navigation">
    <a href="<?= url('home.php') ?>" class="bottom-nav-item <?= $activeNav === 'home' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9.5z"/><rect x="9" y="14" width="6" height="7"/></svg>
        <span>Marketplace</span>
    </a>
    <?php if ($user): ?>
        <a href="<?= url('list.php') ?>" class="bottom-nav-item <?= $activeNav === 'list' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            <span>List</span>
        </a>
        <a href="<?= url('messages.php') ?>" class="bottom-nav-item <?= $activeNav === 'messages' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>Messages</span>
        </a>
        <a href="<?= url('dashboard.php') ?>" class="bottom-nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            <span>Activity</span>
        </a>
        <a href="<?= url('account/profile.php') ?>" class="bottom-nav-item <?= $activeNav === 'account' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 6.5-4 8-4s6.5 0 8 4"/></svg>
            <span>Account</span>
        </a>
    <?php else: ?>
        <a href="<?= url('auth/login.php') ?>" class="bottom-nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            <span>Login</span>
        </a>
        <a href="<?= url('auth/register.php') ?>" class="bottom-nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            <span>Register</span>
        </a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/ragbot-config.js') ?>"></script>
<script src="<?= asset('js/ragbot-widget.js') ?>"></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <?php if (empty($script['src'])) { continue; } ?>
        <script src="<?= e((string) $script['src']) ?>"<?= !empty($script['type']) ? ' type="' . e((string) $script['type']) . '"' : '' ?>></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
