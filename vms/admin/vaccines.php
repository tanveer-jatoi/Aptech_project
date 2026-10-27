<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/vaccines.php
 * Description: Vaccine Catalog & Network Availability Management
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - List all vaccines with recommended age calculations, doses, and statuses.
 * - Toggle availability status (Available / Unavailable) with one-click CSRF form.
 * - Full CRUD: Add new immunization series and update medical descriptions.
 * - Flash toast checkmarks on all updates.
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
// 1. HANDLE POST ACTIONS (Toggle Status, Add, Update)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . url('admin/vaccines.php'));
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';

    // ========================================================================
    // A. ONE-CLICK QUICK STATUS TOGGLE (Available <-> Unavailable)
    // ========================================================================
    if ($formAction === 'toggle_status') {
        $vaccineId = (int)($_POST['vaccine_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['available', 'unavailable']) ? $_POST['new_status'] : 'available';

        if ($vaccineId > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE vaccines SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $vaccineId]);

                setFlash('success', 'Vaccine status changed to ' . ucfirst($newStatus) . ' successfully!');
            } catch (PDOException $e) {
                error_log('[VACCINE STATUS TOGGLE ERROR] ' . $e->getMessage());
                setFlash('danger', 'Failed to update vaccine status: ' . $e->getMessage());
            }
        }
        header('Location: ' . url('admin/vaccines.php'));
        exit;
    }

    // ========================================================================
    // B. ADD NEW VACCINE
    // ========================================================================
    elseif ($formAction === 'add') {
        $name        = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $ageDays     = (int)($_POST['recommended_age_days'] ?? 0);
        $doses       = (int)($_POST['doses'] ?? 1);
        $status      = in_array($_POST['status'] ?? '', ['available', 'unavailable']) ? $_POST['status'] : 'available';

        if (empty($name))        $errors[] = 'Vaccine name is required.';
        if (empty($description)) $errors[] = 'Medical description/indications are required.';
        if ($doses < 1)          $errors[] = 'Total doses must be at least 1.';

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert into vaccines catalog
                $stmt = $pdo->prepare('INSERT INTO vaccines (name, description, recommended_age_days, doses, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$name, $description, $ageDays, $doses, $status]);
                $newVaccineId = (int)$pdo->lastInsertId();

                // Automatically link new vaccine to all active hospitals
                $hStmt = $pdo->query('SELECT id FROM hospitals');
                $hospitals = $hStmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($hospitals)) {
                    $linkStmt = $pdo->prepare('INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status) VALUES (?, ?, ?)');
                    foreach ($hospitals as $hId) {
                        $linkStmt->execute([$hId, $newVaccineId, $status]);
                    }
                }

                $pdo->commit();
                setFlash('success', 'Vaccine "' . $name . '" added to the immunization schedule!');
                header('Location: ' . url('admin/vaccines.php'));
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[ADD VACCINE ERROR] ' . $e->getMessage());
                $errors[] = 'Database error adding vaccine: ' . $e->getMessage();
            }
        }
    }

    // ========================================================================
    // C. UPDATE EXISTING VACCINE
    // ========================================================================
    elseif ($formAction === 'update') {
        $vaccineId   = (int)($_POST['vaccine_id'] ?? 0);
        $name        = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $ageDays     = (int)($_POST['recommended_age_days'] ?? 0);
        $doses       = (int)($_POST['doses'] ?? 1);
        $status      = in_array($_POST['status'] ?? '', ['available', 'unavailable']) ? $_POST['status'] : 'available';

        if ($vaccineId <= 0)     $errors[] = 'Invalid vaccine ID.';
        if (empty($name))        $errors[] = 'Vaccine name is required.';
        if (empty($description)) $errors[] = 'Medical description is required.';
        if ($doses < 1)          $errors[] = 'Total doses must be at least 1.';

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare('UPDATE vaccines SET name = ?, description = ?, recommended_age_days = ?, doses = ?, status = ? WHERE id = ?');
                $stmt->execute([$name, $description, $ageDays, $doses, $status, $vaccineId]);

                setFlash('success', 'Vaccine details for "' . $name . '" updated successfully!');
                header('Location: ' . url('admin/vaccines.php'));
                exit;
            } catch (PDOException $e) {
                error_log('[UPDATE VACCINE ERROR] ' . $e->getMessage());
                $errors[] = 'Database error updating vaccine: ' . $e->getMessage();
            }
        }
    }
}

