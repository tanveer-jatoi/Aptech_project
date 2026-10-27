<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: hospital/vaccine_stock.php
 * Description: Hospital Facility Local Vaccine Inventory & Availability
 * ============================================================================
 * 
 * SPECIFICATION COMPLIANCE:
 * - Update local vaccine availability (Available / Unavailable) for the facility.
 * - Controls whether parents can book appointments for specific vaccines here.
 * - One-click CSRF status toggle with session flash toast notification.
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

// ----------------------------------------------------------------------------
// 1. HANDLE POST ACTIONS (Toggle Availability)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . url('hospital/vaccine_stock.php'));
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';

    // ========================================================================
    // A. SINGLE VACCINE STATUS TOGGLE
    // ========================================================================
    if ($formAction === 'toggle') {
        $vaccineId = (int)($_POST['vaccine_id'] ?? 0);
        $newStatus = in_array($_POST['new_status'] ?? '', ['available', 'unavailable']) ? $_POST['new_status'] : 'available';

        if ($vaccineId > 0) {
            try {
                // Upsert into hospital_vaccines table
                $stmt = $pdo->prepare(
                    'INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status, updated_at) 
                     VALUES (?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
                );
                $stmt->execute([$hospitalId, $vaccineId, $newStatus]);

                // Fetch vaccine title for clean toast feedback
                $vNameStmt = $pdo->prepare('SELECT name FROM vaccines WHERE id = ?');
                $vNameStmt->execute([$vaccineId]);
                $vName = $vNameStmt->fetchColumn() ?: 'Vaccine';

                setFlash('success', $vName . ' status updated to "' . ucfirst($newStatus) . '" at your facility!');
            } catch (PDOException $e) {
                error_log('[VACCINE STOCK TOGGLE ERROR] ' . $e->getMessage());
                setFlash('danger', 'Failed to update stock: ' . $e->getMessage());
            }
        }
    }

    // ========================================================================
    // B. BULK UPDATE: MARK ALL AVAILABLE / UNAVAILABLE
    // ========================================================================
    elseif ($formAction === 'bulk') {
        $bulkStatus = in_array($_POST['bulk_status'] ?? '', ['available', 'unavailable']) ? $_POST['bulk_status'] : 'available';

        try {
            $allV = $pdo->query('SELECT id FROM vaccines WHERE status = "available"')->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($allV)) {
                $bStmt = $pdo->prepare(
                    'INSERT INTO hospital_vaccines (hospital_id, vaccine_id, status, updated_at) 
                     VALUES (?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
                );
                foreach ($allV as $vId) {
                    $bStmt->execute([$hospitalId, $vId, $bulkStatus]);
                }
            }

            setFlash('success', 'All catalog vaccines have been marked as "' . ucfirst($bulkStatus) . '".');
        } catch (PDOException $e) {
            error_log('[BULK STOCK ERROR] ' . $e->getMessage());
            setFlash('danger', 'Bulk update failed: ' . $e->getMessage());
        }
    }

    header('Location: ' . url('hospital/vaccine_stock.php'));
    exit;
}

// ----------------------------------------------------------------------------
// 2. FETCH VACCINES CATALOG WITH THIS HOSPITAL'S CURRENT STOCK STATUS
// ----------------------------------------------------------------------------
$sql = 'SELECT v.*,
               COALESCE(hv.status, "available") AS local_status,
               hv.updated_at AS stock_updated_at
        FROM vaccines v
        LEFT JOIN hospital_vaccines hv ON v.id = hv.vaccine_id AND hv.hospital_id = ?
        WHERE v.status = "available"
        ORDER BY v.recommended_age_days ASC, v.name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute([$hospitalId]);
$vaccines = $stmt->fetchAll();

// Count available vs unavailable
$countInStock = 0;
$countOutOfStock = 0;
foreach ($vaccines as $vac) {
    if ($vac['local_status'] === 'available') {
        $countInStock++;
    } else {
        $countOutOfStock++;
    }
}

