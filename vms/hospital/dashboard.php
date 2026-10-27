<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: hospital/dashboard.php
 * Description: Hospital Pediatric Center Command Dashboard & Patient Queue
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Real-time clinic stats: Today's Queue, Pending Visits, Completed Doses, Stocked Vaccines.
 * - Shows admin-approved appointments assigned to this healthcare facility.
 * - Quick-action workflow to verify and update vaccination statuses.
 * - Enforces role-based authentication strictly for 'hospital' users.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('hospital');

$pdo = getDBConnection();
$userId = currentUserId();

// Fetch Hospital Facility Profile
$hStmt = $pdo->prepare('SELECT id, name, address, city, location, phone, status FROM hospitals WHERE user_id = ? LIMIT 1');
$hStmt->execute([$userId]);
$hospital = $hStmt->fetch();

if (!$hospital) {
    setFlash('danger', 'Healthcare facility profile not found. Please contact administration.');
    header('Location: ' . url('login.php'));
    exit;
}

$hospitalId = (int)$hospital['id'];

// ----------------------------------------------------------------------------
// 1. FETCH CLINICAL STATISTICS & KPI COUNTERS
// ----------------------------------------------------------------------------
try {
    // Today's Approved Patient Appointments
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM bookings 
         WHERE hospital_id = ? AND preferred_date = CURDATE() AND admin_status = "approved"'
    );
    $stmt->execute([$hospitalId]);
    $statTodayQueue = (int)$stmt->fetchColumn();

    // Pending Patient Visits (Approved by admin, awaiting clinical administration)
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM bookings 
         WHERE hospital_id = ? AND admin_status = "approved" AND vaccination_status = "pending"'
    );
    $stmt->execute([$hospitalId]);
    $statPendingVisits = (int)$stmt->fetchColumn();

    // Total Verified Completed Immunizations
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM bookings 
         WHERE hospital_id = ? AND vaccination_status = "vaccinated"'
    );
    $stmt->execute([$hospitalId]);
    $statCompletedDoses = (int)$stmt->fetchColumn();

    // Vaccines Currently Available in Local Stock
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM hospital_vaccines 
         WHERE hospital_id = ? AND status = "available"'
    );
    $stmt->execute([$hospitalId]);
    $statAvailableStock = (int)$stmt->fetchColumn();

    // ------------------------------------------------------------------------
    // 2. FETCH TODAY'S & UPCOMING APPROVED PATIENTS QUEUE (Top 6)
    // ------------------------------------------------------------------------
    $qStmt = $pdo->prepare(
        'SELECT b.*,
                c.name AS child_name, c.dob AS child_dob, c.gender AS child_gender, c.blood_group,
                p.phone AS parent_phone,
                u.name AS parent_name,
                v.name AS vaccine_name, v.doses AS vaccine_doses
         FROM bookings b
         JOIN children c ON b.child_id = c.id
         JOIN parents p ON b.parent_id = p.id
         JOIN users u ON p.user_id = u.id
         JOIN vaccines v ON b.vaccine_id = v.id
         WHERE b.hospital_id = ? AND b.admin_status = "approved" AND b.vaccination_status = "pending"
         ORDER BY b.preferred_date ASC, b.time_slot ASC
         LIMIT 6'
    );
    $qStmt->execute([$hospitalId]);
    $patientQueue = $qStmt->fetchAll();

} catch (PDOException $e) {
    error_log('[HOSPITAL DASHBOARD ERROR] ' . $e->getMessage());
    $statTodayQueue = $statPendingVisits = $statCompletedDoses = $statAvailableStock = 0;
    $patientQueue = [];
}

