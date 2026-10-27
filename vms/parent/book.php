<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/book.php
 * Description: Hospital Search & Vaccination Slot Booking Portal
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Search registered hospitals stocking the requested vaccine.
 * - Book appointment slot (preferred date and time slot).
 * - Enforces admin approval workflow (admin_status = 'pending').
 * - Prevents duplicate pending/completed bookings for the same vaccine & child.
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

if ($parentId === 0) {
    setFlash('warning', 'Please complete your parent profile details first.');
    header('Location: ' . url('parent/profile.php'));
    exit;
}

// Fetch Parent's Registered Children
$cStmt = $pdo->prepare('SELECT id, name, dob, blood_group FROM children WHERE parent_id = ? ORDER BY name ASC');
$cStmt->execute([$parentId]);
$children = $cStmt->fetchAll();

if (empty($children)) {
    setFlash('warning', 'Please register your child before booking a vaccination slot.');
    header('Location: ' . url('parent/children.php?action=add'));
    exit;
}

// Fetch Active Vaccines
$vStmt = $pdo->query('SELECT id, name, recommended_age_days, doses, description FROM vaccines WHERE status = "available" ORDER BY recommended_age_days ASC');
$vaccines = $vStmt->fetchAll();

// Pre-selected parameters from links
$selectedChildId   = (int)($_GET['child_id'] ?? ($children[0]['id'] ?? 0));
$selectedVaccineId = (int)($_GET['vaccine_id'] ?? ($vaccines[0]['id'] ?? 0));
$selectedCity      = sanitizeInput($_GET['city'] ?? '');
$searchHospital    = sanitizeInput($_GET['hospital_search'] ?? '');

// Fetch Hospitals Stocking the Chosen Vaccine
$hSql = 'SELECT h.* 
         FROM hospitals h
         JOIN hospital_vaccines hv ON h.id = hv.hospital_id
         WHERE h.status = "active" AND hv.vaccine_id = ? AND hv.status = "available"';
$hParams = [$selectedVaccineId];

if (!empty($selectedCity)) {
    $hSql .= ' AND h.city = ?';
    $hParams[] = $selectedCity;
}
if (!empty($searchHospital)) {
    $hSql .= ' AND (h.name LIKE ? OR h.location LIKE ?)';
    $term = '%' . $searchHospital . '%';
    $hParams[] = $term;
    $hParams[] = $term;
}
$hSql .= ' ORDER BY h.city ASC, h.name ASC';

$hStmt = $pdo->prepare($hSql);
$hStmt->execute($hParams);
$availableHospitals = $hStmt->fetchAll();

// Distinct cities list for filter
$cities = $pdo->query('SELECT DISTINCT city FROM hospitals WHERE status = "active" ORDER BY city ASC')->fetchAll(PDO::FETCH_COLUMN);

$errors = [];

