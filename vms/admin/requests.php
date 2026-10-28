<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: admin/requests.php
 * Description: Parent Appointment Request Moderation (Approve / Reject)
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Admin reviews incoming appointment requests made by parents.
 * - Approve: updates admin_status = 'approved'. Appointment is transferred to
 *   the designated hospital's live appointment queue.
 * - Reject: updates admin_status = 'rejected' with clinical remarks/reasons.
 * - CSRF verification on all POST requests.
 * - Flash toast checkmark on every status change.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('admin');

$pdo = getDBConnection();
$activeTab = $_GET['tab'] ?? 'pending';

// ----------------------------------------------------------------------------
// 1. HANDLE APPROVE & REJECT ACTIONS (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token). Please try again.');
        header('Location: ' . url('admin/requests.php?tab=' . urlencode($activeTab)));
        exit;
    }

    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    $remarks   = sanitizeInput($_POST['remarks'] ?? '');

    if ($bookingId > 0) {
        try {
            // Fetch booking summary details for clean flash feedback
            $bStmt = $pdo->prepare(
                'SELECT b.id, c.name AS child_name, h.name AS hospital_name, v.name AS vaccine_name
                 FROM bookings b
                 JOIN children c ON b.child_id = c.id
                 JOIN hospitals h ON b.hospital_id = h.id
                 JOIN vaccines v ON b.vaccine_id = v.id
                 WHERE b.id = ?'
            );
            $bStmt->execute([$bookingId]);
            $booking = $bStmt->fetch();

            if ($booking) {
                if ($action === 'approve') {
                    // Update admin_status to approved
                    $defaultRemark = empty($remarks) ? 'Approved by Administration. Slot confirmed.' : $remarks;
                    $upStmt = $pdo->prepare('UPDATE bookings SET admin_status = "approved", remarks = ? WHERE id = ?');
                    $upStmt->execute([$defaultRemark, $bookingId]);

                    setFlash('success', 'Appointment for ' . $booking['child_name'] . ' (' . $booking['vaccine_name'] . ') approved and forwarded to ' . $booking['hospital_name'] . '!');
                } elseif ($action === 'reject') {
                    // Update admin_status to rejected
                    $rejectionReason = empty($remarks) ? 'Request rejected by Administration.' : $remarks;
                    $upStmt = $pdo->prepare('UPDATE bookings SET admin_status = "rejected", remarks = ? WHERE id = ?');
                    $upStmt->execute([$rejectionReason, $bookingId]);

                    setFlash('warning', 'Appointment request for ' . $booking['child_name'] . ' has been rejected.');
                }
            } else {
                setFlash('danger', 'Booking record not found.');
            }
        } catch (PDOException $e) {
            error_log('[REQUEST MODERATION ERROR] ' . $e->getMessage());
            setFlash('danger', 'A database error occurred while updating the appointment status. Please try again.');
        }
    }

    header('Location: ' . url('admin/requests.php?tab=' . urlencode($activeTab)));
    exit;
}

// ----------------------------------------------------------------------------
// 2. FETCH REQUESTS BY TAB STATUS
// ----------------------------------------------------------------------------
$statusFilter = in_array($activeTab, ['pending', 'approved', 'rejected']) ? $activeTab : 'pending';

$stmt = $pdo->prepare(
    'SELECT b.*,
            c.name AS child_name, c.dob AS child_dob, c.gender AS child_gender, c.blood_group,
            p.phone AS parent_phone, p.address AS parent_address,
            u.name AS parent_name, u.email AS parent_email,
            h.name AS hospital_name, h.city AS hospital_city, h.phone AS hospital_phone,
            v.name AS vaccine_name, v.doses AS vaccine_doses
     FROM bookings b
     JOIN children c ON b.child_id = c.id
     JOIN parents p ON b.parent_id = p.id
     JOIN users u ON p.user_id = u.id
     JOIN hospitals h ON b.hospital_id = h.id
     JOIN vaccines v ON b.vaccine_id = v.id
     WHERE b.admin_status = ?
     ORDER BY b.preferred_date ASC, b.created_at ASC'
);
$stmt->execute([$statusFilter]);
$requests = $stmt->fetchAll();

