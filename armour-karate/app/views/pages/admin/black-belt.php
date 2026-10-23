<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Black Belt Hall of Fame Management
 * app/views/pages/admin/black-belt.php
 */

declare(strict_types=1);

requireAdmin();

$pdo     = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);

// ─── POST Action Dispatcher ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $action    = trim($_POST['action'] ?? '');
    $profileId = filter_input(INPUT_POST, 'profile_id', FILTER_VALIDATE_INT);

    if ($action === 'create_profile') {
        $name           = trim($_POST['name'] ?? '');
        $rank           = trim($_POST['black_belt_rank'] ?? '');
        $yearAchieved   = filter_input(INPUT_POST, 'year_achieved', FILTER_VALIDATE_INT);
        $achievements   = trim($_POST['achievements'] ?? '');
        $tournaments    = trim($_POST['tournament_achievements'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $displayOrder   = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT) ?: 0;
        $isVisible      = isset($_POST['is_visible']) && $_POST['is_visible'] === '1' ? 1 : 0;
        $studentProfId  = filter_input(INPUT_POST, 'student_profile_id', FILTER_VALIDATE_INT) ?: null;

        if ($name === '' || $rank === '' || !$yearAchieved || $yearAchieved < 1950 || $yearAchieved > 2100) {
            flashSet('error', 'Please provide a valid Name, Black Belt Rank, and Achieved Year (e.g. 2023).');
            safeRedirect(BASE_PATH . '/admin/black-belt');
        }

        $photoUrl = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = validateImageUpload($_FILES['photo'], 3 * 1024 * 1024);
            if (!$uploadRes['ok']) {
                flashSet('error', 'Photo upload error: ' . $uploadRes['error']);
                safeRedirect(BASE_PATH . '/admin/black-belt');
            }

            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'bb_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $destPath = UPLOADS_PATH . '/black-belts/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                $photoUrl = uploadUrl('black-belts/' . $filename);
            } else {
                flashSet('error', 'Failed to save uploaded photo file.');
                safeRedirect(BASE_PATH . '/admin/black-belt');
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO black_belt_profiles 
             (student_profile_id, name, photo, black_belt_rank, year_achieved, achievements, tournament_achievements, description, display_order, is_visible, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $studentProfId,
            $name,
            $photoUrl,
            $rank,
            $yearAchieved,
            $achievements ?: null,
            $tournaments ?: null,
            $description ?: null,
            $displayOrder,
            $isVisible,
        ]);
        $newId = (int) $pdo->lastInsertId();

        writeAuditLog($pdo, 'BLACK_BELT_CREATED', 'BlackBeltProfile', $newId, [
            'name' => $name,
            'rank' => $rank,
            'year' => $yearAchieved,
        ], $adminId);

        flashSet('success', 'Black Belt profile for ' . $name . ' added successfully.');
        safeRedirect(BASE_PATH . '/admin/black-belt');

    } elseif ($action === 'update_profile' && $profileId && $profileId > 0) {
        $name           = trim($_POST['name'] ?? '');
        $rank           = trim($_POST['black_belt_rank'] ?? '');
        $yearAchieved   = filter_input(INPUT_POST, 'year_achieved', FILTER_VALIDATE_INT);
        $achievements   = trim($_POST['achievements'] ?? '');
        $tournaments    = trim($_POST['tournament_achievements'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $displayOrder   = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT) ?: 0;
        $isVisible      = isset($_POST['is_visible']) && $_POST['is_visible'] === '1' ? 1 : 0;
        $studentProfId  = filter_input(INPUT_POST, 'student_profile_id', FILTER_VALIDATE_INT) ?: null;

        if ($name === '' || $rank === '' || !$yearAchieved) {
            flashSet('error', 'Please provide a valid Name, Black Belt Rank, and Year.');
            safeRedirect(BASE_PATH . '/admin/black-belt');
        }

        $curStmt = $pdo->prepare("SELECT photo FROM black_belt_profiles WHERE id = ?");
        $curStmt->execute([$profileId]);
        $current = $curStmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            flashSet('error', 'Profile not found.');
            safeRedirect(BASE_PATH . '/admin/black-belt');
        }

        $photoUrl = $current['photo'];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = validateImageUpload($_FILES['photo'], 3 * 1024 * 1024);
            if (!$uploadRes['ok']) {
                flashSet('error', 'Photo upload error: ' . $uploadRes['error']);
                safeRedirect(BASE_PATH . '/admin/black-belt');
            }

            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'bb_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $destPath = UPLOADS_PATH . '/black-belts/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                if (!empty($current['photo'])) {
                    $oldName = basename(parse_url($current['photo'], PHP_URL_PATH));
                    $oldFile = UPLOADS_PATH . '/black-belts/' . $oldName;
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $photoUrl = uploadUrl('black-belts/' . $filename);
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE black_belt_profiles 
             SET student_profile_id = ?, name = ?, photo = ?, black_belt_rank = ?, year_achieved = ?, achievements = ?, tournament_achievements = ?, description = ?, display_order = ?, is_visible = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([
            $studentProfId,
            $name,
            $photoUrl,
            $rank,
            $yearAchieved,
            $achievements ?: null,
            $tournaments ?: null,
            $description ?: null,
            $displayOrder,
            $isVisible,
            $profileId,
        ]);

        writeAuditLog($pdo, 'BLACK_BELT_UPDATED', 'BlackBeltProfile', $profileId, [
            'name' => $name,
            'rank' => $rank,
        ], $adminId);

        flashSet('success', 'Profile updated successfully.');
        safeRedirect(BASE_PATH . '/admin/black-belt');

    } elseif ($action === 'toggle_visibility' && $profileId && $profileId > 0) {
        $curStmt = $pdo->prepare("SELECT is_visible, name FROM black_belt_profiles WHERE id = ?");
        $curStmt->execute([$profileId]);
        $row = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newVis = $row['is_visible'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE black_belt_profiles SET is_visible = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newVis, $profileId]);

            writeAuditLog($pdo, 'BLACK_BELT_VISIBILITY_CHANGED', 'BlackBeltProfile', $profileId, ['is_visible' => $newVis], $adminId);
            flashSet('success', 'Profile visibility updated.');
        }
        safeRedirect(BASE_PATH . '/admin/black-belt');

    } elseif ($action === 'delete_profile' && $profileId && $profileId > 0) {
        $curStmt = $pdo->prepare("SELECT name, photo FROM black_belt_profiles WHERE id = ?");
        $curStmt->execute([$profileId]);
        $prof = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($prof) {
            $delStmt = $pdo->prepare("DELETE FROM black_belt_profiles WHERE id = ?");
            $delStmt->execute([$profileId]);

            if (!empty($prof['photo'])) {
                $oldName = basename(parse_url($prof['photo'], PHP_URL_PATH));
                $oldFile = UPLOADS_PATH . '/black-belts/' . $oldName;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            writeAuditLog($pdo, 'BLACK_BELT_DELETED', 'BlackBeltProfile', $profileId, ['name' => $prof['name']], $adminId);
            flashSet('success', 'Black belt profile deleted.');
        }
        safeRedirect(BASE_PATH . '/admin/black-belt');
    }
}

