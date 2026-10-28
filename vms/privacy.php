<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: privacy.php
 * Description: Privacy Policy & Child Healthcare Data Protection Practices
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

$pageTitle = 'Privacy Policy & Data Practices';
$pageDescription = 'Learn how VMS Pakistan securely handles parent profiles, child immunization records, and healthcare data.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3.5rem;">
    <!-- Breadcrumbs / Top Indicator -->
    <div style="margin-bottom: 1.5rem;">
        <span class="badge badge-info">Privacy &amp; Data Protection</span>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto; padding: 2.5rem 2rem;">
        <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem; color: var(--text-main);">Privacy Policy</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            Last Updated: <?= date('d-m-Y'); ?> &bull; Region: Pakistan
        </p>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">1. Commitment to Child Healthcare Privacy</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                At the <strong>Vaccination Management System (VMS)</strong>, protecting the privacy and sensitive health data of children and their guardians is our highest priority. This policy outlines exactly what information we collect, how it is used, how it is secured, and your rights as a parent or healthcare provider.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">2. Information We Collect</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">We collect only the minimum information necessary to safely manage vaccination schedules and appointments:</p>
            <ul style="margin-left: 1.5rem; line-height: 1.7; color: var(--text-muted);">
                <li><strong>Parent / Guardian Information:</strong> Full name, contact phone number, email address, residential physical address, and securely hashed account password.</li>
                <li><strong>Child Profile Records:</strong> Full name, Date of Birth (DOB), biological gender, and blood group.</li>
                <li><strong>Clinical Immunization Data:</strong> Vaccine doses scheduled and administered, timestamps of administration, administering hospital center, and attending clinician observations.</li>
                <li><strong>Healthcare Facility Information:</strong> Hospital name, representative name, contact hotline, physical address, city, and declared vaccine inventory stock.</li>
            </ul>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">3. How We Use Your Data</h2>
            <ul style="margin-left: 1.5rem; line-height: 1.7; color: var(--text-muted);">
                <li>To calculate dynamic immunization milestone due dates based on the child's DOB and national healthcare schedules.</li>
                <li>To facilitate appointment booking between parents and accredited vaccination centers.</li>
                <li>To enable medical personnel to verify child eligibility and record administered doses.</li>
                <li>To generate printable Child Health &amp; Immunization Certificates for school admissions and records.</li>
                <li>To prevent fraud, abuse, and brute-force access attempts via security rate limiting.</li>
            </ul>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">4. Technical Security &amp; Data Safeguards</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">We enforce multi-layered engineering controls to keep healthcare records confidential and secure:</p>
            <ul style="margin-left: 1.5rem; line-height: 1.7; color: var(--text-muted);">
                <li><strong>Cryptographic Password Hashing:</strong> Passwords are never stored in plain text; they are hashed using the strong Bcrypt algorithm (<code>PASSWORD_DEFAULT</code>).</li>
                <li><strong>Database Isolation &amp; Prepared Statements:</strong> All database queries use PDO prepared statements with parameter binding to prevent SQL Injection. Direct database file access is strictly blocked at server level.</li>
                <li><strong>Session &amp; Cookie Security:</strong> Session cookies carry <code>HttpOnly</code> (inaccessible to client scripts), <code>SameSite=Lax</code>, and automatic inactivity expiration.</li>
                <li><strong>Cross-Site Request Forgery (CSRF):</strong> Every state-changing form submission requires a cryptographically generated 64-character token verified on the server.</li>
                <li><strong>Zero Layout Tracking &amp; No Data Selling:</strong> We never sell, rent, or trade your child's data or personal contact info to advertisers or third parties.</li>
            </ul>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">5. Cookie Policy &amp; Analytics</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                Our platform uses strictly necessary cookies solely to maintain your authenticated login session and CSRF security. We provide an accessible Cookie Preferences banner where you can choose between essential-only cookies or allowing privacy-friendly, anonymized page view statistics.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">6. Guardian Rights</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                You retain full control over your family's records. From the parent portal, you can view your child's complete immunization timeline, update demographic details, download official records, or request account removal by contacting system administration.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">7. Privacy Contact &amp; Inquiries</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                If you have questions about how your child's information is handled, or wish to report a privacy concern, please contact our data coordinator at <a href="mailto:support@vms.health.local" style="font-weight: 600;">support@vms.health.local</a> or submit a message through our <a href="<?= url('contact.php'); ?>" style="font-weight: 600;">Helpdesk Contact Form</a>.
            </p>
        </section>

        <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <a href="<?= url('terms.php'); ?>" class="btn btn-outline btn-sm">
                &larr; View Terms of Service
            </a>
            <a href="<?= url('index.php'); ?>" class="btn btn-primary btn-sm">
                Return to Homepage
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

