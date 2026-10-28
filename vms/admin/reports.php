<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/reports.php
 * Description: Immunization Analytics & Reporting (Per Child & Date-Wise)
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Tab 1: Per-Child Immunization Certificate & Complete Dose History.
 * - Tab 2: Date-Wise Vaccination Analytics & Audit Report with Date Range Filters.
 * - Embedded @media print styles for formal paper/PDF export without UI clutter.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('admin');

$pdo = getDBConnection();
$activeTab = $_GET['tab'] ?? (isset($_GET['child_id']) ? 'child' : 'date');

// ============================================================================
// 1. DATA FOR TAB 1: PER CHILD REPORT
// ============================================================================
$selectedChildId = (int)($_GET['child_id'] ?? 0);
$allChildren = $pdo->query('SELECT c.id, c.name, c.dob, u.name AS parent_name FROM children c JOIN parents p ON c.parent_id = p.id JOIN users u ON p.user_id = u.id ORDER BY c.name ASC')->fetchAll();

// Default to first child if none specified and children exist
if ($selectedChildId === 0 && !empty($allChildren)) {
    $selectedChildId = (int)$allChildren[0]['id'];
}

$childData = null;
$childVaccineHistory = [];
$allVaccines = [];

if ($selectedChildId > 0) {
    $cStmt = $pdo->prepare(
        'SELECT c.*,
                p.phone AS parent_phone, p.address AS parent_address,
                u.name AS parent_name, u.email AS parent_email
         FROM children c
         JOIN parents p ON c.parent_id = p.id
         JOIN users u ON p.user_id = u.id
         WHERE c.id = ?'
    );
    $cStmt->execute([$selectedChildId]);
    $childData = $cStmt->fetch();

    if ($childData) {
        $allVaccines = $pdo->query('SELECT * FROM vaccines ORDER BY recommended_age_days ASC, name ASC')->fetchAll();

        // Fetch completed and scheduled bookings
        $bStmt = $pdo->prepare(
            'SELECT b.*, h.name AS hospital_name, v.name AS vaccine_name
             FROM bookings b
             JOIN hospitals h ON b.hospital_id = h.id
             JOIN vaccines v ON b.vaccine_id = v.id
             WHERE b.child_id = ?'
        );
        $bStmt->execute([$selectedChildId]);
        $bookings = $bStmt->fetchAll();

        foreach ($bookings as $b) {
            $childVaccineHistory[$b['vaccine_id']] = $b;
        }
    }
}

// ============================================================================
// 2. DATA FOR TAB 2: DATE-WISE REPORT
// ============================================================================
$defaultStart = date('Y-m-01'); // First day of current month
$defaultEnd   = date('Y-m-d');  // Today

$startDate      = sanitizeInput($_GET['start_date'] ?? $defaultStart);
$endDate        = sanitizeInput($_GET['end_date'] ?? $defaultEnd);
$filterHospital = (int)($_GET['hospital_id'] ?? 0);
$filterStatus   = sanitizeInput($_GET['status'] ?? '');

$hospitals = $pdo->query('SELECT id, name FROM hospitals ORDER BY name ASC')->fetchAll();

$dateSql = 'SELECT b.*,
                   c.name AS child_name, c.dob AS child_dob, c.blood_group,
                   p.phone AS parent_phone,
                   u.name AS parent_name,
                   h.name AS hospital_name, h.city AS hospital_city,
                   v.name AS vaccine_name
            FROM bookings b
            JOIN children c ON b.child_id = c.id
            JOIN parents p ON b.parent_id = p.id
            JOIN users u ON p.user_id = u.id
            JOIN hospitals h ON b.hospital_id = h.id
            JOIN vaccines v ON b.vaccine_id = v.id
            WHERE b.preferred_date BETWEEN ? AND ?';

$dateParams = [$startDate, $endDate];

if ($filterHospital > 0) {
    $dateSql .= ' AND b.hospital_id = ?';
    $dateParams[] = $filterHospital;
}

if (!empty($filterStatus)) {
    $dateSql .= ' AND b.vaccination_status = ?';
    $dateParams[] = $filterStatus;
}

$dateSql .= ' ORDER BY b.preferred_date DESC, b.time_slot ASC';
$dateStmt = $pdo->prepare($dateSql);
$dateStmt->execute($dateParams);
$dateWiseBookings = $dateStmt->fetchAll();

