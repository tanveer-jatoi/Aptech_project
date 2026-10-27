<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/profile.php
 * Description: Parent Account & Contact Profile Management
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - View & update parent contact info (Name, Phone, Residential Address).
 * - Optional password change mechanism with current password verification.
 * - CSRF verification and flash toast notifications.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('parent');

$pdo = getDBConnection();
$userId = currentUserId();
$errors = [];

// Fetch current user and parent profile
$stmt = $pdo->prepare(
    'SELECT u.name, u.email, u.password_hash,
            p.id AS parent_id, p.phone, p.address
     FROM users u
     LEFT JOIN parents p ON u.id = p.user_id
     WHERE u.id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    setFlash('danger', 'Unable to retrieve parent profile.');
    header('Location: ' . url('login.php'));
    exit;
}

// ----------------------------------------------------------------------------
// HANDLE PROFILE & PASSWORD UPDATE (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
        header('Location: ' . url('parent/profile.php'));
        exit;
    }

    $name        = sanitizeInput($_POST['name'] ?? '');
    $phone       = sanitizeInput($_POST['phone'] ?? '');
    $address     = sanitizeInput($_POST['address'] ?? '');
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Validations
    if (empty($name))    $errors[] = 'Full name is required.';
    if (empty($phone))   $errors[] = 'Contact phone number is required.';
    if (empty($address)) $errors[] = 'Residential address is required.';

    // Password change validation if attempted
    $changePassword = false;
    if (!empty($newPass)) {
        if (empty($currentPass)) {
            $errors[] = 'Current password is required to set a new password.';
        } elseif (!password_verify($currentPass, $profile['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'New passwords do not match.';
        } else {
            $changePassword = true;
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Update User Record
            if ($changePassword) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $uStmt = $pdo->prepare('UPDATE users SET name = ?, password_hash = ? WHERE id = ?');
                $uStmt->execute([$name, $newHash, $userId]);
            } else {
                $uStmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
                $uStmt->execute([$name, $userId]);
            }

            // 2. Update or Insert Parent Profile Record
            if (!empty($profile['parent_id'])) {
                $pStmt = $pdo->prepare('UPDATE parents SET phone = ?, address = ? WHERE id = ?');
                $pStmt->execute([$phone, $address, $profile['parent_id']]);
            } else {
                $pStmt = $pdo->prepare('INSERT INTO parents (user_id, phone, address, created_at) VALUES (?, ?, ?, NOW())');
                $pStmt->execute([$userId, $phone, $address]);
                $_SESSION['profile_id'] = (int)$pdo->lastInsertId();
            }

            $pdo->commit();

            // Update session name
            $_SESSION['user_name'] = $name;

            setFlash('success', 'Your parent profile details have been saved successfully!');
            header('Location: ' . url('parent/profile.php'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[PARENT PROFILE UPDATE ERROR] ' . $e->getMessage());
            $errors[] = 'Database error saving profile: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'My Profile';
$pageDescription = 'View and update your parent contact details and security settings.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="max-width: 720px; margin: 0 auto;">
        <div style="margin-bottom: 1.5rem;">
            <h1>Parent Account Profile</h1>
            <p style="margin: 0;">Keep your contact number and home address updated for hospital appointment notifications.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <div>
                    <strong>Please correct the following:</strong>
                    <ul style="margin: 0.25rem 0 0 1.2rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <form action="<?= url('parent/profile.php'); ?>" method="POST">
                <?= csrfField(); ?>

                <!-- Demographic Information -->
                <div class="card-header">
                    <h3>Contact Information</h3>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label required">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?= e($_POST['name'] ?? $profile['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address (Login)</label>
                        <input type="email" id="email" class="form-control" 
                               value="<?= e($profile['email']); ?>" readonly disabled 
                               style="background-color: var(--bg-subtle); cursor: not-allowed;">
                        <small class="form-hint">Email address cannot be changed.</small>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label required">Primary Contact Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="+1-555-0101" 
                               value="<?= e($_POST['phone'] ?? $profile['phone']); ?>" required>
                        <small class="form-hint">Hospitals contact you at this number</small>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label required">Residential Address</label>
                        <input type="text" id="address" name="address" class="form-control" 
                               placeholder="Street address, City" 
                               value="<?= e($_POST['address'] ?? $profile['address']); ?>" required>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="card-header" style="margin-top: 1.5rem;">
                    <div>
                        <h3>Change Password</h3>
                        <p style="margin: 0; font-size: 0.85rem;">Leave blank if you do not wish to update your login password.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" 
                           placeholder="Enter current password to verify identity">
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               placeholder="Min 6 characters">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-enter new password">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Save Profile Changes &rarr;</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
