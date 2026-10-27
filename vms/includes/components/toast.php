<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/toast.php
 * Description: Reusable Flash Success & Notification Toast with Animated Checkmark
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Reads one-time session flash message via getFlash().
 * - Displays an animated SVG checkmark on success.
 * - Sets role="status" and aria-live="polite" for accessibility.
 * - Auto-dismisses via vanilla JS timer (auto-hide).
 * - Never reappears upon page refresh (session is wiped immediately).
 * - Respects prefers-reduced-motion in animations.css.
 */

// Prevent direct HTTP access
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

require_once __DIR__ . '/../flash.php';
require_once __DIR__ . '/../functions.php';

$flash = getFlash();
if ($flash):
    $flashType = e($flash['type']);
    $flashMsg  = e($flash['message']);
    $isSuccess = ($flash['type'] === 'success');
?>
<div id="vms-toast-container" class="toast-container" aria-live="polite" aria-atomic="true">
    <div id="vms-toast" class="toast-card toast-<?= $flashType; ?>" role="status">
        <div class="toast-icon-wrap">
            <?php if ($isSuccess): ?>
                <!-- Animated SVG Checkmark Icon -->
                <svg class="toast-checkmark-svg" viewBox="0 0 52 52" aria-hidden="true">
                    <circle class="toast-checkmark-circle" cx="26" cy="26" r="23" fill="none"/>
                    <path class="toast-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            <?php else: ?>
                <!-- Alert Warning/Error Icon -->
                <svg class="toast-info-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            <?php endif; ?>
        </div>
        <div class="toast-body">
            <strong class="toast-title"><?= ucfirst($flashType); ?></strong>
            <p class="toast-message"><?= $flashMsg; ?></p>
        </div>
        <button type="button" class="toast-close" aria-label="Dismiss Notification" onclick="dismissVmsToast()">&times;</button>
    </div>
</div>
<?php endif; ?>
