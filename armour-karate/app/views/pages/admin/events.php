<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Events & Tournaments Management
 * app/views/pages/admin/events.php
 */

declare(strict_types=1);

requireAdmin();

$pdo     = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);

$allowedTypes = [
    'TOURNAMENT'       => 'Tournament / Championship',
    'BELT_EXAM'        => 'Belt Examination',
    'TRAINING_CAMP'    => 'Training Camp',
    'SEMINAR'          => 'Seminar / Masterclass',
    'DEMONSTRATION'    => 'Demonstration',
    'SPECIAL_PRACTICE' => 'Special Practice Session',
    'ACADEMY_FUNCTION' => 'Academy Function / Gathering',
];

$allowedStatuses = ['DRAFT', 'PUBLISHED', 'COMPLETED', 'CANCELLED'];

// ─── POST Action Dispatcher ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $action  = trim($_POST['action'] ?? '');
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    if ($action === 'create_event') {
        $name        = trim($_POST['event_name'] ?? '');
        $type        = trim($_POST['type'] ?? '');
        $date        = trim($_POST['event_date'] ?? '');
        $time        = trim($_POST['start_time'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $regDetails  = trim($_POST['registration_details'] ?? '');
        $status      = trim($_POST['status'] ?? 'DRAFT');

        if ($name === '' || !array_key_exists($type, $allowedTypes) || $date === '' || $time === '' || $venue === '') {
            flashSet('error', 'Please fill in all required fields (Name, Type, Date, Time, Venue).');
            safeRedirect(BASE_PATH . '/admin/events');
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'DRAFT';
        }

        $posterUrl = null;
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = validateImageUpload($_FILES['poster'], 3 * 1024 * 1024);
            if (!$uploadRes['ok']) {
                flashSet('error', 'Poster upload error: ' . $uploadRes['error']);
                safeRedirect(BASE_PATH . '/admin/events');
            }

            $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'event_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $destPath = UPLOADS_PATH . '/events/' . $filename;

            if (move_uploaded_file($_FILES['poster']['tmp_name'], $destPath)) {
                $posterUrl = uploadUrl('events/' . $filename);
            } else {
                flashSet('error', 'Failed to save uploaded poster file.');
                safeRedirect(BASE_PATH . '/admin/events');
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO events (event_name, type, event_date, start_time, venue, description, registration_details, status, poster_url, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$name, $type, $date, $time, $venue, $description ?: null, $regDetails ?: null, $status, $posterUrl]);
        $newId = (int) $pdo->lastInsertId();

        writeAuditLog($pdo, 'EVENT_CREATED', 'Event', $newId, [
            'name'   => $name,
            'type'   => $type,
            'date'   => $date,
            'status' => $status,
        ], $adminId);

        flashSet('success', 'Event "' . $name . '" created successfully.');
        safeRedirect(BASE_PATH . '/admin/events');

    } elseif ($action === 'update_event' && $eventId && $eventId > 0) {
        $name        = trim($_POST['event_name'] ?? '');
        $type        = trim($_POST['type'] ?? '');
        $date        = trim($_POST['event_date'] ?? '');
        $time        = trim($_POST['start_time'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $regDetails  = trim($_POST['registration_details'] ?? '');
        $status      = trim($_POST['status'] ?? 'DRAFT');

        if ($name === '' || !array_key_exists($type, $allowedTypes) || $date === '' || $time === '' || $venue === '') {
            flashSet('error', 'Please fill in all required fields.');
            safeRedirect(BASE_PATH . '/admin/events');
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'DRAFT';
        }

        // Fetch existing event
        $curStmt = $pdo->prepare("SELECT poster_url FROM events WHERE id = ?");
        $curStmt->execute([$eventId]);
        $current = $curStmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            flashSet('error', 'Event not found.');
            safeRedirect(BASE_PATH . '/admin/events');
        }

        $posterUrl = $current['poster_url'];
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = validateImageUpload($_FILES['poster'], 3 * 1024 * 1024);
            if (!$uploadRes['ok']) {
                flashSet('error', 'Poster upload error: ' . $uploadRes['error']);
                safeRedirect(BASE_PATH . '/admin/events');
            }

            $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'event_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $destPath = UPLOADS_PATH . '/events/' . $filename;

            if (move_uploaded_file($_FILES['poster']['tmp_name'], $destPath)) {
                // If old file exists in local uploads, remove it
                if (!empty($current['poster_url'])) {
                    $oldName = basename(parse_url($current['poster_url'], PHP_URL_PATH));
                    $oldFile = UPLOADS_PATH . '/events/' . $oldName;
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $posterUrl = uploadUrl('events/' . $filename);
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE events 
             SET event_name = ?, type = ?, event_date = ?, start_time = ?, venue = ?, description = ?, registration_details = ?, status = ?, poster_url = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$name, $type, $date, $time, $venue, $description ?: null, $regDetails ?: null, $status, $posterUrl, $eventId]);

        writeAuditLog($pdo, 'EVENT_UPDATED', 'Event', $eventId, [
            'name'   => $name,
            'type'   => $type,
            'date'   => $date,
            'status' => $status,
        ], $adminId);

        flashSet('success', 'Event updated successfully.');
        safeRedirect(BASE_PATH . '/admin/events');

    } elseif ($action === 'toggle_status' && $eventId && $eventId > 0) {
        $newStatus = trim($_POST['status'] ?? '');
        if (in_array($newStatus, $allowedStatuses, true)) {
            $stmt = $pdo->prepare("UPDATE events SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $eventId]);

            writeAuditLog($pdo, 'EVENT_STATUS_CHANGED', 'Event', $eventId, ['new_status' => $newStatus], $adminId);
            flashSet('success', "Event status changed to {$newStatus}.");
        }
        safeRedirect(BASE_PATH . '/admin/events');

    } elseif ($action === 'delete_event' && $eventId && $eventId > 0) {
        $curStmt = $pdo->prepare("SELECT event_name, poster_url FROM events WHERE id = ?");
        $curStmt->execute([$eventId]);
        $ev = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($ev) {
            $delStmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $delStmt->execute([$eventId]);

            if (!empty($ev['poster_url'])) {
                $oldName = basename(parse_url($ev['poster_url'], PHP_URL_PATH));
                $oldFile = UPLOADS_PATH . '/events/' . $oldName;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            writeAuditLog($pdo, 'EVENT_DELETED', 'Event', $eventId, ['name' => $ev['event_name']], $adminId);
            flashSet('success', 'Event deleted successfully.');
        }
        safeRedirect(BASE_PATH . '/admin/events');
    }
}

// ─── Filters & Fetching ───────────────────────────────────────────────────────

$filterStatus = strtoupper(trim($_GET['status'] ?? 'ALL'));
if (!in_array($filterStatus, array_merge(['ALL'], $allowedStatuses), true)) {
    $filterStatus = 'ALL';
}

$filterType = trim($_GET['type'] ?? 'ALL');
if (!in_array($filterType, array_merge(['ALL'], array_keys($allowedTypes)), true)) {
    $filterType = 'ALL';
}

$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

if ($filterStatus !== 'ALL') {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

if ($filterType !== 'ALL') {
    $sql .= " AND type = ?";
    $params[] = $filterType;
}

if ($searchQuery !== '') {
    $sql .= " AND (event_name LIKE ? OR venue LIKE ? OR description LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY event_date DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counters
$countTotal     = (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$countPublished = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'PUBLISHED'")->fetchColumn();
$countDraft     = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'DRAFT'")->fetchColumn();
$countUpcoming  = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'PUBLISHED' AND event_date >= CURDATE()")->fetchColumn();

$pageTitle  = 'Events & Tournaments Management';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3">
        <div>
            <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
                Events &amp; Tournaments Management
            </h1>
            <p class="text-secondary small mb-0">
                Publish state tournaments, belt exams, camps, seminars, and academy functions.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase"
                    data-bs-toggle="modal" data-bs-target="#createEventModal" style="font-size: 0.8rem; letter-spacing: 0.04em;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Create New Event
            </button>
        </div>
    </div>

    <!-- Quick Stats Metric Cards -->
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Events</span>
                <span class="fs-4 fw-black text-dark mt-1"><?= $countTotal ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Published</span>
                <span class="fs-4 fw-black text-success mt-1"><?= $countPublished ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Upcoming Live</span>
                <span class="fs-4 fw-black text-primary mt-1"><?= $countUpcoming ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Drafts</span>
                <span class="fs-4 fw-black text-warning mt-1"><?= $countDraft ?></span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <form method="GET" action="<?= BASE_PATH ?>/admin/events" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" class="form-control bg-light border-start-0"
                           placeholder="Search event name, venue, or description..."
                           value="<?= escAttr($searchQuery) ?>">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">All Event Types</option>
                    <?php foreach ($allowedTypes as $k => $label): ?>
                        <option value="<?= escAttr($k) ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">All Statuses</option>
                    <?php foreach ($allowedStatuses as $st): ?>
                        <option value="<?= escAttr($st) ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Filter</button>
                <?php if ($filterStatus !== 'ALL' || $filterType !== 'ALL' || $searchQuery !== ''): ?>
                    <a href="<?= BASE_PATH ?>/admin/events" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Events List Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <?php if (empty($events)): ?>
            <div class="p-5 text-center text-muted">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 opacity-50">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/>
                </svg>
                <p class="mb-2 fw-bold text-dark">No Events Found</p>
                <p class="small text-secondary mb-3">No event records match the selected filters or search criteria.</p>
                <button type="button" class="btn btn-sm btn-danger px-3" data-bs-toggle="modal" data-bs-target="#createEventModal">
                    Create First Event
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-uppercase text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <tr>
                            <th class="ps-3" style="width: 70px;">Poster</th>
                            <th>Event Details</th>
                            <th>Type</th>
                            <th>Date &amp; Time</th>
                            <th>Venue</th>
                            <th>Status</th>
                            <th class="text-end pe-3" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $ev): ?>
                            <?php
                            $statusBadge = match($ev['status']) {
                                'PUBLISHED' => 'bg-success',
                                'DRAFT'     => 'bg-warning text-dark',
                                'COMPLETED' => 'bg-secondary',
                                'CANCELLED' => 'bg-danger',
                                default     => 'bg-secondary'
                            };
                            $posterImg = !empty($ev['poster_url']) ? $ev['poster_url'] : '';
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <?php if ($posterImg): ?>
                                        <img src="<?= escAttr($posterImg) ?>" alt="Poster" class="rounded object-fit-cover shadow-sm" style="width: 52px; height: 52px;">
                                    <?php else: ?>
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" style="width: 52px; height: 52px;">
                                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                                <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= esc($ev['event_name']) ?></div>
                                    <?php if (!empty($ev['description'])): ?>
                                        <div class="text-secondary small text-truncate" style="max-width: 280px;">
                                            <?= esc($ev['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-medium" style="font-size: 0.72rem;">
                                        <?= esc($allowedTypes[$ev['type']] ?? $ev['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= formatDate($ev['event_date'], 'd M Y') ?></div>
                                    <div class="text-secondary small"><?= esc($ev['start_time']) ?></div>
                                </td>
                                <td>
                                    <div class="text-dark small" style="max-width: 180px;"><?= esc($ev['venue']) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadge ?> px-2 py-1" style="font-size: 0.72rem;">
                                        <?= esc($ev['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" title="Edit Event"
                                                onclick="openEditModal(<?= escAttr(json_encode($ev)) ?>)">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Delete Event"
                                                onclick="confirmDelete(<?= (int)$ev['id'] ?>, <?= escJs($ev['event_name']) ?>)">
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

<!-- ─── Create Event Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Create Academy Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/events" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_event">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold small">Event Name <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" class="form-control" placeholder="e.g. 14th All Gujarat Karate Championship" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Event Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <?php foreach ($allowedTypes as $k => $label): ?>
                                    <option value="<?= escAttr($k) ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Event Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Start Time <span class="text-danger">*</span></label>
                            <input type="text" name="start_time" class="form-control" placeholder="e.g. 08:00 AM onwards" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="DRAFT">DRAFT (Hidden)</option>
                                <option value="PUBLISHED" selected>PUBLISHED (Visible)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Venue Address <span class="text-danger">*</span></label>
                            <input type="text" name="venue" class="form-control" placeholder="e.g. Sardar Patel Indoor Stadium, Surat" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Description / Overview</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief outline of the championship, categories, or seminar agenda..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Registration &amp; Participation Details</label>
                            <textarea name="registration_details" class="form-control" rows="2" placeholder="Fee details, reporting time, kit requirements, contact numbers..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Poster Image (Optional - JPG, PNG, WebP up to 3MB)</label>
                            <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Edit Event Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Edit Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/events" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_event">
                <input type="hidden" name="event_id" id="edit_event_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold small">Event Name <span class="text-danger">*</span></label>
                            <input type="text" name="event_name" id="edit_event_name" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Event Type <span class="text-danger">*</span></label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <?php foreach ($allowedTypes as $k => $label): ?>
                                    <option value="<?= escAttr($k) ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Event Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="edit_event_date" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Start Time <span class="text-danger">*</span></label>
                            <input type="text" name="start_time" id="edit_start_time" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <?php foreach ($allowedStatuses as $st): ?>
                                    <option value="<?= escAttr($st) ?>"><?= esc($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Venue Address <span class="text-danger">*</span></label>
                            <input type="text" name="venue" id="edit_venue" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Description / Overview</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Registration &amp; Participation Details</label>
                            <textarea name="registration_details" id="edit_registration_details" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Replace Poster Image (Optional)</label>
                            <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div id="edit_poster_preview" class="mt-2 small text-muted"></div>
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
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold fs-6">Delete Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/events">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_event">
                <input type="hidden" name="event_id" id="delete_event_id">
                <div class="modal-body p-3 text-center">
                    <p class="mb-1 text-dark">Are you sure you want to permanently delete this event?</p>
                    <p class="fw-bold text-danger mb-0" id="delete_event_name"></p>
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
function openEditModal(eventData) {
    document.getElementById('edit_event_id').value = eventData.id;
    document.getElementById('edit_event_name').value = eventData.event_name;
    document.getElementById('edit_type').value = eventData.type;
    document.getElementById('edit_event_date').value = eventData.event_date;
    document.getElementById('edit_start_time').value = eventData.start_time;
    document.getElementById('edit_venue').value = eventData.venue;
    document.getElementById('edit_status').value = eventData.status;
    document.getElementById('edit_description').value = eventData.description || '';
    document.getElementById('edit_registration_details').value = eventData.registration_details || '';

    const preview = document.getElementById('edit_poster_preview');
    if (eventData.poster_url) {
        preview.innerHTML = `Current poster: <a href="${eventData.poster_url}" target="_blank" class="fw-bold">View Image</a>`;
    } else {
        preview.innerHTML = 'No poster currently attached.';
    }

    const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
    modal.show();
}

function confirmDelete(id, name) {
    document.getElementById('delete_event_id').value = id;
    document.getElementById('delete_event_name').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('deleteEventModal'));
    modal.show();
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