// KPI Metrics for the selected date range
$totalInRange     = count($dateWiseBookings);
$completedInRange = 0;
$pendingInRange   = 0;
$missedInRange    = 0;

foreach ($dateWiseBookings as $b) {
    if ($b['vaccination_status'] === 'vaccinated') {
        $completedInRange++;
    } elseif ($b['vaccination_status'] === 'pending') {
        $pendingInRange++;
    } elseif ($b['vaccination_status'] === 'not_vaccinated') {
        $missedInRange++;
    }
}

$complianceRate = $totalInRange > 0 ? round(($completedInRange / $totalInRange) * 100, 1) : 0;

$pageTitle = 'Vaccination Reports & Analytics';
$pageDescription = 'Official child immunization certificates and date-wise performance reports.';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Print-Only Styling Directives -->
<style>
@media print {
    .navbar, .footer, .form-tabs, .filter-console, .no-print, .btn, .toast-container {
        display: none !important;
    }
    body, .main-content {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .certificate-frame {
        border: 4px double #0284c7 !important;
        padding: 2rem !important;
        margin: 1rem 0 !important;
    }
    .table {
        border: 1px solid #94a3b8 !important;
    }
    .table th, .table td {
        border: 1px solid #cbd5e1 !important;
    }
}
</style>

<div class="container">
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Vaccination Reports & Records</h1>
            <p style="margin: 0;">Generate verifiable immunization certificates per child or audit date-wise administration.</p>
        </div>

        <div>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Mode Tabs -->
    <div class="form-tabs no-print" style="max-width: 550px; margin-bottom: 2rem;">
        <a href="<?= url('admin/reports.php?tab=child'); ?>" 
           class="form-tab-btn <?= $activeTab === 'child' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            Per-Child Certificate
        </a>
        <a href="<?= url('admin/reports.php?tab=date'); ?>" 
           class="form-tab-btn <?= $activeTab === 'date' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            Date-Wise Analytics
        </a>
    </div>

    <!-- ====================================================================== -->
    <!-- TAB 1: PER-CHILD IMMUNIZATION RECORD / CERTIFICATE                     -->
    <!-- ====================================================================== -->
    <?php if ($activeTab === 'child'): ?>
        <!-- Child Selector Bar -->
        <div class="card filter-console no-print" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form action="<?= url('admin/reports.php'); ?>" method="GET" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="child">
                <div style="flex: 2; min-width: 250px;">
                    <label for="child_id" class="form-label">Select Registered Child</label>
                    <select id="child_id" name="child_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($allChildren as $ch): ?>
                            <option value="<?= (int)$ch['id']; ?>" <?= $selectedChildId === (int)$ch['id'] ? 'selected' : ''; ?>>
                                <?= e($ch['name']); ?> (DOB: <?= formatDate($ch['dob']); ?>) &bull; Parent: <?= e($ch['parent_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline btn-sm" style="height: 40px;">Load Record</button>
            </form>
        </div>

        <?php if ($childData): ?>
            <div class="card certificate-frame">
                <!-- Formal Medical Header -->
                <div style="text-align: center; border-bottom: 2px solid var(--primary); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
                    <span style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--primary);">
                        Official Health Authority Document
                    </span>
                    <h2 style="font-size: 1.75rem; margin: 0.35rem 0; color: var(--text-main);">
                        Child Immunization Record Certificate
                    </h2>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">
                        Certified record of administered vaccines, health centers, and recommended immunization schedule.
                    </p>
                </div>

                <!-- Child & Parent Demographic Summary -->
                <div class="grid grid-2" style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                    <div>
                        <h4 style="margin-bottom: 0.5rem; color: var(--primary-dark);">Child Information</h4>
                        <table style="width: 100%; font-size: 0.9rem; line-height: 1.8;">
                            <tr><td style="width: 120px; color: var(--text-muted);">Full Name:</td><td><strong><?= e($childData['name']); ?></strong></td></tr>
                            <tr><td style="color: var(--text-muted);">Date of Birth:</td><td><?= formatDate($childData['dob']); ?></td></tr>
                            <tr><td style="color: var(--text-muted);">Current Age:</td><td><?= formatAge($childData['dob']); ?></td></tr>
                            <tr><td style="color: var(--text-muted);">Gender / Blood:</td><td><?= ucfirst($childData['gender']); ?> &bull; <strong><?= e($childData['blood_group']); ?></strong></td></tr>
                        </table>
                    </div>

                    <div>
                        <h4 style="margin-bottom: 0.5rem; color: var(--primary-dark);">Guardian Information</h4>
                        <table style="width: 100%; font-size: 0.9rem; line-height: 1.8;">
                            <tr><td style="width: 120px; color: var(--text-muted);">Parent Name:</td><td><strong><?= e($childData['parent_name']); ?></strong></td></tr>
                            <tr><td style="color: var(--text-muted);">Contact Phone:</td><td><?= e($childData['parent_phone']); ?></td></tr>
                            <tr><td style="color: var(--text-muted);">Email:</td><td><?= e($childData['parent_email']); ?></td></tr>
                            <tr><td style="color: var(--text-muted);">Address:</td><td><?= e($childData['parent_address']); ?></td></tr>
                        </table>
                    </div>
                </div>

                <!-- Complete Vaccine Milestones Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Target Age</th>
                                <th>Due Date</th>
                                <th>Immunization Status</th>
                                <th>Administering Center</th>
                                <th>Administered Date / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allVaccines as $vac): 
                                $vId = (int)$vac['id'];
                                $dueDate = calculateVaccineDueDate($childData['dob'], (int)$vac['recommended_age_days']);
                                $isOverdue = isDateOverdue($dueDate);
                                $hasBooking = isset($childVaccineHistory[$vId]);
                                $isCompleted = $hasBooking && $childVaccineHistory[$vId]['vaccination_status'] === 'vaccinated';
                                $isScheduled = $hasBooking && $childVaccineHistory[$vId]['admin_status'] === 'approved' && $childVaccineHistory[$vId]['vaccination_status'] === 'pending';
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($vac['name']); ?></strong>
                                    </td>
                                    <td>
                                        <?= (int)$vac['recommended_age_days'] === 0 ? 'Birth' : (int)$vac['recommended_age_days'] . 'd'; ?>
                                    </td>
                                    <td><?= formatDate($dueDate); ?></td>
                                    <td>
                                        <?php if ($isCompleted): ?>
                                            <span class="badge badge-success">&#10004; Administered</span>
                                        <?php elseif ($isScheduled): ?>
                                            <span class="badge badge-info">Scheduled</span>
                                        <?php elseif ($isOverdue): ?>
                                            <span class="badge badge-danger">&#9888; Overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">&#9203; Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $hasBooking ? e($childVaccineHistory[$vId]['hospital_name']) : '&mdash;'; ?>
                                    </td>
                                    <td>
                                        <?php if ($isCompleted): ?>
                                            <strong><?= formatDate($childVaccineHistory[$vId]['vaccinated_on']); ?></strong><br>
                                            <small style="color: var(--text-muted);"><?= e($childVaccineHistory[$vId]['remarks'] ?: 'Routine dose logged'); ?></small>
                                        <?php elseif ($isScheduled): ?>
                                            <small>Appointment for <?= formatDate($childVaccineHistory[$vId]['preferred_date']); ?></small>
                                        <?php else: ?>
                                            <small style="color: var(--text-muted);">Not administered yet</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sign-Off Section for Physical Certificates -->
                <div style="margin-top: 3rem; display: flex; justify-content: space-between; padding-top: 2rem; border-top: 1px dashed #94a3b8;">
                    <div style="text-align: center; width: 220px;">
                        <div style="border-bottom: 1px solid #334155; height: 40px; margin-bottom: 0.5rem;"></div>
                        <small style="font-weight: 600; color: var(--text-muted);">Attending Pediatrician Stamp</small>
                    </div>
                    <div style="text-align: center; width: 220px;">
                        <div style="border-bottom: 1px solid #334155; height: 40px; margin-bottom: 0.5rem;"></div>
                        <small style="font-weight: 600; color: var(--text-muted);">System Administrator Signature</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <!-- ====================================================================== -->
    <!-- TAB 2: DATE-WISE VACCINATION ANALYTICS REPORT                          -->
    <!-- ====================================================================== -->
    <?php else: ?>
        <!-- Date Range Filter Console -->
        <div class="card filter-console no-print" style="margin-bottom: 1.5rem; padding: 1.25rem;">
            <form action="<?= url('admin/reports.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
                <input type="hidden" name="tab" value="date">

                <div style="flex: 1; min-width: 140px;">
                    <label for="start_date" class="form-label">From Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?= e($startDate); ?>" required>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="end_date" class="form-label">To Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?= e($endDate); ?>" required>
                </div>

                <div style="flex: 1; min-width: 170px;">
                    <label for="hospital_id" class="form-label">Healthcare Facility</label>
                    <select id="hospital_id" name="hospital_id" class="form-select">
                        <option value="0">All Healthcare Centers</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= (int)$h['id']; ?>" <?= $filterHospital === (int)$h['id'] ? 'selected' : ''; ?>>
                                <?= e($h['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label for="status" class="form-label">Clinical State</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All States</option>
                        <option value="vaccinated" <?= $filterStatus === 'vaccinated' ? 'selected' : ''; ?>>Vaccinated</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending Visit</option>
                        <option value="not_vaccinated" <?= $filterStatus === 'not_vaccinated' ? 'selected' : ''; ?>>Missed / Cancelled</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Filter Range</button>
                    <a href="<?= url('admin/reports.php?tab=date'); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Metric KPI Summary Row -->
        <div class="grid grid-4" style="gap: 1rem; margin-bottom: 1.5rem;">
            <div class="card" style="padding: 1.25rem; text-align: center;">
                <span class="stat-label">Total Appointments</span>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem;">
                    <?= $totalInRange; ?>
                </div>
            </div>

            <div class="card" style="padding: 1.25rem; text-align: center;">
                <span class="stat-label">Completed Doses</span>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--success); margin-top: 0.25rem;">
                    <?= $completedInRange; ?>
                </div>
            </div>

            <div class="card" style="padding: 1.25rem; text-align: center;">
                <span class="stat-label">Pending Administration</span>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--warning); margin-top: 0.25rem;">
                    <?= $pendingInRange; ?>
                </div>
            </div>

            <div class="card" style="padding: 1.25rem; text-align: center;">
                <span class="stat-label">Completion Rate</span>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">
                    <?= $complianceRate; ?>%
                </div>
            </div>
        </div>

        <!-- Date-Wise Tabular Breakdown -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Audit Log (<?= formatDate($startDate); ?> &mdash; <?= formatDate($endDate); ?>)</h3>
                    <p style="margin: 0; font-size: 0.85rem;">Showing <?= count($dateWiseBookings); ?> appointments recorded in the selected date interval.</p>
                </div>
            </div>

            <?php if (empty($dateWiseBookings)): ?>
                <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                    <p>No vaccination activities recorded for this date window.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Appointment Date</th>
                                <th>Child Name</th>
                                <th>Parent Details</th>
                                <th>Vaccine</th>
                                <th>Healthcare Center</th>
                                <th>Clinical Status</th>
                                <th>Administered Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dateWiseBookings as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= formatDate($row['preferred_date']); ?></strong><br>
                                        <small style="color: var(--text-muted);"><?= e($row['time_slot']); ?></small>
                                    </td>
                                    <td>
                                        <strong><a href="<?= url('admin/children.php?id=' . (int)$row['child_id']); ?>"><?= e($row['child_name']); ?></a></strong><br>
                                        <small style="color: var(--text-muted);"><?= formatAge($row['child_dob']); ?></small>
                                    </td>
                                    <td>
                                        <?= e($row['parent_name']); ?><br>
                                        <small style="color: var(--text-muted);"><?= e($row['parent_phone']); ?></small>
                                    </td>
                                    <td><span class="badge badge-info"><?= e($row['vaccine_name']); ?></span></td>
                                    <td><?= e($row['hospital_name']); ?></td>
                                    <td>
                                        <?php if ($row['vaccination_status'] === 'vaccinated'): ?>
                                            <span class="badge badge-success">&#10004; Vaccinated</span>
                                        <?php elseif ($row['vaccination_status'] === 'not_vaccinated'): ?>
                                            <span class="badge badge-danger">&#10006; Missed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">&#9203; Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatDate($row['vaccinated_on'], 'd-m-Y h:i A'); ?>
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
