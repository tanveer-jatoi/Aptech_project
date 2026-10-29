<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/reports.php
 * Description: Child Immunization History & Official Printable Health Card
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Report of all completed vaccinations for each child under parent guardianship.
 * - Formats official printable certificate for daycare/school registration and travel.
 * - Clean @media print layout removing UI chrome on paper/PDF export.
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
$parent = $pStmt->fetch() ?: ['phone' => '-', 'address' => '-'];
$parentId = (int)($parent['id'] ?? 0);

// Fetch Children for this Parent
$cStmt = $pdo->prepare('SELECT id, name, dob, gender, blood_group FROM children WHERE parent_id = ? ORDER BY name ASC');
$cStmt->execute([$parentId]);
$children = $cStmt->fetchAll();

if (empty($children)) {
    setFlash('warning', 'Please add a child profile before viewing vaccination reports.');
    header('Location: ' . url('parent/children.php?action=add'));
    exit;
}

// Select active child
$selectedChildId = (int)($_GET['child_id'] ?? $children[0]['id']);

// Find active child object
$selectedChild = null;
foreach ($children as $c) {
    if ((int)$c['id'] === $selectedChildId) {
        $selectedChild = $c;
        break;
    }
}
if (!$selectedChild) {
    $selectedChild = $children[0];
    $selectedChildId = (int)$selectedChild['id'];
}

// Fetch Completed Vaccinations for this Child
$vHistStmt = $pdo->prepare(
    'SELECT b.*,
            h.name AS hospital_name, h.city AS hospital_city, h.phone AS hospital_phone,
            v.name AS vaccine_name, v.description AS vaccine_desc, v.doses AS vaccine_doses
     FROM bookings b
     JOIN hospitals h ON b.hospital_id = h.id
     JOIN vaccines v ON b.vaccine_id = v.id
     WHERE b.child_id = ? AND b.parent_id = ? AND b.vaccination_status = \'vaccinated\'
     ORDER BY b.vaccinated_on ASC'
);
$vHistStmt->execute([$selectedChildId, $parentId]);
$completedDoses = $vHistStmt->fetchAll();

// Fetch Master Vaccine Catalog for Completion Progress Calculation
$allVaccines = $pdo->query('SELECT * FROM vaccines WHERE status = \'available\' ORDER BY recommended_age_days ASC')->fetchAll();
$totalVaccinesCount = count($allVaccines);
// Count each vaccine once, even if several doses were recorded, so progress never passes 100%.
$completedCount  = count(array_unique(array_map('intval', array_column($completedDoses, 'vaccine_id'))));
$percentComplete = $totalVaccinesCount > 0 ? min(100, (int)round(($completedCount / $totalVaccinesCount) * 100)) : 0;

$pageTitle = 'Immunization Report: ' . $selectedChild['name'];
$pageDescription = 'Official record of completed vaccinations and immunization history.';
require_once __DIR__ . '/../includes/header.php';
uiStyles();
?>

