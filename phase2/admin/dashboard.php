<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$stats = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'searches' => (int) $pdo->query('SELECT COUNT(*) FROM weather_searches')->fetchColumn(),
    'favorites' => (int) $pdo->query('SELECT COUNT(*) FROM favorites')->fetchColumn(),
    'signed_in_users' => 0,
    'login_events' => 0,
];
$loginTrackingReady = true;
$recentLogins = [];

try {
    $stats['signed_in_users'] = (int) $pdo->query('SELECT COUNT(DISTINCT user_id) FROM user_logins')->fetchColumn();
    $stats['login_events'] = (int) $pdo->query('SELECT COUNT(*) FROM user_logins')->fetchColumn();
    $recentLogins = $pdo->query('SELECT user_logins.logged_in_at, users.username, users.role FROM user_logins INNER JOIN users ON users.id = user_logins.user_id ORDER BY user_logins.logged_in_at DESC LIMIT 20')->fetchAll();
} catch (PDOException $exception) {
    if (($exception->errorInfo[1] ?? null) !== 1146) {
        throw $exception;
    }
    $loginTrackingReady = false;
}

$users = $pdo->query('SELECT username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
$searches = $pdo->query('SELECT weather_searches.city_name, weather_searches.country, weather_searches.temperature, weather_searches.weather_condition, weather_searches.searched_at, users.username FROM weather_searches INNER JOIN users ON users.id = weather_searches.user_id ORDER BY weather_searches.searched_at DESC LIMIT 25')->fetchAll();
$favorites = $pdo->query('SELECT favorites.city_name, favorites.created_at, users.username FROM favorites INNER JOIN users ON users.id = favorites.user_id ORDER BY favorites.created_at DESC LIMIT 25')->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="dashboard-app">
    <nav class="dashboard-nav">
        <div class="dashboard-nav-inner">
            <a class="dashboard-brand" href="<?= htmlspecialchars(APP_BASE_PATH) ?>/dashboard.php">
                <span class="dashboard-brand-mark" aria-hidden="true">W</span>
                <span>Wewo</span>
            </a>
            <div class="dashboard-links">
                <a class="dashboard-link" href="<?= htmlspecialchars(APP_BASE_PATH) ?>/dashboard.php">User dashboard</a>
                <a class="dashboard-link is-active" href="<?= htmlspecialchars(APP_BASE_PATH) ?>/admin/dashboard.php">Admin</a>
                <a class="dashboard-link dashboard-logout" href="<?= htmlspecialchars(APP_BASE_PATH) ?>/auth/logout.php">Sign out</a>
            </div>
        </div>
    </nav>

    <main class="container dashboard-container admin-container">
        <section class="dashboard-heading">
            <div>
                <p class="dashboard-eyebrow">WEWO ADMINISTRATION</p>
                <h1>Admin portal</h1>
                <p class="dashboard-subtitle">Accounts, sign-ins, and weather activity.</p>
            </div>
            <span class="dashboard-account-role">Admin</span>
        </section>

        <?php if (!$loginTrackingReady): ?>
            <div class="admin-notice" role="alert">Login tracking is not set up yet. Import <code>migrate_user_logins.sql</code> into the <code>weather_phase2</code> database.</div>
        <?php endif; ?>

        <section class="admin-metrics" aria-label="Usage totals">
            <article class="admin-metric"><span>Registered accounts</span><strong><?= number_format($stats['users']) ?></strong></article>
            <article class="admin-metric"><span>Accounts signed in</span><strong><?= number_format($stats['signed_in_users']) ?></strong></article>
            <article class="admin-metric"><span>Successful sign-ins</span><strong><?= number_format($stats['login_events']) ?></strong></article>
            <article class="admin-metric"><span>Weather searches</span><strong><?= number_format($stats['searches']) ?></strong></article>
            <article class="admin-metric"><span>Favorites saved</span><strong><?= number_format($stats['favorites']) ?></strong></article>
        </section>

        <section class="admin-section dashboard-card">
            <div class="dashboard-card-heading">
                <div><p class="dashboard-eyebrow">ACCESS ACTIVITY</p><h2>Recent sign-ins</h2></div>
                <span class="admin-record-count"><?= number_format($stats['login_events']) ?> total</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Account</th><th>Role</th><th>Signed in</th></tr></thead>
                    <tbody>
                        <?php if ($recentLogins): ?>
                            <?php foreach ($recentLogins as $login): ?>
                                <tr><td><?= htmlspecialchars($login['username']) ?></td><td><span class="admin-role <?= htmlspecialchars($login['role']) ?>"><?= htmlspecialchars(ucfirst($login['role'])) ?></span></td><td><?= htmlspecialchars($login['logged_in_at']) ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="admin-empty">No sign-in records yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-section dashboard-card">
            <div class="dashboard-card-heading">
                <div><p class="dashboard-eyebrow">WEATHER ACTIVITY</p><h2>Recent searches</h2></div>
                <span class="admin-record-count"><?= number_format($stats['searches']) ?> total</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Account</th><th>City</th><th>Conditions</th><th>Temperature</th><th>Searched</th></tr></thead>
                    <tbody>
                        <?php if ($searches): ?>
                            <?php foreach ($searches as $search): ?>
                                <tr><td><?= htmlspecialchars($search['username']) ?></td><td><?= htmlspecialchars($search['city_name']) ?><?= !empty($search['country']) ? ', ' . htmlspecialchars($search['country']) : '' ?></td><td><?= htmlspecialchars($search['weather_condition'] ?? 'Not recorded') ?></td><td><?= htmlspecialchars((string) ($search['temperature'] ?? 'Not recorded')) ?>°C</td><td><?= htmlspecialchars($search['searched_at']) ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="admin-empty">No weather searches yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-section dashboard-card">
            <div class="dashboard-card-heading">
                <div><p class="dashboard-eyebrow">SAVED PLACES</p><h2>Favorite cities</h2></div>
                <span class="admin-record-count"><?= number_format($stats['favorites']) ?> total</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Account</th><th>City</th><th>Added</th></tr></thead>
                    <tbody>
                        <?php if ($favorites): ?>
                            <?php foreach ($favorites as $favorite): ?>
                                <tr><td><?= htmlspecialchars($favorite['username']) ?></td><td><?= htmlspecialchars($favorite['city_name']) ?></td><td><?= htmlspecialchars($favorite['created_at']) ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="admin-empty">No favorite cities yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-section dashboard-card">
            <div class="dashboard-card-heading">
                <div><p class="dashboard-eyebrow">MEMBERS</p><h2>Registered accounts</h2></div>
                <span class="admin-record-count"><?= number_format($stats['users']) ?> total</span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr><td><?= htmlspecialchars($user['username']) ?></td><td><?= htmlspecialchars($user['email']) ?></td><td><span class="admin-role <?= htmlspecialchars($user['role']) ?>"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></td><td><?= htmlspecialchars($user['created_at']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
