<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/dashboard.php
 * Description: Administrative Command Center & Live Analytics Dashboard
 * ----------------------------------------------------------------------------
 * Changes: emoji / HTML-entity icons (which rendered as empty boxes) replaced
 * with inline SVG icons from includes/ui.php; SQL uses single quotes.
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/ui.php';

requireRole('admin');

$pdo = getDBConnection();

try {
    $statTodayBookings         = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE preferred_date = CURDATE()')->fetchColumn();
    $statTotalChildren         = (int)$pdo->query('SELECT COUNT(*) FROM children')->fetchColumn();
    $statTotalHospitals        = (int)$pdo->query('SELECT COUNT(*) FROM hospitals WHERE status = \'active\'')->fetchColumn();
    $statAvailableVaccines     = (int)$pdo->query('SELECT COUNT(*) FROM vaccines WHERE status = \'available\'')->fetchColumn();
    $statPendingRequests       = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE admin_status = \'pending\'')->fetchColumn();
    $statCompletedVaccinations = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE vaccination_status = \'vaccinated\'')->fetchColumn();

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
         WHERE b.admin_status = \'pending\'
         ORDER BY b.created_at DESC
         LIMIT 5'
    );
    $reqStmt->execute();
    $recentPending = $reqStmt->fetchAll();

    $doneStmt = $pdo->prepare(
        'SELECT b.id, b.vaccinated_on, b.remarks,
                c.name AS child_name,
                h.name AS hospital_name,
                v.name AS vaccine_name
         FROM bookings b
         JOIN children c ON b.child_id = c.id
         JOIN hospitals h ON b.hospital_id = h.id
         JOIN vaccines v ON b.vaccine_id = v.id
         WHERE b.vaccination_status = \'vaccinated\'
         ORDER BY b.vaccinated_on DESC
         LIMIT 5'
    );
    $doneStmt->execute();
    $recentVaccinated = $doneStmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[ADMIN DASHBOARD DB ERROR] ' . $ex->getMessage());
    $statTodayBookings = $statTotalChildren = $statTotalHospitals = $statAvailableVaccines = $statPendingRequests = $statCompletedVaccinations = 0;
    $recentPending = [];
    $recentVaccinated = [];
}

$pageTitle = 'Administrator Dashboard';
$pageDescription = 'Live overview of child registrations, hospital appointments, and vaccine stock.';
require_once __DIR__ . '/../includes/header.php';
uiStyles();
?>

