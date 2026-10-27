<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/process_steps.php
 * Description: 5-Step Immunization Process Walkthrough Component
 * ============================================================================
 * 
 * ANIMATION REQUIREMENT 4 (Process Steps):
 * - Procedural guide: Register -> Add Child -> Book Hospital -> Get Vaccinated -> View Report.
 * - Connecting track line with numbered step indicators.
 * - Minimal, accessible layout with zero layout shift.
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}
?>
<section class="process-steps-container" aria-label="How the System Works">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Simple & Seamless</span>
        <h2 style="font-size: 1.75rem;">How the Vaccination System Works</h2>
        <p style="margin: 0; font-size: 0.95rem; color: var(--text-muted);">Five simple steps to guarantee complete immunization protection for your child.</p>
    </div>

    <div class="process-steps-track">
        <!-- Step 1 -->
        <div class="process-step-item">
            <div class="process-step-circle">1</div>
            <h4 class="process-step-title">Register Account</h4>
            <p class="process-step-desc">Create your parent profile in under 60 seconds.</p>
        </div>

        <!-- Step 2 -->
        <div class="process-step-item">
            <div class="process-step-circle">2</div>
            <h4 class="process-step-title">Add Child Profile</h4>
            <p class="process-step-desc">Enter Date of Birth to auto-generate the vaccine timeline.</p>
        </div>

        <!-- Step 3 -->
        <div class="process-step-item">
            <div class="process-step-circle">3</div>
            <h4 class="process-step-title">Book Hospital</h4>
            <p class="process-step-desc">Pick an authorized pediatric center and appointment slot.</p>
        </div>

        <!-- Step 4 -->
        <div class="process-step-item">
            <div class="process-step-circle">4</div>
            <h4 class="process-step-title">Get Vaccinated</h4>
            <p class="process-step-desc">Visit the clinic; medical staff administer and log the dose.</p>
        </div>

        <!-- Step 5 -->
        <div class="process-step-item">
            <div class="process-step-circle">5</div>
            <h4 class="process-step-title">View Health Card</h4>
            <p class="process-step-desc">Download and print the official child immunization record.</p>
        </div>
    </div>
</section>
