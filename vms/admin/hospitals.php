<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/hospitals.php
 * Description: Hospital Healthcare Centers Management (Full CRUD)
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - List all participating vaccination centers with live status and search filters.
 * - Add new hospital center with transactional account creation and stock seeding.
 * - Update facility metadata (name, address, city, location, phone, active status).
 * - Delete / deactivate hospital facility with foreign key integrity checks.
 * - CSRF verification and success flash toasts on all operations.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('admin');

$pdo = getDBConnection();
$errors = [];
$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ----------------------------------------------------------------------------
// 1. HANDLE POST ACTIONS (Add, Update, Delete)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
        header('Location: ' . url('admin/hospitals.php'));
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';

    // ========================================================================
    // A. ADD NEW HOSPITAL FACILITY
    // ========================================================================
    if ($formAction === 'add') {
        $name     = sanitizeInput($_POST['name'] ?? '');
        $email    = sanitizeInput($_POST['email'] ?? '');
        $phone    = sanitizeInput($_POST['phone'] ?? '');
        $city     = sanitizeInput($_POST['city'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $address  = sanitizeInput($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name))     $errors[] = 'Hospital name is required.';
        if (empty($email))    $errors[] = 'Contact email is required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address format.';
        if (empty($phone))    $errors[] = 'Phone number is required.';
        if (empty($city))     $errors[] = 'City is required.';
        if (empty($location)) $errors[] = 'Location / Landmark is required.';
        if (empty($address))  $errors[] = 'Street address is required.';
        if (empty($password)) $errors[] = 'Password is required.';
        elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        // Check email uniqueness
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'A user account with that email already exists.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Create User account
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $uStmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, "hospital", NOW())');
                $uStmt->execute([$name, $email, $passHash]);
                $userId = (int)$pdo->lastInsertId();

                // 2. Create Hospital Profile
                $hStmt = $pdo->prepare('INSERT INTO hospitals (user_id, name, address, city, location, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, "active", NOW())');
                $hStmt->execute([$userId, $name, $address, $city, $location, $phone]);
                $hospitalId = (int)$pdo->lastInsertId();

                // 3. Populate default vaccine stock records
                $vStmt = $pdo->query('SELECT id FROM vaccines WHERE status = "available"');
                $vaccines = $vStmt->fetchAll();
                if (!empty($vaccines)) {
                    $stockStmt = $pdo->prepare('INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status) VALUES (?, ?, "available")');
                    foreach ($vaccines as $vac) {
                        $stockStmt->execute([$hospitalId, $vac['id']]);
                    }
                }

                $pdo->commit();
                setFlash('success', 'Hospital facility "' . $name . '" has been successfully registered!');
                header('Location: ' . url('admin/hospitals.php'));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[ADD HOSPITAL ERROR] ' . $e->getMessage());
                $errors[] = 'Database error creating hospital facility: ' . $e->getMessage();
            }
        }
    }

    // ========================================================================
    // B. UPDATE EXISTING HOSPITAL FACILITY
    // ========================================================================
    elseif ($formAction === 'update') {
        $hospitalId = (int)($_POST['hospital_id'] ?? 0);
        $name       = sanitizeInput($_POST['name'] ?? '');
        $phone      = sanitizeInput($_POST['phone'] ?? '');
        $city       = sanitizeInput($_POST['city'] ?? '');
        $location   = sanitizeInput($_POST['location'] ?? '');
        $address    = sanitizeInput($_POST['address'] ?? '');
        $status     = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($hospitalId <= 0) $errors[] = 'Invalid hospital ID.';
        if (empty($name))     $errors[] = 'Hospital name is required.';
        if (empty($phone))    $errors[] = 'Phone number is required.';
        if (empty($city))     $errors[] = 'City is required.';
        if (empty($location)) $errors[] = 'Location is required.';
        if (empty($address))  $errors[] = 'Street address is required.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update hospital record
                $stmt = $pdo->prepare('UPDATE hospitals SET name = ?, phone = ?, city = ?, location = ?, address = ?, status = ? WHERE id = ?');
                $stmt->execute([$name, $phone, $city, $location, $address, $status, $hospitalId]);

                // Update users table name
                $uStmt = $pdo->prepare('UPDATE users u JOIN hospitals h ON u.id = h.user_id SET u.name = ? WHERE h.id = ?');
                $uStmt->execute([$name, $hospitalId]);

                $pdo->commit();
                setFlash('success', 'Hospital details for "' . $name . '" were successfully updated!');
                header('Location: ' . url('admin/hospitals.php'));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[UPDATE HOSPITAL ERROR] ' . $e->getMessage());
                $errors[] = 'Database error updating hospital: ' . $e->getMessage();
            }
        }
    }

    // ========================================================================
    // C. DELETE / DEACTIVATE HOSPITAL FACILITY
    // ========================================================================
    elseif ($formAction === 'delete') {
        $hospitalId = (int)($_POST['hospital_id'] ?? 0);

        if ($hospitalId > 0) {
            try {
                // Check if bookings exist for this hospital
                $bStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE hospital_id = ?');
                $bStmt->execute([$hospitalId]);
                $bookingCount = (int)$bStmt->fetchColumn();

                if ($bookingCount > 0) {
                    // Soft-deactivate to preserve clinical appointment audit logs
                    $stmt = $pdo->prepare('UPDATE hospitals SET status = "inactive" WHERE id = ?');
                    $stmt->execute([$hospitalId]);
                    setFlash('warning', 'Hospital facility has active patient booking history. Status has been safely set to "Inactive" to preserve records.');
                } else {
                    // Hard delete user and cascading hospital record
                    $hStmt = $pdo->prepare('SELECT user_id, name FROM hospitals WHERE id = ?');
                    $hStmt->execute([$hospitalId]);
                    $hData = $hStmt->fetch();

                    if ($hData) {
                        $uStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                        $uStmt->execute([$hData['user_id']]);
                        setFlash('success', 'Hospital facility "' . $hData['name'] . '" has been permanently deleted.');
                    }
                }
            } catch (PDOException $e) {
                error_log('[DELETE HOSPITAL ERROR] ' . $e->getMessage());
                setFlash('danger', 'Failed to delete hospital: ' . $e->getMessage());
            }
        }
        header('Location: ' . url('admin/hospitals.php'));
        exit;
    }
}

