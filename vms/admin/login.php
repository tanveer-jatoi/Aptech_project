<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/login.php
 * Description: Dedicated Administrative Access Portal with Rate Limiting
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

// If already authenticated as admin, go straight to admin dashboard
if (isLoggedIn()) {
    if (currentRole() === 'admin') {
        header('Location: ' . url('admin/dashboard.php'));
        exit;
    }
    // If logged in as parent/hospital, redirect to their respective dashboard
    redirectToRoleDashboard();
}

$pageTitle = 'Administrator Secure Access';
$pageDescription = 'Restricted access control portal for system administrators.';
$errorMessage = '';
$submittedEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimit = checkRateLimit('admin_login_' . $clientIp, 5, 300);

    if (!$rateLimit['allowed']) {
        $errorMessage = 'Too many failed administrative login attempts. Please wait ' . ceil($rateLimit['retry_after'] / 60) . ' minutes before trying again.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Security validation failed (Invalid CSRF token). Please try again.';
    } else {
        $submittedEmail = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($submittedEmail) || empty($password)) {
            $errorMessage = 'Please provide administrator credentials.';
        } else {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$submittedEmail]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['role'] !== 'admin') {
                        $errorMessage = 'Access denied. This administrative portal is restricted to system administrators.';
                    } else {
                        clearRateLimit('admin_login_' . $clientIp);
                        loginUser($user, null);
                        setFlash('success', 'Welcome to the Administration Panel, ' . $user['name'] . '!');
                        header('Location: ' . url('admin/dashboard.php'));
                        exit;
                    }
                } else {
                    recordRateLimitAttempt('admin_login_' . $clientIp);
                    $attemptsLeft = $rateLimit['remaining'] - 1;
                    $attemptNote = $attemptsLeft > 0 ? " ({$attemptsLeft} attempts remaining)" : " (Account temporarily locked)";
                    $errorMessage = 'Invalid administrative credentials. Please verify your login details.' . $attemptNote;
                }
            } catch (PDOException $e) {
                error_log('[VMS ADMIN LOGIN ERROR] ' . $e->getMessage());
                $errorMessage = 'A database error occurred. Please contact technical support.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-card" style="border-top: 4px solid var(--danger);">
            <div class="auth-header">
                <span class="badge badge-danger" style="margin-bottom: 0.5rem;">Restricted Access</span>
                <h1>Admin Control Portal</h1>
                <p>System management, appointment moderation &amp; vaccine oversight.</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <span><?= e($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= url('admin/login.php'); ?>" method="POST" novalidate>
                <?= csrfField(); ?>

                <div class="form-group">
                    <label for="admin_email" class="form-label required">Administrator Email</label>
                    <input type="email" id="admin_email" name="email" class="form-control" 
                           placeholder="admin@vms.local" 
                           value="<?= e($submittedEmail); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="admin_password" class="form-label required">Security Password</label>
                    <input type="password" id="admin_password" name="password" class="form-control" 
                           placeholder="Enter admin password" required>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-danger btn-block">Authenticate Administrator &rarr;</button>
                </div>
            </form>

            <div class="auth-footer">
                <p style="margin-bottom: 0.5rem;">Looking for Guardian or Clinic Portal?</p>
                <a href="<?= url('login.php'); ?>" style="font-weight: 600;">Go to User Login &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
