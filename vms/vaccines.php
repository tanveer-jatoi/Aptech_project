<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: vaccines.php
 * Description: Public Recommended Childhood Vaccines Directory & Schedule
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/components/cards_grid.php';

$pdo = getDBConnection();

$search = sanitizeInput($_GET['search'] ?? '');
$filterStatus = sanitizeInput($_GET['status'] ?? '');

$sql = 'SELECT * FROM vaccines WHERE 1=1';
$params = [];

if (!empty($search)) {
    $sql .= ' AND (name LIKE ? OR description LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filterStatus)) {
    $sql .= ' AND status = ?';
    $params[] = $filterStatus;
}

$sql .= ' ORDER BY recommended_age_days ASC, name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vaccines = $stmt->fetchAll();

$pageTitle = 'Vaccines Directory & Schedule';
$pageDescription = 'Browse recommended pediatric vaccines, dose series, and recommended immunization ages.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 750px; margin: 0 auto 2.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">National Immunization Schedule</span>
        <h1 style="font-size: 2.25rem; margin-bottom: 0.75rem;">Pediatric Vaccines Directory</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin: 0;">
            Comprehensive guide to vital childhood vaccines recommended by pediatric health authorities to shield infants from infectious diseases.
        </p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
        <form action="<?= url('vaccines.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <div style="flex: 2; min-width: 240px;">
                <label for="search" class="form-label">Search Vaccine or Preventable Illness</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="e.g. Polio, Measles, BCG, Hepatitis..." value="<?= e($search); ?>">
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label for="status" class="form-label">Network Availability</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="available" <?= $filterStatus === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="unavailable" <?= $filterStatus === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                <?php if (!empty($search) || !empty($filterStatus)): ?>
                    <a href="<?= url('vaccines.php'); ?>" class="btn btn-outline" style="height: 42px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Vaccines Display Grid -->
    <?php if (empty($vaccines)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No vaccines found matching your search.</p>
            <a href="<?= url('vaccines.php'); ?>" class="btn btn-outline btn-sm">Clear Search</a>
        </div>
    <?php else: ?>
        <?php renderVaccinesGrid($vaccines, false); ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
