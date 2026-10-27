<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: hospitals.php
 * Description: Public Participating Pediatric Healthcare Centers Directory
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/components/cards_grid.php';

$pdo = getDBConnection();

$search     = sanitizeInput($_GET['search'] ?? '');
$filterCity = sanitizeInput($_GET['city'] ?? '');

$sql = 'SELECT h.*,
               (SELECT COUNT(*) FROM hospital_vaccines hv WHERE hv.hospital_id = h.id AND hv.status = "available") AS available_stock_count
        FROM hospitals h
        WHERE h.status = "active"';
$params = [];

if (!empty($search)) {
    $sql .= ' AND (h.name LIKE ? OR h.location LIKE ? OR h.address LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filterCity)) {
    $sql .= ' AND h.city = ?';
    $params[] = $filterCity;
}

$sql .= ' ORDER BY h.city ASC, h.name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hospitals = $stmt->fetchAll();

// Distinct cities list
$cities = $pdo->query('SELECT DISTINCT city FROM hospitals WHERE status = "active" ORDER BY city ASC')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Participating Healthcare Centers | Hospitals';
$pageDescription = 'Discover authorized pediatric hospitals and vaccination clinics near you.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
    <!-- Page Header -->
    <div style="text-align: center; max-width: 750px; margin: 0 auto 2.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Accredited Healthcare Network</span>
        <h1 style="font-size: 2.25rem; margin-bottom: 0.75rem;">Participating Vaccination Centers</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin: 0;">
            Browse certified pediatric hospitals, community health centers, and clinical facilities administering approved vaccines in our digital network.
        </p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
        <form action="<?= url('hospitals.php'); ?>" method="GET" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end;">
            <div style="flex: 2; min-width: 240px;">
                <label for="search" class="form-label">Search Center by Name or Location</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Hospital name, district, or landmark..." value="<?= e($search); ?>">
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label for="city" class="form-label">Filter by City</label>
                <select id="city" name="city" class="form-select">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c); ?>" <?= $filterCity === $c ? 'selected' : ''; ?>><?= e($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="height: 42px;">Search</button>
                <?php if (!empty($search) || !empty($filterCity)): ?>
                    <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline" style="height: 42px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Hospitals Display Grid -->
    <?php if (empty($hospitals)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">No participating hospitals found matching your criteria.</p>
            <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline btn-sm">Clear Search Filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid-3" style="gap: 1.25rem;">
            <?php foreach ($hospitals as $h): ?>
                <article class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <span class="badge badge-info">&#128205; <?= e($h['city']); ?></span>
                            <span class="badge badge-success">&#10004; Verified Center</span>
                        </div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.35rem; color: var(--text-main);"><?= e($h['name']); ?></h3>
                        <p style="font-size: 0.88rem; color: var(--primary-dark); font-weight: 600; margin-bottom: 0.5rem;">
                            <?= e($h['location']); ?>
                        </p>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                            <?= e($h['address']); ?>
                        </p>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                        <small style="color: var(--text-muted); font-weight: 600;">
                            &#128222; <?= e($h['phone']); ?>
                        </small>
                        <a href="<?= url('login.php'); ?>" class="btn btn-primary btn-sm">
                            Book Slot &rarr;
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
