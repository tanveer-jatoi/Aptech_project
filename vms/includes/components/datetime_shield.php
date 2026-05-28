<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/datetime_shield.php
 * Description: Client Clock Skew & SSL Date-Time Validation Shield
 * Disallows / warns users when system date and time is not set properly.
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

$serverTimestamp = time();
$serverFormatted = date('d-m-Y h:i:s A');
?>
<!-- Date & Time Security Shield Dialog (Hidden by default, triggered via main.js) -->
<div id="vms-datetime-shield" class="datetime-shield-overlay" style="display: none;" role="alertdialog" aria-modal="true" aria-labelledby="clockShieldTitle" aria-describedby="clockShieldDesc">
    <div class="datetime-shield-card">
        <div class="datetime-shield-icon" aria-hidden="true">&#9888;</div>
        <h3 id="clockShieldTitle">Device Date &amp; Time Out of Sync</h3>
        <p id="clockShieldDesc">
            Your device clock is not synchronized with the standard healthcare time. Because cryptographic SSL encryption, infant vaccine age milestones, and appointment schedules depend strictly on accurate timestamps, access to booking and submissions is restricted until your clock is corrected.
        </p>

        <div class="datetime-comparison">
            <div class="time-box">
                <strong>Standard Time (Pakistan):</strong>
                <span id="vms-server-time-display"><?= e($serverFormatted); ?></span>
            </div>
            <div class="time-box">
                <strong>Your Device Clock:</strong>
                <span id="vms-client-time-display">Detecting...</span>
            </div>
        </div>

        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Please open your device settings, enable <strong>"Set time automatically"</strong> (or correct the date and time), then click below to re-verify.
        </p>

        <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" id="vms-recheck-clock">
                Re-check Date &amp; Time
            </button>
            <button type="button" class="btn btn-outline" id="vms-dismiss-clock-warning" style="font-size: 0.85rem;">
                Proceed Read-Only
            </button>
        </div>
    </div>
</div>

