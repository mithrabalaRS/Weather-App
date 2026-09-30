<?php
require_once __DIR__ . '/../config/db.php';

$errors = [];
$selectedRole = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleInput = $_POST['role'] ?? 'user';

    if (is_string($roleInput) && in_array($roleInput, ['user', 'admin'], true)) {
        $selectedRole = $roleInput;
    } else {
        $errors[] = 'Choose a valid account type.';
    }

    if (empty($username)) {
        $errors[] = 'Username is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] !== $selectedRole) {
                $errors[] = 'This account does not match the selected account type.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                try {
                    $loginStmt = $pdo->prepare('INSERT INTO user_logins (user_id) VALUES (:user_id)');
                    $loginStmt->execute(['user_id' => $user['id']]);
                } catch (PDOException $exception) {
                    if (($exception->errorInfo[1] ?? null) !== 1146) {
                        throw $exception;
                    }
                    error_log('Wewo login tracking is unavailable; import migrate_user_logins.sql.');
                }

                header('Location: ' . APP_BASE_PATH . '/dashboard.php');
                exit;
            }
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="auth-screen">
    <section class="auth-visual" aria-label="Wewo">
        <img class="auth-photo" src="https://images.unsplash.com/photo-1534088568595-a066f410bcda?auto=format&amp;fit=crop&amp;w=1800&amp;q=85" alt="">
        <div class="auth-brand"><span class="auth-brand-mark" aria-hidden="true">W</span><span>Wewo</span></div>
        <div class="auth-message">
            <p class="auth-eyebrow">YOUR DAY, IN VIEW</p>
            <h1>Step outside<br><span>with confidence</span></h1>
            <p>A fresh perspective on the weather, wherever the day takes you.</p>
        </div>
    </section>
    <section class="auth-panel">
        <div class="auth-form-wrap">
            <p class="auth-eyebrow">WELCOME BACK</p>
            <h2>Good to see you.</h2>
            <p class="auth-intro">Sign in to pick up where you left off.</p>
            <?php if ($errors): ?>
                <div class="auth-alert error" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="login.php">
                <fieldset class="auth-role-fieldset">
                    <legend>Sign in as</legend>
                    <label class="auth-role-option">
                        <input type="radio" name="role" value="user" <?= $selectedRole === 'user' ? 'checked' : '' ?>>
                        <span>User</span>
                    </label>
                    <label class="auth-role-option">
                        <input type="radio" name="role" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                        <span>Admin</span>
                    </label>
                </fieldset>
                <div class="auth-field">
                    <label for="login-username">Username</label>
                    <input id="login-username" type="text" name="username" placeholder="Your username" autocomplete="username" required>
                </div>
                <div class="auth-field">
                    <label for="login-password">Password</label>
                    <input id="login-password" type="password" name="password" placeholder="Your password" autocomplete="current-password" required>
                </div>
                <button class="auth-submit" type="submit">Sign in <span aria-hidden="true">&#8594;</span></button>
            </form>
            <p class="auth-switch">New here? <a href="register.php">Create an account</a></p>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
