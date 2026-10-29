<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: index.php
 * Description: Public homepage
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/includes/components/count_up.php';
require_once __DIR__ . '/includes/components/cards_grid.php';

$pdo = getDBConnection();

// Live counts and featured vaccines. A database problem must not take the whole homepage down.
$statHospitals = $statChildren = $statCompleted = 0;
$featuredVaccines = [];

try {
    $statHospitals = (int)$pdo->query('SELECT COUNT(*) FROM hospitals WHERE status = \'active\'')->fetchColumn();
    $statChildren  = (int)$pdo->query('SELECT COUNT(*) FROM children')->fetchColumn();
    $statCompleted = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE vaccination_status = \'vaccinated\'')->fetchColumn();

    $featuredVaccines = $pdo->query(
        'SELECT * FROM vaccines WHERE status = \'available\' ORDER BY recommended_age_days ASC LIMIT 6'
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('[VMS HOMEPAGE ERROR] ' . $ex->getMessage());
}

$pageTitle = 'Child Vaccination Management System Pakistan';
$pageDescription = 'Track childhood vaccinations, find a vaccination center, and keep a printable immunization record.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<!-- 1. Hero -->
<?php include __DIR__ . '/includes/components/hero.php'; ?>

<!-- 2. Live platform numbers -->
<section class="ui-stats" aria-label="Platform statistics">
    <div class="container">
        <div class="ui-center" style="margin-bottom: 1.75rem;">
            <h2 style="font-size: 1.5rem; margin: 0;">VMS in numbers</h2>
        </div>

        <div class="grid grid-3" style="gap: 1.5rem;">
            <?php count_up($statHospitals, 'Participating hospitals', '', 'Active centers in the network'); ?>
            <?php count_up($statChildren, 'Children registered', '', 'Profiles with a vaccination schedule'); ?>
            <?php count_up($statCompleted, 'Vaccinations recorded', '', 'Doses confirmed by a center'); ?>
        </div>
    </div>
</section>

<!-- 3. How it works -->
<div class="container" style="padding-top: 3.5rem; padding-bottom: 3.5rem;">
    <?php include __DIR__ . '/includes/components/process_steps.php'; ?>
</div>

<!-- 4. Featured vaccines -->
<section class="ui-section-muted" aria-label="Recommended vaccines">
    <div class="container">
        <div class="ui-section-head">
            <div>
                <span class="badge badge-info">Recommended schedule</span>
                <h2>Essential childhood vaccines</h2>
                <p>The first immunizations a child needs, from birth onward.</p>
            </div>
            <a href="<?= url('vaccines.php'); ?>" class="btn btn-outline ui-btn-icon">Full vaccine catalog <?= uiIcon('arrow', 16); ?></a>
        </div>

        <?php if (!empty($featuredVaccines)): ?>
            <?php renderVaccinesGrid($featuredVaccines, false); ?>
        <?php else: ?>
            <div class="card ui-empty" style="background: #fff;">
                <p style="margin: 0;">The vaccine list is not available right now. Please check back soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 5. Parent feedback component (see note: label it as sample feedback if the text is hard-coded) -->
<div class="container" style="padding-top: 3.5rem; padding-bottom: 2.5rem;">
    <?php include __DIR__ . '/includes/components/review_shuffle.php'; ?>
</div>

<!-- 6. Call to action -->
<section class="ui-cta-dark" aria-label="Get started">
    <div class="container">
        <h2>Never miss a vaccination date</h2>
        <p>Create a parent profile, add your child, and VMS works out every due date for you.</p>
        <div class="ui-btn-row">
            <?php if (isLoggedIn()): ?>
                <a href="<?= url(currentRole() . '/dashboard.php'); ?>" class="btn btn-outline-light" style="padding: .85rem 1.75rem;">Go to your dashboard</a>
            <?php else: ?>
                <a href="<?= url('register.php'); ?>" class="btn btn-outline-light" style="padding: .85rem 1.75rem;">Create a free parent account</a>
                <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline" style="padding: .85rem 1.5rem; color: #e2e8f0; border-color: #64748b;">Browse vaccination centers</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>