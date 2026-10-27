<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: register.php
 * Description: Dual Registration Portal for Parents & Participating Hospitals
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

// Redirect if already authenticated
if (isLoggedIn()) {
    redirectToRoleDashboard();
}

$pageTitle = 'Create an Account | Register';
$pageDescription = 'Register as a parent to track child immunizations or register a medical facility.';

$activeTab = 'parent';
$errors = [];
$formData = [
    'parent_name'      => '',
    'parent_email'     => '',
    'parent_phone'     => '',
    'parent_address'   => '',
    'hospital_name'    => '',
    'rep_name'         => '',
    'hospital_email'   => '',
    'hospital_phone'   => '',
    'hospital_city'    => '',
    'hospital_location'=> '',
    'hospital_address' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token validation failed. Please refresh the page and try again.';
    } else {
        $regType = $_POST['reg_type'] ?? 'parent';
        $pdo = getDBConnection();

        // ====================================================================
        // A. PARENT REGISTRATION PROCESSING
        // ====================================================================
        if ($regType === 'parent') {
            $activeTab = 'parent';

            $name     = sanitizeInput($_POST['parent_name'] ?? '');
            $email    = sanitizeInput($_POST['parent_email'] ?? '');
            $phone    = sanitizeInput($_POST['parent_phone'] ?? '');
            $address  = sanitizeInput($_POST['parent_address'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            $formData['parent_name']    = $name;
            $formData['parent_email']   = $email;
            $formData['parent_phone']   = $phone;
            $formData['parent_address'] = $address;

            // Field Validations
            if (empty($name))    $errors[] = 'Full name is required.';
            if (empty($email))   $errors[] = 'Email address is required.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address format.';
            if (empty($phone))   $errors[] = 'Phone number is required.';
            if (empty($address)) $errors[] = 'Residential address is required.';
            if (empty($password)) $errors[] = 'Password is required.';
            elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
            if ($password !== $confirm) $errors[] = 'Passwords do not match.';

            // Check email uniqueness
            if (empty($errors)) {
                $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $errors[] = 'That email address is already registered. Please sign in or use a different email.';
                }
            }

            // Execute Transaction
            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    // 1. Insert User account
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $uStmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, "parent", NOW())');
                    $uStmt->execute([$name, $email, $passwordHash]);
                    $userId = (int)$pdo->lastInsertId();

                    // 2. Insert Parent Profile
                    $pStmt = $pdo->prepare('INSERT INTO parents (user_id, phone, address, created_at) VALUES (?, ?, ?, NOW())');
                    $pStmt->execute([$userId, $phone, $address]);

                    $pdo->commit();

                    setFlash('success', 'Your parent account has been created successfully! Please sign in below.');
                    header('Location: ' . url('login.php'));
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('[VMS PARENT REG ERROR] ' . $e->getMessage());
                    $errors[] = 'Registration failed due to a database error. Please try again.';
                }
            }

        // ====================================================================
        // B. HOSPITAL REGISTRATION PROCESSING
        // ====================================================================
        } elseif ($regType === 'hospital') {
            $activeTab = 'hospital';

            $hName    = sanitizeInput($_POST['hospital_name'] ?? '');
            $repName  = sanitizeInput($_POST['rep_name'] ?? '');
            $email    = sanitizeInput($_POST['hospital_email'] ?? '');
            $phone    = sanitizeInput($_POST['hospital_phone'] ?? '');
            $city     = sanitizeInput($_POST['hospital_city'] ?? '');
            $location = sanitizeInput($_POST['hospital_location'] ?? '');
            $address  = sanitizeInput($_POST['hospital_address'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            $formData['hospital_name']     = $hName;
            $formData['rep_name']          = $repName;
            $formData['hospital_email']    = $email;
            $formData['hospital_phone']    = $phone;
            $formData['hospital_city']     = $city;
            $formData['hospital_location'] = $location;
            $formData['hospital_address']  = $address;

            // Validations
            if (empty($hName))    $errors[] = 'Hospital / Center name is required.';
            if (empty($repName))  $errors[] = 'Representative contact name is required.';
            if (empty($email))    $errors[] = 'Hospital email address is required.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address format.';
            if (empty($phone))    $errors[] = 'Contact phone number is required.';
            if (empty($city))     $errors[] = 'City is required.';
            if (empty($location)) $errors[] = 'Location / District / Landmark is required.';
            if (empty($address))  $errors[] = 'Physical street address is required.';
            if (empty($password)) $errors[] = 'Password is required.';
            elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
            if ($password !== $confirm) $errors[] = 'Passwords do not match.';

            if (empty($errors)) {
                $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $errors[] = 'That email address is already registered. Please sign in or use a different email.';
                }
            }

            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();

                    // 1. Insert User account
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $uStmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, "hospital", NOW())');
                    $uStmt->execute([$hName, $email, $passwordHash]);
                    $userId = (int)$pdo->lastInsertId();

                    // 2. Insert Hospital Profile
                    $hStmt = $pdo->prepare('INSERT INTO hospitals (user_id, name, address, city, location, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "active", NOW())');
                    $hStmt->execute([$userId, $hName, $address, $city, $location, $phone]);
                    $hospitalId = (int)$pdo->lastInsertId();

                    // 3. Initialize default hospital vaccine inventory from active vaccines catalog
                    $vStmt = $pdo->query('SELECT id FROM vaccines WHERE status = "available"');
                    $vaccines = $vStmt->fetchAll();
                    if (!empty($vaccines)) {
                        $stockStmt = $pdo->prepare('INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status) VALUES (?, ?, "available")');
                        foreach ($vaccines as $vac) {
                            $stockStmt->execute([$hospitalId, $vac['id']]);
                        }
                    }

                    $pdo->commit();

                    setFlash('success', 'Your healthcare facility has been registered successfully! You may now sign in.');
                    header('Location: ' . url('login.php'));
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('[VMS HOSPITAL REG ERROR] ' . $e->getMessage());
                    $errors[] = 'Facility registration failed due to a database error. Please try again.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <h1>Create an Account</h1>
                <p>Register as a parent or partner with us as an authorized vaccination center.</p>
            </div>

            <!-- Role Selector Tabs -->
            <div class="form-tabs" role="tablist" aria-label="Registration Options">
                <button type="button" class="form-tab-btn <?= $activeTab === 'parent' ? 'is-active' : ''; ?>" 
                        id="tabParentBtn" role="tab" aria-selected="<?= $activeTab === 'parent' ? 'true' : 'false'; ?>">
                    &#128106; Parent Registration
                </button>
                <button type="button" class="form-tab-btn <?= $activeTab === 'hospital' ? 'is-active' : ''; ?>" 
                        id="tabHospitalBtn" role="tab" aria-selected="<?= $activeTab === 'hospital' ? 'true' : 'false'; ?>">
                    &#127973; Hospital / Clinic Center
                </button>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <div>
                        <strong>Please resolve the following issues:</strong>
                        <ul style="margin: 0.35rem 0 0 1.2rem; line-height: 1.4;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ============================================================== -->
            <!-- 1. PARENT REGISTRATION FORM                                   -->
            <!-- ============================================================== -->
            <form action="<?= url('register.php'); ?>" method="POST" id="registerParentForm" 
                  style="display: <?= $activeTab === 'parent' ? 'block' : 'none'; ?>;" 
                  onsubmit="return validateRegistrationForm(this);">
                <?= csrfField(); ?>
                <input type="hidden" name="reg_type" value="parent">

                <div class="form-group">
                    <label for="parent_name" class="form-label required">Parent Full Name</label>
                    <input type="text" id="parent_name" name="parent_name" class="form-control" 
                           placeholder="e.g. John Doe" value="<?= e($formData['parent_name']); ?>" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="parent_email" class="form-label required">Email Address</label>
                        <input type="email" id="parent_email" name="parent_email" class="form-control" 
                               placeholder="e.g. parent@example.com" value="<?= e($formData['parent_email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="parent_phone" class="form-label required">Phone Number</label>
                        <input type="tel" id="parent_phone" name="parent_phone" class="form-control" 
                               placeholder="e.g. +1-555-0199" value="<?= e($formData['parent_phone']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="parent_address" class="form-label required">Residential Address</label>
                    <input type="text" id="parent_address" name="parent_address" class="form-control" 
                           placeholder="House / Street address, City" value="<?= e($formData['parent_address']); ?>" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="parent_pass" class="form-label required">Password</label>
                        <input type="password" id="parent_pass" name="password" class="form-control" 
                               placeholder="Min 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="parent_pass_confirm" class="form-label required">Confirm Password</label>
                        <input type="password" id="parent_pass_confirm" name="password_confirm" class="form-control" 
                               placeholder="Re-enter password" required>
                    </div>
                </div>

                <div class="form-error form-password-error" style="display: none; margin-bottom: 1rem;"></div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-block">Complete Parent Registration &rarr;</button>
                </div>
            </form>

            <!-- ============================================================== -->
            <!-- 2. HOSPITAL REGISTRATION FORM                                 -->
            <!-- ============================================================== -->
            <form action="<?= url('register.php'); ?>" method="POST" id="registerHospitalForm" 
                  style="display: <?= $activeTab === 'hospital' ? 'block' : 'none'; ?>;" 
                  onsubmit="return validateRegistrationForm(this);">
                <?= csrfField(); ?>
                <input type="hidden" name="reg_type" value="hospital">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="hospital_name" class="form-label required">Hospital / Center Name</label>
                        <input type="text" id="hospital_name" name="hospital_name" class="form-control" 
                               placeholder="e.g. Hope Children Hospital" value="<?= e($formData['hospital_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="rep_name" class="form-label required">Representative Contact Name</label>
                        <input type="text" id="rep_name" name="rep_name" class="form-control" 
                               placeholder="e.g. Dr. Robert Miller" value="<?= e($formData['rep_name']); ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="hospital_email" class="form-label required">Hospital Official Email</label>
                        <input type="email" id="hospital_email" name="hospital_email" class="form-control" 
                               placeholder="pediatrics@hospital.org" value="<?= e($formData['hospital_email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="hospital_phone" class="form-label required">Hotline / Contact Phone</label>
                        <input type="tel" id="hospital_phone" name="hospital_phone" class="form-control" 
                               placeholder="e.g. +1-555-4422" value="<?= e($formData['hospital_phone']); ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="hospital_city" class="form-label required">City</label>
                        <input type="text" id="hospital_city" name="hospital_city" class="form-control" 
                               placeholder="e.g. Metropolis" value="<?= e($formData['hospital_city']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="hospital_location" class="form-label required">District / Area Landmark</label>
                        <input type="text" id="hospital_location" name="hospital_location" class="form-control" 
                               placeholder="e.g. East Wing, Sector 5" value="<?= e($formData['hospital_location']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="hospital_address" class="form-label required">Full Street Address</label>
                    <input type="text" id="hospital_address" name="hospital_address" class="form-control" 
                           placeholder="Physical location for visiting parents" value="<?= e($formData['hospital_address']); ?>" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="hosp_pass" class="form-label required">Password</label>
                        <input type="password" id="hosp_pass" name="password" class="form-control" 
                               placeholder="Min 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="hosp_pass_confirm" class="form-label required">Confirm Password</label>
                        <input type="password" id="hosp_pass_confirm" name="password_confirm" class="form-control" 
                               placeholder="Re-enter password" required>
                    </div>
                </div>

                <div class="form-error form-password-error" style="display: none; margin-bottom: 1rem;"></div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-teal btn-block">Register Healthcare Center &rarr;</button>
                </div>
            </form>

            <div class="auth-footer">
                <p style="margin: 0;">Already registered with VMS? 
                    <a href="<?= url('login.php'); ?>" style="font-weight: 600;">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
