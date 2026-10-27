<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/hero.php
 * Description: Homepage Hero Reveal Section with Staggered Keyframe Animation
 * ============================================================================
 * 
 * ANIMATION REQUIREMENT 1 (Hero Reveal):
 * - Name, tagline, and CTA button sliding/fading in on load.
 * - Powered by .animate-hero-reveal, .animate-hero-delay-1, .animate-hero-delay-2
 *   defined in animations.css (transform & opacity only).
 * - Immediately renders final resting position when prefers-reduced-motion is active.
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}
?>
<section class="hero-wrapper" aria-label="Hero Introduction">
    <div class="container">
        <!-- Tag / Pill Reveal -->
        <div class="animate-hero-reveal">
            <span class="hero-tag">
                <span style="font-size: 1rem;">&#128137;</span> National Child Immunization Initiative
            </span>
        </div>

        <!-- Headline Reveal -->
        <h1 class="hero-title animate-hero-delay-1">
            Protecting Every Child’s Future Through Timely, Verified Immunization
        </h1>

        <!-- Tagline / Subtitle Reveal -->
        <p class="hero-subtitle animate-hero-delay-1">
            Never miss an essential vaccination milestone. Track automated age-based schedules, discover authorized pediatric clinics, and book appointments seamlessly online.
        </p>

        <!-- Call to Action Buttons Reveal -->
        <div class="hero-actions animate-hero-delay-2">
            <?php if (isLoggedIn()): ?>
                <a href="<?= url(currentRole() . '/dashboard.php'); ?>" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1.05rem;">
                    Go to Your Dashboard &rarr;
                </a>
            <?php else: ?>
                <a href="<?= url('register.php'); ?>" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 1.05rem;">
                    &#10010; Book Vaccination Appointment
                </a>
                <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline" style="padding: 0.85rem 1.5rem; font-size: 1.05rem; background: #ffffff;">
                    &#127973; Find Nearby Hospitals
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
