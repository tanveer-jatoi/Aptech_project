<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: login.php
 * Description: Unified Portal Login (Parent, Hospital, Admin) with Rate Limiting
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

// If already authenticated, redirect directly to user's portal dashboard
if (isLoggedIn()) {
    redirectToRoleDashboard();
}

$pageTitle = 'Sign In to Your Account';
$pageDescription = 'Sign in to access your child vaccination records or hospital appointment queue in Pakistan.';
$errorMessage = '';
$submittedEmail = '';

// Handle POST authentication request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Rate Limiting Check (Max 5 attempts per 5 minutes per IP)
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimit = checkRateLimit('login_' . $clientIp, 5, 300);

    if (!$rateLimit['allowed']) {
        $errorMessage = 'Too many failed sign-in attempts. Please wait ' . ceil($rateLimit['retry_after'] / 60) . ' minutes before trying again.';
    }
    // 2. Enforce CSRF token verification
    elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed (Invalid CSRF token). Please try again.';
    } else {
        $submittedEmail = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // 3. Validate input constraints
        if (empty($submittedEmail) || empty($password)) {
            $errorMessage = 'Please provide both your email address and password.';
        } elseif (!filter_var($submittedEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$submittedEmail]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $profileId = null;

                    // Fetch associated parent profile ID
                    if ($user['role'] === 'parent') {
                        $pStmt = $pdo->prepare('SELECT id FROM parents WHERE user_id = ? LIMIT 1');
                        $pStmt->execute([$user['id']]);
                        $parentProfile = $pStmt->fetch();
                        $profileId = $parentProfile['id'] ?? null;
                    }

                    // Fetch associated hospital profile ID and verify active status
                    if ($user['role'] === 'hospital') {
                        $hStmt = $pdo->prepare('SELECT id, status FROM hospitals WHERE user_id = ? LIMIT 1');
                        $hStmt->execute([$user['id']]);
                        $hospitalProfile = $hStmt->fetch();

                        if ($hospitalProfile && $hospitalProfile['status'] === 'inactive') {
                            $errorMessage = 'Your hospital facility account is currently inactive. Please contact system administration.';
                        } else {
                            $profileId = $hospitalProfile['id'] ?? null;
                        }
                    }

                    // If no block condition, complete login
                    if (empty($errorMessage)) {
                        clearRateLimit('login_' . $clientIp);
                        loginUser($user, $profileId);
                        setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                        redirectToRoleDashboard($user['role']);
                    }
                } else {
                    recordRateLimitAttempt('login_' . $clientIp);
                    $attemptsLeft = $rateLimit['remaining'] - 1;
                    $attemptNote = $attemptsLeft > 0 ? " ({$attemptsLeft} attempts remaining)" : " (Account temporarily rate-limited)";
                    $errorMessage = 'Invalid email address or password. Please verify your credentials.' . $attemptNote;
                }
            } catch (PDOException $e) {
                error_log('[VMS LOGIN ERROR] ' . $e->getMessage());
                $errorMessage = 'A database error occurred during sign in. Please try again later.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <span class="badge badge-info" style="margin-bottom: 0.5rem;">User Portal</span>
                <h1>Sign In</h1>
                <p>Access your immunization dashboard, bookings, or records.</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <span><?= e($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= url('login.php'); ?>" method="POST" novalidate>
                <!-- CSRF Protection Field -->
                <?= csrfField(); ?>

                <div class="form-group">
                    <label for="email" class="form-label required">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="e.g. ahmed.khan@example.com" 
                           value="<?= e($submittedEmail); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <label for="password" class="form-label required">Password</label>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your account password" required>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-block">Sign In &rarr;</button>
                </div>
            </form>

            <div class="auth-footer">
                <p style="margin-bottom: 0.5rem;">Don't have an account yet? 
                    <a href="<?= url('register.php'); ?>" style="font-weight: 600;">Register Here</a>
                </p>
                <p style="margin: 0; font-size: 0.82rem;">
                    Hospital Staff? You can log in above or register your clinic.
                </p>
            </div>

            <!-- Demo Quick-Fill Credentials Helper -->
            <div style="margin-top: 1.5rem; background: var(--bg-subtle); padding: 1rem; border-radius: var(--radius-md); font-size: 0.8rem; border: 1px solid var(--border-color);">
                <strong>Test Accounts (Pakistan Localized):</strong>
                <ul style="margin: 0.4rem 0 0 1.2rem; line-height: 1.6; color: var(--text-muted);">
                    <li><strong>Parent:</strong> <code>ahmed.khan@example.com</code> / <code>Parent@123</code> (or <code>john.doe@example.com</code>)</li>
                    <li><strong>Hospital:</strong> <code>city.general@hospital.local</code> / <code>Hospital@123</code></li>
                    <li><strong>Admin:</strong> <code>admin@vms.local</code> / <code>Admin@123</code> (<a href="<?= url('admin/login.php'); ?>">Admin Portal</a>)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
