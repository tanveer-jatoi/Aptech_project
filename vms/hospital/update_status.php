<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: hospital/update_status.php
 * Description: Clinical Vaccination Verification & Status Updater
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Update clinical status to 'Vaccinated' or 'Not Vaccinated'.
 * - Records administration timestamp and clinical notes/observations.
 * - Strict verification: hospital can only modify their own approved bookings.
 * - CSRF verification and flash toast notifications.
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';

requireRole('hospital');

$pdo = getDBConnection();
$userId = currentUserId();

// Fetch Hospital ID
$hStmt = $pdo->prepare('SELECT id, name FROM hospitals WHERE user_id = ? LIMIT 1');
$hStmt->execute([$userId]);
$hospital = $hStmt->fetch();

if (!$hospital) {
    setFlash('danger', 'Facility profile not found.');
    header('Location: ' . url('login.php'));
    exit;
}

$hospitalId = (int)$hospital['id'];
$bookingId  = (int)($_GET['id'] ?? ($_POST['booking_id'] ?? 0));
$errors     = [];

if ($bookingId <= 0) {
    setFlash('danger', 'Invalid appointment record.');
    header('Location: ' . url('hospital/appointments.php'));
    exit;
}

// ----------------------------------------------------------------------------
// 1. FETCH & VERIFY APPOINTMENT RECORD (Strict Ownership Check)
// ----------------------------------------------------------------------------
$bStmt = $pdo->prepare(
    'SELECT b.*,
            c.name AS child_name, c.dob AS child_dob, c.gender AS child_gender, c.blood_group,
            p.phone AS parent_phone, p.address AS parent_address,
            u.name AS parent_name, u.email AS parent_email,
            v.name AS vaccine_name, v.description AS vaccine_desc, v.doses AS vaccine_doses
     FROM bookings b
     JOIN children c ON b.child_id = c.id
     JOIN parents p ON b.parent_id = p.id
     JOIN users u ON p.user_id = u.id
     JOIN vaccines v ON b.vaccine_id = v.id
     WHERE b.id = ? AND b.hospital_id = ? AND b.admin_status = "approved" LIMIT 1'
);
$bStmt->execute([$bookingId, $hospitalId]);
$booking = $bStmt->fetch();

if (!$booking) {
    setFlash('danger', 'Appointment record not found or not approved by administration.');
    header('Location: ' . url('hospital/appointments.php'));
    exit;
}

