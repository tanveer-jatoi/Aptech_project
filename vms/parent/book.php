<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: parent/book.php
 * Description: Hospital search & vaccination slot booking
 * ----------------------------------------------------------------------------
 * Changes in this version
 *  - Changing the vaccine no longer resets the selected child.
 *  - Server re-checks that the hospital is active AND stocks the vaccine
 *    (previously a tampered hospital_id was accepted).
 *  - Stops booking more doses than the vaccine's dose count.
 *  - Time slot whitelist, valid date, max 90 days ahead, remarks length cap.
 *  - Form keeps the parent's choices after a validation error.
 *  - ?hospital_id= pre-selects a hospital (used by hospitals.php "Book a slot").
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../includes/ui.php';

requireRole('parent');

$pdo    = getDBConnection();
$userId = currentUserId();

$timeSlots = [
    '09:00 AM - 10:00 AM' => 'Morning, 1st slot',
    '10:30 AM - 11:30 AM' => 'Morning, 2nd slot',
    '02:00 PM - 03:00 PM' => 'Afternoon, 1st slot',
    '03:30 PM - 04:30 PM' => 'Afternoon, 2nd slot',
];
const BOOKING_MAX_DAYS_AHEAD = 90;

// Parent profile
$pStmt = $pdo->prepare('SELECT id FROM parents WHERE user_id = ? LIMIT 1');
$pStmt->execute([$userId]);
$parentId = (int)$pStmt->fetchColumn();

if ($parentId === 0) {
    setFlash('warning', 'Please complete your parent profile first.');
    header('Location: ' . url('parent/profile.php'));
    exit;
}

// Children
$cStmt = $pdo->prepare('SELECT id, name, dob, blood_group FROM children WHERE parent_id = ? ORDER BY name ASC');
$cStmt->execute([$parentId]);
$children = $cStmt->fetchAll();

if (empty($children)) {
    setFlash('warning', 'Add your child first, then you can book a vaccination slot.');
    header('Location: ' . url('parent/children.php?action=add'));
    exit;
}

// Vaccines
$vaccines = $pdo->query(
    'SELECT id, name, recommended_age_days, doses, description
     FROM vaccines WHERE status = \'available\' ORDER BY recommended_age_days ASC'
)->fetchAll();