// ----------------------------------------------------------------------------
// 2. FETCH DATA FOR VIEW
// ----------------------------------------------------------------------------
$search = sanitizeInput($_GET['search'] ?? '');
$filterCity = sanitizeInput($_GET['city'] ?? '');
$filterStatus = sanitizeInput($_GET['status'] ?? '');

$sql = 'SELECT h.*, u.email,
               (SELECT COUNT(*) FROM bookings b WHERE b.hospital_id = h.id) AS total_bookings,
               (SELECT COUNT(*) FROM bookings b WHERE b.hospital_id = h.id AND b.vaccination_status = "vaccinated") AS total_completed
        FROM hospitals h
        JOIN users u ON h.user_id = u.id
        WHERE 1=1';

$params = [];

if (!empty($search)) {
    $sql .= ' AND (h.name LIKE ? OR h.location LIKE ? OR h.city LIKE ? OR u.email LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($filterCity)) {
    $sql .= ' AND h.city = ?';
    $params[] = $filterCity;
}

if (!empty($filterStatus)) {
    $sql .= ' AND h.status = ?';
    $params[] = $filterStatus;
}

$sql .= ' ORDER BY h.name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hospitalsList = $stmt->fetchAll();

// Distinct cities list for filter dropdown
$cityStmt = $pdo->query('SELECT DISTINCT city FROM hospitals ORDER BY city ASC');
$availableCities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

// If action is edit, fetch target hospital
$editHospital = null;
if ($action === 'edit' && $editId > 0) {
    $eStmt = $pdo->prepare('SELECT h.*, u.email FROM hospitals h JOIN users u ON h.user_id = u.id WHERE h.id = ?');
    $eStmt->execute([$editId]);
    $editHospital = $eStmt->fetch();
}

