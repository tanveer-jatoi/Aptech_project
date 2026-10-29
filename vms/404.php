<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: 404.php
 * Description: Custom 404 Not Found page
 * ============================================================================
 */

define('VMS_APP', true);

http_response_code(404);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

$pageTitle = 'Page Not Found (404)';
$pageDescription = 'The requested page could not be located.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4.5rem;">
    <div class="card ui-center-card">
        <div class="ui-hero-icon is-bad"><?= uiIcon('alert', 32); ?></div>

        <span class="badge badge-danger">Error 404</span>
        <h1 style="font-size: 2rem; margin: .75rem 0;">We couldn't find that page</h1>
        <p style="color: var(--text-muted); line-height: 1.6; margin: 0 0 1.75rem;">
            The link may be outdated or the page may have moved. Search the vaccine catalog or use one of the links below.
        </p>

        <form action="<?= url('vaccines.php'); ?>" method="GET" role="search"
              style="display: flex; gap: .5rem; max-width: 440px; margin: 0 auto 1.75rem;">
            <label for="q404" class="ui-sr-only">Search vaccines</label>
            <input type="search" id="q404" name="search" class="form-control" placeholder="Search a vaccine, e.g. Polio" maxlength="60">
            <button type="submit" class="btn btn-primary ui-btn-icon"><?= uiIcon('search', 16); ?> Search</button>
        </form>

        <div class="ui-note">
            <strong style="font-size: .92rem;">Useful links</strong>
            <ul class="ui-nav-list">
                <li><a href="<?= url('index.php'); ?>"><?= uiIcon('arrow', 16); ?> Homepage</a></li>
                <li><a href="<?= url('vaccines.php'); ?>"><?= uiIcon('syringe', 16); ?> Vaccine catalog</a></li>
                <li><a href="<?= url('hospitals.php'); ?>"><?= uiIcon('hospital', 16); ?> Vaccination centers</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= url(currentRole() . '/dashboard.php'); ?>"><?= uiIcon('user', 16); ?> Your dashboard</a></li>
                <?php else: ?>
                    <li><a href="<?= url('login.php'); ?>"><?= uiIcon('lock', 16); ?> Sign in</a></li>
                <?php endif; ?>
                <li><a href="<?= url('contact.php'); ?>"><?= uiIcon('mail', 16); ?> Contact the helpdesk</a></li>
            </ul>
        </div>

        <div class="ui-btn-row" style="margin-top: 1.75rem;">
            <a href="<?= url('index.php'); ?>" class="btn btn-primary ui-btn-icon"><?= uiIcon('back', 16); ?> Back to home</a>
            <a href="<?= url('contact.php?subject=broken-link'); ?>" class="btn btn-outline">Report a broken link</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>