if (empty($vaccines)) {
    setFlash('warning', 'No vaccines are available for booking right now.');
    header('Location: ' . url('parent/dashboard.php'));
    exit;
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$input  = $isPost ? $_POST : $_GET;

// Selected child / vaccine / hospital (from POST after an error, otherwise from the link)
$childIds   = array_map(static fn($c) => (int)$c['id'], $children);
$vaccineIds = array_map(static fn($v) => (int)$v['id'], $vaccines);

$selectedChildId = (int)($input['child_id'] ?? 0);
if (!in_array($selectedChildId, $childIds, true)) {
    $selectedChildId = $childIds[0];
}
$selectedHospitalId = (int)($input['hospital_id'] ?? 0);
$selectedVaccineId  = (int)($input['vaccine_id'] ?? 0);

// Arrived from hospitals.php with only a hospital: start with a vaccine that center stocks.
if (!$isPost && $selectedVaccineId === 0 && $selectedHospitalId > 0) {
    $q = $pdo->prepare(
        'SELECT hv.vaccine_id FROM hospital_vaccines hv
         JOIN vaccines v ON v.id = hv.vaccine_id
         WHERE hv.hospital_id = ? AND hv.status = \'available\' AND v.status = \'available\'
         ORDER BY v.recommended_age_days ASC LIMIT 1'
    );
    $q->execute([$selectedHospitalId]);
    $selectedVaccineId = (int)$q->fetchColumn();
}
if (!in_array($selectedVaccineId, $vaccineIds, true)) {
    $selectedVaccineId = $vaccineIds[0];
}

$selectedCity   = mb_substr(trim((string)($_GET['city'] ?? '')), 0, 60);
$searchHospital = mb_substr(trim((string)($_GET['hospital_search'] ?? '')), 0, 80);

$formDate   = $isPost ? (string)($_POST['preferred_date'] ?? '') : date('Y-m-d', strtotime('+2 days'));
$formSlot   = $isPost ? (string)($_POST['time_slot'] ?? '') : '';
$formRemark = $isPost ? mb_substr(trim((string)($_POST['remarks'] ?? '')), 0, 300) : '';

// Hospitals that are active and stock the chosen vaccine
$hSql = 'SELECT h.*
         FROM hospitals h
         JOIN hospital_vaccines hv ON h.id = hv.hospital_id
         WHERE h.status = \'active\' AND hv.vaccine_id = ? AND hv.status = \'available\'';
$hParams = [$selectedVaccineId];

if ($selectedCity !== '') {
    $hSql .= ' AND h.city = ?';
    $hParams[] = $selectedCity;
}
if ($searchHospital !== '') {
    $hSql .= ' AND (h.name LIKE ? OR h.location LIKE ?)';
    $term = '%' . likeEscape($searchHospital) . '%';
    array_push($hParams, $term, $term);
}
$hSql .= ' ORDER BY h.city ASC, h.name ASC';

$hStmt = $pdo->prepare($hSql);
$hStmt->execute($hParams);
$availableHospitals = $hStmt->fetchAll();

$hospitalIds = array_map(static fn($h) => (int)$h['id'], $availableHospitals);
if (!in_array($selectedHospitalId, $hospitalIds, true)) {
    $selectedHospitalId = $hospitalIds[0] ?? 0;
}

$errors = [];

// ----------------------------------------------------------------------------
// SUBMIT BOOKING
// ----------------------------------------------------------------------------
if ($isPost) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Your session expired. Please reload the page and try again.';
    } else {
        $childId       = (int)($_POST['child_id'] ?? 0);
        $vaccineId     = (int)($_POST['vaccine_id'] ?? 0);
        $hospitalId    = (int)($_POST['hospital_id'] ?? 0);
        $preferredDate = $formDate;
        $timeSlot      = $formSlot;
        $remarks       = $formRemark;

        if ($childId <= 0)    { $errors[] = 'Select a child.'; }
        if ($vaccineId <= 0)  { $errors[] = 'Select a vaccine.'; }
        if ($hospitalId <= 0) { $errors[] = 'Select a vaccination center.'; }

        if ($preferredDate === '' || !isValidYmd($preferredDate)) {
            $errors[] = 'Choose a valid appointment date.';
        } else {
            $ts = strtotime($preferredDate);
            if ($ts <= strtotime('today')) {
                $errors[] = 'The appointment date must be tomorrow or later.';
            } elseif ($ts > strtotime('+' . BOOKING_MAX_DAYS_AHEAD . ' days')) {
                $errors[] = 'Appointments can be booked up to ' . BOOKING_MAX_DAYS_AHEAD . ' days ahead.';
            }
        }
        if (!isset($timeSlots[$timeSlot])) {
            $errors[] = 'Choose one of the listed time slots.';
        }

        $childRecord = null;
        $vaccineRow  = null;

        // The child must belong to this parent
        if (empty($errors)) {
            $q = $pdo->prepare('SELECT id, name FROM children WHERE id = ? AND parent_id = ? LIMIT 1');
            $q->execute([$childId, $parentId]);
            $childRecord = $q->fetch();
            if (!$childRecord) {
                $errors[] = 'That child profile does not belong to your account.';
            }
        }

        // The vaccine must exist and be available
        if (empty($errors)) {
            $q = $pdo->prepare('SELECT id, name, doses FROM vaccines WHERE id = ? AND status = \'available\' LIMIT 1');
            $q->execute([$vaccineId]);
            $vaccineRow = $q->fetch();
            if (!$vaccineRow) {
                $errors[] = 'That vaccine is not available for booking.';
            }
        }

        // The hospital must be active AND stock this vaccine (never trust the posted id)
        if (empty($errors)) {
            $q = $pdo->prepare(
                'SELECT h.id FROM hospitals h
                 JOIN hospital_vaccines hv ON hv.hospital_id = h.id
                 WHERE h.id = ? AND h.status = \'active\' AND hv.vaccine_id = ? AND hv.status = \'available\' LIMIT 1'
            );
            $q->execute([$hospitalId, $vaccineId]);
            if (!$q->fetch()) {
                $errors[] = 'That center does not currently stock this vaccine. Please pick another center.';
            }
        }

        // No second active request, and no more doses than the vaccine has
        if (empty($errors)) {
            $q = $pdo->prepare(
                'SELECT COUNT(*) FROM bookings
                 WHERE child_id = ? AND vaccine_id = ? AND admin_status != \'rejected\' AND vaccination_status = \'pending\''
            );
            $q->execute([$childId, $vaccineId]);
            if ((int)$q->fetchColumn() > 0) {
                $errors[] = $childRecord['name'] . ' already has an active request for ' . $vaccineRow['name'] . '. Cancel it first if you want to change it.';
            } else {
                $q = $pdo->prepare(
                    'SELECT COUNT(*) FROM bookings WHERE child_id = ? AND vaccine_id = ? AND vaccination_status = \'vaccinated\''
                );
                $q->execute([$childId, $vaccineId]);
                if ((int)$q->fetchColumn() >= max(1, (int)$vaccineRow['doses'])) {
                    $errors[] = $childRecord['name'] . ' has already completed all doses of ' . $vaccineRow['name'] . '.';
                }
            }
        }

        if (empty($errors)) {
            try {
                $ins = $pdo->prepare(
                    'INSERT INTO bookings (child_id, parent_id, hospital_id, vaccine_id, preferred_date, time_slot, admin_status, vaccination_status, remarks, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, \'pending\', \'pending\', ?, NOW())'
                );
                $ins->execute([$childId, $parentId, $hospitalId, $vaccineId, $preferredDate, $timeSlot, $remarks]);

                setFlash('success', 'Request sent. An administrator will review it shortly.');
                header('Location: ' . url('parent/bookings.php'));
                exit;
            } catch (PDOException $ex) {
                error_log('[VMS BOOKING ERROR] ' . $ex->getMessage());
                $errors[] = 'We could not save your request. Please try again.';
            }
        }
    }
}

