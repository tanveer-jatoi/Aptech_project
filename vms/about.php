<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: about.php
 * Description: About the initiative, how the system works, FAQ
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

$pageTitle = 'About | Child Vaccination Management System';
$pageDescription = 'Learn how VMS helps parents in Pakistan track childhood immunizations and book vaccination slots.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">

    <?php uiPageHeader(
        'Making sure no child misses a vaccine',
        'VMS gives parents one place to track immunization dates, find a vaccination center, and keep a printable record.',
        'About VMS'
    ); ?>

    <div class="ui-split" style="margin-bottom: 3.5rem;">
        <div class="card is-bad">
            <h3 style="color: var(--danger);"><?= uiIcon('alert', 26); ?> <span style="color: var(--text-main);">The problem: paper cards get lost</span></h3>
            <p>
                Vaccination records in Pakistan are mostly kept on paper cards. When a card is lost or a clinic changes, parents are left guessing which doses were given, and boosters get delayed or skipped.
            </p>
            <p style="margin: 0;">
                Clinics also have no shared view of what a child has already received elsewhere.
            </p>
        </div>

        <div class="card is-ok">
            <h3 style="color: var(--success);"><?= uiIcon('shield', 26); ?> <span style="color: var(--text-main);">The solution: one digital record</span></h3>
            <p>
                Once a parent enters a child's date of birth, VMS calculates every due date from the EPI-based schedule in the vaccine catalog.
            </p>
            <p style="margin: 0;">
                Parents can find centers that stock a vaccine, request an appointment, and print an immunization health card for school or daycare admission.
            </p>
        </div>
    </div>

    <div class="ui-center" style="margin-bottom: 1.75rem;">
        <h2 style="font-size: 1.75rem; margin-bottom: .4rem;">How it works for each user</h2>
        <p style="color: var(--text-muted); margin: 0;">Three roles share one system: parents, vaccination centers, and administrators.</p>
    </div>

    <div class="ui-roles" style="margin-bottom: 4rem;">
        <div class="card ui-role">
            <div class="ui-hero-icon is-brand"><?= uiIcon('users', 26); ?></div>
            <h3>Parents</h3>
            <p>Add each child, see overdue and upcoming doses on the dashboard, request appointments, and print the health card at any time.</p>
        </div>

        <div class="card ui-role">
            <div class="ui-hero-icon is-ok"><?= uiIcon('hospital', 26); ?></div>
            <h3>Vaccination centers</h3>
            <p>See the approved patient queue, record each dose given with clinical remarks, and keep vaccine availability up to date.</p>
        </div>

        <div class="card ui-role">
            <div class="ui-hero-icon is-bad"><?= uiIcon('scale', 26); ?></div>
            <h3>Administrators</h3>
            <p>Review appointment requests, manage participating centers, maintain the vaccine catalog, and view reports.</p>
        </div>
    </div>

    <section class="ui-faq" aria-labelledby="faq-title">
        <h2 id="faq-title" class="ui-center" style="font-size: 1.6rem; margin-bottom: 1.5rem;">Frequently asked questions</h2>

        <details>
            <summary>How are due dates calculated?</summary>
            <p>VMS takes the child's date of birth and adds the recommended age in days for each vaccine. For example, BCG is due at birth, Polio and Pentavalent at 42 days (6 weeks), and Measles at 270 days (9 months).</p>
        </details>

        <details>
            <summary>Why does an appointment need admin approval?</summary>
            <p>The administrator checks that the vaccine suits the child's age and that the chosen center can take the appointment before the family travels there.</p>
        </details>

        <details>
            <summary>Can I print my child's immunization record?</summary>
            <p>Yes. Open Reports in the parent portal and choose Print. The health card lists every recorded dose with the center, date, and space for a stamp and signature.</p>
        </details>

        <details>
            <summary>What if my appointment is rejected or I miss it?</summary>
            <p>Open My Appointments, check the admin's note, and book the same vaccine again with a new date.</p>
        </details>
    </section>

    <div class="card ui-cta" style="max-width: 780px; margin: 0 auto;">
        <h3 style="font-size: 1.35rem; margin-bottom: .4rem;">Start tracking your child's vaccines</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.4rem;">Create a free parent account, add your child, and see the full schedule in a minute.</p>
        <div class="ui-btn-row">
            <?php if (isLoggedIn()): ?>
                <a href="<?= url(currentRole() . '/dashboard.php'); ?>" class="btn btn-primary ui-btn-icon">Open dashboard <?= uiIcon('arrow', 16); ?></a>
            <?php else: ?>
                <a href="<?= url('register.php'); ?>" class="btn btn-primary">Create parent account</a>
                <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline">Browse vaccination centers</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>