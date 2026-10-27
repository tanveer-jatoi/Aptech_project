<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/dashboard.php
 * Description: Administrative Command Center & Live Analytics Dashboard
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Live stat cards with real DB counts (Today's Bookings, Total Children, Total Hospitals, 
 *   Available Vaccines, Pending Requests).
 * - Minimal, performant count-up micro-animations (data-count-to) via animations.js.
 * - Quick-action workflow to approve/reject urgent parent requests.
 * - Strict role check enforcing 'admin' access only.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

// Enforce administrative access privileges
requireRole('admin');

$pdo = getDBConnection();

// ----------------------------------------------------------------------------
// 1. Fetch Real-Time Key Performance Indicators (Live Stat Cards)
// ----------------------------------------------------------------------------
try {
    // Stat 1: Bookings scheduled for today
    $stmt = $pdo->query('SELECT COUNT(*) FROM bookings WHERE preferred_date = CURDATE()');
    $statTodayBookings = (int)$stmt->fetchColumn();

    // Stat 2: Total registered infants & children
    $stmt = $pdo->query('SELECT COUNT(*) FROM children');
    $statTotalChildren = (int)$stmt->fetchColumn();

    // Stat 3: Total active hospitals / centers
    $stmt = $pdo->query('SELECT COUNT(*) FROM hospitals WHERE status = "active"');
    $statTotalHospitals = (int)$stmt->fetchColumn();

    // Stat 4: Available vaccines in the network
    $stmt = $pdo->query('SELECT COUNT(*) FROM vaccines WHERE status = "available"');
    $statAvailableVaccines = (int)$stmt->fetchColumn();

    // Stat 5: Parent booking requests awaiting admin review
    $stmt = $pdo->query('SELECT COUNT(*) FROM bookings WHERE admin_status = "pending"');
    $statPendingRequests = (int)$stmt->fetchColumn();

    // Stat 6: Total completed immunizations
    $stmt = $pdo->query('SELECT COUNT(*) FROM bookings WHERE vaccination_status = "vaccinated"');
    $statCompletedVaccinations = (int)$stmt->fetchColumn();

    // ------------------------------------------------------------------------
    // 2. Fetch Urgent Pending Requests (Latest 5 for Immediate Moderation)
    // ------------------------------------------------------------------------
    $reqStmt = $pdo->prepare(
        'SELECT b.id, b.preferred_date, b.time_slot, b.created_at,
                c.name AS child_name, c.dob AS child_dob,
                u.name AS parent_name, p.phone AS parent_phone,
                h.name AS hospital_name,
                v.name AS vaccine_name
         FROM bookings b
         JOIN children c ON b.child_id = c.id
         JOIN parents p ON b.parent_id = p.id
         JOIN users u ON p.user_id = u.id
         JOIN hospitals h ON b.hospital_id = h.id
         JOIN vaccines v ON b.vaccine_id = v.id
         WHERE b.admin_status = "pending"
         ORDER BY b.created_at DESC
         LIMIT 5'
    );
    $reqStmt->execute();
    $recentPending = $reqStmt->fetchAll();

    // ------------------------------------------------------------------------
    // 3. Fetch Recent Completed Vaccinations (Latest 5)
    // ------------------------------------------------------------------------
    $doneStmt = $pdo->prepare(
        'SELECT b.id, b.vaccinated_on, b.remarks,
                c.name AS child_name,
                h.name AS hospital_name,
                v.name AS vaccine_name
         FROM bookings b
         JOIN children c ON b.child_id = c.id
         JOIN hospitals h ON b.hospital_id = h.id
         JOIN vaccines v ON b.vaccine_id = v.id
         WHERE b.vaccination_status = "vaccinated"
         ORDER BY b.vaccinated_on DESC
         LIMIT 5'
    );
    $doneStmt->execute();
    $recentVaccinated = $doneStmt->fetchAll();

} catch (PDOException $e) {
    error_log('[ADMIN DASHBOARD DB ERROR] ' . $e->getMessage());
    $statTodayBookings = $statTotalChildren = $statTotalHospitals = $statAvailableVaccines = $statPendingRequests = $statCompletedVaccinations = 0;
    $recentPending = [];
    $recentVaccinated = [];
}

