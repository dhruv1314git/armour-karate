<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Dojo Class Locations Management
 * app/views/pages/admin/locations.php
 */

declare(strict_types=1);

requireAdmin();

$pdo     = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);

// ─── POST Action Dispatcher ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $action     = trim($_POST['action'] ?? '');
    $locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);

    if ($action === 'create_location') {
        $name         = trim($_POST['location_name'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $instructor   = trim($_POST['instructor'] ?? '');
        $trainingDays = trim($_POST['training_days'] ?? '');
        $trainingTime = trim($_POST['training_time'] ?? '');
        $contact      = trim($_POST['contact'] ?? '');
        $mapUrl       = trim($_POST['map_url'] ?? '');
        $isActive     = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;

        if ($name === '' || $address === '' || $trainingDays === '' || $trainingTime === '') {
            flashSet('error', 'Please fill in all required fields (Location Name, Address, Training Days, Training Time).');
            safeRedirect(BASE_PATH . '/admin/locations');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO class_locations 
             (location_name, address, instructor, training_days, training_time, contact, map_url, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $name,
            $address,
            $instructor ?: null,
            $trainingDays,
            $trainingTime,
            $contact ?: null,
            $mapUrl ?: null,
            $isActive,
        ]);
        $newId = (int) $pdo->lastInsertId();

        writeAuditLog($pdo, 'LOCATION_CREATED', 'ClassLocation', $newId, [
            'name'      => $name,
            'is_active' => $isActive,
        ], $adminId);

        flashSet('success', 'Dojo location "' . $name . '" added successfully.');
        safeRedirect(BASE_PATH . '/admin/locations');

    } elseif ($action === 'update_location' && $locationId && $locationId > 0) {
        $name         = trim($_POST['location_name'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $instructor   = trim($_POST['instructor'] ?? '');
        $trainingDays = trim($_POST['training_days'] ?? '');
        $trainingTime = trim($_POST['training_time'] ?? '');
        $contact      = trim($_POST['contact'] ?? '');
        $mapUrl       = trim($_POST['map_url'] ?? '');
        $isActive     = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;

        if ($name === '' || $address === '' || $trainingDays === '' || $trainingTime === '') {
            flashSet('error', 'Please fill in all required fields.');
            safeRedirect(BASE_PATH . '/admin/locations');
        }

        $stmt = $pdo->prepare(
            "UPDATE class_locations 
             SET location_name = ?, address = ?, instructor = ?, training_days = ?, training_time = ?, contact = ?, map_url = ?, is_active = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([
            $name,
            $address,
            $instructor ?: null,
            $trainingDays,
            $trainingTime,
            $contact ?: null,
            $mapUrl ?: null,
            $isActive,
            $locationId,
        ]);

        writeAuditLog($pdo, 'LOCATION_UPDATED', 'ClassLocation', $locationId, [
            'name'      => $name,
            'is_active' => $isActive,
        ], $adminId);

        flashSet('success', 'Dojo location updated successfully.');
        safeRedirect(BASE_PATH . '/admin/locations');

    } elseif ($action === 'toggle_active' && $locationId && $locationId > 0) {
        $curStmt = $pdo->prepare("SELECT is_active, location_name FROM class_locations WHERE id = ?");
        $curStmt->execute([$locationId]);
        $row = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newActive = $row['is_active'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE class_locations SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newActive, $locationId]);

            writeAuditLog($pdo, 'LOCATION_STATUS_CHANGED', 'ClassLocation', $locationId, [
                'is_active' => $newActive,
            ], $adminId);

            flashSet('success', 'Dojo status set to ' . ($newActive ? 'Active' : 'Inactive') . '.');
        }
        safeRedirect(BASE_PATH . '/admin/locations');

    } elseif ($action === 'delete_location' && $locationId && $locationId > 0) {
        $curStmt = $pdo->prepare("SELECT location_name FROM class_locations WHERE id = ?");
        $curStmt->execute([$locationId]);
        $loc = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($loc) {
            $delStmt = $pdo->prepare("DELETE FROM class_locations WHERE id = ?");
            $delStmt->execute([$locationId]);

            writeAuditLog($pdo, 'LOCATION_DELETED', 'ClassLocation', $locationId, [
                'name' => $loc['location_name'],
            ], $adminId);

            flashSet('success', 'Dojo location deleted.');
        }
        safeRedirect(BASE_PATH . '/admin/locations');
    }
}

// ─── Filters & Fetching ───────────────────────────────────────────────────────

$filterStatus = trim($_GET['status'] ?? 'ALL');
if (!in_array($filterStatus, ['ALL', '1', '0'], true)) {
    $filterStatus = 'ALL';
}

$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT cl.*, 
               (SELECT COUNT(*) FROM student_profiles sp WHERE sp.preferred_location_id = cl.id AND sp.status = 'ACTIVE') as active_students_count 
        FROM class_locations cl 
        WHERE 1=1";
$params = [];

if ($filterStatus !== 'ALL') {
    $sql .= " AND cl.is_active = ?";
    $params[] = (int) $filterStatus;
}

if ($searchQuery !== '') {
    $sql .= " AND (cl.location_name LIKE ? OR cl.address LIKE ? OR cl.instructor LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY cl.location_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counters
$countTotal    = (int) $pdo->query("SELECT COUNT(*) FROM class_locations")->fetchColumn();
$countActive   = (int) $pdo->query("SELECT COUNT(*) FROM class_locations WHERE is_active = 1")->fetchColumn();
$countInactive = (int) $pdo->query("SELECT COUNT(*) FROM class_locations WHERE is_active = 0")->fetchColumn();

$pageTitle  = 'Dojo Locations Management';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3">
        <div>
            <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
                Dojo Locations Management
            </h1>
            <p class="text-secondary small mb-0">
                Configure training centers, branch dojos, schedules, and instructor assignments.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase"
                    data-bs-toggle="modal" data-bs-target="#createLocationModal" style="font-size: 0.8rem; letter-spacing: 0.04em;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Add New Dojo Branch
            </button>
        </div>
    </div>

    <!-- Stats Metric Cards -->
    <div class="row g-3">
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Branches</span>
                <span class="fs-4 fw-black text-dark mt-1"><?= $countTotal ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Active Dojos (Public)</span>
                <span class="fs-4 fw-black text-success mt-1"><?= $countActive ?></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Inactive / Setup</span>
                <span class="fs-4 fw-black text-warning mt-1"><?= $countInactive ?></span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <form method="GET" action="<?= BASE_PATH ?>/admin/locations" class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" class="form-control bg-light border-start-0"
                           placeholder="Search dojo branch name, address, or instructor..."
                           value="<?= escAttr($searchQuery) ?>">
                </div>
            </div>

            <div class="col-6 col-md-4">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">All Branch Statuses</option>
                    <option value="1" <?= $filterStatus === '1' ? 'selected' : '' ?>>Active (Visible)</option>
                    <option value="0" <?= $filterStatus === '0' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Filter</button>
                <?php if ($filterStatus !== 'ALL' || $searchQuery !== ''): ?>
                    <a href="<?= BASE_PATH ?>/admin/locations" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Locations List Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <?php if (empty($locations)): ?>
            <div class="p-5 text-center text-muted">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 opacity-50">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                <p class="mb-2 fw-bold text-dark">No Dojo Branches Found</p>
                <p class="small text-secondary mb-3">No locations match your filter or search criteria.</p>
                <button type="button" class="btn btn-sm btn-danger px-3" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                    Add First Dojo Branch
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-uppercase text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <tr>
                            <th class="ps-3">Branch Dojo</th>
                            <th>Address</th>
                            <th>Instructor</th>
                            <th>Schedule &amp; Timings</th>
                            <th>Enrolled</th>
                            <th>Status</th>
                            <th class="text-end pe-3" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                            <?php $isActive = (int) $loc['is_active'] === 1; ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark mb-0"><?= esc($loc['location_name']) ?></div>
                                    <?php if (!empty($loc['contact'])): ?>
                                        <div class="text-secondary small">
                                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                                <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                                            </svg>
                                            <?= esc($loc['contact']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-secondary small text-truncate" style="max-width: 240px;" title="<?= escAttr($loc['address']) ?>">
                                        <?= esc($loc['address']) ?>
                                    </div>
                                    <?php if (!empty($loc['map_url'])): ?>
                                        <a href="<?= escAttr($loc['map_url']) ?>" target="_blank" class="small text-danger text-decoration-none">
                                            Directions Map &rarr;
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= esc($loc['instructor'] ?: 'Chief Instructor') ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= esc($loc['training_days']) ?></div>
                                    <div class="text-secondary small"><?= esc($loc['training_time']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= (int) $loc['active_students_count'] ?> Active
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?>" style="font-size: 0.7rem;">
                                        <?= $isActive ? 'Active Dojo' : 'Inactive / Setup' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <form method="POST" action="<?= BASE_PATH ?>/admin/locations" class="m-0 d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="location_id" value="<?= (int) $loc['id'] ?>">
                                            <button type="submit" class="btn btn-outline-secondary" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                                                <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-outline-secondary" title="Edit Dojo"
                                                onclick="openEditModal(<?= escAttr(json_encode($loc)) ?>)">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Delete Dojo"
                                                onclick="confirmDelete(<?= (int)$loc['id'] ?>, <?= escJs($loc['location_name']) ?>)">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ─── Create Location Modal ────────────────────────────────────────────────── -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Add Dojo Branch Location</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/locations">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_location">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold small">Dojo / Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="location_name" class="form-control" placeholder="e.g. Navrangpura Main Dojo" required>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold small">Branch Instructor</label>
                            <input type="text" name="instructor" class="form-control" placeholder="e.g. Sensei Patel">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Full Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Full street address, landmark, city, pin code..." required></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Training Days <span class="text-danger">*</span></label>
                            <input type="text" name="training_days" class="form-control" placeholder="e.g. Mon, Wed, Fri" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Training Time / Batches <span class="text-danger">*</span></label>
                            <input type="text" name="training_time" class="form-control" placeholder="e.g. 06:00 PM – 07:30 PM (Evening Batch)" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Contact Phone</label>
                            <input type="text" name="contact" class="form-control" placeholder="e.g. +91 98765 43210">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Google Maps Directions URL</label>
                            <input type="url" name="map_url" class="form-control" placeholder="https://maps.google.com/?q=...">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="add_active" checked>
                                <label class="form-check-label fw-bold small" for="add_active">Activate branch immediately on public locations page</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Save Dojo Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Edit Location Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Edit Dojo Branch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/locations">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_location">
                <input type="hidden" name="location_id" id="edit_location_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold small">Dojo / Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="location_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold small">Branch Instructor</label>
                            <input type="text" name="instructor" id="edit_instructor" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Full Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="edit_address" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Training Days <span class="text-danger">*</span></label>
                            <input type="text" name="training_days" id="edit_days" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Training Time <span class="text-danger">*</span></label>
                            <input type="text" name="training_time" id="edit_time" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Contact Phone</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold small">Google Maps URL</label>
                            <input type="url" name="map_url" id="edit_map_url" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_active">
                                <label class="form-check-label fw-bold small" for="edit_active">Active dojo branch</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Delete Confirmation Modal ───────────────────────────────────────────── -->
<div class="modal fade" id="deleteLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold fs-6">Delete Dojo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/locations">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_location">
                <input type="hidden" name="location_id" id="delete_location_id">
                <div class="modal-body p-3 text-center">
                    <p class="mb-1 text-dark">Are you sure you want to permanently delete this dojo branch?</p>
                    <p class="fw-bold text-danger mb-0" id="delete_location_name"></p>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-bold">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(loc) {
    document.getElementById('edit_location_id').value = loc.id;
    document.getElementById('edit_name').value = loc.location_name || '';
    document.getElementById('edit_instructor').value = loc.instructor || '';
    document.getElementById('edit_address').value = loc.address || '';
    document.getElementById('edit_days').value = loc.training_days || '';
    document.getElementById('edit_time').value = loc.training_time || '';
    document.getElementById('edit_contact').value = loc.contact || '';
    document.getElementById('edit_map_url').value = loc.map_url || '';
    document.getElementById('edit_active').checked = loc.is_active == 1;

    const modal = new bootstrap.Modal(document.getElementById('editLocationModal'));
    modal.show();
}

function confirmDelete(id, name) {
    document.getElementById('delete_location_id').value = id;
    document.getElementById('delete_location_name').textContent = name || 'Dojo Branch';
    const modal = new bootstrap.Modal(document.getElementById('deleteLocationModal'));
    modal.show();
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
