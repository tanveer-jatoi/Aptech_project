<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/children.php
 * Description: Child Profiles Management (Add, Update, Maintain & Timeline)
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Add, update, and maintain child details (Name, DOB, Gender, Blood Group).
 * - Enforces strict ownership checks (parents can only access their own children).
 * - Displays individual immunization timeline and status matrix per child.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/ui.php';

requireRole('parent');

$pdo = getDBConnection();
$userId = currentUserId();

// Fetch Parent Profile ID
$pStmt = $pdo->prepare('SELECT id FROM parents WHERE user_id = ? LIMIT 1');
$pStmt->execute([$userId]);
$parentId = (int)$pStmt->fetchColumn();

if ($parentId === 0) {
    setFlash('warning', 'Please complete your parent profile first.');
    header('Location: ' . url('parent/profile.php'));
    exit;
}

$action = $_GET['action'] ?? 'list';
$childId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

// ----------------------------------------------------------------------------
// 1. HANDLE POST ACTIONS (Add, Update, Delete)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . url('parent/children.php'));
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';

    // ========================================================================
    // A. ADD NEW CHILD
    // ========================================================================
    if ($formAction === 'add') {
        $name       = sanitizeInput($_POST['name'] ?? '');
        $dob        = sanitizeInput($_POST['dob'] ?? '');
        $gender     = in_array($_POST['gender'] ?? '', ['male', 'female', 'other']) ? $_POST['gender'] : '';
        $bloodGroup = sanitizeInput($_POST['blood_group'] ?? '');

        if (empty($name))       $errors[] = 'Child name is required.';
        elseif (mb_strlen($name) > 100) $errors[] = 'Child name is too long (100 characters maximum).';
        if ($dob === '' || !isValidYmd($dob)) $errors[] = 'Enter a valid date of birth.';
        elseif (strtotime($dob) > strtotime('today')) $errors[] = 'Date of birth cannot be in the future.';
        elseif (strtotime($dob) < strtotime('-18 years')) $errors[] = 'VMS is for children under 18. Please check the date of birth.';
        if (empty($gender))     $errors[] = 'Gender is required.';
        if (!in_array($bloodGroup, $bloodGroups, true)) $errors[] = 'Select a blood group.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO children (parent_id, name, dob, gender, blood_group, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$parentId, $name, $dob, $gender, $bloodGroup]);
                $newChildId = (int)$pdo->lastInsertId();

                setFlash('success', 'Child profile for ' . $name . ' added successfully! You can now book their vaccinations.');
                header('Location: ' . url('parent/children.php?action=view&id=' . $newChildId));
                exit;
            } catch (PDOException $e) {
                error_log('[ADD CHILD ERROR] ' . $e->getMessage());
                $errors[] = 'A database error occurred while creating the child profile. Please try again.';
            }
        }

    // ========================================================================
    // B. UPDATE EXISTING CHILD (Ownership Enforced)
    // ========================================================================
    } elseif ($formAction === 'update') {
        $targetId   = (int)($_POST['child_id'] ?? 0);
        $name       = sanitizeInput($_POST['name'] ?? '');
        $dob        = sanitizeInput($_POST['dob'] ?? '');
        $gender     = in_array($_POST['gender'] ?? '', ['male', 'female', 'other']) ? $_POST['gender'] : '';
        $bloodGroup = sanitizeInput($_POST['blood_group'] ?? '');

        if ($targetId <= 0)     $errors[] = 'Invalid child ID.';
        if (empty($name))       $errors[] = 'Child name is required.';
        elseif (mb_strlen($name) > 100) $errors[] = 'Child name is too long (100 characters maximum).';
        if ($dob === '' || !isValidYmd($dob)) $errors[] = 'Enter a valid date of birth.';
        elseif (strtotime($dob) > strtotime('today')) $errors[] = 'Date of birth cannot be in the future.';
        elseif (strtotime($dob) < strtotime('-18 years')) $errors[] = 'VMS is for children under 18. Please check the date of birth.';
        if (empty($gender))     $errors[] = 'Gender is required.';
        if (!in_array($bloodGroup, $bloodGroups, true)) $errors[] = 'Select a blood group.';

        // Strict ownership check
        if (empty($errors)) {
            $checkStmt = $pdo->prepare('SELECT id FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
            $checkStmt->execute([$targetId, $parentId]);
            if (!$checkStmt->fetch()) {
                setFlash('danger', 'Unauthorized access to that child record.');
                header('Location: ' . url('parent/children.php'));
                exit;
            }

            try {
                $stmt = $pdo->prepare('UPDATE children SET name = ?, dob = ?, gender = ?, blood_group = ? WHERE id = ? AND parent_id = ?');
                $stmt->execute([$name, $dob, $gender, $bloodGroup, $targetId, $parentId]);

                setFlash('success', 'Profile details for ' . $name . ' updated successfully!');
                header('Location: ' . url('parent/children.php'));
                exit;
            } catch (PDOException $e) {
                error_log('[UPDATE CHILD ERROR] ' . $e->getMessage());
                $errors[] = 'A database error occurred while updating the child profile. Please try again.';
            }
        }

    // ========================================================================
    // C. DELETE CHILD (Ownership Enforced)
    // ========================================================================
    } elseif ($formAction === 'delete') {
        $targetId = (int)($_POST['child_id'] ?? 0);

        if ($targetId > 0) {
            try {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE child_id = ? AND parent_id = ? AND vaccination_status = \'vaccinated\'');
                $chk->execute([$targetId, $parentId]);

                if ((int)$chk->fetchColumn() > 0) {
                    // Recorded vaccinations are medical records: do not wipe them by deleting the profile.
                    setFlash('warning', 'This child has recorded vaccinations, so the profile cannot be deleted. You can edit the details instead, or ask the helpdesk.');
                } else {
                    $pdo->beginTransaction();
                    $pdo->prepare('DELETE FROM bookings WHERE child_id = ? AND parent_id = ?')->execute([$targetId, $parentId]);
                    $dStmt = $pdo->prepare('DELETE FROM children WHERE id = ? AND parent_id = ?');
                    $dStmt->execute([$targetId, $parentId]);
                    $pdo->commit();

                    setFlash($dStmt->rowCount() > 0 ? 'success' : 'warning',
                             $dStmt->rowCount() > 0 ? 'The child profile has been removed.' : 'That child profile was not found.');
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('[DELETE CHILD ERROR] ' . $e->getMessage());
                setFlash('danger', 'Failed to remove child profile. It may have linked active records.');
            }
        }
        header('Location: ' . url('parent/children.php'));
        exit;
    }
}