// ----------------------------------------------------------------------------
// 2. HANDLE STATUS UPDATE SUBMISSION (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    } else {
        $status       = in_array($_POST['vaccination_status'] ?? '', ['vaccinated', 'not_vaccinated']) ? $_POST['vaccination_status'] : '';
        $vaccinatedOn = sanitizeInput($_POST['vaccinated_on'] ?? date('Y-m-d H:i:s'));
        $remarks      = sanitizeInput($_POST['remarks'] ?? '');

        if (empty($status)) {
            $errors[] = 'Please select a vaccination status (Vaccinated or Not Vaccinated).';
        }

        if (empty($errors)) {
            try {
                if ($status === 'vaccinated') {
                    // Convert datetime-local format if necessary
                    $formattedDate = date('Y-m-d H:i:s', strtotime($vaccinatedOn));
                    $defaultRemark = empty($remarks) ? 'Routine dose administered successfully with no immediate adverse reactions.' : $remarks;

                    $stmt = $pdo->prepare(
                        'UPDATE bookings SET vaccination_status = "vaccinated", vaccinated_on = ?, remarks = ? WHERE id = ? AND hospital_id = ?'
                    );
                    $stmt->execute([$formattedDate, $defaultRemark, $bookingId, $hospitalId]);

                    setFlash('success', 'Immunization for ' . $booking['child_name'] . ' (' . $booking['vaccine_name'] . ') marked as Vaccinated!');
                } else {
                    // Not vaccinated
                    $defaultRemark = empty($remarks) ? 'Patient did not attend appointment or vaccination deferred for medical assessment.' : $remarks;

                    $stmt = $pdo->prepare(
                        'UPDATE bookings SET vaccination_status = "not_vaccinated", vaccinated_on = NULL, remarks = ? WHERE id = ? AND hospital_id = ?'
                    );
                    $stmt->execute([$defaultRemark, $bookingId, $hospitalId]);

                    setFlash('warning', 'Appointment for ' . $booking['child_name'] . ' marked as Not Vaccinated.');
                }

                header('Location: ' . url('hospital/appointments.php'));
                exit;
            } catch (PDOException $e) {
                error_log('[UPDATE STATUS ERROR] ' . $e->getMessage());
                $errors[] = 'Database error updating status: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Record Vaccination: ' . $booking['child_name'];
$pageDescription = 'Record clinical administration status and observations for child patient.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="max-width: 780px; margin: 0 auto;">
        <div style="margin-bottom: 1.5rem;">
            <a href="<?= url('hospital/appointments.php'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 0.5rem;">&larr; Back to Patient Queue</a>
            <h1>Record Vaccination Administration</h1>
            <p style="margin: 0;">Official clinical confirmation for <?= e($booking['child_name']); ?>'s immunization slot.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <div>
                    <strong>Please resolve the following:</strong>
                    <ul style="margin: 0.25rem 0 0 1.2rem;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= e($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Patient & Appointment Summary Card -->
        <div class="card" style="margin-bottom: 1.5rem; background: var(--bg-subtle);">
            <div class="grid grid-2" style="gap: 1.5rem;">
                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">&#128102; Child Patient</h4>
                    <p style="margin: 0; line-height: 1.7; font-size: 0.92rem;">
                        <strong>Name:</strong> <?= e($booking['child_name']); ?><br>
                        <strong>Date of Birth:</strong> <?= formatDate($booking['child_dob']); ?> (<?= formatAge($booking['child_dob']); ?> old)<br>
                        <strong>Gender / Blood:</strong> <?= ucfirst($booking['child_gender']); ?> &bull; <span class="badge badge-info"><?= e($booking['blood_group']); ?></span><br>
                        <strong>Parent:</strong> <?= e($booking['parent_name']); ?> (&#128222; <?= e($booking['parent_phone']); ?>)
                    </p>
                </div>

                <div>
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">&#128137; Vaccine & Slot</h4>
                    <p style="margin: 0; line-height: 1.7; font-size: 0.92rem;">
                        <strong>Vaccine:</strong> <span class="badge badge-info" style="font-size: 0.85rem;"><?= e($booking['vaccine_name']); ?></span><br>
                        <strong>Scheduled Date:</strong> <?= formatDate($booking['preferred_date']); ?><br>
                        <strong>Time Slot:</strong> &#128337; <?= e($booking['time_slot']); ?><br>
                        <strong>Current Status:</strong> <span class="badge badge-warning"><?= ucfirst($booking['vaccination_status']); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Clinical Recording Form -->
        <div class="card">
            <div class="card-header">
                <h3>Clinical Assessment & Status</h3>
            </div>

            <form action="<?= url('hospital/update_status.php'); ?>" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="booking_id" value="<?= (int)$booking['id']; ?>">

                <div class="form-group">
                    <label class="form-label required">Vaccination Status</label>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; border: 2px solid #10b981; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); background: #ecfdf5; cursor: pointer;">
                            <input type="radio" name="vaccination_status" value="vaccinated" <?= $booking['vaccination_status'] === 'vaccinated' || $booking['vaccination_status'] === 'pending' ? 'checked' : ''; ?> required>
                            <span style="font-weight: 700; color: #065f46;">&#10004; Vaccinated (Dose Administered)</span>
                        </label>

                        <label style="display: flex; align-items: center; gap: 0.5rem; border: 2px solid #ef4444; padding: 0.75rem 1.25rem; border-radius: var(--radius-md); background: #fef2f2; cursor: pointer;">
                            <input type="radio" name="vaccination_status" value="not_vaccinated" <?= $booking['vaccination_status'] === 'not_vaccinated' ? 'checked' : ''; ?> required>
                            <span style="font-weight: 700; color: #991b1b;">&#10006; Not Vaccinated (Missed / Deferred)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="vaccinated_on" class="form-label required">Administration Date & Time</label>
                    <input type="datetime-local" id="vaccinated_on" name="vaccinated_on" class="form-control" 
                           value="<?= date('Y-m-d\TH:i', !empty($booking['vaccinated_on']) ? strtotime($booking['vaccinated_on']) : time()); ?>" required>
                    <small class="form-hint">Exact timestamp when the dose was completed</small>
                </div>

                <div class="form-group">
                    <label for="remarks" class="form-label">Clinical Remarks & Observations</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Injection site (e.g. left deltoid), post-dose observation period, batch number, or deferral reason..."><?= e($booking['remarks']); ?></textarea>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <a href="<?= url('hospital/appointments.php'); ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Clinical Status &rarr;</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
