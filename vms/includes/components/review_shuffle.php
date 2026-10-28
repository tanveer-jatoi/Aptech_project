<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/review_shuffle.php
 * Description: Parent Feedback Display & Genuine Healthcare Assurance
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Only displays genuine, verified parent reviews approved by administration.
 * - Never displays fabricated testimonials, fake ratings, or artificial claims.
 * - If no genuine reviews exist yet, displays authentic national healthcare assurance guidance.
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';

$pdo = getDBConnection();

// Fetch approved genuine parent reviews if any exist
$reviews = [];
try {
    $rStmt = $pdo->query(
        'SELECT r.*, u.name AS parent_name, p.address AS parent_city
         FROM reviews r
         JOIN parents p ON r.parent_id = p.id
         JOIN users u ON p.user_id = u.id
         WHERE r.is_approved = 1
         ORDER BY r.created_at DESC
         LIMIT 5'
    );
    $reviews = $rStmt->fetchAll();
} catch (PDOException $e) {
    error_log('[REVIEWS FETCH ERROR] ' . $e->getMessage());
    $reviews = [];
}

if (!empty($reviews)):
?>
<section class="review-carousel-wrap" aria-label="Parent Experiences">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Verified Parent Experiences</span>
        <h2 style="font-size: 1.75rem;">Trusted by Families Across Pakistan</h2>
        <p style="margin: 0; font-size: 0.95rem; color: var(--text-muted);">Real feedback from registered guardians using our immunization scheduling system.</p>
    </div>

    <!-- Stacked Testimonial Cards Viewport -->
    <div class="review-card-stack" role="region" aria-live="polite">
        <?php foreach ($reviews as $idx => $rev): 
            $initial = strtoupper(substr($rev['parent_name'], 0, 1));
            $ratingStars = str_repeat('&#9733;', (int)$rev['rating']) . str_repeat('&#9734;', 5 - (int)$rev['rating']);
        ?>
            <article class="review-slide <?= $idx === 0 ? 'is-active' : ''; ?>" data-slide-index="<?= $idx; ?>" aria-hidden="<?= $idx === 0 ? 'false' : 'true'; ?>">
                <div class="review-stars" aria-label="Rated <?= (int)$rev['rating']; ?> out of 5 stars">
                    <?= $ratingStars; ?>
                </div>

                <blockquote class="review-comment">
                    &ldquo;<?= e($rev['comment']); ?>&rdquo;
                </blockquote>

                <div class="review-author">
                    <div class="review-avatar" aria-hidden="true"><?= $initial; ?></div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;"><?= e($rev['parent_name']); ?></strong><br>
                        <small style="color: var(--text-muted);">Verified Guardian &bull; <?= formatDate($rev['created_at']); ?></small>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Carousel Nav Buttons & Indicators -->
    <div class="review-controls">
        <button type="button" class="carousel-btn" id="carouselPrevBtn" aria-label="Previous Testimonial">&larr;</button>
        <div class="carousel-indicators">
            <?php foreach ($reviews as $idx => $r): ?>
                <button type="button" class="carousel-dot <?= $idx === 0 ? 'is-active' : ''; ?>" data-slide-to="<?= $idx; ?>" aria-label="Go to slide <?= $idx + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <button type="button" class="carousel-btn" id="carouselNextBtn" aria-label="Next Testimonial">&rarr;</button>
    </div>
</section>
<?php else: ?>
<!-- Authentic Healthcare Assurance Section (Strictly Zero Fabricated Reviews) -->
<section style="max-width: 820px; margin: 0 auto;" aria-label="Healthcare Quality Assurance">
    <div class="card" style="text-align: center; padding: 2.5rem 2rem; border-top: 3px solid var(--primary-dark);">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Healthcare Quality Commitment</span>
        <h3 style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--text-main);">National Immunization Standards</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; max-width: 680px; margin: 0 auto 1.5rem;">
            Child immunization appointments coordinated through VMS adhere strictly to national health standards and the Expanded Programme on Immunization (EPI) guidelines for cold-chain vaccine integrity and clinical safety.
        </p>
        <div class="grid grid-3" style="gap: 1rem; text-align: left; font-size: 0.88rem;">
            <div style="background: var(--bg-subtle); padding: 1rem; border-radius: var(--radius-md);">
                <strong style="color: var(--text-main); display: block; margin-bottom: 0.25rem;">&#10004; Cold-Chain Integrity</strong>
                <span style="color: var(--text-muted);">Hospitals maintain continuous temperature-controlled storage.</span>
            </div>
            <div style="background: var(--bg-subtle); padding: 1rem; border-radius: var(--radius-md);">
                <strong style="color: var(--text-main); display: block; margin-bottom: 0.25rem;">&#10004; Clinical Record Verification</strong>
                <span style="color: var(--text-muted);">Every dose is logged with administration timestamps.</span>
            </div>
            <div style="background: var(--bg-subtle); padding: 1rem; border-radius: var(--radius-md);">
                <strong style="color: var(--text-main); display: block; margin-bottom: 0.25rem;">&#10004; Automatic Milestone Alerts</strong>
                <span style="color: var(--text-muted);">Calculated dynamically from child Date of Birth.</span>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
