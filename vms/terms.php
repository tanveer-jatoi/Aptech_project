<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: terms.php
 * Description: Terms and conditions
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

// Fixed date: change it only when the terms really change.
$effectiveDate = '29-10-2026';

$pageTitle = 'Terms & Conditions';
$pageDescription = 'Terms of service, guardian responsibilities, and medical disclaimer for VMS.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3.5rem;">
    <div class="ui-legal">
        <nav aria-label="On this page">
            <strong>On this page</strong>
            <a href="#acceptance">1. Acceptance</a>
            <a href="#medical">2. Medical disclaimer</a>
            <a href="#parents">3. Parent responsibilities</a>
            <a href="#centers">4. Center commitments</a>
            <a href="#appointments">5. Appointments</a>
            <a href="#records">6. Records and certificates</a>
            <a href="#law">7. Governing law</a>
        </nav>

        <article class="card">
            <h1>Terms and Conditions</h1>
            <p class="meta">Effective: <?= e($effectiveDate); ?> &middot; Jurisdiction: Islamic Republic of Pakistan</p>

            <section id="acceptance">
                <h2>1. Acceptance</h2>
                <p>
                    By creating an account, registering a child, enrolling a center, or requesting an appointment on the <strong>Vaccination Management System (VMS)</strong>, you agree to these terms. If you do not agree, please do not use the portal.
                </p>
            </section>

            <section id="medical">
                <h2>2. Medical disclaimer</h2>
                <div class="alert alert-info" style="margin-bottom: 1rem;">
                    <div>
                        <strong>Important:</strong> VMS tracks schedules, sends reminders, and coordinates appointments. It is not a medical practice and does not give medical advice, diagnosis, or emergency care.
                    </div>
                </div>
                <p>
                    Ask your pediatrician or a licensed health professional about vaccines, allergies, contraindications, or reactions. Do not delay care because of anything shown on this site.
                </p>
            </section>

            <section id="parents">
                <h2>3. Parent and guardian responsibilities</h2>
                <ul>
                    <li>You are the parent or legal guardian of every child you register.</li>
                    <li>You give accurate information, especially the child's date of birth, because due dates are calculated from it.</li>
                    <li>You keep your password private and are responsible for activity under your account.</li>
                    <li>You tell the clinic about known illnesses or allergies before a dose is given.</li>
                </ul>
            </section>

            <section id="centers">
                <h2>4. Center commitments</h2>
                <ul>
                    <li>Participating hospitals and clinics confirm they are certified under the relevant provincial healthcare commission in Pakistan.</li>
                    <li>Staff verify patient identity, maintain the vaccine cold chain, and record each administered dose accurately.</li>
                    <li>Each center is responsible for its own clinical practice and facilities.</li>
                </ul>
            </section>

            <section id="appointments">
                <h2>5. Appointments and approval</h2>
                <p>
                    Appointment requests are reviewed by an administrator and depend on vaccine availability. VMS may change or cancel an appointment because of stock shortages, center closures, or health authority directions. You can cancel a request while it is still pending.
                </p>
            </section>

            <section id="records">
                <h2>6. Records and certificates</h2>
                <p>
                    The immunization health card shows doses recorded by participating centers in VMS. Editing, forging, or fabricating a vaccination record is prohibited and may be prosecuted under applicable law.
                </p>
            </section>

            <section id="law">
                <h2>7. Governing law</h2>
                <p>
                    These terms are governed by the laws of the Islamic Republic of Pakistan. Disputes fall under the jurisdiction of the competent courts of Pakistan.
                </p>
            </section>

            <div class="ui-legal-foot">
                <a href="<?= url('privacy.php'); ?>" class="btn btn-outline btn-sm">Privacy Policy</a>
                <a href="<?= url('contact.php'); ?>" class="btn btn-primary btn-sm">Contact the helpdesk</a>
            </div>
        </article>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>