// ----------------------------------------------------------------------------
// 2. FETCH VACCINES FOR DISPLAY
// ----------------------------------------------------------------------------
$search = sanitizeInput($_GET['search'] ?? '');
$filterStatus = sanitizeInput($_GET['status'] ?? '');

$sql = 'SELECT v.*,
               (SELECT COUNT(*) FROM bookings b WHERE b.vaccine_id = v.id) AS total_administered,
               (SELECT COUNT(*) FROM hospital_vaccines hv WHERE hv.vaccine_id = v.id AND hv.status = "available") AS hospitals_stocking
        FROM vaccines v
        WHERE 1=1';

$params = [];
if (!empty($search)) {
    $sql .= ' AND (v.name LIKE ? OR v.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if (!empty($filterStatus)) {
    $sql .= ' AND v.status = ?';
    $params[] = $filterStatus;
}
$sql .= ' ORDER BY v.recommended_age_days ASC, v.name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vaccinesList = $stmt->fetchAll();

// Target vaccine for edit view
$editVaccine = null;
if ($action === 'edit' && $editId > 0) {
    $eStmt = $pdo->prepare('SELECT * FROM vaccines WHERE id = ?');
    $eStmt->execute([$editId]);
    $editVaccine = $eStmt->fetch();
}

/**
 * Helper to display human-friendly recommended age
 */
function displayRecommendedAge(int $days): string
{
    if ($days === 0) {
        return 'At Birth (Day 0)';
    }
    if ($days < 30) {
        return $days . ' Days';
    }
    if ($days % 7 === 0 && $days < 60) {
        return ($days / 7) . ' Weeks';
    }
    if ($days < 365) {
        $months = round($days / 30.4);
        return $months . ' Months (' . $days . 'd)';
    }
    $years = round($days / 365, 1);
    return $years . ' Years (' . $days . 'd)';
}

