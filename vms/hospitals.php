<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: hospitals.php
 * Description: Public directory of participating vaccination centers
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';
require_once __DIR__ . '/includes/ui.php';

$pdo = getDBConnection();

const HOSPITALS_PER_PAGE = 12;

$search     = trim((string)($_GET['search'] ?? ''));
$filterCity = trim((string)($_GET['city'] ?? ''));
$search     = mb_substr($search, 0, 80);
$page       = max(1, (int)($_GET['page'] ?? 1));

$where  = ' WHERE h.status = \'active\'';
$params = [];

if ($search !== '') {
    $where .= ' AND (h.name LIKE ? OR h.location LIKE ? OR h.address LIKE ?)';
    $term = '%' . likeEscape($search) . '%';
    array_push($params, $term, $term, $term);
}
if ($filterCity !== '') {
    $where .= ' AND h.city = ?';
    $params[] = $filterCity;
}

try {
    // Total for pagination
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM hospitals h' . $where);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $totalPages = max(1, (int)ceil($total / HOSPITALS_PER_PAGE));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * HOSPITALS_PER_PAGE;

    $sql = 'SELECT h.*,
                   (SELECT COUNT(*) FROM hospital_vaccines hv
                     WHERE hv.hospital_id = h.id AND hv.status = \'available\') AS available_stock_count
            FROM hospitals h' . $where . '
            ORDER BY h.city ASC, h.name ASC
            LIMIT ' . (int)HOSPITALS_PER_PAGE . ' OFFSET ' . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll();

    $cities = $pdo->query('SELECT DISTINCT city FROM hospitals WHERE status = \'active\' ORDER BY city ASC')
                  ->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $ex) {
    error_log('[VMS HOSPITALS ERROR] ' . $ex->getMessage());
    $hospitals = [];
    $cities    = [];
    $total     = 0;
    $totalPages = 1;
    $dbFailed  = true;
}

// Role-aware "Book" link: guests -> login, parents -> booking page with this hospital selected
$isParent = isLoggedIn() && currentRole() === 'parent';

function hospitalsPageUrl(int $p, string $search, string $city): string
{
    $q = ['page' => $p];
    if ($search !== '') { $q['search'] = $search; }
    if ($city !== '')   { $q['city']   = $city; }
    return url('hospitals.php?' . http_build_query($q));
}

$pageTitle = 'Vaccination Centers | Hospitals';
$pageDescription = 'Find registered pediatric hospitals and vaccination clinics near you.';
require_once __DIR__ . '/includes/header.php';
uiStyles();
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 3rem;">

    <?php uiPageHeader(
        'Vaccination centers',
        'Browse hospitals and clinics in the VMS network, check which vaccines they stock, and book a slot.',
        'Participating network'
    ); ?>

    <?php if (!empty($dbFailed)): ?>
        <div class="alert alert-danger" role="alert"><span>The center list could not be loaded. Please try again shortly.</span></div>
    <?php endif; ?>

    <form action="<?= url('hospitals.php'); ?>" method="GET" class="card ui-filter" role="search">
        <div class="grow">
            <label for="search" class="form-label">Search by name or area</label>
            <input type="search" id="search" name="search" class="form-control" maxlength="80"
                   placeholder="Hospital name, area, or landmark" value="<?= e($search); ?>">
        </div>

        <div class="mid">
            <label for="city" class="form-label">City</label>
            <select id="city" name="city" class="form-select">
                <option value="">All cities</option>
                <?php foreach ($cities as $c): ?>
                    <option value="<?= e($c); ?>" <?= $filterCity === $c ? 'selected' : ''; ?>><?= e($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="btns">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search !== '' || $filterCity !== ''): ?>
                <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($hospitals)): ?>
        <div class="card ui-empty">
            <?= uiIcon('hospital', 36); ?>
            <h3>No centers match your search</h3>
            <p>Try a different name or area, or clear the filters to see every center.</p>
            <a href="<?= url('hospitals.php'); ?>" class="btn btn-outline btn-sm">Clear filters</a>
        </div>
    <?php else: ?>
        <p class="ui-count">
            <?= (int)$total; ?> <?= $total === 1 ? 'center' : 'centers'; ?>
            <?= $filterCity !== '' ? 'in ' . e($filterCity) : 'across ' . count($cities) . ' ' . (count($cities) === 1 ? 'city' : 'cities'); ?>
        </p>

        <div class="grid grid-3" style="gap: 1.25rem;">
            <?php foreach ($hospitals as $h):
                $stock   = (int)$h['available_stock_count'];
                $mapsUrl = 'https://www.google.com/maps/search/?api=1&query='
                         . rawurlencode($h['name'] . ', ' . $h['address'] . ', ' . $h['city']);
                $telHref = 'tel:' . preg_replace('/[^0-9+]/', '', (string)$h['phone']);
            ?>
                <article class="card ui-hosp">
                    <div>
                        <div class="ui-hosp-top">
                            <span class="badge badge-info"><?= e($h['city']); ?></span>
                            <span class="badge <?= $stock > 0 ? 'badge-success' : 'badge-warning'; ?>">
                                <?= $stock > 0 ? $stock . ' ' . ($stock === 1 ? 'vaccine' : 'vaccines') . ' in stock' : 'Stock not listed'; ?>
                            </span>
                        </div>
                        <h3><?= e($h['name']); ?></h3>
                        <p class="where"><?= e($h['location']); ?></p>
                        <p class="addr"><?= e($h['address']); ?></p>
                    </div>

                    <div class="ui-hosp-foot">
                        <div class="ui-hosp-meta">
                            <a href="<?= e($telHref); ?>"><?= uiIcon('phone', 15); ?> <?= e($h['phone']); ?></a>
                            <a href="<?= e($mapsUrl); ?>" target="_blank" rel="noopener noreferrer"><?= uiIcon('map', 15); ?> Open in Maps</a>
                        </div>

                        <div class="ui-hosp-actions">
                            <?php if ($isParent): ?>
                                <a href="<?= url('parent/book.php?hospital_id=' . (int)$h['id']); ?>" class="btn btn-primary btn-sm btn-block">Book a slot</a>
                            <?php elseif (!isLoggedIn()): ?>
                                <a href="<?= url('login.php'); ?>" class="btn btn-primary btn-sm btn-block">Sign in to book</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="ui-pager" aria-label="Pages">
                <?php if ($page > 1): ?>
                    <a href="<?= e(hospitalsPageUrl($page - 1, $search, $filterCity)); ?>" rel="prev">Previous</a>
                <?php else: ?>
                    <span class="is-off">Previous</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="is-current" aria-current="page"><?= $i; ?></span>
                    <?php else: ?>
                        <a href="<?= e(hospitalsPageUrl($i, $search, $filterCity)); ?>"><?= $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= e(hospitalsPageUrl($page + 1, $search, $filterCity)); ?>" rel="next">Next</a>
                <?php else: ?>
                    <span class="is-off">Next</span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>