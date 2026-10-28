<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/cookie_banner.php
 * Description: Accessible Cookie Consent & Privacy Preference Banner
 * ============================================================================
 */
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}
?>
<!-- Accessible Cookie Consent Banner (Client-controlled via localStorage) -->
<div class="cookie-consent-overlay" id="vms-cookie-banner" style="display: none;" role="dialog" aria-labelledby="cookieBannerTitle" aria-describedby="cookieBannerDesc">
    <div class="cookie-consent-card">
        <h4 id="cookieBannerTitle">Cookie &amp; Privacy Preferences</h4>
        <p id="cookieBannerDesc">
            We use strictly necessary cookies for secure session authentication and CSRF defense. We also offer privacy-friendly, anonymized analytics to improve vaccination coverage. You may choose your preferences below. Learn more in our <a href="<?= url('privacy.php'); ?>" style="font-weight: 600; text-decoration: underline;">Privacy Policy</a>.
        </p>
        <div class="cookie-consent-actions">
            <button type="button" class="btn btn-primary btn-sm" id="cookie-accept-all">
                Accept All
            </button>
            <button type="button" class="btn btn-outline btn-sm" id="cookie-reject">
                Essential Only
            </button>
            <a href="<?= url('privacy.php'); ?>" class="btn btn-outline btn-sm">
                View Policy
            </a>
        </div>
    </div>
</div>

