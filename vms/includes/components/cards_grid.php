<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/cards_grid.php
 * Description: Reusable Responsive Grid Component for Vaccines & Hospitals
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

/**
 * Renders a responsive grid of vaccine cards.
 *
 * @param array $vaccines Array of vaccine rows from database
 * @param bool $animateOnScroll Whether to apply subtle scroll reveal
 * @return void
 */
function renderVaccinesGrid(array $vaccines, bool $animateOnScroll = false): void
{
    $animClass = $animateOnScroll ? 'fade-in-on-scroll' : '';
    ?>
    <div class="grid grid-3" style="gap: 1.25rem;">
        <?php foreach ($vaccines as $v): 
            $isAvail = ($v['status'] === 'available');
            $ageDisplay = (int)$v['recommended_age_days'] === 0 ? 'At Birth' : (int)$v['recommended_age_days'] . ' Days';
            if ((int)$v['recommended_age_days'] >= 30) {
                $ageDisplay = round((int)$v['recommended_age_days'] / 30.4) . ' Months';
            }
        ?>
            <article class="card <?= $animClass; ?>" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <span class="badge badge-info">&#128137; Target: <?= $ageDisplay; ?></span>
                        <?php if ($isAvail): ?>
                            <span class="badge badge-success">&#10004; Available</span>
                        <?php else: ?>
                            <span class="badge badge-danger">&#10006; Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--text-main);"><?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                        <?= htmlspecialchars($v['description'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                    <small style="color: var(--text-muted);">
                        <strong><?= (int)$v['doses']; ?></strong> <?= (int)$v['doses'] === 1 ? 'dose' : 'doses'; ?> in series
                    </small>
                    <a href="<?= url('register.php'); ?>" class="btn btn-outline btn-sm">
                        Schedule &rarr;
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Renders a responsive grid of hospital facility cards.
 *
 * @param array $hospitals Array of hospital rows from database
 * @param bool $animateOnScroll Whether to apply subtle scroll reveal
 * @return void
 */
function renderHospitalsGrid(array $hospitals, bool $animateOnScroll = false): void
{
    $animClass = $animateOnScroll ? 'fade-in-on-scroll' : '';
    ?>
    <div class="grid grid-3" style="gap: 1.25rem;">
        <?php foreach ($hospitals as $h): ?>
            <article class="card <?= $animClass; ?>" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <span class="badge badge-info">&#128205; <?= htmlspecialchars($h['city'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="badge badge-success">&#10004; Verified Center</span>
                    </div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem; color: var(--text-main);"><?= htmlspecialchars($h['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p style="font-size: 0.85rem; color: var(--primary-dark); font-weight: 600; margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($h['location'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4; margin-bottom: 0.75rem;">
                        <?= htmlspecialchars($h['address'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                    <small style="color: var(--text-muted); font-weight: 600;">
                        &#128222; <?= htmlspecialchars($h['phone'], ENT_QUOTES, 'UTF-8'); ?>
                    </small>
                    <a href="<?= url('register.php'); ?>" class="btn btn-primary btn-sm">
                        Book Slot &rarr;
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}
