<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/dashboard.php
 * Description: Parent Dashboard, Children Overview & Dynamic Due Date Alerts
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Live KPI metric cards for parent's children, completed doses, and appointments.
 * - Dynamic Due Date Notifications calculated from child DOB & vaccine schedule.
 * - Overdue and Due-Soon vaccine warnings with one-click "Book Slot" shortcuts.
 * - Summary of registered children and recent appointment requests.
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
$pStmt = $pdo->prepare('SELECT id, phone, address FROM parents WHERE user_id = ? LIMIT 1');
$pStmt->execute([$userId]);
$parentProfile = $pStmt->fetch();
$parentId = $parentProfile ? (int)$parentProfile['id'] : 0;

// If parent record missing, auto-create to ensure uninterrupted access
if ($parentId === 0) {
    $createStmt = $pdo->prepare('INSERT INTO parents (user_id, phone, address, created_at) VALUES (?, \'Not provided\', \'Not provided\', NOW())');
    $createStmt->execute([$userId]);
    $parentId = (int)$pdo->lastInsertId();
    $_SESSION['profile_id'] = $parentId;
}

// ----------------------------------------------------------------------------
// 1. FETCH REGISTERED CHILDREN
// ----------------------------------------------------------------------------
$cStmt = $pdo->prepare(
    'SELECT c.*,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id AND b.vaccination_status = \'vaccinated\') AS doses_completed
     FROM children c
     WHERE c.parent_id = ?
     ORDER BY c.dob DESC'
);
$cStmt->execute([$parentId]);
$children = $cStmt->fetchAll();

// ----------------------------------------------------------------------------
// 2. DYNAMIC DUE DATE NOTIFICATIONS ENGINE
// Computes upcoming & overdue vaccinations from Child DOB + Vaccine Catalog
// ----------------------------------------------------------------------------
$vaccinesList = $pdo->query('SELECT * FROM vaccines WHERE status = \'available\' ORDER BY recommended_age_days ASC')->fetchAll();

// Fetch all bookings for this parent's children to know completed / booked status
$bStmt = $pdo->prepare('SELECT child_id, vaccine_id, admin_status, vaccination_status FROM bookings WHERE parent_id = ?');
$bStmt->execute([$parentId]);
$existingBookings = $bStmt->fetchAll();

$bookedMap = [];
foreach ($existingBookings as $eb) {
    // Keep only what counts as "handled": vaccinated, or an active (pending/approved) request.
    // Rejected or missed requests must not hide an overdue vaccine.
    if ($eb['admin_status'] === 'rejected' || $eb['vaccination_status'] === 'not_vaccinated') {
        continue;
    }
    $key = $eb['child_id'] . '_' . $eb['vaccine_id'];
    if (isset($bookedMap[$key]) && $bookedMap[$key]['vaccination_status'] === 'vaccinated'
        && $eb['vaccination_status'] !== 'vaccinated') {
        continue;
    }
    $bookedMap[$key] = $eb;
}

$upcomingAlerts = [];
$overdueAlerts  = [];

foreach ($children as $child) {
    $childId = (int)$child['id'];
    $childDob = $child['dob'];

    foreach ($vaccinesList as $vac) {
        $vacId = (int)$vac['id'];
        $mapKey = $childId . '_' . $vacId;

        // Skip if dose is already vaccinated or booked/in-process
        if (isset($bookedMap[$mapKey])) {
            continue;
        }

        // Calculate due date
        $dueDate = calculateVaccineDueDate($childDob, (int)$vac['recommended_age_days']);
        $daysUntilDue = (int)floor((strtotime($dueDate) - strtotime('today')) / 86400);

        // Classify alert
        if ($daysUntilDue < 0) {
            // Overdue milestone
            $overdueAlerts[] = [
                'child_id'     => $childId,
                'child_name'   => $child['name'],
                'vaccine_id'   => $vacId,
                'vaccine_name' => $vac['name'],
                'due_date'     => $dueDate,
                'days_diff'    => abs($daysUntilDue),
                'type'         => 'overdue'
            ];
        } elseif ($daysUntilDue <= 30) {
            // Due within next 30 days
            $upcomingAlerts[] = [
                'child_id'     => $childId,
                'child_name'   => $child['name'],
                'vaccine_id'   => $vacId,
                'vaccine_name' => $vac['name'],
                'due_date'     => $dueDate,
                'days_diff'    => $daysUntilDue,
                'type'         => 'upcoming'
            ];
        }
    }
}

// ----------------------------------------------------------------------------
// 3. FETCH RECENT APPOINTMENT BOOKINGS
// ----------------------------------------------------------------------------
$recentBookingsStmt = $pdo->prepare(
    'SELECT b.*,
            c.name AS child_name,
            h.name AS hospital_name, h.city AS hospital_city,
            v.name AS vaccine_name
     FROM bookings b
     JOIN children c ON b.child_id = c.id
     JOIN hospitals h ON b.hospital_id = h.id
     JOIN vaccines v ON b.vaccine_id = v.id
     WHERE b.parent_id = ?
     ORDER BY b.created_at DESC
     LIMIT 5'
);
$recentBookingsStmt->execute([$parentId]);
$recentBookings = $recentBookingsStmt->fetchAll();