$pageTitle = 'Manage Hospitals & Centers';
$pageDescription = 'Add, modify, and monitor verified pediatric vaccination centers.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
        <div>
            <h1>Participating Healthcare Facilities</h1>
            <p style="margin: 0;">Authorized clinics and pediatric hospitals administering approved vaccines.</p>
        </div>

        <div>
            <?php if ($action === 'list'): ?>
                <a href="<?= url('admin/hospitals.php?action=add'); ?>" class="btn btn-primary">
                    &#10010; Add New Hospital
                </a>
            <?php else: ?>
                <a href="<?= url('admin/hospitals.php'); ?>" class="btn btn-outline">
                    &larr; Back to Hospitals List
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <div>
                <strong>Please resolve the following:</strong>
                <ul style="margin: 0.25rem 0 0 1.2rem;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= e($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- ====================================================================== -->
    <!-- VIEW A: ADD NEW HOSPITAL FORM                                          -->
    <!-- ====================================================================== -->
    <?php if ($action === 'add'): ?>
        <div class="card" style="max-width: 800px; margin: 0 auto 2rem;">
            <div class="card-header">
                <h3>Enroll New Healthcare Facility</h3>
            </div>
            <form action="<?= url('admin/hospitals.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="add">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label required">Hospital / Center Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Westside Pediatrics Center" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label required">Official Contact Email (Login)</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="center@hospital.org" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label required">Hotline Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="+1-555-0199" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label required">Account Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="city" class="form-label required">City</label>
                        <input type="text" id="city" name="city" class="form-control" placeholder="e.g. Metropolis" required>
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label required">District / Area Landmark</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Central Avenue, Block 2" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label required">Physical Street Address</label>
                    <textarea id="address" name="address" class="form-control" rows="2" placeholder="Full street address for parent appointment directions" required></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <a href="<?= url('admin/hospitals.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save & Register Facility &rarr;</button>
                </div>
            </form>
        </div>

    <!-- ====================================================================== -->
    <!-- VIEW B: EDIT EXISTING HOSPITAL FORM                                    -->
    <!-- ====================================================================== -->
    <?php elseif ($action === 'edit' && $editHospital): ?>
        <div class="card" style="max-width: 800px; margin: 0 auto 2rem;">
            <div class="card-header">
                <h3>Edit Facility Details: <?= e($editHospital['name']); ?></h3>
            </div>
            <form action="<?= url('admin/hospitals.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="hospital_id" value="<?= (int)$editHospital['id']; ?>">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="name" class="form-label required">Hospital Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= e($editHospital['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label required">Contact Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= e($editHospital['phone']); ?>" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="city" class="form-label required">City</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?= e($editHospital['city']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label required">District / Landmark</label>
                        <input type="text" id="location" name="location" class="form-control" value="<?= e($editHospital['location']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label required">Full Street Address</label>
                    <textarea id="address" name="address" class="form-control" rows="2" required><?= e($editHospital['address']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label required">Operational Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?= $editHospital['status'] === 'active' ? 'selected' : ''; ?>>Active (Accepting Bookings)</option>
                        <option value="inactive" <?= $editHospital['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Suspended / Closed)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <a href="<?= url('admin/hospitals.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Facility &rarr;</button>
                </div>
            </form>
        </div>

    <!-- ====================================================================== -->
    <!-- VIEW C: HOSPITALS LIST WITH SEARCH & FILTERS                          -->
    <!-- ====================================================================== -->
    <?php else: ?>
        <!-- Search and Filter Bar -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form action="<?= url('admin/hospitals.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
                <div style="flex: 2; min-width: 220px;">
                    <label for="search" class="form-label">Search Facility</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Name, city, or email..." value="<?= e($search); ?>">
                </div>

                <div style="flex: 1; min-width: 160px;">
                    <label for="filter_city" class="form-label">Filter by City</label>
                    <select id="filter_city" name="city" class="form-select">
                        <option value="">All Cities</option>
                        <?php foreach ($availableCities as $c): ?>
                            <option value="<?= e($c); ?>" <?= $filterCity === $c ? 'selected' : ''; ?>><?= e($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="filter_status" class="form-label">Status</label>
                    <select id="filter_status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Filter</button>
                    <?php if (!empty($search) || !empty($filterCity) || !empty($filterStatus)): ?>
                        <a href="<?= url('admin/hospitals.php'); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Hospitals Table -->
        <div class="card">
            <?php if (empty($hospitalsList)): ?>
                <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                    <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No healthcare centers match your search criteria.</p>
                    <a href="<?= url('admin/hospitals.php'); ?>" class="btn btn-outline btn-sm">Clear Search Filters</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Facility Name & Location</th>
                                <th>Contact Information</th>
                                <th>City</th>
                                <th>Total Appointments</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hospitalsList as $h): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($h['name']); ?></strong><br>
                                        <small style="color: var(--text-muted);">&#128205; <?= e($h['location']); ?></small><br>
                                        <small style="color: var(--text-light);"><?= e($h['address']); ?></small>
                                    </td>
                                    <td>
                                        <span>&#128222; <?= e($h['phone']); ?></span><br>
                                        <small style="color: var(--text-muted);">&#9993; <?= e($h['email']); ?></small>
                                    </td>
                                    <td><strong><?= e($h['city']); ?></strong></td>
                                    <td>
                                        <span title="Completed / Total"><?= (int)$h['total_completed']; ?> / <?= (int)$h['total_bookings']; ?> doses</span>
                                    </td>
                                    <td>
                                        <?php if ($h['status'] === 'active'): ?>
                                            <span class="badge badge-success">&#10004; Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">&#10006; Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?= url('admin/hospitals.php?action=edit&id=' . (int)$h['id']); ?>" class="btn btn-outline btn-sm" title="Edit Facility">
                                            &#9998; Edit
                                        </a>

                                        <form action="<?= url('admin/hospitals.php'); ?>" method="POST" style="display: inline-block; margin-left: 0.25rem;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="hospital_id" value="<?= (int)$h['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete or Deactivate" 
                                                    onclick="return confirm('Are you sure you want to delete or deactivate <?= e($h['name']); ?>?');">
                                                &#128465;
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