// ─── Filters & Fetching ───────────────────────────────────────────────────────

$filterVisibility = trim($_GET['visibility'] ?? 'ALL');
if (!in_array($filterVisibility, ['ALL', '1', '0'], true)) {
    $filterVisibility = 'ALL';
}

$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT b.*, sp.student_id, sp.full_name as registered_student_name 
        FROM black_belt_profiles b
        LEFT JOIN student_profiles sp ON sp.id = b.student_profile_id
        WHERE 1=1";
$params = [];

if ($filterVisibility !== 'ALL') {
    $sql .= " AND b.is_visible = ?";
    $params[] = (int) $filterVisibility;
}

if ($searchQuery !== '') {
    $sql .= " AND (b.name LIKE ? OR b.black_belt_rank LIKE ? OR b.achievements LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY b.display_order ASC, b.year_achieved DESC, b.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$countTotal   = (int) $pdo->query("SELECT COUNT(*) FROM black_belt_profiles")->fetchColumn();
$countVisible = (int) $pdo->query("SELECT COUNT(*) FROM black_belt_profiles WHERE is_visible = 1")->fetchColumn();
$countHidden  = (int) $pdo->query("SELECT COUNT(*) FROM black_belt_profiles WHERE is_visible = 0")->fetchColumn();

// Fetch eligible black belt students for optional link
$bbStudents = $pdo->query(
    "SELECT sp.id, sp.student_id, sp.full_name, b.name as belt_name 
     FROM student_profiles sp 
     JOIN belts b ON b.id = sp.current_belt_id 
     WHERE b.rank = 8 AND sp.status = 'ACTIVE' 
     ORDER BY sp.full_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle  = 'Black Belt Hall of Fame';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3">
        <div>
            <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
                Black Belt Hall of Fame
            </h1>
            <p class="text-secondary small mb-0">
                Honor master practitioners, Dan-ranked black belts, and tournament champions on the public portal.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase"
                    data-bs-toggle="modal" data-bs-target="#createProfileModal" style="font-size: 0.8rem; letter-spacing: 0.04em;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Add Black Belt Profile
            </button>
        </div>
    </div>

    <!-- Stats Metric Cards -->
    <div class="row g-3">
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Black Belts</span>
                <span class="fs-4 fw-black text-dark mt-1"><?= $countTotal ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Public Hall of Fame</span>
                <span class="fs-4 fw-black text-success mt-1"><?= $countVisible ?></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Hidden Profiles</span>
                <span class="fs-4 fw-black text-warning mt-1"><?= $countHidden ?></span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <form method="GET" action="<?= BASE_PATH ?>/admin/black-belt" class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" class="form-control bg-light border-start-0"
                           placeholder="Search name, rank, or achievements..."
                           value="<?= escAttr($searchQuery) ?>">
                </div>
            </div>

            <div class="col-6 col-md-4">
                <select name="visibility" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">All Visibility Statuses</option>
                    <option value="1" <?= $filterVisibility === '1' ? 'selected' : '' ?>>Visible (Public)</option>
                    <option value="0" <?= $filterVisibility === '0' ? 'selected' : '' ?>>Hidden (Archived)</option>
                </select>
            </div>

            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Filter</button>
                <?php if ($filterVisibility !== 'ALL' || $searchQuery !== ''): ?>
                    <a href="<?= BASE_PATH ?>/admin/black-belt" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Profiles List -->
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <?php if (empty($profiles)): ?>
            <div class="p-5 text-center text-muted">
                <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 opacity-50">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.004 0A2.25 2.25 0 0015 12V6.75a2.25 2.25 0 00-2.25-2.25h-1.5a2.25 2.25 0 00-2.25 2.25V12a2.25 2.25 0 001.5 2.25z"/>
                </svg>
                <p class="mb-2 fw-bold text-dark">No Black Belt Profiles Found</p>
                <p class="small text-secondary mb-3">No profiles matched your filter criteria.</p>
                <button type="button" class="btn btn-sm btn-danger px-3" data-bs-toggle="modal" data-bs-target="#createProfileModal">
                    Add Black Belt Profile
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light text-uppercase text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <tr>
                            <th class="ps-3" style="width: 70px;">Photo</th>
                            <th>Master / Black Belt</th>
                            <th>Dan Rank</th>
                            <th>Year</th>
                            <th>Key Achievements</th>
                            <th>Display Order</th>
                            <th>Status</th>
                            <th class="text-end pe-3" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles as $prof): ?>
                            <?php
                            $photoSrc = !empty($prof['photo']) ? $prof['photo'] : '';
                            $isVisible = (int) $prof['is_visible'] === 1;
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <?php if ($photoSrc): ?>
                                        <img src="<?= escAttr($photoSrc) ?>" alt="Photo" class="rounded-circle object-fit-cover shadow-sm border" style="width: 48px; height: 48px;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px; font-size: 0.9rem;">
                                            <?= esc(strtoupper(substr($prof['name'], 0, 1))) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= esc($prof['name']) ?></div>
                                    <?php if (!empty($prof['student_id'])): ?>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.68rem;">ID: <?= esc($prof['student_id']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark text-white px-2 py-1" style="font-size: 0.72rem;">
                                        <?= esc($prof['black_belt_rank']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary"><?= esc((string) $prof['year_achieved']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($prof['achievements'])): ?>
                                        <div class="text-secondary small text-truncate" style="max-width: 250px;">
                                            <?= esc($prof['achievements']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= (int) $prof['display_order'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $isVisible ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size: 0.7rem;">
                                        <?= $isVisible ? 'Public' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" title="Edit Profile"
                                                onclick="openEditModal(<?= escAttr(json_encode($prof)) ?>)">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" title="Delete Profile"
                                                onclick="confirmDelete(<?= (int)$prof['id'] ?>, <?= escJs($prof['name']) ?>)">
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

<!-- ─── Create Profile Modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="createProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Add Black Belt Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/black-belt" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_profile">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Sensei Rahul Sharma" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Dan Rank <span class="text-danger">*</span></label>
                            <input type="text" name="black_belt_rank" class="form-control" placeholder="e.g. 1st Dan Black Belt" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-bold small">Year Achieved <span class="text-danger">*</span></label>
                            <input type="number" name="year_achieved" class="form-control" value="<?= date('Y') ?>" min="1950" max="2100" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-bold small">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Link Student (Optional)</label>
                            <select name="student_profile_id" class="form-select">
                                <option value="">None / External Master</option>
                                <?php foreach ($bbStudents as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['student_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Key Achievements &amp; Qualifications</label>
                            <textarea name="achievements" class="form-control" rows="2" placeholder="e.g. Certified State Referee, National Kata Gold Medalist..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Tournament Honors</label>
                            <textarea name="tournament_achievements" class="form-control" rows="2" placeholder="e.g. All India Karate Championship 2022 - Gold (Kumite)"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Martial Motto / Quote</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="e.g. Karate begins and ends with respect."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Profile Photo (JPG, PNG, WebP up to 3MB)</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="bb_visible" checked>
                                <label class="form-check-label fw-bold small" for="bb_visible">Display publicly in Hall of Fame</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Edit Profile Modal ──────────────────────────────────────────────────── -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Edit Black Belt Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/black-belt" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="profile_id" id="edit_profile_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Dan Rank <span class="text-danger">*</span></label>
                            <input type="text" name="black_belt_rank" id="edit_rank" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-bold small">Year Achieved <span class="text-danger">*</span></label>
                            <input type="number" name="year_achieved" id="edit_year" class="form-control" min="1950" max="2100" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label fw-bold small">Display Order</label>
                            <input type="number" name="display_order" id="edit_order" class="form-control">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold small">Link Student</label>
                            <select name="student_profile_id" id="edit_student_id" class="form-select">
                                <option value="">None / External Master</option>
                                <?php foreach ($bbStudents as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= esc($s['full_name']) ?> (<?= esc($s['student_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Key Achievements</label>
                            <textarea name="achievements" id="edit_achievements" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Tournament Honors</label>
                            <textarea name="tournament_achievements" id="edit_tournaments" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Martial Motto / Quote</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Replace Photo (Optional)</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div id="edit_photo_preview" class="mt-2 small text-muted"></div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="edit_visible">
                                <label class="form-check-label fw-bold small" for="edit_visible">Display publicly in Hall of Fame</label>
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
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold fs-6">Delete Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/black-belt">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_profile">
                <input type="hidden" name="profile_id" id="delete_profile_id">
                <div class="modal-body p-3 text-center">
                    <p class="mb-1 text-dark">Are you sure you want to permanently delete this Black Belt profile?</p>
                    <p class="fw-bold text-danger mb-0" id="delete_profile_name"></p>
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
function openEditModal(prof) {
    document.getElementById('edit_profile_id').value = prof.id;
    document.getElementById('edit_name').value = prof.name || '';
    document.getElementById('edit_rank').value = prof.black_belt_rank || '';
    document.getElementById('edit_year').value = prof.year_achieved || '';
    document.getElementById('edit_order').value = prof.display_order || 0;
    document.getElementById('edit_student_id').value = prof.student_profile_id || '';
    document.getElementById('edit_achievements').value = prof.achievements || '';
    document.getElementById('edit_tournaments').value = prof.tournament_achievements || '';
    document.getElementById('edit_description').value = prof.description || '';
    document.getElementById('edit_visible').checked = prof.is_visible == 1;

    const preview = document.getElementById('edit_photo_preview');
    if (prof.photo) {
        preview.innerHTML = `Current photo: <a href="${prof.photo}" target="_blank" class="fw-bold">View Photo</a>`;
    } else {
        preview.innerHTML = 'No photo currently attached.';
    }

    const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
    modal.show();
}

function confirmDelete(id, name) {
    document.getElementById('delete_profile_id').value = id;
    document.getElementById('delete_profile_name').textContent = name || 'Profile';
    const modal = new bootstrap.Modal(document.getElementById('deleteProfileModal'));
    modal.show();
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
