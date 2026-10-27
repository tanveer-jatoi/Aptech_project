<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: index.php
 * Description: Public Homepage & Healthcare Immunization Landing Portal
 * ============================================================================
 * 
 * ANIMATION INTEGRATION (Max 3 on page):
 * 1. Hero reveal (name, tagline, CTA button sliding/fading in on load).
 * 2. Live Count-up stats (Registered Hospitals, Children Registered, Vaccinations Completed).
 * 3. Parent review shuffle carousel (auto-rotates, pause on hover/focus).
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/components/count_up.php';
require_once __DIR__ . '/includes/components/cards_grid.php';

$pdo = getDBConnection();

// Fetch Live Database Metrics for Count-Up Animation
try {
    $statHospitals = (int)$pdo->query('SELECT COUNT(*) FROM hospitals WHERE status = "active"')->fetchColumn();
    $statChildren  = (int)$pdo->query('SELECT COUNT(*) FROM children')->fetchColumn();
    $statCompleted = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE vaccination_status = "vaccinated"')->fetchColumn();
} catch (PDOException $e) {
    error_log('[HOMEPAGE STATS ERROR] ' . $e->getMessage());
    $statHospitals = $statChildren = $statCompleted = 0;
}

// Fetch Featured Vaccines
$vacStmt = $pdo->query('SELECT * FROM vaccines WHERE status = "available" ORDER BY recommended_age_days ASC LIMIT 6');
$featuredVaccines = $vacStmt->fetchAll();

$pageTitle = 'Home | Child Vaccination Management System';
$pageDescription = 'Protecting child health through automated vaccination schedules, hospital booking, and verified records.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. ANIMATION COMPONENT: HERO REVEAL -->
<?php require_once __DIR__ . '/includes/components/hero.php'; ?>

<!-- 2. ANIMATION COMPONENT: REAL DATABASE COUNT-UP STATISTICS -->
<section style="background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 2.5rem 0;" aria-label="National System Statistics">
    <div class="container">
        <div style="text-align: center; margin-bottom: 1.75rem;">
            <span class="badge badge-info" style="margin-bottom: 0.35rem;">Live Healthcare Impact</span>
            <h2 style="font-size: 1.5rem; margin: 0;">Verified National Immunization Metrics</h2>
        </div>

        <div class="grid grid-3" style="gap: 1.5rem;">
            <?php count_up($statHospitals, 'Participating Hospitals', '&#127973;', 'Active accredited pediatric centers'); ?>
            <?php count_up($statChildren, 'Children Protected', '&#128102;', 'Registered on national schedule'); ?>
            <?php count_up($statCompleted, 'Vaccinations Completed', '&#10004;', 'Clinical doses administered & verified'); ?>
        </div>
    </div>
</section>

<!-- 3. PROCESS STEPS SECTION -->
<div class="container" style="padding-top: 3.5rem; padding-bottom: 3.5rem;">
    <?php require_once __DIR__ . '/includes/components/process_steps.php'; ?>
</div>

<!-- 4. FEATURED VACCINE CATALOG SECTION -->
<section style="background: var(--bg-subtle); padding: 3.5rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);" aria-label="Recommended Vaccines">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 0.5rem;">Recommended Schedule</span>
                <h2 style="font-size: 1.75rem; margin: 0;">Essential Childhood Vaccines</h2>
                <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">Key immunizations recommended by pediatric health authorities from birth onward.</p>
            </div>
            <a href="<?= url('vaccines.php'); ?>" class="btn btn-outline">
                View Full Vaccine Catalog &rarr;
            </a>
        </div>

        <?php renderVaccinesGrid($featuredVaccines, false); ?>
    </div>
</section>

<!-- 5. ANIMATION COMPONENT: PARENT REVIEW SHUFFLE CAROUSEL -->
<div class="container" style="padding-top: 3.5rem; padding-bottom: 2rem;">
    <?php require_once __DIR__ . '/includes/components/review_shuffle.php'; ?>
</div>

<!-- 6. CALL TO ACTION BANNER -->
<section style="background: linear-gradient(135deg, var(--primary-dark), #0f172a); color: #ffffff; padding: 4rem 1rem; text-align: center; margin-top: 2rem;" aria-label="Call to Action">
    <div class="container">
        <h2 style="color: #ffffff; font-size: 2.2rem; margin-bottom: 1rem;">
            Never Miss Another Child Vaccination Date
        </h2>
        <p style="max-width: 650px; margin: 0 auto 2rem; color: #cbd5e1; font-size: 1.1rem; line-height: 1.6;">
            Create your parent profile today, add your newborn or toddler, and let our automated immunization engine keep your family safe and up to date.
        </p>
        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <?php if (isLoggedIn()): ?>
                <a href="<?= url(currentRole() . '/dashboard.php'); ?>" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1rem;">
                    Go to Your Portal Dashboard &rarr;
                </a>
            <?php else: ?>
                <a href="<?= url('register.php'); ?>" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1rem;">
                    Create Free Parent Account
                </a>
                <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline" style="padding: 0.85rem 1.5rem; font-size: 1rem; color: #ffffff; border-color: #64748b;">
                    Browse Vaccination Centers
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
