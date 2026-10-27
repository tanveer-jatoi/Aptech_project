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
    $createStmt = $pdo->prepare('INSERT INTO parents (user_id, phone, address, created_at) VALUES (?, "Not provided", "Not provided", NOW())');
    $createStmt->execute([$userId]);
    $parentId = (int)$pdo->lastInsertId();
    $_SESSION['profile_id'] = $parentId;
}

// ----------------------------------------------------------------------------
// 1. FETCH REGISTERED CHILDREN
// ----------------------------------------------------------------------------
$cStmt = $pdo->prepare(
    'SELECT c.*,
            (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id AND b.vaccination_status = "vaccinated") AS doses_completed
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
$vaccinesList = $pdo->query('SELECT * FROM vaccines WHERE status = "available" ORDER BY recommended_age_days ASC')->fetchAll();

// Fetch all bookings for this parent's children to know completed / booked status
$bStmt = $pdo->prepare('SELECT child_id, vaccine_id, admin_status, vaccination_status FROM bookings WHERE parent_id = ?');
$bStmt->execute([$parentId]);
$existingBookings = $bStmt->fetchAll();

$bookedMap = [];
foreach ($existingBookings as $eb) {
    $key = $eb['child_id'] . '_' . $eb['vaccine_id'];
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
            $status = $bookedMap[$mapKey]['vaccination_status'];
            $adminStatus = $bookedMap[$mapKey]['admin_status'];
            if ($status === 'vaccinated' || ($adminStatus !== 'rejected' && $status === 'pending')) {
                continue;
            }
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
$countPendingApproval = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE parent_id = ' . $parentId . ' AND admin_status = "pending"')->fetchColumn();

$pageTitle = 'Parent Dashboard';
$pageDescription = 'Child immunization milestones, upcoming vaccine alerts, and appointment status.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="badge badge-info">&#128106; Parent Portal</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">&#128197; <?= date('l, F j, Y'); ?></span>
            </div>
            <h1>Welcome, <?= e(currentUserName()); ?>!</h1>
            <p style="margin: 0;">Stay on top of your children's vital immunization milestones and book clinic slots.</p>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= url('parent/book.php'); ?>" class="btn btn-primary">
                &#10010; Book Vaccine Slot
            </a>
            <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-outline">
                &#128102; Register Child
            </a>
        </div>
    </div>

    <!-- ====================================================================== -->
    <!-- 1. VACCINATION NOTIFICATIONS & DUE DATE ALERTS                         -->
    <!-- ====================================================================== -->
    <?php if (!empty($overdueAlerts) || !empty($upcomingAlerts)): ?>
        <div style="margin-bottom: 2rem;">
            <!-- Overdue Critical Warnings -->
            <?php foreach ($overdueAlerts as $oa): ?>
                <div class="alert alert-danger" style="margin-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.5rem;">&#9888;</span>
                        <div>
                            <strong>Attention: <?= e($oa['child_name']); ?> is overdue for <?= e($oa['vaccine_name']); ?>!</strong><br>
                            <span style="font-size: 0.85rem;">
                                Recommended due date was <strong><?= formatDate($oa['due_date']); ?></strong> (<?= (int)$oa['days_diff']; ?> days ago). Please book an appointment promptly to protect your child.
                            </span>
                        </div>
                    </div>
                    <div>
                        <a href="<?= url('parent/book.php?child_id=' . (int)$oa['child_id'] . '&vaccine_id=' . (int)$oa['vaccine_id']); ?>" class="btn btn-danger btn-sm" style="white-space: nowrap;">
                            Book Now &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Due Soon Friendly Reminders -->
            <?php foreach ($upcomingAlerts as $ua): ?>
                <div class="alert alert-warning" style="margin-bottom: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.5rem;">&#128197;</span>
                        <div>
                            <strong>Upcoming Milestone: <?= e($ua['child_name']); ?> &bull; <?= e($ua['vaccine_name']); ?></strong><br>
                            <span style="font-size: 0.85rem;">
                                Due on <strong><?= formatDate($ua['due_date']); ?></strong> (in <?= (int)$ua['days_diff']; ?> <?= (int)$ua['days_diff'] === 1 ? 'day' : 'days'; ?>). Schedule your hospital slot in advance.
                            </span>
                        </div>
                    </div>
                    <div>
                        <a href="<?= url('parent/book.php?child_id=' . (int)$ua['child_id'] . '&vaccine_id=' . (int)$ua['vaccine_id']); ?>" class="btn btn-primary btn-sm" style="white-space: nowrap;">
                            Schedule Slot &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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
                &#128102;
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Completed Vaccinations</span>
                <div class="stat-value" data-count-to="<?= $countVaccinatedDoses; ?>"><?= $countVaccinatedDoses; ?></div>
                <small><a href="<?= url('parent/reports.php'); ?>">View certificates &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #ecfdf5; color: #059669;">
                &#10004;
            </div>
        </div>

        <div class="stat-card">
            <div>
                <span class="stat-label">Awaiting Admin Approval</span>
                <div class="stat-value" data-count-to="<?= $countPendingApproval; ?>"><?= $countPendingApproval; ?></div>
                <small><a href="<?= url('parent/bookings.php'); ?>">View appointments &rarr;</a></small>
            </div>
            <div class="stat-icon" style="background-color: #fef3c7; color: #b45309;">
                &#9203;
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
            <a href="<?= url('parent/children.php?action=add'); ?>" class="btn btn-outline btn-sm">&#10010; Add Child</a>
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
                                <span style="font-size: 1.6rem;">&#128102;</span>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.15rem;"><?= e($c['name']); ?></h4>
                                    <small style="color: var(--text-muted);"><?= ucfirst($c['gender']); ?> &bull; Blood Group: <strong><?= e($c['blood_group']); ?></strong></small>
                                </div>
                            </div>
                            <span class="badge badge-success">&#10004; <?= (int)$c['doses_completed']; ?> doses taken</span>
                        </div>

                        <p style="font-size: 0.88rem; margin-bottom: 1rem; color: var(--text-muted);">
                            <strong>DOB:</strong> <?= formatDate($c['dob']); ?> (<?= formatAge($c['dob']); ?> old)
                        </p>

                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="<?= url('parent/book.php?child_id=' . (int)$c['id']); ?>" class="btn btn-primary btn-sm">
                                &#128137; Book Vaccine
                            </a>
                            <a href="<?= url('parent/reports.php?child_id=' . (int)$c['id']); ?>" class="btn btn-outline btn-sm">
                                &#128203; View History
                            </a>
                            <a href="<?= url('parent/children.php?action=edit&id=' . (int)$c['id']); ?>" class="btn btn-outline btn-sm">
                                &#9998; Edit
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
                                    <small style="color: var(--text-muted);">&#128205; <?= e($b['hospital_city']); ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($b['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);">&#128337; <?= e($b['time_slot']); ?></small>
                                </td>
                                <td>
                                    <?php if ($b['admin_status'] === 'approved'): ?>
                                        <span class="badge badge-success">&#10004; Approved</span>
                                    <?php elseif ($b['admin_status'] === 'pending'): ?>
                                        <span class="badge badge-warning">&#9203; In Review</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">&#10006; Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['vaccination_status'] === 'vaccinated'): ?>
                                        <span class="badge badge-success">&#10004; Vaccinated</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($b['vaccinated_on']); ?></small>
                                    <?php elseif ($b['vaccination_status'] === 'not_vaccinated'): ?>
                                        <span class="badge badge-danger">&#10006; Not Vaccinated</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">&#9203; Pending Visit</span>
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
