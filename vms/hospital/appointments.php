<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: hospital/appointments.php
 * Description: Hospital Patient Appointments Queue (Approved by Admin)
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - View appointments approved by admin assigned specifically to this facility.
 * - Filter by Today's Schedule, Upcoming, Completed Vaccinations, and Missed visits.
 * - Direct clinical navigation to record dose administration and patient remarks.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('hospital');

$pdo = getDBConnection();
$userId = currentUserId();

// Fetch Hospital ID
$hStmt = $pdo->prepare('SELECT id, name FROM hospitals WHERE user_id = ? LIMIT 1');
$hStmt->execute([$userId]);
$hospital = $hStmt->fetch();

if (!$hospital) {
    setFlash('danger', 'Facility profile not found.');
    header('Location: ' . url('login.php'));
    exit;
}

$hospitalId = (int)$hospital['id'];
$activeTab  = $_GET['tab'] ?? 'upcoming';
$search     = sanitizeInput($_GET['search'] ?? '');
$filterDate = sanitizeInput($_GET['date'] ?? '');

// Base Query: Strictly enforce hospital_id ownership and admin approval
$sql = 'SELECT b.*,
               c.name AS child_name, c.dob AS child_dob, c.gender AS child_gender, c.blood_group,
               p.phone AS parent_phone, p.address AS parent_address,
               u.name AS parent_name, u.email AS parent_email,
               v.name AS vaccine_name, v.doses AS vaccine_doses
        FROM bookings b
        JOIN children c ON b.child_id = c.id
        JOIN parents p ON b.parent_id = p.id
        JOIN users u ON p.user_id = u.id
        JOIN vaccines v ON b.vaccine_id = v.id
        WHERE b.hospital_id = ? AND b.admin_status = "approved"';

$params = [$hospitalId];

// Tab Filter
if ($activeTab === 'today') {
    $sql .= ' AND b.preferred_date = CURDATE() AND b.vaccination_status = "pending"';
} elseif ($activeTab === 'upcoming') {
    $sql .= ' AND b.vaccination_status = "pending"';
} elseif ($activeTab === 'vaccinated') {
    $sql .= ' AND b.vaccination_status = "vaccinated"';
} elseif ($activeTab === 'not_vaccinated') {
    $sql .= ' AND b.vaccination_status = "not_vaccinated"';
}

if (!empty($search)) {
    $sql .= ' AND (c.name LIKE ? OR u.name LIKE ? OR v.name LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filterDate)) {
    $sql .= ' AND b.preferred_date = ?';
    $params[] = $filterDate;
}

$sql .= ' ORDER BY b.preferred_date ASC, b.time_slot ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Badge counts for tabs
$countToday = (int)$pdo->query(
    'SELECT COUNT(*) FROM bookings WHERE hospital_id = ' . $hospitalId . ' AND admin_status = "approved" AND preferred_date = CURDATE() AND vaccination_status = "pending"'
)->fetchColumn();

$countUpcoming = (int)$pdo->query(
    'SELECT COUNT(*) FROM bookings WHERE hospital_id = ' . $hospitalId . ' AND admin_status = "approved" AND vaccination_status = "pending"'
)->fetchColumn();

$countVaccinated = (int)$pdo->query(
    'SELECT COUNT(*) FROM bookings WHERE hospital_id = ' . $hospitalId . ' AND vaccination_status = "vaccinated"'
)->fetchColumn();

$countMissed = (int)$pdo->query(
    'SELECT COUNT(*) FROM bookings WHERE hospital_id = ' . $hospitalId . ' AND vaccination_status = "not_vaccinated"'
)->fetchColumn();