$pageTitle = 'Hospital Dashboard: ' . $hospital['name'];
$pageDescription = 'Manage patient queue, record administered vaccinations, and monitor vaccine stock.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-success">&#127973; Healthcare Facility</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">&#128205; <?= e($hospital['location']); ?>, <?= e($hospital['city']); ?></span>
            </div>
            <h1><?= e($hospital['name']); ?></h1>
            <p style="margin: 0;">Clinical appointment queue, patient verification, and vaccine stock management.</p>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= url('hospital/appointments.php'); ?>" class="btn btn-primary">
                &#128102; Patient Appointments
            </a>
            <a href="<?= url('hospital/vaccine_stock.php'); ?>" class="btn btn-outline">
                &#128137; Manage Vaccine Stock
            </a>
        </div>
    </div>

    <?php if ($hospital['status'] === 'inactive'): ?>
        <div class="alert alert-danger" role="alert">
            <span>&#9888; <strong>Facility Inactive:</strong> Your facility is currently marked inactive by administration. Parents cannot book new appointments at your clinic until reactivated.</span>
        </div>
    <?php endif; ?>

    <!-- ====================================================================== -->
    <!-- 1. LIVE STAT CARDS WITH COUNT-UP MICRO-ANIMATIONS                     -->
    <!-- ====================================================================== -->
    <div class="grid grid-4" style="gap: 1.25rem; margin-bottom: 2rem;">
        <div class="stat-card">
            <div>
                <span class="stat-label">Today's Schedule</span>
                <div class="stat-value" data-count-to="<?= $statTodayQueue; ?>"><?= $statTodayQueue; ?></div>
                <small style="color: var(--text-muted);">Scheduled for <?= date('M d'); ?></small>
            </div>
            <div class="stat-icon" style="background-color: #e0f2fe; color: #0369a1;">
                &#128197;
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Pending Doses</span>
                <div class="stat-value" data-count-to="<?= $statPendingVisits; ?>" style="<?= $statPendingVisits > 0 ? 'color: #d97706;' : ''; ?>">
                    <?= $statPendingVisits; ?>
                </div>
                <small><a href="<?= url('hospital/appointments.php'); ?>">Awaiting attendance &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #fef3c7; color: #b45309;">
                &#9203;
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Administered</span>
                <div class="stat-value" data-count-to="<?= $statCompletedDoses; ?>"><?= $statCompletedDoses; ?></div>
                <small style="color: var(--text-muted);">Verified doses given</small>
            </div>
            <div class="stat-icon" style="background-color: #ecfdf5; color: #059669;">
                &#10004;
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Stocked Vaccines</span>
                <div class="stat-value" data-count-to="<?= $statAvailableStock; ?>"><?= $statAvailableStock; ?></div>
                <small><a href="<?= url('hospital/vaccine_stock.php'); ?>">Update inventory &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #ccfbf1; color: #0f766e;">
                &#128137;
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 2. CLINICAL QUEUE: APPROVED PATIENTS AWAITING IMMUNIZATION            -->
    <!-- ====================================================================== -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div>
                <h3>Upcoming Patient Appointments</h3>
                <p style="margin: 0; font-size: 0.85rem;">Admin-approved appointments scheduled at your facility. Record dose completion once administered.</p>
            </div>
            <a href="<?= url('hospital/appointments.php'); ?>" class="btn btn-outline btn-sm">Full Patient Roster &rarr;</a>
        </div>

        <?php if (empty($patientQueue)): ?>
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <span style="font-size: 2.2rem; display: block; margin-bottom: 0.5rem;">&#10004;</span>
                <p style="font-size: 1.1rem; margin-bottom: 0.25rem;">Patient queue is clear!</p>
                <small>No pending approved appointments are waiting for administration at this time.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient (Child)</th>
                            <th>Parent / Contact</th>
                            <th>Vaccine Required</th>
                            <th>Scheduled Slot</th>
                            <th style="text-align: right;">Clinical Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patientQueue as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= e($p['child_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        Age: <?= formatAge($p['child_dob']); ?> &bull; <?= ucfirst($p['child_gender']); ?> &bull; Blood: <strong><?= e($p['blood_group']); ?></strong>
                                    </small>
                                </td>
                                <td>
                                    <?= e($p['parent_name']); ?><br>
                                    <small style="color: var(--text-muted);">&#128222; <?= e($p['parent_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e($p['vaccine_name']); ?></span>
                                </td>
                                <td>
                                    <strong style="<?= $p['preferred_date'] === date('Y-m-d') ? 'color: var(--primary);' : ''; ?>">
                                        <?= formatDate($p['preferred_date']); ?>
                                        <?= $p['preferred_date'] === date('Y-m-d') ? '<span class="badge badge-warning" style="font-size: 0.65rem;">TODAY</span>' : ''; ?>
                                    </strong><br>
                                    <small style="color: var(--text-muted);">&#128337; <?= e($p['time_slot']); ?></small>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="<?= url('hospital/update_status.php?id=' . (int)$p['id']); ?>" class="btn btn-primary btn-sm">
                                        &#9998; Record Vaccination &rarr;
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
