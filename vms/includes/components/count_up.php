<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/count_up.php
 * Description: Real DB Stat Counter Component with Smooth Count-Up Animation
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

/**
 * Renders an animated KPI statistic card.
 *
 * @param int $to Final target integer value from the database
 * @param string $label Descriptive human-readable label
 * @param string $icon Visual SVG or unicode icon
 * @param string $subtitle Optional explanatory subtext
 * @return void Echoes HTML markup directly
 */
function count_up(int $to, string $label, string $icon = '', string $subtitle = ''): void
{
    ?>
    <div class="stat-card" style="flex: 1; min-width: 200px;">
        <div>
            <span class="stat-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="stat-value" data-count-to="<?= (int)$to; ?>"><?= (int)$to; ?></div>
            <?php if (!empty($subtitle)): ?>
                <small style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>
        <?php if (!empty($icon)): ?>
            <div class="stat-icon" aria-hidden="true">
                <?= $icon; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
