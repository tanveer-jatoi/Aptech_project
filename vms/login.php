<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: login.php
 * Description: Unified login (Parent, Hospital, Admin)
 * ----------------------------------------------------------------------------
 * Changes in this version
 *  - Rate limit is per IP + email (plus a looser per-IP cap), so users behind
 *    a shared mobile IP are not all locked out together.
 *  - password_verify() always runs (dummy hash) so response time does not
 *    reveal whether an email exists.
 *  - Demo credentials show only on localhost or when APP_ENV=development.
 *  - Show/hide password, autocomplete attributes.
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

if (isLoggedIn()) {
    redirectToRoleDashboard();
}

// Valid bcrypt hash of a throw-away string; only used to keep timing constant.
const LOGIN_DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$pageTitle = 'Sign In to Your Account';
$pageDescription = 'Sign in to view your child\'s vaccination records or your hospital appointment queue.';
$errorMessage = '';
$submittedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Your session expired. Please reload the page and try again.';
    } else {
        $submittedEmail = mb_substr(trim((string)($_POST['email'] ?? '')), 0, 150);
        $password       = (string)($_POST['password'] ?? '');

        if ($submittedEmail === '' || $password === '') {
            $errorMessage = 'Enter both your email address and password.';
        } elseif (!filter_var($submittedEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Enter a valid email address.';
        } else {
            $accountKey = 'login_' . $clientIp . '_' . substr(hash('sha256', strtolower($submittedEmail)), 0, 16);
            $ipKey      = 'loginip_' . $clientIp;

            $accountLimit = checkRateLimit($accountKey, 5, 300);   // 5 failures / 5 min per IP + email
            $ipLimit      = checkRateLimit($ipKey, 30, 300);       // loose cap against password spraying

            if (!$accountLimit['allowed'] || !$ipLimit['allowed']) {
                $wait = max((int)($accountLimit['retry_after'] ?? 0), (int)($ipLimit['retry_after'] ?? 0));
                $errorMessage = 'Too many failed attempts. Please wait ' . max(1, (int)ceil($wait / 60)) . ' minute(s) and try again.';
            } else {
                try {
                    $pdo  = getDBConnection();
                    $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
                    $stmt->execute([$submittedEmail]);
                    $user = $stmt->fetch();

                    // Always verify against *a* hash so unknown emails take as long as known ones.
                    $hash       = is_array($user) ? $user['password_hash'] : LOGIN_DUMMY_HASH;
                    $passwordOk = password_verify($password, $hash) && is_array($user);

                    if ($passwordOk) {
                        $profileId = null;

                        if ($user['role'] === 'parent') {
                            $pStmt = $pdo->prepare('SELECT id FROM parents WHERE user_id = ? LIMIT 1');
                            $pStmt->execute([$user['id']]);
                            $profileId = $pStmt->fetchColumn() ?: null;
                        }

                        if ($user['role'] === 'hospital') {
                            $hStmt = $pdo->prepare('SELECT id, status FROM hospitals WHERE user_id = ? LIMIT 1');
                            $hStmt->execute([$user['id']]);
                            $hospitalProfile = $hStmt->fetch();

                            if ($hospitalProfile && $hospitalProfile['status'] === 'inactive') {
                                $errorMessage = 'This hospital account is inactive. Please contact the system administrator.';
                            } else {
                                $profileId = $hospitalProfile['id'] ?? null;
                            }
                        }

                        if ($errorMessage === '') {
                            clearRateLimit($accountKey);
                            clearRateLimit($ipKey);
                            loginUser($user, $profileId);
                            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                            redirectToRoleDashboard($user['role']);
                        }
                    } else {
                        recordRateLimitAttempt($accountKey);
                        recordRateLimitAttempt($ipKey);
                        $attemptsLeft = max(0, (int)$accountLimit['remaining'] - 1);
                        $errorMessage = 'Incorrect email or password.'
                            . ($attemptsLeft > 0
                                ? " You have {$attemptsLeft} " . ($attemptsLeft === 1 ? 'attempt' : 'attempts') . ' left.'
                                : ' Sign-in is locked for a few minutes.');
                    }
                } catch (PDOException $ex) {
                    error_log('[VMS LOGIN ERROR] ' . $ex->getMessage());
                    $errorMessage = 'Something went wrong on our side. Please try again in a moment.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <span class="badge badge-info" style="margin-bottom: .5rem;">VMS portal</span>
                <h1>Sign in</h1>
                <p>Parents, hospital staff, and administrators sign in here.</p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <span><?= e($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= url('login.php'); ?>" method="POST">
                <?= csrfField(); ?>

                <div class="form-group">
                    <label for="email" class="form-label required">Email address</label>
                    <input type="email" id="email" name="email" class="form-control" maxlength="150"
                           placeholder="e.g. ahmed.khan@example.com" autocomplete="username"
                           value="<?= e($submittedEmail); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label required">Password</label>
                    <div class="ui-pass">
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Your password" autocomplete="current-password" required>
                        <button type="button" id="togglePass" aria-label="Show password" aria-pressed="false">
                            <?= uiIcon('eye', 18); ?>
                        </button>
                    </div>
                    <small class="form-hint">
                        Forgot your password? <a href="<?= url('contact.php?subject=password'); ?>">Ask the helpdesk</a>.
                    </small>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
                </div>
            </form>

            <div class="auth-footer">
                <p style="margin-bottom: .5rem;">New to VMS?
                    <a href="<?= url('register.php'); ?>" style="font-weight: 600;">Create a parent account</a>
                </p>
                <p style="margin: 0; font-size: .82rem;">
                    Running a clinic? <a href="<?= url('register.php?type=hospital'); ?>">Register your center</a>.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('togglePass');
    var input = document.getElementById('password');
    if (!btn || !input) { return; }
    btn.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>