// Counts for navigation badges
$countPending  = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE admin_status = "pending"')->fetchColumn();
$countApproved = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE admin_status = "approved"')->fetchColumn();
$countRejected = (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE admin_status = "rejected"')->fetchColumn();

$pageTitle = 'Parent Appointment Requests';
$pageDescription = 'Review, approve, or reject vaccination appointment requests submitted by parents.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 1.5rem;">
        <h1>Appointment Request Moderation</h1>
        <p style="margin: 0;">Once an appointment is approved, it appears in the designated hospital's daily schedule.</p>
    </div>

    <!-- Status Tabs -->
    <div class="form-tabs" style="max-width: 600px; margin-bottom: 1.5rem;">
        <a href="<?= url('admin/requests.php?tab=pending'); ?>" 
           class="form-tab-btn <?= $statusFilter === 'pending' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#9203; Pending Review (<?= $countPending; ?>)
        </a>
        <a href="<?= url('admin/requests.php?tab=approved'); ?>" 
           class="form-tab-btn <?= $statusFilter === 'approved' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#10004; Approved (<?= $countApproved; ?>)
        </a>
        <a href="<?= url('admin/requests.php?tab=rejected'); ?>" 
           class="form-tab-btn <?= $statusFilter === 'rejected' ? 'is-active' : ''; ?>" style="text-decoration: none;">
            &#10006; Rejected (<?= $countRejected; ?>)
        </a>
    </div>

    <!-- Requests Table Card -->
    <div class="card">
        <?php if (empty($requests)): ?>
            <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                <p style="font-size: 1.15rem; margin-bottom: 0.5rem;">
                    <?= $statusFilter === 'pending' ? 'No pending requests!' : 'No ' . $statusFilter . ' records found.'; ?>
                </p>
                <small>All parent appointment requests in this category have been processed.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Child Info</th>
                            <th>Parent Contact</th>
                            <th>Vaccine & Clinic</th>
                            <th>Requested Slot</th>
                            <th>Current State</th>
                            <th style="text-align: right;">Administrative Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['child_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        Age: <?= formatAge($r['child_dob']); ?> (DOB: <?= formatDate($r['child_dob']); ?>)<br>
                                        Gender: <?= ucfirst($r['child_gender']); ?> | Blood: <?= e($r['blood_group']); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?= e($r['parent_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);">
                                        <?= e($r['parent_phone']); ?><br>
                                        &#9993; <?= e($r['parent_email']); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= e($r['vaccine_name']); ?></span><br>
                                    <strong><?= e($r['hospital_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($r['hospital_city']); ?></small>
                                </td>
                                <td>
                                    <strong><?= formatDate($r['preferred_date']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= e($r['time_slot']); ?></small>
                                </td>
                                <td>
                                    <?php if ($r['admin_status'] === 'pending'): ?>
                                        <span class="badge badge-warning">&#9203; Pending Approval</span>
                                    <?php elseif ($r['admin_status'] === 'approved'): ?>
                                        <span class="badge badge-success">&#10004; Approved</span><br>
                                        <small style="color: var(--text-muted);">Clinical status: <strong><?= ucfirst($r['vaccination_status']); ?></strong></small>
                                    <?php elseif ($r['admin_status'] === 'rejected'): ?>
                                        <span class="badge badge-danger">&#10006; Rejected</span>
                                    <?php endif; ?>

                                    <?php if (!empty($r['remarks'])): ?>
                                        <br><small style="color: var(--text-light); font-style: italic;">Note: <?= e($r['remarks']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($r['admin_status'] === 'pending'): ?>
                                        <!-- Inline Approve Form -->
                                        <form action="<?= url('admin/requests.php?tab=' . $activeTab); ?>" method="POST" style="display: inline-block;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?= (int)$r['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="Approve and send to Hospital">
                                                &#10004; Approve
                                            </button>
                                        </form>

                                        <!-- Inline Reject Form with Prompt Modal / Confirmation -->
                                        <form action="<?= url('admin/requests.php?tab=' . $activeTab); ?>" method="POST" style="display: inline-block; margin-left: 0.35rem;">
                                            <?= csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?= (int)$r['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Reject Request"
                                                    onclick="return confirm('Are you sure you want to reject this request for <?= e($r['child_name']); ?>?');">
                                                &#10006; Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted);">Processed on <?= formatDate($r['created_at']); ?></small>
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
