<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/bookings.php
 * Description: Master Bookings Registry Across All Facilities & Parents
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - View all bookings made by parents across all hospitals.
 * - Multi-criteria filters: Admin Status, Clinical Vaccination Status, Hospital, Date.
 * - Comprehensive appointment audit trail including remarks and timestamps.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('admin');

$pdo = getDBConnection();

// Filters
$search          = sanitizeInput($_GET['search'] ?? '');
$adminStatus     = sanitizeInput($_GET['admin_status'] ?? '');
$vacStatus       = sanitizeInput($_GET['vac_status'] ?? '');
$filterHospital  = (int)($_GET['hospital_id'] ?? 0);
$filterDate      = sanitizeInput($_GET['date'] ?? '');

// Fetch dropdown lists
$hospitalsList = $pdo->query('SELECT id, name FROM hospitals ORDER BY name ASC')->fetchAll();

// Build Query
$sql = 'SELECT b.*,
               c.name AS child_name, c.dob AS child_dob, c.blood_group,
               p.phone AS parent_phone,
               u.name AS parent_name, u.email AS parent_email,
               h.name AS hospital_name, h.city AS hospital_city,
               v.name AS vaccine_name, v.doses AS vaccine_doses
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN parents p ON b.parent_id = p.id
        JOIN users u ON p.user_id = u.id
        JOIN hospitals h ON b.hospital_id = h.id
        JOIN vaccines v ON b.vaccine_id = v.id
        WHERE 1=1';

$params = [];

if (!empty($search)) {
    $sql .= ' AND (c.name LIKE ? OR u.name LIKE ? OR h.name LIKE ? OR v.name LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($adminStatus)) {
    $sql .= ' AND b.admin_status = ?';
    $params[] = $adminStatus;
}

if (!empty($vacStatus)) {
    $sql .= ' AND b.vaccination_status = ?';
    $params[] = $vacStatus;
}

if ($filterHospital > 0) {
    $sql .= ' AND b.hospital_id = ?';
    $params[] = $filterHospital;
}

if (!empty($filterDate)) {
    $sql .= ' AND b.preferred_date = ?';
    $params[] = $filterDate;
}

$sql .= ' ORDER BY b.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Master Bookings Registry';
$pageDescription = 'Comprehensive log of all parent vaccination appointments across all pediatric facilities.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Master Appointment Registry</h1>
            <p style="margin: 0;">Audit and trace all vaccination bookings from initial parent request to hospital completion.</p>
        </div>

        <div>
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-outline btn-sm">
                &#9888; Review Pending Requests &rarr;
            </a>
        </div>
    </div>

    <!-- Filter Console -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="<?= url('admin/bookings.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <div style="flex: 2; min-width: 200px;">
                <label for="search" class="form-label">Search Keywords</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Child, parent, clinic, or vaccine..." value="<?= e($search); ?>">
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label for="admin_status" class="form-label">Admin Approval</label>
                <select id="admin_status" name="admin_status" class="form-select">
                    <option value="">All Approvals</option>
                    <option value="pending" <?= $adminStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?= $adminStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?= $adminStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label for="vac_status" class="form-label">Clinical Status</label>
                <select id="vac_status" name="vac_status" class="form-select">
                    <option value="">All Clinical</option>
                    <option value="pending" <?= $vacStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="vaccinated" <?= $vacStatus === 'vaccinated' ? 'selected' : ''; ?>>Vaccinated</option>
                    <option value="not_vaccinated" <?= $vacStatus === 'not_vaccinated' ? 'selected' : ''; ?>>Not Vaccinated</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label for="hospital_id" class="form-label">Facility</label>
                <select id="hospital_id" name="hospital_id" class="form-select">
                    <option value="0">All Facilities</option>
                    <?php foreach ($hospitalsList as $h): ?>
                        <option value="<?= (int)$h['id']; ?>" <?= $filterHospital === (int)$h['id'] ? 'selected' : ''; ?>>
                            <?= e($h['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 130px;">
                <label for="date" class="form-label">Appointment Date</label>
                <input type="date" id="date" name="date" class="form-control" value="<?= e($filterDate); ?>">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Filter</button>
                <?php if (!empty($search) || !empty($adminStatus) || !empty($vacStatus) || $filterHospital > 0 || !empty($filterDate)): ?>
                    <a href="<?= url('admin/bookings.php'); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Bookings Table Card -->
    <div class="card">
        <div class="card-header">
            <h3>Total Records: <?= count($bookings); ?></h3>
        </div>

        <?php if (empty($bookings)): ?>
            <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                <p>No appointment records match your search filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Child Details</th>
                            <th>Parent Details</th>
                            <th>Vaccine & Facility</th>
                            <th>Slot Date & Time</th>
                            <th>Admin Status</th>
                            <th>Clinical State</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><small style="color: var(--text-muted);">#<?= (int)$b['id']; ?></small></td>
                                <td>
                                    <strong><a href="<?= url('admin/children.php?id=' . (int)$b['child_id']); ?>"><?= e($b['child_name']); ?></a></strong><br>
                                    <small style="color: var(--text-muted);"><?= formatAge($b['child_dob']); ?> old</small>
                                </td>
                                <td>
                                    <?= e($b['parent_name']); ?><br>
                                    <small style="color: var(--text-muted);"><?= e($b['parent_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e($b['vaccine_name']); ?></span><br>
                                    <strong><?= e($b['hospital_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($b['hospital_city']); ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($b['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($b['time_slot']); ?></small>
                                </td>
                                <td>
                                    <?php if ($b['admin_status'] === 'approved'): ?>
                                        <span class="badge badge-success">&#10004; Approved</span>
                                    <?php elseif ($b['admin_status'] === 'pending'): ?>
                                        <span class="badge badge-warning">&#9203; Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">&#10006; Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['vaccination_status'] === 'vaccinated'): ?>
                                        <span class="badge badge-success">&#10004; Vaccinated</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($b['vaccinated_on'], 'd-m-Y'); ?></small>
                                    <?php elseif ($b['vaccination_status'] === 'not_vaccinated'): ?>
                                        <span class="badge badge-danger">&#10006; Missed / Not Done</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">&#9203; Awaiting Dose</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 200px;">
                                    <small style="color: var(--text-muted);"><?= e($b['remarks'] ?: '&mdash;'); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