<div class="container">
    <!-- Header -->
    <div class="ui-bar" style="margin-bottom: 2rem;">
        <div>
            <div style="display: flex; align-items: center; gap: .5rem; margin-bottom: .25rem;">
                <span class="badge badge-danger">Administrator panel</span>
                <span style="font-size: .85rem; color: var(--text-muted);"><?= date('l, F j, Y'); ?></span>
            </div>
            <h1>System overview</h1>
            <p>Manage immunization schedules, centers, and appointments in one place.</p>
        </div>

        <div style="display: flex; gap: .5rem; flex-wrap: wrap;">
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-primary btn-sm ui-btn-icon">
                <?= uiIcon('alert', 16); ?> Review requests (<?= $statPendingRequests; ?>)
            </a>
            <a href="<?= url('admin/reports.php'); ?>" class="btn btn-outline btn-sm ui-btn-icon">
                <?= uiIcon('print', 16); ?> Reports
            </a>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-3" style="gap: 1.25rem; margin-bottom: 2rem;">
        <div class="stat-card">
            <div>
                <span class="stat-label">Today's appointments</span>
                <div class="stat-value" data-count-to="<?= $statTodayBookings; ?>"><?= $statTodayBookings; ?></div>
                <small style="color: var(--text-muted);">Scheduled for <?= date('M d'); ?></small>
            </div>
            <div class="stat-icon ui-stat-ico" style="background-color: #e0f2fe; color: #0369a1;">
                <?= uiIcon('calendar', 24); ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Registered children</span>
                <div class="stat-value" data-count-to="<?= $statTotalChildren; ?>"><?= $statTotalChildren; ?></div>
                <small style="color: var(--text-muted);"><a href="<?= url('admin/children.php'); ?>">View profiles &rarr;</a></small>
            </div>
            <div class="stat-icon ui-stat-ico" style="background-color: #fef3c7; color: #b45309;">
                <?= uiIcon('child', 24); ?>
            </div>
        </div>

        <div class="stat-card" style="<?= $statPendingRequests > 0 ? 'border-color: #f59e0b;' : ''; ?>">
            <div>
                <span class="stat-label">Pending approvals</span>
                <div class="stat-value" data-count-to="<?= $statPendingRequests; ?>" style="<?= $statPendingRequests > 0 ? 'color: #d97706;' : ''; ?>">
                    <?= $statPendingRequests; ?>
                </div>
                <small><a href="<?= url('admin/requests.php'); ?>" style="color: var(--warning-text, #92400e); font-weight: 600;">Review now &rarr;</a></small>
            </div>
            <div class="stat-icon ui-stat-ico" style="background-color: #fffbeb; color: #d97706;">
                <?= uiIcon('clock', 24); ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Active hospitals</span>
                <div class="stat-value" data-count-to="<?= $statTotalHospitals; ?>"><?= $statTotalHospitals; ?></div>
                <small style="color: var(--text-muted);"><a href="<?= url('admin/hospitals.php'); ?>">Manage centers &rarr;</a></small>
            </div>
            <div class="stat-icon ui-stat-ico" style="background-color: #ccfbf1; color: #0f766e;">
                <?= uiIcon('hospital', 24); ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Active vaccines</span>
                <div class="stat-value" data-count-to="<?= $statAvailableVaccines; ?>"><?= $statAvailableVaccines; ?></div>
                <small style="color: var(--text-muted);"><a href="<?= url('admin/vaccines.php'); ?>">Catalog and stock &rarr;</a></small>
            </div>
            <div class="stat-icon ui-stat-ico" style="background-color: #f0fdf4; color: #166534;">
                <?= uiIcon('syringe', 24); ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Doses administered</span>
                <div class="stat-value" data-count-to="<?= $statCompletedVaccinations; ?>"><?= $statCompletedVaccinations; ?></div>
                <small style="color: var(--text-muted);">Recorded by hospitals</small>
            </div>
            <div class="stat-icon ui-stat-ico" style="background-color: #ecfdf5; color: #047857;">
                <?= uiIcon('check', 24); ?>
            </div>
        </div>
    </div>

    <!-- Shortcuts -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3>Quick actions</h3>
        </div>
        <div class="grid grid-4" style="gap: 1rem;">
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <?= uiIcon('check', 24); ?>
                <span style="text-align: left; margin-left: .6rem;">
                    <strong>Approve requests</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Review parent bookings</small>
                </span>
            </a>
            <a href="<?= url('admin/hospitals.php?action=add'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <?= uiIcon('plus', 24); ?>
                <span style="text-align: left; margin-left: .6rem;">
                    <strong>Add hospital</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Enroll a center</small>
                </span>
            </a>
            <a href="<?= url('admin/vaccines.php?action=add'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <?= uiIcon('syringe', 24); ?>
                <span style="text-align: left; margin-left: .6rem;">
                    <strong>Add vaccine</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">Extend the catalog</small>
                </span>
            </a>
            <a href="<?= url('admin/reports.php'); ?>" class="btn btn-outline" style="justify-content: flex-start; padding: 1rem;">
                <?= uiIcon('print', 24); ?>
                <span style="text-align: left; margin-left: .6rem;">
                    <strong>Print reports</strong><br>
                    <small style="color: var(--text-muted); font-weight: normal;">By child or date</small>
                </span>
            </a>
        </div>
    </div>

    <!-- Pending requests -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div>
                <h3>Requests waiting for approval</h3>
                <p style="margin: 0; font-size: .85rem;">Approved requests go to the hospital's queue.</p>
            </div>
            <a href="<?= url('admin/requests.php'); ?>" class="btn btn-outline btn-sm">View all (<?= $statPendingRequests; ?>)</a>
        </div>

        <?php if (empty($recentPending)): ?>
            <div class="ui-empty">
                <span style="color: var(--success);"><?= uiIcon('check', 36); ?></span>
                <h3>All caught up</h3>
                <p>No appointment requests need review right now.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Parent</th>
                            <th>Center</th>
                            <th>Vaccine</th>
                            <th>Preferred slot</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPending as $req): ?>
                            <tr>
                                <td>
                                    <strong><?= e($req['child_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e(formatAge($req['child_dob'])); ?> old</small>
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
                                    <form action="<?= url('admin/requests.php'); ?>" method="POST" style="display: inline-block;">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="booking_id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm ui-btn-icon" title="Approve and send to hospital">
                                            <?= uiIcon('check', 14); ?> Approve
                                        </button>
                                    </form>

                                    <form action="<?= url('admin/requests.php'); ?>" method="POST" style="display: inline-block; margin-left: .25rem;">
                                        <?= csrfField(); ?>
                                        <input type="hidden" name="booking_id" value="<?= (int)$req['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm ui-btn-icon" title="Reject request"
                                                onclick="return confirm('Reject this appointment request?');">
                                            <?= uiIcon('x', 14); ?> Reject
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

    <!-- Recent vaccinations -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Recent vaccinations</h3>
                <p style="margin: 0; font-size: .85rem;">Doses recorded by participating hospitals.</p>
            </div>
            <a href="<?= url('admin/reports.php'); ?>" class="btn btn-outline btn-sm">Full report &rarr;</a>
        </div>

        <?php if (empty($recentVaccinated)): ?>
            <div class="ui-empty">
                <?= uiIcon('syringe', 36); ?>
                <h3>No vaccinations recorded yet</h3>
                <p>Doses appear here once hospitals mark appointments as vaccinated.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Vaccine</th>
                            <th>Hospital</th>
                            <th>Vaccinated on</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentVaccinated as $v): ?>
                            <tr>
                                <td><strong><?= e($v['child_name']); ?></strong></td>
                                <td><span class="badge badge-success"><?= e($v['vaccine_name']); ?></span></td>
                                <td><?= e($v['hospital_name']); ?></td>
                                <td><?= formatDate($v['vaccinated_on'], 'd-m-Y h:i A'); ?></td>
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