<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/bookings.php
 * Description: Parent Appointment History & Booking Status Tracker
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - View all appointments booked by the parent across all children.
 * - Live status indicators for Admin Approval & Hospital Administration.
 * - Ability to safely cancel appointment requests while still pending approval.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('parent');

$pdo = getDBConnection();
$userId = currentUserId();

// Fetch Parent Profile ID
$pStmt = $pdo->prepare('SELECT id FROM parents WHERE user_id = ? LIMIT 1');
$pStmt->execute([$userId]);
$parentId = (int)$pStmt->fetchColumn();

// ----------------------------------------------------------------------------
// HANDLE CANCELLATION OF PENDING REQUEST (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . url('parent/bookings.php'));
        exit;
    }

    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($bookingId > 0) {
        try {
            // Strictly check ownership and ensure request is still pending
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ? AND parent_id = ? AND admin_status = "pending"');
            $stmt->execute([$bookingId, $parentId]);

            if ($stmt->rowCount() > 0) {
                setFlash('success', 'Your appointment request has been cancelled.');
            } else {
                setFlash('warning', 'Only pending requests can be cancelled. Please contact the clinic directly for scheduled slots.');
            }
        } catch (PDOException $e) {
            error_log('[CANCEL BOOKING ERROR] ' . $e->getMessage());
            setFlash('danger', 'Failed to cancel appointment: ' . $e->getMessage());
        }
    }
    header('Location: ' . url('parent/bookings.php'));
    exit;
}

// ----------------------------------------------------------------------------
// FETCH ALL BOOKINGS FOR THIS PARENT
// ----------------------------------------------------------------------------
$stmt = $pdo->prepare(
    'SELECT b.*,
            c.name AS child_name, c.dob AS child_dob,
            h.name AS hospital_name, h.city AS hospital_city, h.phone AS hospital_phone, h.location AS hospital_location,
            v.name AS vaccine_name
     FROM bookings b
     JOIN children c ON b.child_id = c.id
     JOIN hospitals h ON b.hospital_id = h.id
     JOIN vaccines v ON b.vaccine_id = v.id
     WHERE b.parent_id = ?
     ORDER BY b.preferred_date DESC, b.created_at DESC'
);
$stmt->execute([$parentId]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Appointments & Bookings';
$pageDescription = 'Track approval and vaccination attendance status for your child appointment requests.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>My Appointment Requests</h1>
            <p style="margin: 0;">Track the complete lifecycle of your child immunization bookings.</p>
        </div>

        <div>
            <a href="<?= url('parent/book.php'); ?>" class="btn btn-primary">
                &#10010; Book New Appointment
            </a>
        </div>
    </div>

    <div class="card">
        <?php if (empty($bookings)): ?>
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">&#128197;</span>
                <h3>No Appointments Booked Yet</h3>
                <p style="max-width: 450px; margin: 0 auto 1.25rem;">
                    You have not submitted any vaccination requests. Schedule a slot at an authorized pediatric hospital to ensure your child remains protected.
                </p>
                <a href="<?= url('parent/book.php'); ?>" class="btn btn-primary">Book an Appointment</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child Details</th>
                            <th>Vaccine</th>
                            <th>Healthcare Center</th>
                            <th>Appointment Slot</th>
                            <th>Admin Approval</th>
                            <th>Vaccination Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong><a href="<?= url('parent/children.php?action=view&id=' . (int)$b['child_id']); ?>"><?= e($b['child_name']); ?></a></strong><br>
                                    <small style="color: var(--text-muted);"><?= formatAge($b['child_dob']); ?> old</small>
                                </td>
                                <td><span class="badge badge-info"><?= e($b['vaccine_name']); ?></span></td>
                                <td>
                                    <strong><?= e($b['hospital_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">&#128205; <?= e($b['hospital_location']); ?>, <?= e($b['hospital_city']); ?></small><br>
                                    <small style="color: var(--text-light);">&#128222; <?= e($b['hospital_phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($b['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);">&#128337; <?= e($b['time_slot']); ?></small>
                                </td>
                                <td>
                                    <?php if ($b['admin_status'] === 'approved'): ?>
                                        <span class="badge badge-success">&#10004; Approved</span>
                                    <?php elseif ($b['admin_status'] === 'pending'): ?>
                                        <span class="badge badge-warning">&#9203; In Review</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">&#10006; Rejected</span>
                                    <?php endif; ?>

                                    <?php if (!empty($b['remarks'])): ?>
                                        <br><small style="color: var(--text-light); font-style: italic;">Note: <?= e($b['remarks']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['vaccination_status'] === 'vaccinated'): ?>
                                        <span class="badge badge-success">&#10004; Vaccinated</span><br>
                                        <small style="color: var(--text-muted);"><?= formatDate($b['vaccinated_on']); ?></small>
                                    <?php elseif ($b['vaccination_status'] === 'not_vaccinated'): ?>
                                        <span class="badge badge-danger">&#10006; Missed Visit</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">&#9203; Pending Visit</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($b['vaccination_status'] === 'vaccinated'): ?>
                                        <a href="<?= url('parent/reports.php?child_id=' . (int)$b['child_id']); ?>" class="btn btn-outline btn-sm">
                                            &#128203; Record
                                        </a>
                                    <?php elseif ($b['admin_status'] === 'pending'): ?>
                                        <form action="<?= url('parent/bookings.php'); ?>" method="POST" style="display: inline-block;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);" 
                                                    onclick="return confirm('Cancel this pending appointment request?');">
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted);">&#10004; Slot Confirmed</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
