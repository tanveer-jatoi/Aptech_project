<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/children.php
 * Description: Child Registry & Individual Immunization Medical History
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - View all child details (master registry + individual detail medical profile).
 * - Child detail profile calculates age-based immunization schedule from DOB.
 * - Displays complete history of administered doses and pending milestones.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('admin');

$pdo = getDBConnection();
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ----------------------------------------------------------------------------
// VIEW MODE A: SINGLE CHILD DETAILED MEDICAL PROFILE
// ----------------------------------------------------------------------------
if ($viewId > 0) {
    // 1. Fetch Child & Parent Details
    $cStmt = $pdo->prepare(
        'SELECT c.*,
                p.phone AS parent_phone, p.address AS parent_address,
                u.name AS parent_name, u.email AS parent_email
         FROM children c
         JOIN parents p ON c.parent_id = p.id
         JOIN users u ON p.user_id = u.id
         WHERE c.id = ?'
    );
    $cStmt->execute([$viewId]);
    $child = $cStmt->fetch();

    if (!$child) {
        setFlash('danger', 'Child profile record not found.');
        header('Location: ' . url('admin/children.php'));
        exit;
    }

    // 2. Fetch All Vaccines to Calculate Schedule
    $vStmt = $pdo->query('SELECT * FROM vaccines ORDER BY recommended_age_days ASC, name ASC');
    $allVaccines = $vStmt->fetchAll();

    // 3. Fetch All Bookings & Dose History for This Child
    $bStmt = $pdo->prepare(
        'SELECT b.*,
                h.name AS hospital_name,
                v.name AS vaccine_name
         FROM bookings b
         JOIN hospitals h ON b.hospital_id = h.id
         JOIN vaccines v ON b.vaccine_id = v.id
         WHERE b.child_id = ?
         ORDER BY b.preferred_date ASC'
    );
    $bStmt->execute([$viewId]);
    $childBookings = $bStmt->fetchAll();

    // Index completed vaccinations by vaccine_id
    $completedDoses = [];
    $scheduledDoses = [];
    foreach ($childBookings as $b) {
        if ($b['vaccination_status'] === 'vaccinated') {
            $completedDoses[$b['vaccine_id']] = $b;
        } elseif ($b['admin_status'] === 'approved' && $b['vaccination_status'] === 'pending') {
            $scheduledDoses[$b['vaccine_id']] = $b;
        }
    }

    $pageTitle = 'Medical Profile: ' . $child['name'];
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <a href="<?= url('admin/children.php'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 0.5rem;">&larr; Back to Children Registry</a>
                <h1>Child Immunization Profile</h1>
                <p style="margin: 0;">Comprehensive clinical immunization record and future dose timeline.</p>
            </div>
            <div>
                <a href="<?= url('admin/reports.php?child_id=' . (int)$child['id']); ?>" class="btn btn-primary btn-sm">
                    Print Child Certificate
                </a>
            </div>
        </div>

        <!-- Child & Parent Header Card -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="grid grid-2" style="gap: 1.5rem;">
                <!-- Child Overview -->
                <div style="border-right: 1px solid var(--border-color); padding-right: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <span style="font-size: 2rem;"></span>
                        <div>
                            <h2 style="margin: 0; font-size: 1.4rem;"><?= e($child['name']); ?></h2>
                            <span class="badge badge-info" style="margin-top: 0.2rem;">Blood Group: <?= e($child['blood_group']); ?></span>
                        </div>
                    </div>
                    <ul style="list-style: none; padding: 0; line-height: 1.8; font-size: 0.92rem;">
                        <li><strong>Date of Birth:</strong> <?= formatDate($child['dob']); ?></li>
                        <li><strong>Current Age:</strong> <?= formatAge($child['dob']); ?> (<?= calculateAgeInDays($child['dob']); ?> days)</li>
                        <li><strong>Gender:</strong> <?= ucfirst($child['gender']); ?></li>
                        <li><strong>Registered On:</strong> <?= formatDate($child['created_at']); ?></li>
                    </ul>
                </div>

                <!-- Parent & Guardian Info -->
                <div>
                    <h3 style="margin-bottom: 0.75rem; font-size: 1.1rem; color: var(--text-muted);">Parent / Guardian Details</h3>
                    <ul style="list-style: none; padding: 0; line-height: 1.8; font-size: 0.92rem;">
                        <li><strong>Parent Name:</strong> <?= e($child['parent_name']); ?></li>
                        <li><strong>Contact Phone:</strong> <?= e($child['parent_phone']); ?></li>
                        <li><strong>Email Address:</strong> &#9993; <?= e($child['parent_email']); ?></li>
                        <li><strong>Residential Address:</strong> <?= e($child['parent_address']); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Immunization Schedule & Doses Matrix -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Immunization Timeline & Vaccine Status</h3>
                    <p style="margin: 0; font-size: 0.85rem;">Calculated based on child's Date of Birth (<?= formatDate($child['dob']); ?>) and the standard national vaccine schedule.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vaccine</th>
                            <th>Recommended Age</th>
                            <th>Calculated Due Date</th>
                            <th>Status</th>
                            <th>Administering Hospital</th>
                            <th>Administration Date / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allVaccines as $vac): 
                            $vId = (int)$vac['id'];
                            $dueDate = calculateVaccineDueDate($child['dob'], (int)$vac['recommended_age_days']);
                            $isOverdue = isDateOverdue($dueDate);
                            $isCompleted = isset($completedDoses[$vId]);
                            $isScheduled = isset($scheduledDoses[$vId]);
                        ?>
                            <tr>
                                <td>
                                    <strong><?= e($vac['name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= (int)$vac['doses']; ?> <?= (int)$vac['doses'] === 1 ? 'dose' : 'doses'; ?></small>
                                </td>
                                <td>
                                    <?= (int)$vac['recommended_age_days'] === 0 ? 'At Birth' : (int)$vac['recommended_age_days'] . ' days'; ?>
                                </td>
                                <td>
                                    <strong><?= formatDate($dueDate); ?></strong>
                                </td>
                                <td>
                                    <?php if ($isCompleted): ?>
                                        <span class="badge badge-success">&#10004; Vaccinated</span>
                                    <?php elseif ($isScheduled): ?>
                                        <span class="badge badge-info">Scheduled</span>
                                    <?php elseif ($isOverdue): ?>
                                        <span class="badge badge-danger">&#9888; Overdue</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">&#9203; Upcoming</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isCompleted): ?>
                                        <strong><?= e($completedDoses[$vId]['hospital_name']); ?></strong>
                                    <?php elseif ($isScheduled): ?>
                                        <?= e($scheduledDoses[$vId]['hospital_name']); ?>
                                    <?php else: ?>
                                        <span style="color: var(--text-light);">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isCompleted): ?>
                                        <?= formatDate($completedDoses[$vId]['vaccinated_on'], 'd-m-Y h:i A'); ?><br>
                                        <small style="color: var(--text-muted);"><?= e($completedDoses[$vId]['remarks'] ?: 'Routine administration'); ?></small>
                                    <?php elseif ($isScheduled): ?>
                                        <small>Slot: <?= formatDate($scheduledDoses[$vId]['preferred_date']); ?> (<?= e($scheduledDoses[$vId]['time_slot']); ?>)</small>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted);">Awaiting booking</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ----------------------------------------------------------------------------
