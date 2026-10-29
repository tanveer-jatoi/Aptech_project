<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: vaccines.php
 * Description: Public vaccine catalog grouped by recommended age (timeline)
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/includes/components/cards_grid.php';

$pdo = getDBConnection();

$search       = mb_substr(trim((string)($_GET['search'] ?? '')), 0, 80);
$filterStatus = (string)($_GET['status'] ?? '');
if (!in_array($filterStatus, ['available', 'unavailable'], true)) {
    $filterStatus = '';
}

$sql    = 'SELECT * FROM vaccines WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR description LIKE ?)';
    $term = '%' . likeEscape($search) . '%';
    array_push($params, $term, $term);
}
if ($filterStatus !== '') {
    $sql .= ' AND status = ?';
    $params[] = $filterStatus;
}
$sql .= ' ORDER BY recommended_age_days ASC, name ASC';

$vaccines = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vaccines = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('[VMS VACCINES ERROR] ' . $ex->getMessage());
    $dbFailed = true;
}

// Group by recommended age so parents read it as a timeline: Birth -> 6 weeks -> 10 weeks ...
$groups = [];
foreach ($vaccines as $v) {
    $groups[(int)$v['recommended_age_days']][] = $v;
}
ksort($groups);

$pageTitle = 'Vaccine Catalog & Schedule';
$pageDescription = 'Recommended childhood vaccines grouped by age, from birth to 15 months and beyond.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">

    <?php uiPageHeader(
        'Childhood vaccine schedule',
        'Every vaccine in the catalog, grouped by the age at which it is recommended.',
        'Immunization catalog'
    ); ?>

    <?php if (!empty($dbFailed)): ?>
        <div class="alert alert-danger" role="alert"><span>The vaccine list could not be loaded. Please try again shortly.</span></div>
    <?php endif; ?>

    <form action="<?= url('vaccines.php'); ?>" method="GET" class="card ui-filter" role="search">
        <div class="grow">
            <label for="search" class="form-label">Search by vaccine or disease</label>
            <input type="search" id="search" name="search" class="form-control" maxlength="80"
                   placeholder="e.g. Polio, Measles, BCG, Hepatitis" value="<?= e($search); ?>">
        </div>

        <div class="mid">
            <label for="status" class="form-label">Availability</label>
            <select id="status" name="status" class="form-select">
                <option value="">All</option>
                <option value="available" <?= $filterStatus === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="unavailable" <?= $filterStatus === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
            </select>
        </div>

        <div class="btns">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search !== '' || $filterStatus !== ''): ?>
                <a href="<?= url('vaccines.php'); ?>" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($vaccines)): ?>
        <div class="card ui-empty">
            <?= uiIcon('syringe', 36); ?>
            <h3>No vaccines match your search</h3>
            <p>Check the spelling or clear the filters to see the full catalog.</p>
            <a href="<?= url('vaccines.php'); ?>" class="btn btn-outline btn-sm">Clear filters</a>
        </div>
    <?php else: ?>
        <p class="ui-count"><?= count($vaccines); ?> <?= count($vaccines) === 1 ? 'vaccine' : 'vaccines'; ?> in <?= count($groups); ?> age <?= count($groups) === 1 ? 'group' : 'groups'; ?></p>

        <?php foreach ($groups as $days => $items): ?>
            <section class="ui-vgroup" aria-labelledby="age-<?= (int)$days; ?>">
                <div class="ui-vgroup-head">
                    <h2 id="age-<?= (int)$days; ?>"><?= e(ageGroupLabel((int)$days)); ?></h2>
                </div>
                <?php renderVaccinesGrid($items, false); ?>
            </section>
        <?php endforeach; ?>

        <div class="ui-note" style="margin-top: 1rem;">
            <strong>Note:</strong> VMS is a tracking and booking tool, not medical advice.
            Ask your pediatrician if your child needs a different schedule.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>