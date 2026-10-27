<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/components/review_shuffle.php
 * Description: Parent Review Shuffle Carousel Component
 * ============================================================================
 * 
 * ANIMATION REQUIREMENT 3 (Parent Review Shuffle):
 * - Stacked-card carousel pulling verified testimonials from reviews table.
 * - Auto-rotates smoothly via JavaScript timer.
 * - Pauses on hover (mouseenter) and focus (focusin) for accessibility.
 * - Respects prefers-reduced-motion.
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../functions.php';

$pdo = getDBConnection();

// Fetch approved parent reviews
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

if (!empty($reviews)):
?>
<section class="review-carousel-wrap" aria-label="Parent Testimonials">
    <div style="text-align: center; margin-bottom: 1.5rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Parent Experiences</span>
        <h2 style="font-size: 1.75rem;">Trusted by Parents Nationwide</h2>
        <p style="margin: 0; font-size: 0.95rem; color: var(--text-muted);">See how our automated immunization reminders give families peace of mind.</p>
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
<?php endif; ?>
