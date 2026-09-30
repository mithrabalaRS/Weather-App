<?php
require_once __DIR__ . '/../config/db.php';

$errors = [];
$success = '';
$selectedRole = 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $roleInput = $_POST['role'] ?? 'user';

    if (is_string($roleInput) && in_array($roleInput, ['user', 'admin'], true)) {
        $selectedRole = $roleInput;
    } else {
        $errors[] = 'Choose a valid account type.';
    }

    if ($selectedRole === 'admin') {
        if (($_SESSION['role'] ?? null) !== 'admin') {
            $errors[] = 'Only an existing administrator can create an admin account.';
        }
    }

    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = 'Username must be between 3 and 30 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscore.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
        $stmt->execute(['username' => $username, 'email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
            $insert->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $selectedRole,
            ]);

            $success = 'Registration successful. Please login.';
            $username = '';
            $email = '';
            $selectedRole = 'user';
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
            <p class="auth-eyebrow">A NEW DAY, A NEW VIEW</p>
            <h1>Find your kind<br>of <span>forecast.</span></h1>
            <p>Make a little space for the places and weather you care about.</p>
        </div>
    </section>
    <section class="auth-panel">
        <div class="auth-form-wrap">
            <p class="auth-eyebrow">GET STARTED</p>
            <h2>Make it yours.</h2>
            <p class="auth-intro">Create an account to save your favorite places.</p>
        <?php if ($success): ?>
            <div class="auth-alert success" role="status"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="auth-alert error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="register.php">
            <fieldset class="auth-role-fieldset">
                <legend>Account type</legend>
                <label class="auth-role-option">
                    <input type="radio" name="role" value="user" <?= $selectedRole === 'user' ? 'checked' : '' ?>>
                    <span>User</span>
                </label>
                <label class="auth-role-option">
                    <input type="radio" name="role" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                    <span>Admin</span>
                </label>
            </fieldset>
            <small class="auth-field-note">Admin accounts can only be created by an existing admin.</small>
            <div class="auth-field">
                <label for="register-username">Username</label>
                <input id="register-username" type="text" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($username ?? '') ?>" minlength="3" maxlength="30" autocomplete="username" required>
            </div>
            <div class="auth-field">
                <label for="register-email">Email address</label>
                <input id="register-email" type="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($email ?? '') ?>" autocomplete="email" required>
            </div>
            <div class="auth-field">
                <label for="register-password">Password</label>
                <input id="register-password" type="password" name="password" placeholder="At least 6 characters" minlength="6" autocomplete="new-password" required>
            </div>
            <div class="auth-field">
                <label for="register-confirm-password">Confirm password</label>
                <input id="register-confirm-password" type="password" name="confirm_password" placeholder="Enter your password again" minlength="6" autocomplete="new-password" required>
            </div>
            <button class="auth-submit" type="submit">Create account <span aria-hidden="true">&#8594;</span></button>
        </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