// VIEW MODE B: MASTER CHILDREN REGISTRY TABLE
// ----------------------------------------------------------------------------
$search = sanitizeInput($_GET['search'] ?? '');
$genderFilter = sanitizeInput($_GET['gender'] ?? '');

$sql = 'SELECT c.*,
               p.phone AS parent_phone,
               u.name AS parent_name, u.email AS parent_email,
               (SELECT COUNT(*) FROM bookings b WHERE b.child_id = c.id AND b.vaccination_status = "vaccinated") AS doses_completed
        FROM children c
        JOIN parents p ON c.parent_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE 1=1';

$params = [];
if (!empty($search)) {
    $sql .= ' AND (c.name LIKE ? OR u.name LIKE ? OR p.phone LIKE ? OR c.blood_group LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if (!empty($genderFilter)) {
    $sql .= ' AND c.gender = ?';
    $params[] = $genderFilter;
}
$sql .= ' ORDER BY c.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$childrenList = $stmt->fetchAll();

$pageTitle = 'Children Registry';
$pageDescription = 'View all registered infants and children with their immunization progress.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <h1>Children Immunization Registry</h1>
        <p style="margin: 0;">Comprehensive database of all registered infants, guardian contacts, and vaccination histories.</p>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="<?= url('admin/children.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <div style="flex: 2; min-width: 220px;">
                <label for="search" class="form-label">Search Child or Parent</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Child name, parent name, or phone..." value="<?= e($search); ?>">
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-select">
                    <option value="">All Genders</option>
                    <option value="male" <?= $genderFilter === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?= $genderFilter === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?= $genderFilter === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">Filter</button>
                <?php if (!empty($search) || !empty($genderFilter)): ?>
                    <a href="<?= url('admin/children.php'); ?>" class="btn btn-outline btn-sm" style="height: 40px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Children Master Table -->
    <div class="card">
        <?php if (empty($childrenList)): ?>
            <div style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                <p>No child records match your criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child Full Name</th>
                            <th>DOB & Age</th>
                            <th>Gender / Blood Group</th>
                            <th>Parent / Guardian</th>
                            <th>Immunizations Taken</th>
                            <th style="text-align: right;">Medical History</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($childrenList as $ch): ?>
                            <tr>
                                <td>
                                    <strong><a href="<?= url('admin/children.php?id=' . (int)$ch['id']); ?>"><?= e($ch['name']); ?></a></strong><br>
                                    <small style="color: var(--text-muted);">Registered <?= formatDate($ch['created_at']); ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($ch['dob']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= formatAge($ch['dob']); ?> (<?= calculateAgeInDays($ch['dob']); ?> days)</small>
                                </td>
                                <td>
                                    <span><?= ucfirst($ch['gender']); ?></span> &bull; 
                                    <span class="badge badge-info"><?= e($ch['blood_group']); ?></span>
                                </td>
                                <td>
                                    <strong><?= e($ch['parent_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($ch['parent_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-success">&#10004; <?= (int)$ch['doses_completed']; ?> doses</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= url('admin/children.php?id=' . (int)$ch['id']); ?>" class="btn btn-outline btn-sm">
                                        View Schedule &rarr;
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
