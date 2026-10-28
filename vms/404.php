<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: 404.php
 * Description: Professional Custom 404 Not Found Error Handler
 * ============================================================================
 */

define('VMS_APP', true);

http_response_code(404);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

$pageTitle = 'Page Not Found (404)';
$pageDescription = 'The requested healthcare resource or page could not be located.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 4.5rem;">
    <div class="card" style="max-width: 650px; margin: 0 auto; text-align: center; padding: 3rem 2rem;">
        <div style="width: 72px; height: 72px; border-radius: 50%; border: 3px solid var(--danger); color: var(--danger); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 1.5rem;">
            &#9888;
        </div>

        <span class="badge badge-danger" style="margin-bottom: 0.75rem; font-size: 0.85rem;">Error 404</span>
        <h1 style="font-size: 2.2rem; margin-bottom: 0.75rem; color: var(--text-main);">Page Not Located</h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 2rem;">
            The medical page, appointment URL, or record you requested could not be found. It may have been moved, updated, or the link may be outdated.
        </p>

        <div style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2rem; text-align: left;">
            <strong style="font-size: 0.9rem; color: var(--text-main);">Helpful Navigation Links:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem; line-height: 1.8; font-size: 0.9rem;">
                <li><a href="<?= url('index.php'); ?>">Return to Homepage</a></li>
                <li><a href="<?= url('vaccines.php'); ?>">View Recommended Vaccines Catalog</a></li>
                <li><a href="<?= url('hospitals.php'); ?>">Find Participating Vaccination Centers</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= url(currentRole() . '/dashboard.php'); ?>">Go to Your Portal Dashboard</a></li>
                <?php else: ?>
                    <li><a href="<?= url('login.php'); ?>">Sign In to Your Account</a></li>
                <?php endif; ?>
                <li><a href="<?= url('contact.php'); ?>">Contact Pediatric Support Helpdesk</a></li>
            </ul>
        </div>

        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?= url('index.php'); ?>" class="btn btn-primary">
                &larr; Return to Home
            </a>
            <a href="<?= url('contact.php'); ?>" class="btn btn-outline">
                Report Broken Link
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

