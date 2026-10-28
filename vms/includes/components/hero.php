<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/hero.php
 * Description: Homepage Hero Section with Clean Outlined CTAs
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}
?>
<section class="hero-wrapper" aria-label="Hero Introduction">
    <div class="container">
        <!-- Tag / Pill Reveal -->
        <div class="hero-reveal">
            <span class="hero-tag">
                Pakistan Child Immunization Initiative
            </span>
        </div>

        <!-- Headline Reveal -->
        <h1 class="hero-title hero-reveal">
            Protecting Every Child’s Future Through Timely, Verified Immunization
        </h1>

        <!-- Tagline / Subtitle Reveal -->
        <p class="hero-subtitle hero-reveal">
            Never miss an essential vaccination milestone. Track automated age-based schedules, discover authorized pediatric clinics, and book appointments seamlessly online.
        </p>

        <!-- Call to Action Buttons Reveal (Outlined, Non-Filled Styles) -->
        <div class="hero-actions hero-reveal">
            <?php if (isLoggedIn()): ?>
                <a href="<?= url(currentRole() . '/dashboard.php'); ?>" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1rem;">
                    Go to Your Portal Dashboard &rarr;
                </a>
            <?php else: ?>
                <a href="<?= url('register.php'); ?>" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1rem;">
                    Register Child for Vaccination
                </a>
                <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline" style="padding: 0.85rem 1.5rem; font-size: 1rem;">
                    Find Participating Hospitals
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
