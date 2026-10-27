<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/vaccinations.php
 * Description: Master Vaccination Timeline & Upcoming Child Immunization Log
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - View date and time of completed vaccinations.
 * - View upcoming scheduled appointments for all children across all facilities.
 * - Filterable by healthcare center, vaccine, and date ranges.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('admin');

$pdo = getDBConnection();
$activeView = $_GET['view'] ?? 'completed';

// Filter parameters
$filterHospital = (int)($_GET['hospital_id'] ?? 0);
$filterVaccine  = (int)($_GET['vaccine_id'] ?? 0);
$startDate      = sanitizeInput($_GET['start_date'] ?? '');
$endDate        = sanitizeInput($_GET['end_date'] ?? '');

// Hospitals and Vaccines for dropdown filters
$hospitals = $pdo->query('SELECT id, name FROM hospitals ORDER BY name ASC')->fetchAll();
$vaccines  = $pdo->query('SELECT id, name FROM vaccines ORDER BY name ASC')->fetchAll();

// ----------------------------------------------------------------------------
// 1. COMPLETED VACCINATIONS QUERY
// ----------------------------------------------------------------------------
if ($activeView === 'completed') {
    $sql = 'SELECT b.*,
                   c.name AS child_name, c.dob AS child_dob,
                   u.name AS parent_name, p.phone AS parent_phone,
                   h.name AS hospital_name, h.city AS hospital_city,
                   v.name AS vaccine_name
            FROM bookings b
            JOIN children c ON b.child_id = c.id
            JOIN parents p ON b.parent_id = p.id
            JOIN users u ON p.user_id = u.id
            JOIN hospitals h ON b.hospital_id = h.id
            JOIN vaccines v ON b.vaccine_id = v.id
            WHERE b.vaccination_status = "vaccinated"';

    $params = [];
    if ($filterHospital > 0) {
        $sql .= ' AND b.hospital_id = ?';
        $params[] = $filterHospital;
    }
    if ($filterVaccine > 0) {
        $sql .= ' AND b.vaccine_id = ?';
        $params[] = $filterVaccine;
    }
    if (!empty($startDate)) {
        $sql .= ' AND DATE(b.vaccinated_on) >= ?';
        $params[] = $startDate;
    }
    if (!empty($endDate)) {
        $sql .= ' AND DATE(b.vaccinated_on) <= ?';
        $params[] = $endDate;
    }
    $sql .= ' ORDER BY b.vaccinated_on DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

// ----------------------------------------------------------------------------
// 2. UPCOMING SCHEDULED APPOINTMENTS QUERY
// ----------------------------------------------------------------------------
} else {
    $sql = 'SELECT b.*,
                   c.name AS child_name, c.dob AS child_dob,
                   u.name AS parent_name, p.phone AS parent_phone,
                   h.name AS hospital_name, h.city AS hospital_city,
                   v.name AS vaccine_name
            FROM bookings b
            JOIN children c ON b.child_id = c.id
            JOIN parents p ON b.parent_id = p.id
            JOIN users u ON p.user_id = u.id
            JOIN hospitals h ON b.hospital_id = h.id
            JOIN vaccines v ON b.vaccine_id = v.id
            WHERE b.admin_status = "approved" AND b.vaccination_status = "pending"';

    $params = [];
    if ($filterHospital > 0) {
        $sql .= ' AND b.hospital_id = ?';
        $params[] = $filterHospital;
    }
    if ($filterVaccine > 0) {
        $sql .= ' AND b.vaccine_id = ?';
        $params[] = $filterVaccine;
    }
    if (!empty($startDate)) {
        $sql .= ' AND b.preferred_date >= ?';
        $params[] = $startDate;
    }
    if (!empty($endDate)) {
        $sql .= ' AND b.preferred_date <= ?';
        $params[] = $endDate;
    }
    $sql .= ' ORDER BY b.preferred_date ASC, b.time_slot ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}

// Counts for quick tabs
$countCompleted = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE vaccination_status = "vaccinated"')->fetchColumn();
$countUpcoming  = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE admin_status = "approved" AND vaccination_status = "pending"')->fetchColumn();

$pageTitle = 'Vaccination Logs & Upcoming Schedules';
$pageDescription = 'Monitor completed vaccinations and upcoming appointment schedules for all children.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <h1>Vaccination Records & Upcoming Dates</h1>
        <p style="margin: 0;">Comprehensive clinical verification of administered doses and upcoming child appointments.</p>
    </div>

    <!-- View Switcher Tabs -->
    <div class="form-tabs" style="max-width: 550px; margin-bottom: 1.5rem;">
        <a href="<?= url('admin/vaccinations.php?view=completed'); ?>" 
           class="form-tab-btn <?= $activeView === 'completed' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#10004; Administered Doses (<?= $countCompleted; ?>)
        </a>
        <a href="<?= url('admin/vaccinations.php?view=upcoming'); ?>" 
           class="form-tab-btn <?= $activeView === 'upcoming' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#128197; Upcoming Schedule (<?= $countUpcoming; ?>)
        </a>
    </div>

    <!-- Filter Console -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="<?= url('admin/vaccinations.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <input type="hidden" name="view" value="<?= e($activeView); ?>">

            <div style="flex: 1; min-width: 170px;">
                <label for="hospital_id" class="form-label">Healthcare Center</label>
                <select id="hospital_id" name="hospital_id" class="form-select">
                    <option value="0">All Facilities</option>
                    <?php foreach ($hospitals as $h): ?>
                        <option value="<?= (int)$h['id']; ?>" <?= $filterHospital === (int)$h['id'] ? 'selected' : ''; ?>>
                            <?= e($h['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 170px;">
                <label for="vaccine_id" class="form-label">Vaccine</label>
                <select id="vaccine_id" name="vaccine_id" class="form-select">
                    <option value="0">All Vaccines</option>
                    <?php foreach ($vaccines as $v): ?>
                        <option value="<?= (int)$v['id']; ?>" <?= $filterVaccine === (int)$v['id'] ? 'selected' : ''; ?>>
                            <?= e($v['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 130px;">
                <label for="start_date" class="form-label">From Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate); ?>">
            </div>

            <div style="flex: 1; min-width: 130px;">
                <label for="end_date" class="form-label">To Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= e($endDate); ?>">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Apply</button>
                <?php if ($filterHospital > 0 || $filterVaccine > 0 || !empty($startDate) || !empty($endDate)): ?>
                    <a href="<?= url('admin/vaccinations.php?view=' . $activeView); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <?php if (empty($records)): ?>
            <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                <p>No vaccination records found matching your filter selections.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child Full Name</th>
                            <th>Vaccine</th>
                            <th>Center & Location</th>
                            <th>Parent Contact</th>
                            <?php if ($activeView === 'completed'): ?>
                                <th>Vaccination Date & Time</th>
                                <th>Clinical Observation / Remarks</th>
                            <?php else: ?>
                                <th>Scheduled Date & Slot</th>
                                <th>Admin Approval Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td>
                                    <strong><a href="<?= url('admin/children.php?id=' . (int)$r['child_id']); ?>"><?= e($r['child_name']); ?></a></strong><br>
                                    <small style="color: var(--text-muted);"><?= formatAge($r['child_dob']); ?> old</small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e($r['vaccine_name']); ?></span>
                                </td>
                                <td>
                                    <strong><?= e($r['hospital_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">&#128205; <?= e($r['hospital_city']); ?></small>
                                </td>
                                <td>
                                    <?= e($r['parent_name']); ?><br>
                                    <small style="color: var(--text-muted);">&#128222; <?= e($r['parent_phone']); ?></small>
                                </td>

                                <?php if ($activeView === 'completed'): ?>
                                    <td>
                                        <strong style="color: var(--success);">&#10004; <?= formatDate($r['vaccinated_on'], 'M d, Y'); ?></strong><br>
                                        <small style="color: var(--text-muted);">&#128337; <?= formatDate($r['vaccinated_on'], 'h:i A'); ?></small>
                                    </td>
                                    <td>
                                        <small><?= e($r['remarks'] ?: 'Successfully administered with no immediate contraindications.'); ?></small>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <strong><?= formatDate($r['preferred_date']); ?></strong><br>
                                        <small style="color: var(--text-muted);">&#128337; <?= e($r['time_slot']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">&#10004; Approved</span><br>
                                        <small style="color: var(--text-muted);">Awaiting hospital visit</small>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