$cities = $pdo->query('SELECT DISTINCT city FROM hospitals WHERE status = \'active\' ORDER BY city ASC')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Book Vaccination Appointment';
$pageDescription = 'Choose a child, a vaccine, and a vaccination center, then request a time slot.';
require_once __DIR__ . '/../includes/header.php';
uiStyles();
?>

<div class="container">
    <div style="max-width: 850px; margin: 0 auto;">
        <div style="margin-bottom: 1.5rem;">
            <h1>Book a vaccination slot</h1>
            <p style="margin: 0;">Pick the child, the vaccine, and a center that has it in stock. An administrator approves each request.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <div>
                    <strong>Please fix the following:</strong>
                    <ul style="margin: .25rem 0 0 1.2rem;">
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

                <div class="card-header">
                    <h3 class="ui-step"><span class="ui-step-n">1</span> Child</h3>
                </div>
                <div class="form-group">
                    <label for="child_id" class="form-label required">Who is getting the vaccine?</label>
                    <select id="child_id" name="child_id" class="form-select" required>
                        <?php foreach ($children as $c): ?>
                            <option value="<?= (int)$c['id']; ?>" <?= $selectedChildId === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?= e($c['name']); ?> &middot; <?= e(formatAge($c['dob'])); ?> old &middot; Blood group <?= e($c['blood_group']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="card-header" style="margin-top: 1.5rem;">
                    <h3 class="ui-step"><span class="ui-step-n">2</span> Vaccine</h3>
                </div>
                <div class="form-group">
                    <label for="vaccine_id" class="form-label required">Vaccine</label>
                    <select id="vaccine_id" name="vaccine_id" class="form-select" onchange="changeVaccine(this)" required>
                        <?php foreach ($vaccines as $v): ?>
                            <option value="<?= (int)$v['id']; ?>" <?= $selectedVaccineId === (int)$v['id'] ? 'selected' : ''; ?>>
                                <?= e($v['name']); ?> (<?= e(ageGroupLabel((int)$v['recommended_age_days'])); ?>, <?= (int)$v['doses']; ?> <?= (int)$v['doses'] === 1 ? 'dose' : 'doses'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Changing the vaccine reloads the list of centers that stock it.</small>
                </div>

                <div class="card-header" style="margin-top: 1.5rem;">
                    <div>
                        <h3 class="ui-step"><span class="ui-step-n">3</span> Vaccination center</h3>
                        <p style="margin: .25rem 0 0; font-size: .85rem;">Only active centers with this vaccine in stock are listed.</p>
                    </div>
                </div>

                <?php if (empty($availableHospitals)): ?>
                    <div class="alert alert-warning" role="alert">
                        <span>No active center has this vaccine in stock right now. Choose another vaccine or <a href="<?= url('contact.php'); ?>">contact the helpdesk</a>.</span>
                    </div>
                <?php else: ?>
                    <div class="ui-note" style="margin-bottom: 1rem; padding: .75rem 1rem;">
                        <label for="filter_hosp_input" class="ui-sr-only">Filter centers</label>
                        <input type="search" id="filter_hosp_input" class="form-control" placeholder="Filter centers by name or area" oninput="filterHospitalCards()">
                    </div>

                    <div class="grid grid-2" id="hospitalsContainer" style="gap: 1rem; margin-bottom: 1.5rem;">
                        <?php foreach ($availableHospitals as $h): ?>
                            <label class="hospital-radio-card">
                                <span class="ui-hosp-pick">
                                    <input type="radio" name="hospital_id" value="<?= (int)$h['id']; ?>"
                                           <?= $selectedHospitalId === (int)$h['id'] ? 'checked' : ''; ?> required style="margin-top: .25rem;">
                                    <span>
                                        <strong><?= e($h['name']); ?></strong><br>
                                        <small style="color: var(--text-muted);"><?= e($h['location']); ?>, <?= e($h['city']); ?></small><br>
                                        <small style="color: var(--text-light, #64748b);"><?= e($h['phone']); ?></small><br>
                                        <span class="badge badge-success" style="margin-top: .35rem; font-size: .7rem;">In stock</span>
                                    </span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card-header" style="margin-top: 1.5rem;">
                    <h3 class="ui-step"><span class="ui-step-n">4</span> Date and time</h3>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="preferred_date" class="form-label required">Preferred date</label>
                        <input type="date" id="preferred_date" name="preferred_date" class="form-control"
                               min="<?= date('Y-m-d', strtotime('+1 day')); ?>"
                               max="<?= date('Y-m-d', strtotime('+' . BOOKING_MAX_DAYS_AHEAD . ' days')); ?>"
                               value="<?= e($formDate); ?>" required>
                        <small class="form-hint">Book at least one day ahead, up to <?= BOOKING_MAX_DAYS_AHEAD; ?> days.</small>
                    </div>

                    <div class="form-group">
                        <label for="time_slot" class="form-label required">Time slot</label>
                        <select id="time_slot" name="time_slot" class="form-select" required>
                            <?php foreach ($timeSlots as $slot => $label): ?>
                                <option value="<?= e($slot); ?>" <?= $formSlot === $slot ? 'selected' : ''; ?>><?= e($slot); ?> (<?= e($label); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="remarks" class="form-label">Notes or allergies (optional)</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="2" maxlength="300"
                              placeholder="Allergies, earlier reactions, or timing requests"><?= e($formRemark); ?></textarea>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="<?= url('parent/dashboard.php'); ?>" class="btn btn-outline" style="margin-right: .5rem;">Cancel</a>
                    <button type="submit" class="btn btn-primary" <?= empty($availableHospitals) ? 'disabled' : ''; ?>>Send request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Reload the center list for the new vaccine, keeping the child that is currently selected.
function changeVaccine(select) {
    var child = document.getElementById('child_id').value;
    var url = new URL(window.location.href);
    url.search = '';
    url.searchParams.set('child_id', child);
    url.searchParams.set('vaccine_id', select.value);
    window.location.href = url.toString();
}

function filterHospitalCards() {
    var needle = document.getElementById('filter_hosp_input').value.toLowerCase();
    document.querySelectorAll('.hospital-radio-card').forEach(function (card) {
        card.style.display = card.textContent.toLowerCase().indexOf(needle) !== -1 ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>