$pageTitle = 'Administrator Dashboard';
$pageDescription = 'Live overview of infant registrations, hospital appointments, and vaccine logistics.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Dashboard Header & Welcome Banner -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-danger">Administrator Panel</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">&#128197; <?= date('l, F j, Y'); ?></span>
            </div>
            <h1>System Overview & Live Metrics</h1>
            <p style="margin: 0;">Centralized control of all immunization schedules, centers, and appointments.</p>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-primary btn-sm">
                &#9888; Review Requests (<?= $statPendingRequests; ?>)
            </a>
            <a href="<?= url('admin/reports.php'); ?>" class="btn btn-outline btn-sm">
                &#128202; Generate Reports
            </a>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 1. LIVE STAT CARDS WITH COUNT-UP MICRO-ANIMATIONS                     -->
    <!-- ====================================================================== -->
    <div class="grid grid-3" style="gap: 1.25rem; margin-bottom: 2rem;">
        <!-- Card 1: Today's Bookings -->
        <div class="stat-card">
            <div>
                <span class="stat-label">Today's Appointments</span>
                <div class="stat-value" data-count-to="<?= $statTodayBookings; ?>"><?= $statTodayBookings; ?></div>
                <small style="color: var(--text-muted);">Scheduled for <?= date('M d'); ?></small>
            </div>
            <div class="stat-icon" style="background-color: #e0f2fe; color: #0369a1;">
                &#128197;
            </div>
        </div>

        <!-- Card 2: Total Children Registered -->
        <div class="stat-card">
            <div>
                <span class="stat-label">Registered Children</span>
                <div class="stat-value" data-count-to="<?= $statTotalChildren; ?>"><?= $statTotalChildren; ?></div>
                <small style="color: var(--text-muted);"><a href="<?= url('admin/children.php'); ?>">View profiles &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #fef3c7; color: #b45309;">
                &#128102;
            </div>
        </div>

        <!-- Card 3: Pending Appointment Requests -->
        <div class="stat-card" style="<?= $statPendingRequests > 0 ? 'border-color: #f59e0b;' : ''; ?>">
            <div>
                <span class="stat-label">Pending Approvals</span>
                <div class="stat-value" data-count-to="<?= $statPendingRequests; ?>" style="<?= $statPendingRequests > 0 ? 'color: #d97706;' : ''; ?>">
                    <?= $statPendingRequests; ?>
                </div>
                <small><a href="<?= url('admin/requests.php'); ?>" style="color: var(--warning-text); font-weight: 600;">Action required &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #fffbeb; color: #d97706;">
                &#9203;
            </div>
        </div>

        <!-- Card 4: Registered Active Hospitals -->
        <div class="stat-card">
            <div>
                <span class="stat-label">Active Hospitals</span>
                <div class="stat-value" data-count-to="<?= $statTotalHospitals; ?>"><?= $statTotalHospitals; ?></div>
                <small style="color: var(--text-muted);"><a href="<?= url('admin/hospitals.php'); ?>">Manage centers &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #ccfbf1; color: #0f766e;">
                &#127973;
            </div>
        </div>

        <!-- Card 5: Available Vaccines -->
        <div class="stat-card">
            <div>
                <span class="stat-label">Active Vaccines</span>
                <div class="stat-value" data-count-to="<?= $statAvailableVaccines; ?>"><?= $statAvailableVaccines; ?></div>
                <small style="color: var(--text-muted);"><a href="<?= url('admin/vaccines.php'); ?>">Catalog & stock &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #f3e8ff; color: #7e22ce;">
                &#128137;
            </div>
        </div>

        <!-- Card 6: Completed Vaccinations -->
        <div class="stat-card">
            <div>
                <span class="stat-label">Doses Administered</span>
                <div class="stat-value" data-count-to="<?= $statCompletedVaccinations; ?>"><?= $statCompletedVaccinations; ?></div>
                <small style="color: var(--text-muted);">Verified by hospitals</small>
            </div>
            <div class="stat-icon" style="background-color: #ecfdf5; color: #047857;">
                &#10004;
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 2. QUICK ADMINISTRATIVE ACTIONS                                       -->
    <!-- ====================================================================== -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3>Quick Administration Shortcuts</h3>
        </div>
        <div class="grid grid-4" style="gap: 1rem;">
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <span style="font-size: 1.4rem;">&#9989;</span>
                <div style="text-align: left; margin-left: 0.5rem;">
                    <strong>Approve Requests</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Moderate parent bookings</small>
                </div>
            </a>
            <a href="<?= url('admin/hospitals.php?action=add'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <span style="font-size: 1.4rem;">&#10010;</span>
                <div style="text-align: left; margin-left: 0.5rem;">
                    <strong>Add New Hospital</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Enroll pediatric center</small>
                </div>
            </a>
            <a href="<?= url('admin/vaccines.php?action=add'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <span style="font-size: 1.4rem;">&#128137;</span>
                <div style="text-align: left; margin-left: 0.5rem;">
                    <strong>Add Vaccine</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Expand catalog schedule</small>
                </div>
            </a>
            <a href="<?= url('admin/reports.php'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <span style="font-size: 1.4rem;">&#128203;</span>
                <div style="text-align: left; margin-left: 0.5rem;">
                    <strong>Print Reports</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Child & date analytics</small>
                </div>
            </a>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 3. PENDING PARENT APPOINTMENT REQUESTS (URGENT QUEUE)                   -->
    <!-- ====================================================================== -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div>
                <h3>Urgent Parent Requests Awaiting Approval</h3>
                <p style="margin: 0; font-size: 0.85rem;">Once approved, appointment slots are forwarded directly to the hospital queue.</p>
            </div>
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-outline btn-sm">View All (<?= $statPendingRequests; ?>)</a>
        </div>

        <?php if (empty($recentPending)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 0.25rem;">&#10004; All caught up!</p>
                <small>There are currently no pending appointment requests requiring administrative review.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child Details</th>
                            <th>Parent Contact</th>
                            <th>Requested Center</th>
                            <th>Vaccine</th>
                            <th>Preferred Slot</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPending as $req): ?>
                            <tr>
                                <td>
                                    <strong><?= e($req['child_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= formatAge($req['child_dob']); ?> old</small>
                                </td>
                                <td>
                                    <?= e($req['parent_name']); ?><br>
                                    <small style="color: var(--text-muted);"><?= e($req['parent_phone']); ?></small>
                                </td>
                                <td><?= e($req['hospital_name']); ?></td>
                                <td><span class="badge badge-info"><?= e($req['vaccine_name']); ?></span></td>
                                <td>
                                    <strong><?= formatDate($req['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($req['time_slot']); ?></small>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <!-- Direct Approve Form -->
                                    <form action="<?= url('admin/requests.php'); ?>" method="POST" style="display: inline-block;">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="booking_id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm" title="Approve & Send to Hospital">
                                            &#10004; Approve
                                        </button>
                                    </form>

                                    <!-- Direct Reject Form -->
                                    <form action="<?= url('admin/requests.php'); ?>" method="POST" style="display: inline-block; margin-left: 0.25rem;">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="booking_id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Reject Request" 
                                                onclick="return confirm('Are you sure you want to reject this appointment request?');">
                                            &#10006; Reject
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

    <!-- ====================================================================== -->
    <!-- 4. RECENTLY COMPLETED IMMUNIZATIONS                                   -->
    <!-- ====================================================================== -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Recently Completed Vaccinations</h3>
                <p style="margin: 0; font-size: 0.85rem;">Official doses verified and recorded by participating pediatric hospitals.</p>
            </div>
            <a href="<?= url('admin/reports.php'); ?>" class="btn btn-outline btn-sm">Full Report &rarr;</a>
        </div>

        <?php if (empty($recentVaccinated)): ?>
            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted);">
                <small>No recent vaccination logs recorded yet.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Vaccine Administered</th>
                            <th>Administering Hospital</th>
                            <th>Vaccinated On</th>
                            <th>Clinical Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentVaccinated as $v): ?>
                            <tr>
                                <td><strong><?= e($v['child_name']); ?></strong></td>
                                <td><span class="badge badge-success"><?= e($v['vaccine_name']); ?></span></td>
                                <td><?= e($v['hospital_name']); ?></td>
                                <td><?= formatDate($v['vaccinated_on'], 'M d, Y h:i A'); ?></td>
                                <td><small style="color: var(--text-muted);"><?= e($v['remarks'] ?: 'Routine dose administered.'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
