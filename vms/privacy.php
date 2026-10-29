<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: privacy.php
 * Description: Privacy policy
 * NOTE: Keep every statement below true for the code you actually ship.
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

// Fixed date: change it only when the policy text really changes.
$lastUpdated = '29-10-2026';

$pageTitle = 'Privacy Policy';
$pageDescription = 'How VMS collects, uses, and protects parent, child, and hospital data.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3.5rem;">
    <div class="ui-legal">
        <nav aria-label="On this page">
            <strong>On this page</strong>
            <a href="#commitment">1. Our commitment</a>
            <a href="#collect">2. What we collect</a>
            <a href="#use">3. How we use it</a>
            <a href="#security">4. Security</a>
            <a href="#cookies">5. Cookies</a>
            <a href="#rights">6. Your rights</a>
            <a href="#contact">7. Contact</a>
        </nav>

        <article class="card">
            <h1>Privacy Policy</h1>
            <p class="meta">Last updated: <?= e($lastUpdated); ?> &middot; Region: Pakistan</p>

            <section id="commitment">
                <h2>1. Our commitment</h2>
                <p>
                    The <strong>Vaccination Management System (VMS)</strong> handles health information about children, so we collect only what is needed to run vaccination schedules and appointments. This page explains what we store, why, and how it is protected.
                </p>
            </section>

            <section id="collect">
                <h2>2. What we collect</h2>
                <ul>
                    <li><strong>Parent or guardian:</strong> name, phone number, email address, home address, and a hashed password.</li>
                    <li><strong>Child profile:</strong> name, date of birth, gender, and blood group.</li>
                    <li><strong>Immunization data:</strong> vaccines booked and given, dates, the center that gave them, and clinical remarks.</li>
                    <li><strong>Hospitals:</strong> center name, phone, city, address, and the vaccines it lists as in stock.</li>
                    <li><strong>Helpdesk messages:</strong> the name, email, phone (optional), and message you send through the contact form.</li>
                </ul>
            </section>

            <section id="use">
                <h2>3. How we use it</h2>
                <ul>
                    <li>To calculate due dates from a child's date of birth and the vaccine catalog.</li>
                    <li>To let parents request appointments and let approved centers see their queue.</li>
                    <li>To let centers record the doses they administer.</li>
                    <li>To produce a printable immunization health card.</li>
                    <li>To limit repeated sign-in and form attempts (rate limiting).</li>
                    <li>To answer helpdesk messages.</li>
                </ul>
                <p>We do not sell, rent, or trade personal or child data to advertisers or other third parties.</p>
            </section>

            <section id="security">
                <h2>4. Security</h2>
                <ul>
                    <li><strong>Passwords</strong> are never stored as plain text. They are hashed with PHP's <code>password_hash()</code> (bcrypt).</li>
                    <li><strong>Database queries</strong> use PDO prepared statements to prevent SQL injection.</li>
                    <li><strong>Forms</strong> that change data carry a CSRF token that the server checks.</li>
                    <li><strong>Session cookies</strong> are set with the <code>HttpOnly</code> and <code>SameSite=Lax</code> flags.</li>
                    <li><strong>Server rules</strong> block web access to configuration, SQL, and log files, and the site redirects to HTTPS on live servers.</li>
                </ul>
                <p>No system is perfectly secure. If you suspect misuse of your account, change your password and contact us.</p>
            </section>

            <section id="cookies">
                <h2>5. Cookies</h2>
                <p>
                    VMS uses only the cookies it needs to keep you signed in and to protect forms. It does not use advertising or analytics cookies.
                </p>
            </section>

            <section id="rights">
                <h2>6. Your rights</h2>
                <p>
                    In the parent portal you can view your child's immunization timeline, edit your profile and your child's details, and print the health card. To delete your account, contact the helpdesk.
                </p>
            </section>

            <section id="contact">
                <h2>7. Contact</h2>
                <p>
                    Questions or privacy concerns? Email <a href="mailto:support@vms.health.local" style="font-weight: 600;">support@vms.health.local</a> or use the <a href="<?= url('contact.php'); ?>" style="font-weight: 600;">contact form</a>.
                </p>
            </section>

            <div class="ui-legal-foot">
                <a href="<?= url('terms.php'); ?>" class="btn btn-outline btn-sm">Terms of Service</a>
                <a href="<?= url('index.php'); ?>" class="btn btn-primary btn-sm">Back to home</a>
            </div>
        </article>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>