// Quick Counts
$countChildren = count($children);
$countVaccinatedDoses = 0;
foreach ($children as $c) {
    $countVaccinatedDoses += (int)$c['doses_completed'];
}
$pendStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE parent_id = ? AND admin_status = \'pending\'');
$pendStmt->execute([$parentId]);
$countPendingApproval = (int)$pendStmt->fetchColumn();

// Group alerts per child so a child with many overdue vaccines does not flood the page.
$overdueByChild = [];
foreach ($overdueAlerts as $oa) {
    $overdueByChild[$oa['child_id']][] = $oa;
}
$upcomingShown = array_slice($upcomingAlerts, 0, 4);
$upcomingHidden = max(0, count($upcomingAlerts) - count($upcomingShown));

$pageTitle = 'Parent Dashboard';
$pageDescription = 'Child immunization milestones, upcoming vaccine alerts, and appointment status.';
require_once __DIR__ . '/../includes/header.php';
uiStyles();
?>

<div class="container">
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-info">Parent Portal</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);"><?= date('l, F j, Y'); ?></span>
            </div>
            <h1>Welcome, <?= e(currentUserName()); ?>!</h1>
            <p style="margin: 0;">Stay on top of your children's vital immunization milestones and book clinic slots.</p>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= url('parent/book.php'); ?>" class="btn btn-primary">
                <?= uiIcon('plus', 16); ?> Book Vaccine Slot
            </a>
            <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-outline">
                Register Child
            </a>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 1. VACCINATION NOTIFICATIONS & DUE DATE ALERTS                         -->
    <!-- ====================================================================== -->
    <?php if (!empty($overdueAlerts) || !empty($upcomingAlerts)): ?>
        <section style="margin-bottom: 2rem;" aria-label="Vaccination alerts">
            <?php foreach ($overdueByChild as $cid => $items):
                $first = $items[0];
                $shown = array_slice($items, 0, 3);
                $more  = count($items) - count($shown);
            ?>
                <div class="alert alert-danger" style="margin-bottom: .75rem;">
                    <div class="ui-alert-row" style="width: 100%;">
                        <div>
                            <?= uiIcon('alert', 22); ?>
                            <div>
                                <strong><?= e($first['child_name']); ?> is overdue for <?= count($items); ?> <?= count($items) === 1 ? 'vaccine' : 'vaccines'; ?></strong>
                                <small>
                                    <?php foreach ($shown as $i => $oa): ?><?= $i > 0 ? ', ' : ''; ?><?= e($oa['vaccine_name']); ?> (<?= (int)$oa['days_diff']; ?> <?= (int)$oa['days_diff'] === 1 ? 'day' : 'days'; ?> late)<?php endforeach; ?><?= $more > 0 ? ' and ' . $more . ' more' : ''; ?>.
                                </small>
                            </div>
                        </div>
                        <div style="display: flex; gap: .5rem; flex-wrap: wrap;">
                            <a href="<?= url('parent/book.php?child_id=' . (int)$cid . '&vaccine_id=' . (int)$first['vaccine_id']); ?>" class="btn btn-danger btn-sm" style="white-space: nowrap;">Book <?= e($first['vaccine_name']); ?></a>
                            <a href="<?= url('parent/children.php?action=view&id=' . (int)$cid); ?>" class="btn btn-outline btn-sm" style="white-space: nowrap;">Full schedule</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php foreach ($upcomingShown as $ua): ?>
                <div class="alert alert-warning" style="margin-bottom: .75rem;">
                    <div class="ui-alert-row" style="width: 100%;">
                        <div>
                            <?= uiIcon('calendar', 22); ?>
                            <div>
                                <strong><?= e($ua['child_name']); ?>: <?= e($ua['vaccine_name']); ?></strong>
                                <small>
                                    Due <?= formatDate($ua['due_date']); ?>
                                    (<?= (int)$ua['days_diff'] === 0 ? 'today' : 'in ' . (int)$ua['days_diff'] . ((int)$ua['days_diff'] === 1 ? ' day' : ' days'); ?>).
                                </small>
                            </div>
                        </div>
                        <a href="<?= url('parent/book.php?child_id=' . (int)$ua['child_id'] . '&vaccine_id=' . (int)$ua['vaccine_id']); ?>" class="btn btn-primary btn-sm" style="white-space: nowrap;">Book a slot</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($upcomingHidden > 0): ?>
                <p style="margin: 0; font-size: .9rem; color: var(--text-muted);">
                    <?= $upcomingHidden; ?> more due in the next 30 days. <a href="<?= url('parent/children.php'); ?>">See each child's schedule</a>.
                </p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- ====================================================================== -->
    <!-- 2. QUICK STATS                                                         -->
    <!-- ====================================================================== -->
    <div class="grid grid-3" style="gap: 1.25rem; margin-bottom: 2rem;">
        <div class="stat-card">
            <div>
                <span class="stat-label">Registered Children</span>
                <div class="stat-value" data-count-to="<?= $countChildren; ?>"><?= $countChildren; ?></div>
                <small><a href="<?= url('parent/children.php'); ?>">Manage children &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #e0f2fe; color: #0369a1;">
                <?= uiIcon('child', 22); ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Completed Vaccinations</span>
                <div class="stat-value" data-count-to="<?= $countVaccinatedDoses; ?>"><?= $countVaccinatedDoses; ?></div>
                <small><a href="<?= url('parent/reports.php'); ?>">View certificates &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #ecfdf5; color: #059669;">
                <?= uiIcon('check', 14); ?>
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Awaiting Admin Approval</span>
                <div class="stat-value" data-count-to="<?= $countPendingApproval; ?>"><?= $countPendingApproval; ?></div>
                <small><a href="<?= url('parent/bookings.php'); ?>">View appointments &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #fef3c7; color: #b45309;">
                <?= uiIcon('clock', 14); ?>
            </div>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 3. MY CHILDREN OVERVIEW                                                -->
    <!-- ====================================================================== -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div>
                <h3>My Children Profiles</h3>
                <p style="margin: 0; font-size: 0.85rem;">Registered infants under your guardianship.</p>
            </div>
            <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-outline btn-sm"><?= uiIcon('plus', 16); ?> Add Child</a>
        </div>

        <?php if (empty($children)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No children registered under your profile yet.</p>
                <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-primary btn-sm">Add Your First Child</a>
            </div>
        <?php else: ?>
            <div class="grid grid-2" style="gap: 1.25rem;">
                <?php foreach ($children as $c): ?>
                    <div style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="ui-avatar"><?= uiIcon('child', 22); ?></span>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.15rem;"><?= e($c['name']); ?></h4>
                                    <small style="color: var(--text-muted);"><?= ucfirst($c['gender']); ?> &bull; Blood Group: <strong><?= e($c['blood_group']); ?></strong></small>
                                </div>
                            </div>
                            <span class="badge badge-success"><?= uiIcon('check', 14); ?> <?= (int)$c['doses_completed']; ?> doses taken</span>
                        </div>

                        <p style="font-size: 0.88rem; margin-bottom: 1rem; color: var(--text-muted);">
                            <strong>DOB:</strong> <?= formatDate($c['dob']); ?> (<?= formatAge($c['dob']); ?> old)
                        </p>

                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="<?= url('parent/book.php?child_id=' . (int)$c['id']); ?>" class="btn btn-primary btn-sm">
                                Book Vaccine
                            </a>
                            <a href="<?= url('parent/reports.php?child_id=' . (int)$c['id']); ?>" class="btn btn-outline btn-sm">
                                View History
                            </a>
                            <a href="<?= url('parent/children.php?action=edit&id=' . (int)$c['id']); ?>" class="btn btn-outline btn-sm">
                                <?= uiIcon('edit', 16); ?> Edit
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ====================================================================== -->
    <!-- 4. RECENT APPOINTMENT REQUESTS & STATUSES                             -->
    <!-- ====================================================================== -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Recent Appointment Requests</h3>
                <p style="margin: 0; font-size: 0.85rem;">Track approval from admin and attendance status at the clinic.</p>
            </div>
            <a href="<?= url('parent/bookings.php'); ?>" class="btn btn-outline btn-sm">View All Appointments &rarr;</a>
        </div>

        <?php if (empty($recentBookings)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                <p>You have not made any appointment bookings yet.</p>
                <a href="<?= url('parent/book.php'); ?>" class="btn btn-primary btn-sm">Book a Vaccination Slot</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Vaccine</th>
                            <th>Healthcare Center</th>
                            <th>Preferred Slot</th>
                            <th>Admin Approval</th>
                            <th>Vaccination Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr>
                                <td><strong><?= e($b['child_name']); ?></strong></td>
                                <td><span class="badge badge-info"><?= e($b['vaccine_name']); ?></span></td>
                                <td>
                                    <strong><?= e($b['hospital_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($b['hospital_city']); ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($b['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($b['time_slot']); ?></small>
                                </td>
                                <td>
                                    <?php if ($b['admin_status'] === 'approved'): ?>
                                        <span class="badge badge-success"><?= uiIcon('check', 14); ?> Approved</span>
                                    <?php elseif ($b['admin_status'] === 'pending'): ?>
                                        <span class="badge badge-warning"><?= uiIcon('clock', 14); ?> In Review</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= uiIcon('x', 14); ?> Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['vaccination_status'] === 'vaccinated'): ?>
                                        <span class="badge badge-success"><?= uiIcon('check', 14); ?> Vaccinated</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($b['vaccinated_on']); ?></small>
                                    <?php elseif ($b['vaccination_status'] === 'not_vaccinated'): ?>
                                        <span class="badge badge-danger"><?= uiIcon('x', 14); ?> Not Vaccinated</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= uiIcon('clock', 14); ?> Pending Visit</span>
                                    <?php endif; ?>
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