// After a failed add/update, show the same form again with what the parent typed.
$old = [];
if (!empty($errors) && in_array($formAction ?? '', ['add', 'update'], true)) {
    $action = ($formAction === 'add') ? 'add' : 'edit';
    $childId = (int)($_POST['child_id'] ?? 0);
    foreach (['name', 'dob', 'gender', 'blood_group'] as $f) {
        $old[$f] = (string)($_POST[$f] ?? '');
    }
}

// ----------------------------------------------------------------------------
// 2. VIEW MODE: SINGLE CHILD TIMELINE
// ----------------------------------------------------------------------------
if ($action === 'view' && $childId > 0) {
    $cStmt = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
    $cStmt->execute([$childId, $parentId]);
    $child = $cStmt->fetch();

    if (!$child) {
        setFlash('danger', 'Child record not found.');
        header('Location: ' . url('parent/children.php'));
        exit;
    }

    $allVaccines = $pdo->query('SELECT * FROM vaccines WHERE status = \'available\' ORDER BY recommended_age_days ASC')->fetchAll();

    // Fetch existing bookings for this child
    $bStmt = $pdo->prepare('SELECT b.*, h.name AS hospital_name FROM bookings b JOIN hospitals h ON b.hospital_id = h.id WHERE b.child_id = ? ORDER BY b.created_at ASC');
    $bStmt->execute([$childId]);
    $existingBookings = $bStmt->fetchAll();

    $bookingHistory = [];
    foreach ($existingBookings as $eb) {
        // A rejected or missed request must not hide the "Book" button.
        if ($eb['admin_status'] === 'rejected' || $eb['vaccination_status'] === 'not_vaccinated') {
            continue;
        }
        $vid = (int)$eb['vaccine_id'];
        if (isset($bookingHistory[$vid]) && $bookingHistory[$vid]['vaccination_status'] === 'vaccinated'
            && $eb['vaccination_status'] !== 'vaccinated') {
            continue;
        }
        $bookingHistory[$vid] = $eb;
    }

    $pageTitle = 'Immunization Schedule: ' . $child['name'];
    require_once __DIR__ . '/../includes/header.php';
    uiStyles();
    ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <a href="<?= url('parent/children.php'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 0.5rem;"><?= uiIcon('back', 16); ?> Back to Children List</a>
                <h1><?= e($child['name']); ?>'s Immunization Timeline</h1>
                <p style="margin: 0;">National immunization schedule calculated dynamically from Date of Birth.</p>
            </div>
            <div>
                <a href="<?= url('parent/book.php?child_id=' . (int)$child['id']); ?>" class="btn btn-primary">
                    <?= uiIcon('plus', 16); ?> Book Vaccine Appointment
                </a>
            </div>
        </div>

        <!-- Child Summary Badge Card -->
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <span class="ui-avatar"><?= uiIcon('child', 24); ?></span>
                <div>
                    <h3 style="margin: 0;"><?= e($child['name']); ?></h3>
                    <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                        <strong>DOB:</strong> <?= formatDate($child['dob']); ?> &bull; 
                        <strong>Age:</strong> <?= formatAge($child['dob']); ?> (<?= calculateAgeInDays($child['dob']); ?> days) &bull; 
                        <strong>Gender:</strong> <?= ucfirst($child['gender']); ?> &bull; 
                        <strong>Blood Group:</strong> <?= e($child['blood_group']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Schedule Matrix Table -->
        <div class="card">
            <div class="card-header">
                <h3>Recommended Vaccines & Milestone Status</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vaccine</th>
                            <th>Recommended Age</th>
                            <th>Calculated Due Date</th>
                            <th>Status</th>
                            <th>Healthcare Center</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allVaccines as $vac): 
                            $vId = (int)$vac['id'];
                            $dueDate = calculateVaccineDueDate($child['dob'], (int)$vac['recommended_age_days']);
                            $isOverdue = isDateOverdue($dueDate);
                            $hasRecord = isset($bookingHistory[$vId]);
                            $isVaccinated = $hasRecord && $bookingHistory[$vId]['vaccination_status'] === 'vaccinated';
                            $isPending = $hasRecord && $bookingHistory[$vId]['admin_status'] === 'pending';
                            $isApproved = $hasRecord && $bookingHistory[$vId]['admin_status'] === 'approved' && $bookingHistory[$vId]['vaccination_status'] === 'pending';
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($vac['name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= (int)$vac['doses']; ?> <?= (int)$vac['doses'] === 1 ? 'dose' : 'doses'; ?></small>
                                </td>
                                <td><?= (int)$vac['recommended_age_days'] === 0 ? 'At Birth' : (int)$vac['recommended_age_days'] . ' days'; ?></td>
                                <td><strong><?= formatDate($dueDate); ?></strong></td>
                                <td>
                                    <?php if ($isVaccinated): ?>
                                        <span class="badge badge-success"><?= uiIcon('check', 14); ?> Vaccinated</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($bookingHistory[$vId]['vaccinated_on']); ?></small>
                                    <?php elseif ($isApproved): ?>
                                        <span class="badge badge-info">Scheduled</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($bookingHistory[$vId]['preferred_date']); ?></small>
                                    <?php elseif ($isPending): ?>
                                        <span class="badge badge-warning"><?= uiIcon('clock', 14); ?> Approval Pending</span>
                                    <?php elseif ($isOverdue): ?>
                                        <span class="badge badge-danger"><?= uiIcon('alert', 14); ?> Overdue</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= uiIcon('clock', 14); ?> Upcoming</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $hasRecord ? e($bookingHistory[$vId]['hospital_name']) : '<span style="color: var(--text-light);">&mdash;</span>'; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($isVaccinated): ?>
                                        <a href="<?= url('parent/reports.php?child_id=' . (int)$child['id']); ?>" class="btn btn-outline btn-sm">
                                            Certificate
                                        </a>
                                    <?php elseif (!$hasRecord): ?>
                                        <a href="<?= url('parent/book.php?child_id=' . (int)$child['id'] . '&vaccine_id=' . $vId); ?>" class="btn btn-primary btn-sm">
                                            Book Slot &rarr;
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url('parent/bookings.php'); ?>" class="btn btn-outline btn-sm">
                                            Track Booking
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ----------------------------------------------------------------------------
// 3. EDIT MODE
// ----------------------------------------------------------------------------
$editChild = null;
if ($action === 'edit' && $childId > 0) {
    $eStmt = $pdo->prepare('SELECT * FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
    $eStmt->execute([$childId, $parentId]);
    $editChild = $eStmt->fetch();
    if ($editChild && !empty($old)) {
        foreach ($old as $f => $val) { $editChild[$f] = $val; }
    }
}

// ----------------------------------------------------------------------------
// 4. LIST MODE: ALL CHILDREN BELONGING TO THIS PARENT
// ----------------------------------------------------------------------------
$stmt = $pdo->prepare(
    'SELECT c.*,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id AND b.vaccination_status = \'vaccinated\') AS doses_completed
     FROM children c
     WHERE c.parent_id = ?
     ORDER BY c.dob DESC'
);
$stmt->execute([$parentId]);
$childrenList = $stmt->fetchAll();

$pageTitle = 'My Children';
$pageDescription = 'Register and maintain your children details for immunization scheduling.';
require_once __DIR__ . '/../includes/header.php';
uiStyles();
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>My Children Profiles</h1>
            <p style="margin: 0;">Add and maintain child profiles to generate automated immunization schedules.</p>
        </div>

        <div>
            <?php if ($action === 'list'): ?>
                <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-primary">
                    <?= uiIcon('plus', 16); ?> Add New Child
                </a>
            <?php else: ?>
                <a href="<?= url('parent/children.php'); ?>" class="btn btn-outline">
                    <?= uiIcon('back', 16); ?> Back to Children List
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <div>
                <strong>Please resolve the following:</strong>
                <ul style="margin: 0.25rem 0 0 1.2rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- ====================================================================== -->
    <!-- VIEW A: ADD NEW CHILD FORM                                             -->
    <!-- ====================================================================== -->
    <?php if ($action === 'add'): ?>
        <div class="card" style="max-width: 650px; margin: 0 auto 2rem;">
            <div class="card-header">
                <h3>Register Infant / Child</h3>
            </div>
            <form action="<?= url('parent/children.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="add">

                <div class="form-group">
                    <label for="name" class="form-label required">Child Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Hamza Khan" value="<?= e($old['name'] ?? ''); ?>" maxlength="100" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="dob" class="form-label required">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control" max="<?= date('Y-m-d'); ?>" min="<?= date('Y-m-d', strtotime('-18 years')); ?>" value="<?= e($old['dob'] ?? ''); ?>" required>
                        <small class="form-hint">Used to calculate exact vaccination due dates</small>
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label required">Gender</label>
                        <select id="gender" name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?= ($old['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?= ($old['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?= ($old['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="blood_group" class="form-label required">Blood Group</label>
                    <select id="blood_group" name="blood_group" class="form-select" required>
                        <option value="">Select Blood Group</option>
                        <?php foreach ($bloodGroups as $bg): ?>
                            <option value="<?= $bg; ?>" <?= ($old['blood_group'] ?? '') === $bg ? 'selected' : ''; ?>><?= $bg; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <a href="<?= url('parent/children.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Child Profile &rarr;</button>
                </div>
            </form>
        </div>

    <!-- ====================================================================== -->
    <!-- VIEW B: EDIT CHILD FORM                                                -->
    <!-- ====================================================================== -->
    <?php elseif ($action === 'edit' && $editChild): ?>
        <div class="card" style="max-width: 650px; margin: 0 auto 2rem;">
            <div class="card-header">
                <h3>Edit Child: <?= e($editChild['name']); ?></h3>
            </div>
            <form action="<?= url('parent/children.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="child_id" value="<?= (int)$editChild['id']; ?>">

                <div class="form-group">
                    <label for="name" class="form-label required">Child Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= e($editChild['name']); ?>" required>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="dob" class="form-label required">Date of Birth</label>
                        <input type="date" id="dob" name="dob" class="form-control" max="<?= date('Y-m-d'); ?>" value="<?= e($editChild['dob']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label required">Gender</label>
                        <select id="gender" name="gender" class="form-select" required>
                            <option value="male" <?= $editChild['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?= $editChild['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?= $editChild['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="blood_group" class="form-label required">Blood Group</label>
                    <select id="blood_group" name="blood_group" class="form-select" required>
                        <?php foreach ($bloodGroups as $bg): ?>
                            <option value="<?= $bg; ?>" <?= $editChild['blood_group'] === $bg ? 'selected' : ''; ?>><?= $bg; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <a href="<?= url('parent/children.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Child Details &rarr;</button>
                </div>
            </form>
        </div>

    <!-- ====================================================================== -->
    <!-- VIEW C: CHILDREN LIST                                                  -->
    <!-- ====================================================================== -->
    <?php else: ?>
        <div class="card">
            <?php if (empty($childrenList)): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <?= uiIcon('child', 40); ?>
                    <h3>No Children Registered Yet</h3>
                    <p style="max-width: 420px; margin: 0 auto 1.25rem;">
                        Register your newborn or child to generate a personalized immunization schedule and book hospital appointments.
                    </p>
                    <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-primary">
                        <?= uiIcon('plus', 16); ?> Add Child Details
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Child Name</th>
                                <th>Date of Birth & Age</th>
                                <th>Gender / Blood Group</th>
                                <th>Immunizations Completed</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($childrenList as $ch): ?>
                                <tr>
                                    <td>
                                        <strong><a href="<?= url('parent/children.php?action=view&id=' . (int)$ch['id']); ?>"><?= e($ch['name']); ?></a></strong><br>
                                        <small style="color: var(--text-muted);">Added <?= formatDate($ch['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?= formatDate($ch['dob']); ?></strong><br>
                                        <small style="color: var(--text-muted);"><?= formatAge($ch['dob']); ?> (<?= calculateAgeInDays($ch['dob']); ?> days)</small>
                                    </td>
                                    <td>
                                        <?= ucfirst($ch['gender']); ?> &bull; 
                                        <span class="badge badge-info"><?= e($ch['blood_group']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?= uiIcon('check', 14); ?> <?= (int)$ch['doses_completed']; ?> doses taken</span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?= url('parent/children.php?action=view&id=' . (int)$ch['id']); ?>" class="btn btn-primary btn-sm" title="View Schedule">
                                            Timeline
                                        </a>
                                        <a href="<?= url('parent/children.php?action=edit&id=' . (int)$ch['id']); ?>" class="btn btn-outline btn-sm" title="Edit">
                                            <?= uiIcon('edit', 16); ?>
                                        </a>
                                        <form action="<?= url('parent/children.php'); ?>" method="POST" style="display: inline-block; margin-left: 0.25rem;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="child_id" value="<?= (int)$ch['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Profile"
                                                    onclick="return confirm(<?= htmlspecialchars(json_encode('Delete ' . $ch['name'] . "'s profile? This cannot be undone."), ENT_QUOTES); ?>);">
                                                Delete
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