$pageTitle = 'Patient Appointments: ' . $hospital['name'];
$pageDescription = 'Admin-approved patient appointment queue and vaccination records.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Patient Appointments Queue</h1>
            <p style="margin: 0;">Admin-approved appointments scheduled for immunization at <?= e($hospital['name']); ?>.</p>
        </div>

        <div>
            <a href="<?= url('hospital/vaccine_stock.php'); ?>" class="btn btn-outline btn-sm">
                Verify Vaccine Stock &rarr;
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="form-tabs" style="max-width: 750px; margin-bottom: 1.5rem;">
        <a href="<?= url('hospital/appointments.php?tab=today'); ?>" 
           class="form-tab-btn <?= $activeTab === 'today' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            Today's Queue (<?= $countToday; ?>)
        </a>
        <a href="<?= url('hospital/appointments.php?tab=upcoming'); ?>" 
           class="form-tab-btn <?= $activeTab === 'upcoming' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#9203; Pending Visits (<?= $countUpcoming; ?>)
        </a>
        <a href="<?= url('hospital/appointments.php?tab=vaccinated'); ?>" 
           class="form-tab-btn <?= $activeTab === 'vaccinated' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#10004; Vaccinated (<?= $countVaccinated; ?>)
        </a>
        <a href="<?= url('hospital/appointments.php?tab=not_vaccinated'); ?>" 
           class="form-tab-btn <?= $activeTab === 'not_vaccinated' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#10006; Missed (<?= $countMissed; ?>)
        </a>
        <a href="<?= url('hospital/appointments.php?tab=all'); ?>" 
           class="form-tab-btn <?= $activeTab === 'all' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            All
        </a>
    </div>

    <!-- Search / Filter Bar -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="<?= url('hospital/appointments.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <input type="hidden" name="tab" value="<?= e($activeTab); ?>">

            <div style="flex: 2; min-width: 220px;">
                <label for="search" class="form-label">Search Patient / Parent</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Child name, parent, or vaccine..." value="<?= e($search); ?>">
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label for="date" class="form-label">Specific Date</label>
                <input type="date" id="date" name="date" class="form-control" value="<?= e($filterDate); ?>">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Filter</button>
                <?php if (!empty($search) || !empty($filterDate)): ?>
                    <a href="<?= url('hospital/appointments.php?tab=' . $activeTab); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Appointments Table -->
    <div class="card">
        <?php if (empty($appointments)): ?>
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <span style="font-size: 2.2rem; display: block; margin-bottom: 0.5rem;"></span>
                <p style="font-size: 1.1rem; margin-bottom: 0.35rem;">No appointments found in this category.</p>
                <small>New appointments will appear here once approved by system administration.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child Patient</th>
                            <th>Parent Contact</th>
                            <th>Vaccine</th>
                            <th>Scheduled Slot</th>
                            <th>Status</th>
                            <th>Clinical Remarks</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td>
                                    <strong><?= e($a['child_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        Age: <?= formatAge($a['child_dob']); ?> &bull; <?= ucfirst($a['child_gender']); ?> &bull; Blood: <strong><?= e($a['blood_group']); ?></strong>
                                    </small>
                                </td>
                                <td>
                                    <?= e($a['parent_name']); ?><br>
                                    <small style="color: var(--text-muted);"><?= e($a['parent_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e($a['vaccine_name']); ?></span><br>
                                    <small style="color: var(--text-muted);"><?= (int)$a['vaccine_doses']; ?> <?= (int)$a['vaccine_doses'] === 1 ? 'dose' : 'doses'; ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($a['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($a['time_slot']); ?></small>
                                </td>
                                <td>
                                    <?php if ($a['vaccination_status'] === 'vaccinated'): ?>
                                        <span class="badge badge-success">&#10004; Vaccinated</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($a['vaccinated_on'], 'd-m-Y h:i A'); ?></small>
                                    <?php elseif ($a['vaccination_status'] === 'not_vaccinated'): ?>
                                        <span class="badge badge-danger">&#10006; Missed / Not Given</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">&#9203; Pending Attendance</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 200px;">
                                    <small style="color: var(--text-muted); font-style: italic;">
                                        <?= e($a['remarks'] ?: '&mdash;'); ?>
                                    </small>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="<?= url('hospital/update_status.php?id=' . (int)$a['id']); ?>" class="btn btn-primary btn-sm">
                                        &#9998; Update Status
                                    </a>
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