<!-- Print Style Directives -->
<style>
@media print {
    .navbar, .footer, .no-print, .btn, .toast-container {
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
    .cert-box {
        border: 4px double #0284c7 !important;
        padding: 2.5rem !important;
    }
}
</style>

<div class="container">
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Child Vaccination History & Records</h1>
            <p style="margin: 0;">Official clinical immunization report verified by licensed healthcare centers.</p>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn btn-primary ui-btn-icon" onclick="window.print()">
                <?= uiIcon('print', 16); ?> Print health card
            </button>
            <a href="<?= url('parent/book.php?child_id=' . $selectedChildId); ?>" class="btn btn-outline">
                <?= uiIcon('plus', 16); ?> Book Next Dose
            </a>
        </div>
    </div>

    <!-- Child Selector Bar (Hidden when printing) -->
    <div class="card no-print" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="<?= url('parent/reports.php'); ?>" method="GET" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 250px;">
                <label for="child_id" class="form-label">Select Child Profile</label>
                <select id="child_id" name="child_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($children as $c): ?>
                        <option value="<?= (int)$c['id']; ?>" <?= $selectedChildId === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?= e($c['name']); ?> (DOB: <?= formatDate($c['dob']); ?>) &bull; <?= formatAge($c['dob']); ?> old
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-outline btn-sm" style="height: 40px;">Load Record</button>
        </form>
    </div>

    <!-- Immunization Progress Bar -->
    <div class="card no-print" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
            <strong>National Immunization Schedule Progress</strong>
            <span><strong><?= $completedCount; ?></strong> of <?= $totalVaccinesCount; ?> catalog vaccines completed (<?= $percentComplete; ?>%)</span>
        </div>
        <div style="background-color: var(--bg-subtle); height: 12px; border-radius: var(--radius-full); overflow: hidden;">
            <div style="background-color: var(--teal); width: <?= $percentComplete; ?>%; height: 100%; transition: width 0.4s ease;"></div>
        </div>
    </div>

    <!-- Official Printable Immunization Health Certificate Card -->
    <div class="card cert-box">
        <!-- Header -->
        <div style="text-align: center; border-bottom: 2px solid var(--primary); padding-bottom: 1.25rem; margin-bottom: 1.5rem;">
            <span style="font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--primary);">
                National Child Immunization Initiative
            </span>
            <h2 style="font-size: 1.8rem; margin: 0.35rem 0; color: var(--text-main);">
                Certificate of Completed Immunizations
            </h2>
            <p style="margin: 0; font-size: 0.88rem; color: var(--text-muted);">
                Electronic immunization record &bull; Generated by the VMS Health System
            </p>
        </div>

        <!-- Demographics Box -->
        <div class="grid grid-2" style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <div>
                <h4 style="margin-bottom: 0.5rem; color: var(--primary-dark);">Child Patient Information</h4>
                <table style="width: 100%; font-size: 0.9rem; line-height: 1.8;">
                    <tr><td style="width: 110px; color: var(--text-muted);">Full Name:</td><td><strong><?= e($selectedChild['name']); ?></strong></td></tr>
                    <tr><td style="color: var(--text-muted);">Date of Birth:</td><td><?= formatDate($selectedChild['dob']); ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Age:</td><td><?= formatAge($selectedChild['dob']); ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Gender / Blood:</td><td><?= ucfirst($selectedChild['gender']); ?> &bull; <strong><?= e($selectedChild['blood_group']); ?></strong></td></tr>
                </table>
            </div>

            <div>
                <h4 style="margin-bottom: 0.5rem; color: var(--primary-dark);">Guardian Information</h4>
                <table style="width: 100%; font-size: 0.9rem; line-height: 1.8;">
                    <tr><td style="width: 110px; color: var(--text-muted);">Parent Name:</td><td><strong><?= e(currentUserName()); ?></strong></td></tr>
                    <tr><td style="color: var(--text-muted);">Contact Phone:</td><td><?= e($parent['phone']); ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Address:</td><td><?= e($parent['address']); ?></td></tr>
                    <tr><td style="color: var(--text-muted);">Issued on:</td><td><?= date('d-m-Y'); ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Completed Doses Table -->
        <div class="card-header" style="border: none; padding-bottom: 0.5rem;">
            <h3>Administered Immunization Doses</h3>
        </div>

        <?php if (empty($completedDoses)): ?>
            <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); background: #ffffff; border: 1px dashed var(--border-color); border-radius: var(--radius-md);">
                <p style="font-size: 1.05rem; margin-bottom: 0.35rem;">No completed vaccinations recorded yet for <?= e($selectedChild['name']); ?>.</p>
                <small>Once appointments are attended and confirmed by the hospital, official records will appear here.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Vaccine Administered</th>
                            <th>Administering Healthcare Center</th>
                            <th>Date Administered</th>
                            <th>Time Recorded</th>
                            <th>Clinical Remarks / Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedDoses as $dose): ?>
                            <tr>
                                <td>
                                    <strong><?= e($dose['vaccine_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($dose['vaccine_desc']); ?></small>
                                </td>
                                <td>
                                    <strong><?= e($dose['hospital_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($dose['hospital_city']); ?></small>
                                </td>
                                <td>
                                    <strong style="color: var(--success);"><?= uiIcon('check', 14); ?> <?= formatDate($dose['vaccinated_on'], 'd-m-Y'); ?></strong>
                                </td>
                                <td>
                                    <?= formatDate($dose['vaccinated_on'], 'h:i A'); ?>
                                </td>
                                <td>
                                    <small style="color: var(--text-muted); font-style: italic;">
                                        <?= e($dose['remarks'] ?: 'Routine dose administered with standard pediatric tolerances.'); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Official Seal and Signature Lines for Print Verification -->
        <div style="margin-top: 3.5rem; display: flex; justify-content: space-between; padding-top: 2rem; border-top: 1px dashed #94a3b8;">
            <div style="text-align: center; width: 230px;">
                <div style="border-bottom: 1px solid #334155; height: 35px; margin-bottom: 0.5rem;"></div>
                <small style="font-weight: 600; color: var(--text-muted);">Authorized Medical Officer Stamp</small>
            </div>
            <div style="text-align: center; width: 230px;">
                <div style="border-bottom: 1px solid #334155; height: 35px; margin-bottom: 0.5rem;"></div>
                <small style="font-weight: 600; color: var(--text-muted);">Healthcare Facility Verification</small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>