// ----------------------------------------------------------------------------
// HANDLE BOOKING FORM SUBMISSION (POST)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    } else {
        $childId       = (int)($_POST['child_id'] ?? 0);
        $vaccineId     = (int)($_POST['vaccine_id'] ?? 0);
        $hospitalId    = (int)($_POST['hospital_id'] ?? 0);
        $preferredDate = sanitizeInput($_POST['preferred_date'] ?? '');
        $timeSlot      = sanitizeInput($_POST['time_slot'] ?? '');
        $remarks       = sanitizeInput($_POST['remarks'] ?? '');

        // Validation constraints
        if ($childId <= 0)       $errors[] = 'Please select a child.';
        if ($vaccineId <= 0)     $errors[] = 'Please select a vaccine.';
        if ($hospitalId <= 0)    $errors[] = 'Please select a healthcare facility.';
        if (empty($preferredDate)) $errors[] = 'Please select a preferred appointment date.';
        elseif (strtotime($preferredDate) <= strtotime('today')) $errors[] = 'Appointment date must be scheduled in advance (at least tomorrow).';
        if (empty($timeSlot))    $errors[] = 'Please choose a preferred time slot.';

        // Verify child belongs to this parent
        if (empty($errors)) {
            $verifyChild = $pdo->prepare('SELECT id, name FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
            $verifyChild->execute([$childId, $parentId]);
            $childRecord = $verifyChild->fetch();
            if (!$childRecord) {
                $errors[] = 'Unauthorized child selection.';
            }
        }

        // Check for duplicate pending or approved booking
        if (empty($errors)) {
            $dupStmt = $pdo->prepare(
                'SELECT id, admin_status, vaccination_status 
                 FROM bookings 
                 WHERE child_id = ? AND vaccine_id = ? AND admin_status != "rejected" AND vaccination_status = "pending" LIMIT 1'
            );
            $dupStmt->execute([$childId, $vaccineId]);
            $existing = $dupStmt->fetch();

            if ($existing) {
                $errors[] = 'An active appointment request for this vaccine is already registered for ' . $childRecord['name'] . '.';
            }
        }

        // Insert Booking
        if (empty($errors)) {
            try {
                $bInsert = $pdo->prepare(
                    'INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, preferred_date, time_slot, admin_status, vaccination_status, remarks, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, "pending", "pending", ?, NOW())'
                );
                $bInsert->execute([
                    $childId,
                    $parentId,
                    $hospitalId,
                    $vaccineId,
                    $preferredDate,
                    $timeSlot,
                    $remarks
                ]);

                setFlash('success', 'Your appointment request has been submitted successfully! It is now awaiting administrative approval.');
                header('Location: ' . url('parent/bookings.php'));
                exit;
            } catch (PDOException $e) {
                error_log('[BOOKING ERROR] ' . $e->getMessage());
                $errors[] = 'Database error submitting appointment: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Book Vaccination Appointment';
$pageDescription = 'Search participating pediatric hospitals and reserve an immunization slot.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="max-width: 850px; margin: 0 auto;">
        <div style="margin-bottom: 1.5rem;">
            <h1>Book Vaccination Appointment</h1>
            <p style="margin: 0;">Select your child, required vaccine, and preferred healthcare center. Requests are verified by administration.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <div>
                    <strong>Please resolve the following:</strong>
                    <ul style="margin: 0.25rem 0 0 1.2rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <form action="<?= url('parent/book.php'); ?>" method="POST" id="bookingForm">
                <?= csrfField(); ?>

                <!-- Step 1: Select Child -->
                <div class="card-header">
                    <h3>Step 1: Select Child</h3>
                </div>
                <div class="form-group">
                    <label for="child_id" class="form-label required">Which child is receiving the vaccine?</label>
                    <select id="child_id" name="child_id" class="form-select" required>
                        <?php foreach ($children as $c): ?>
                            <option value="<?= (int)$c['id']; ?>" <?= $selectedChildId === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?= e($c['name']); ?> &bull; <?= formatAge($c['dob']); ?> old (DOB: <?= formatDate($c['dob']); ?>) &bull; Blood: <?= e($c['blood_group']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Select Vaccine -->
                <div class="card-header" style="margin-top: 1.5rem;">
                    <h3>Step 2: Select Vaccine</h3>
                </div>
                <div class="form-group">
                    <label for="vaccine_id" class="form-label required">Target Immunization</label>
                    <select id="vaccine_id" name="vaccine_id" class="form-select" onchange="window.location.href='<?= url('parent/book.php?child_id=' . $selectedChildId . '&vaccine_id='); ?>' + this.value;" required>
                        <?php foreach ($vaccines as $v): ?>
                            <option value="<?= (int)$v['id']; ?>" <?= $selectedVaccineId === (int)$v['id'] ? 'selected' : ''; ?>>
                                <?= e($v['name']); ?> (Target Age: <?= (int)$v['recommended_age_days'] === 0 ? 'Birth' : (int)$v['recommended_age_days'] . 'd'; ?>, <?= (int)$v['doses']; ?> doses)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 3: Hospital Selection -->
                <div class="card-header" style="margin-top: 1.5rem;">
                    <div>
                        <h3>Step 3: Select Healthcare Facility</h3>
                        <p style="margin: 0; font-size: 0.85rem;">Showing authorized centers with confirmed stock of the selected vaccine.</p>
                    </div>
                </div>

                <!-- Hospital Filter Bar -->
                <div style="background: var(--bg-subtle); padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <input type="text" id="filter_hosp_input" class="form-control" placeholder="Quick filter clinics..." style="flex: 2; min-width: 180px;" onkeyup="filterHospitalCards()">
                </div>

                <?php if (empty($availableHospitals)): ?>
                    <div class="alert alert-warning" role="alert">
                        <span>No participating hospitals currently have verified stock for the selected vaccine in this area. Please select another vaccine or contact support.</span>
                    </div>
                <?php else: ?>
                    <div class="grid grid-2" id="hospitalsContainer" style="gap: 1rem; margin-bottom: 1.5rem;">
                        <?php foreach ($availableHospitals as $idx => $h): ?>
                            <label class="hospital-radio-card" style="display: block; border: 2px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; cursor: pointer; transition: all 0.15s ease;" onclick="selectHospitalCard(this)">
                                <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <input type="radio" name="hospital_id" value="<?= (int)$h['id']; ?>" <?= $idx === 0 ? 'checked' : ''; ?> required style="margin-top: 0.25rem;">
                                    <div class="hospital-info">
                                        <strong class="hospital-name"><?= e($h['name']); ?></strong><br>
                                        <small style="color: var(--text-muted);">&#128205; <?= e($h['location']); ?>, <?= e($h['city']); ?></small><br>
                                        <small style="color: var(--text-light);">&#128222; <?= e($h['phone']); ?></small><br>
                                        <span class="badge badge-success" style="margin-top: 0.35rem; font-size: 0.7rem;">In Stock</span>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Step 4: Schedule Preferred Date & Time -->
                <div class="card-header" style="margin-top: 1.5rem;">
                    <h3>Step 4: Date & Time Slot</h3>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="preferred_date" class="form-label required">Preferred Appointment Date</label>
                        <input type="date" id="preferred_date" name="preferred_date" class="form-control" 
                               min="<?= date('Y-m-d', strtotime('+1 day')); ?>" 
                               value="<?= date('Y-m-d', strtotime('+2 days')); ?>" required>
                        <small class="form-hint">At least 24 hours notice is required</small>
                    </div>

                    <div class="form-group">
                        <label for="time_slot" class="form-label required">Preferred Time Slot</label>
                        <select id="time_slot" name="time_slot" class="form-select" required>
                            <option value="09:00 AM - 10:00 AM">09:00 AM - 10:00 AM (Morning Slot 1)</option>
                            <option value="10:30 AM - 11:30 AM">10:30 AM - 11:30 AM (Morning Slot 2)</option>
                            <option value="02:00 PM - 03:00 PM">02:00 PM - 03:00 PM (Afternoon Slot 1)</option>
                            <option value="03:30 PM - 04:30 PM">03:30 PM - 04:30 PM (Afternoon Slot 2)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="remarks" class="form-label">Special Notes / Allergies (Optional)</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="2" placeholder="Any infant allergies, prior reactions, or special timing requests..."></textarea>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="<?= url('parent/dashboard.php'); ?>" class="btn btn-outline" style="margin-right: 0.5rem;">Cancel</a>
                    <button type="submit" class="btn btn-primary" <?= empty($availableHospitals) ? 'disabled' : ''; ?>>
                        Submit Appointment Request &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectHospitalCard(labelEl) {
    document.querySelectorAll('.hospital-radio-card').forEach(function(card) {
        card.style.borderColor = 'var(--border-color)';
        card.style.backgroundColor = 'transparent';
    });
    labelEl.style.borderColor = 'var(--primary)';
    labelEl.style.backgroundColor = 'var(--primary-light)';
}

function filterHospitalCards() {
    var filter = document.getElementById('filter_hosp_input').value.toLowerCase();
    var cards = document.querySelectorAll('.hospital-radio-card');
    cards.forEach(function(card) {
        var text = card.textContent.toLowerCase();
        if (text.includes(filter)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
// Initialize first selected hospital border
document.addEventListener('DOMContentLoaded', function() {
    var checkedRadio = document.querySelector('input[name="hospital_id"]:checked');
    if (checkedRadio) {
        selectHospitalCard(checkedRadio.closest('.hospital-radio-card'));
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