$pageTitle = 'Vaccine Catalog & Schedules';
$pageDescription = 'Configure recommended pediatric vaccines, doses, and network availability.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
        <div>
            <h1>Vaccine Schedule & Catalog</h1>
            <p style="margin: 0;">National immunization schedule, dosage rules, and general stock status.</p>
        </div>

        <div>
            <?php if ($action === 'list'): ?>
                <a href="<?= url('admin/vaccines.php?action=add'); ?>" class="btn btn-primary">
                    &#10010; Add New Vaccine
                </a>
            <?php else: ?>
                <a href="<?= url('admin/vaccines.php'); ?>" class="btn btn-outline">
                    &larr; Back to Vaccine Catalog
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
    <!-- VIEW A: ADD NEW VACCINE FORM                                           -->
    <!-- ====================================================================== -->
    <?php if ($action === 'add'): ?>
        <div class="card" style="max-width: 750px; margin: 0 auto 2rem;">
            <div class="card-header">
                <h3>Add Vaccine to National Schedule</h3>
            </div>
            <form action="<?= url('admin/vaccines.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="add">

                <div class="form-group">
                    <label for="name" class="form-label required">Vaccine Name & Acronym</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Varicella (Chickenpox)" required>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="recommended_age_days" class="form-label required">Recommended Age (Days)</label>
                        <input type="number" id="recommended_age_days" name="recommended_age_days" class="form-control" min="0" placeholder="0 = At birth, 42 = 6wks" required>
                        <small class="form-hint">Days after DOB when dose is due</small>
                    </div>

                    <div class="form-group">
                        <label for="doses" class="form-label required">Total Doses in Series</label>
                        <input type="number" id="doses" name="doses" class="form-control" min="1" max="10" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label required">Network Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label required">Medical Description & Target Illnesses</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Clinical notes, diseases prevented, and contraindications..." required></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <a href="<?= url('admin/vaccines.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Vaccine &rarr;</button>
                </div>
            </form>
        </div>

    <!-- ====================================================================== -->
    <!-- VIEW B: EDIT EXISTING VACCINE FORM                                     -->
    <!-- ====================================================================== -->
    <?php elseif ($action === 'edit' && $editVaccine): ?>
        <div class="card" style="max-width: 750px; margin: 0 auto 2rem;">
            <div class="card-header">
                <h3>Edit Vaccine: <?= e($editVaccine['name']); ?></h3>
            </div>
            <form action="<?= url('admin/vaccines.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="vaccine_id" value="<?= (int)$editVaccine['id']; ?>">

                <div class="form-group">
                    <label for="name" class="form-label required">Vaccine Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= e($editVaccine['name']); ?>" required>
                </div>

                <div class="grid grid-3">
                    <div class="form-group">
                        <label for="recommended_age_days" class="form-label required">Recommended Age (Days)</label>
                        <input type="number" id="recommended_age_days" name="recommended_age_days" class="form-control" min="0" value="<?= (int)$editVaccine['recommended_age_days']; ?>" required>
                        <small class="form-hint">E.g. 0, 42 (6w), 98 (14w), 270 (9m)</small>
                    </div>

                    <div class="form-group">
                        <label for="doses" class="form-label required">Total Doses</label>
                        <input type="number" id="doses" name="doses" class="form-control" min="1" max="10" value="<?= (int)$editVaccine['doses']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label required">Network Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="available" <?= $editVaccine['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?= $editVaccine['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label required">Medical Description & Indications</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required><?= e($editVaccine['description']); ?></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <a href="<?= url('admin/vaccines.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Vaccine &rarr;</button>
                </div>
            </form>
        </div>

    <!-- ====================================================================== -->
    <!-- VIEW C: VACCINES LIST & STATUS TOGGLE                                  -->
    <!-- ====================================================================== -->
    <?php else: ?>
        <!-- Search & Filter Bar -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form action="<?= url('admin/vaccines.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
                <div style="flex: 2; min-width: 220px;">
                    <label for="search" class="form-label">Search Vaccine</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Vaccine name or disease..." value="<?= e($search); ?>">
                </div>

                <div style="flex: 1; min-width: 160px;">
                    <label for="filter_status" class="form-label">Availability</label>
                    <select id="filter_status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="available" <?= $filterStatus === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="unavailable" <?= $filterStatus === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Filter</button>
                    <?php if (!empty($search) || !empty($filterStatus)): ?>
                        <a href="<?= url('admin/vaccines.php'); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Vaccines Table -->
        <div class="card">
            <?php if (empty($vaccinesList)): ?>
                <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                    <p>No vaccines found matching your filter criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vaccine Name & Description</th>
                                <th>Recommended Schedule</th>
                                <th>Doses</th>
                                <th>Hospital Stock</th>
                                <th>Current Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccinesList as $v): ?>
                                <tr>
                                    <td style="max-width: 320px;">
                                        <strong><?= e($v['name']); ?></strong><br>
                                        <small style="color: var(--text-muted); line-height: 1.4; display: block; margin-top: 0.2rem;">
                                            <?= e($v['description']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= displayRecommendedAge((int)$v['recommended_age_days']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= (int)$v['doses']; ?> <?= (int)$v['doses'] === 1 ? 'dose' : 'doses'; ?></span>
                                    </td>
                                    <td>
                                        <small><?= (int)$v['hospitals_stocking']; ?> hospitals carrying</small>
                                    </td>
                                    <td>
                                        <!-- Quick Status Toggle Form -->
                                        <form action="<?= url('admin/vaccines.php'); ?>" method="POST" style="display: inline-block;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="form_action" value="toggle_status">
                                            <input type="hidden" name="vaccine_id" value="<?= (int)$v['id']; ?>">
                                            <?php if ($v['status'] === 'available'): ?>
                                                <input type="hidden" name="new_status" value="unavailable">
                                                <button type="submit" class="badge badge-success" style="cursor: pointer; border: none;" title="Click to set Unavailable">
                                                    &#10004; Available
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_status" value="available">
                                                <button type="submit" class="badge badge-danger" style="cursor: pointer; border: none;" title="Click to set Available">
                                                    &#10006; Unavailable
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?= url('admin/vaccines.php?action=edit&id=' . (int)$v['id']); ?>" class="btn btn-outline btn-sm">
                                            &#9998; Edit
                                        </a>
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