$pageTitle = 'Vaccine Inventory & Stock: ' . $hospital['name'];
$pageDescription = 'Declare vaccine availability and stock status for parent appointment booking.';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Vaccine Stock & Availability</h1>
            <p style="margin: 0;">Declare local vaccine inventory for <?= e($hospital['name']); ?>. Only available vaccines can be booked by parents.</p>
        </div>

        <!-- Bulk Controls -->
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <form action="<?= url('hospital/vaccine_stock.php'); ?>" method="POST" style="display: inline-block;">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="bulk">
                <input type="hidden" name="bulk_status" value="available">
                <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Mark all vaccines as Available in your clinic?');">
                    &#10004; Mark All In Stock
                </button>
            </form>

            <form action="<?= url('hospital/vaccine_stock.php'); ?>" method="POST" style="display: inline-block;">
                <?= csrfField(); ?>
                <input type="hidden" name="form_action" value="bulk">
                <input type="hidden" name="bulk_status" value="unavailable">
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);" 
                        onclick="return confirm('Mark all vaccines as Out of Stock?');">
                    &#10006; Mark All Out of Stock
                </button>
            </form>
        </div>
    </div>

    <!-- Stock Summary Cards -->
    <div class="grid grid-2" style="gap: 1.25rem; margin-bottom: 2rem; max-width: 600px;">
        <div class="card" style="padding: 1.25rem; text-align: center; border-left: 4px solid var(--success);">
            <span class="stat-label">Currently In Stock</span>
            <div style="font-size: 2rem; font-weight: 800; color: var(--success); margin-top: 0.25rem;">
                <?= $countInStock; ?>
            </div>
            <small style="color: var(--text-muted);">Available for parent booking</small>
        </div>

        <div class="card" style="padding: 1.25rem; text-align: center; border-left: 4px solid var(--danger);">
            <span class="stat-label">Temporarily Out of Stock</span>
            <div style="font-size: 2rem; font-weight: 800; color: var(--danger); margin-top: 0.25rem;">
                <?= $countOutOfStock; ?>
            </div>
            <small style="color: var(--text-muted);">Hidden from booking options</small>
        </div>
    </div>

    <!-- Stock Management Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vaccine Name & Indications</th>
                        <th>Recommended Target Age</th>
                        <th>Doses</th>
                        <th>Stock Status</th>
                        <th>Last Updated</th>
                        <th style="text-align: right;">Toggle Availability</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vaccines as $v): 
                        $isAvailable = ($v['local_status'] === 'available');
                    ?>
                        <tr>
                            <td style="max-width: 320px;">
                                <strong><?= e($v['name']); ?></strong><br>
                                <small style="color: var(--text-muted); line-height: 1.4; display: block; margin-top: 0.2rem;">
                                    <?= e($v['description']); ?>
                                </small>
                            </td>
                            <td>
                                <?= (int)$v['recommended_age_days'] === 0 ? 'At Birth' : (int)$v['recommended_age_days'] . ' days'; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= (int)$v['doses']; ?> <?= (int)$v['doses'] === 1 ? 'dose' : 'doses'; ?></span>
                            </td>
                            <td>
                                <?php if ($isAvailable): ?>
                                    <span class="badge badge-success">&#10004; In Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">&#10006; Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small style="color: var(--text-muted);">
                                    <?= !empty($v['stock_updated_at']) ? formatDate($v['stock_updated_at'], 'M d, Y') : 'Default'; ?>
                                </small>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <form action="<?= url('hospital/vaccine_stock.php'); ?>" method="POST" style="display: inline-block;">
                                    <?= csrfField(); ?>
                                    <input type="hidden" name="form_action" value="toggle">
                                    <input type="hidden" name="vaccine_id" value="<?= (int)$v['id']; ?>">

                                    <?php if ($isAvailable): ?>
                                        <input type="hidden" name="new_status" value="unavailable">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);" title="Set Out of Stock">
                                            Mark Out of Stock
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="available">
                                        <button type="submit" class="btn btn-success btn-sm" title="Set In Stock">
                                            &#10004; Mark In Stock
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
