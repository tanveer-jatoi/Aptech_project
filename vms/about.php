<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: about.php
 * Description: About the Initiative, Healthcare Mission & Vaccine Safety
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

$pageTitle = 'About the Initiative | Child Vaccination Management System';
$pageDescription = 'Learn about our mission to eradicate missed childhood immunizations through digital healthcare coordination.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">
    <!-- Page Header Banner -->
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Our Healthcare Mission</span>
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Transforming Child Immunization Delivery</h1>
        <p style="font-size: 1.15rem; color: var(--text-muted); line-height: 1.6;">
            A modern public health platform designed to eliminate missed vaccinations, bridge families with licensed pediatric facilities, and provide real-time immunization tracking.
        </p>
    </div>

    <!-- The Problem & Solution Grid -->
    <div class="grid grid-2" style="gap: 2rem; margin-bottom: 3.5rem;">
        <div class="card" style="border-left: 5px solid var(--danger);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <span style="font-size: 2rem;">&#9888;</span>
                <h3 style="margin: 0;">The Problem: Fragmented Manual Records</h3>
            </div>
            <p style="color: var(--text-muted); line-height: 1.6;">
                Historically, childhood vaccination tracking relied on manual paper cards and school-based collection drives. Busy parents frequently misplaced paper booklets, leading to skipped booster doses, delayed immunizations, and preventable disease outbreaks.
            </p>
            <p style="color: var(--text-muted); line-height: 1.6; margin: 0;">
                Furthermore, clinics had no unified method to forecast local vaccine demand or verify whether a visiting child had already received specific doses elsewhere.
            </p>
        </div>

        <div class="card" style="border-left: 5px solid var(--success);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <span style="font-size: 2rem;">&#10004;</span>
                <h3 style="margin: 0;">The Solution: Centralized Digital Coordination</h3>
            </div>
            <p style="color: var(--text-muted); line-height: 1.6;">
                The <strong>Vaccination Management System (VMS)</strong> establishes a single source of truth. As soon as a parent registers their child's Date of Birth, our automated engine computes the exact recommended milestones according to national healthcare protocols.
            </p>
            <p style="color: var(--text-muted); line-height: 1.6; margin: 0;">
                Parents can discover nearby centers with confirmed vaccine stock, submit appointment requests, and download tamper-evident digital immunization certificates for school admissions and travel.
            </p>
        </div>
    </div>

    <!-- Platform Pillars -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="font-size: 2rem;">How Our Ecosystem Works</h2>
        <p style="color: var(--text-muted);">A collaborative tripartite architecture uniting parents, medical facilities, and administrators.</p>
    </div>

    <div class="grid grid-3" style="gap: 1.5rem; margin-bottom: 4rem;">
        <div class="card" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.25rem;">
                &#128106;
            </div>
            <h3>For Parents</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                Maintain complete profiles for all children, receive automated dashboard notifications for overdue and upcoming doses, search nearby hospitals, and download verified certificates anytime.
            </p>
        </div>

        <div class="card" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #ccfbf1; color: #0f766e; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.25rem;">
                &#127973;
            </div>
            <h3>For Pediatric Centers</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                Access daily patient queues approved by health administrators, log verified dose administrations, record clinical observations, and manage local vaccine inventory availability.
            </p>
        </div>

        <div class="card" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.25rem;">
                &#9878;
            </div>
            <h3>For Administration</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                Review and moderate parent booking requests, oversee participating healthcare centers, manage the national vaccine catalog schedule, and generate comprehensive compliance reports.
            </p>
        </div>
    </div>

    <!-- Frequently Asked Questions -->
    <div style="max-width: 800px; margin: 0 auto 3rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 style="font-size: 1.8rem;">Frequently Asked Questions</h2>
        </div>

        <div class="card" style="margin-bottom: 1rem;">
            <h4>How are vaccination due dates calculated?</h4>
            <p style="margin: 0; font-size: 0.92rem; color: var(--text-muted); line-height: 1.6;">
                Due dates are calculated by our engine by taking the child's recorded Date of Birth (DOB) and adding the recommended age in days for each milestone (e.g. BCG at 0 days/birth, Polio and Pentavalent at 42 days/6 weeks, MMR at 270 days/9 months).
            </p>
        </div>

        <div class="card" style="margin-bottom: 1rem;">
            <h4>Why do appointment requests require administrative approval?</h4>
            <p style="margin: 0; font-size: 0.92rem; color: var(--text-muted); line-height: 1.6;">
                Administrative moderation ensures that the requested vaccine matches the child's age profile and confirms that the chosen clinic has sufficient medical staff and supply before the patient arrives.
            </p>
        </div>

        <div class="card">
            <h4>Can I print my child's official immunization certificate?</h4>
            <p style="margin: 0; font-size: 0.92rem; color: var(--text-muted); line-height: 1.6;">
                Yes! From the parent portal under Reports, you can print an official, formatted Child Immunization Health Card that includes certified dates, clinic stamps, and attending physician signature lines for school or daycare admissions.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
