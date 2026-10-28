<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: terms.php
 * Description: Terms and Conditions of Service
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

$pageTitle = 'Terms & Conditions of Service';
$pageDescription = 'Review the official terms of service, guardian responsibilities, and medical disclaimers for VMS Pakistan.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3.5rem;">
    <!-- Breadcrumbs / Top Indicator -->
    <div style="margin-bottom: 1.5rem;">
        <span class="badge badge-info">Legal &amp; Compliance</span>
    </div>

    <div class="card" style="max-width: 900px; margin: 0 auto; padding: 2.5rem 2rem;">
        <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem; color: var(--text-main);">Terms and Conditions</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
            Effective Date: <?= date('d-m-Y'); ?> &bull; Jurisdiction: Islamic Republic of Pakistan
        </p>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">1. Acceptance of Terms</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                Welcome to the <strong>Vaccination Management System (VMS)</strong>. By creating an account, registering a child profile, enrolling a medical center, or scheduling vaccination appointments, you agree to comply with and be bound by these Terms and Conditions. If you do not agree to these terms, please do not use this portal.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">2. Medical Disclaimer &amp; Nature of Service</h2>
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <div>
                    <strong>Important Healthcare Notice:</strong> VMS is an immunization tracking, reminder, and scheduling coordinator. It is not a licensed medical practice and does not dispense medical advice, diagnosis, or emergency healthcare services.
                </div>
            </div>
            <p style="line-height: 1.6; color: var(--text-muted);">
                Always seek the advice of your qualified pediatrician or licensed healthcare professional with any questions regarding childhood vaccinations, contraindications, allergies, or adverse reactions. Never disregard professional pediatric advice or delay seeking care because of information displayed on this platform.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">3. Guardian &amp; Parent Responsibilities</h2>
            <ul style="margin-left: 1.5rem; line-height: 1.7; color: var(--text-muted);">
                <li>You confirm that you are the legal parent or authorized legal guardian of any minor child registered under your account.</li>
                <li>You agree to provide accurate, truthful, and up-to-date information, particularly your child's Date of Birth (DOB), as milestone calculations directly depend on this data.</li>
                <li>You are responsible for maintaining the confidentiality of your portal credentials and all activities occurring under your account.</li>
                <li>You agree to notify attending medical personnel of any known medical conditions, pre-existing illnesses, or medication allergies prior to dose administration.</li>
            </ul>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">4. Healthcare Facility Commitments</h2>
            <ul style="margin-left: 1.5rem; line-height: 1.7; color: var(--text-muted);">
                <li>Participating hospitals and clinics represent that they are duly certified under provincial healthcare commissions in Pakistan.</li>
                <li>Staff agree to verify patient identity, ensure cold-chain vaccine preservation, and accurately log clinical administration status in real time.</li>
                <li>Facilities are independently responsible for their clinical practices, medical advice, and physical clinic standards.</li>
            </ul>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">5. Appointment Lifecycle &amp; Administrative Moderation</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                Appointment submissions are subject to administrative review and hospital inventory availability. VMS administration reserves the right to reassign or cancel appointments in cases of stock shortages, emergency center closures, or health authority directives.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">6. Digital Health Records &amp; Certificates</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                Immunization health cards generated through this system reflect records verified by accredited participating medical centers. Modifying, forging, or fabricating digital vaccination certificates is strictly prohibited and subject to legal prosecution under applicable laws.
            </p>
        </section>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">7. Governing Law</h2>
            <p style="line-height: 1.6; color: var(--text-muted);">
                These terms are governed by and construed in accordance with the laws of the Islamic Republic of Pakistan. Any legal disputes arising in connection with the platform shall be subject to the exclusive jurisdiction of the competent courts in Pakistan.
            </p>
        </section>

        <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <a href="<?= url('privacy.php'); ?>" class="btn btn-outline btn-sm">
                Read Privacy Policy &rarr;
            </a>
            <a href="<?= url('contact.php'); ?>" class="btn btn-primary btn-sm">
                Contact Legal Helpdesk
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

