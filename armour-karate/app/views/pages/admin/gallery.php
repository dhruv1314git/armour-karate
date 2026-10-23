<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Photo Gallery Management
 * app/views/pages/admin/gallery.php
 */

declare(strict_types=1);

requireAdmin();

$pdo     = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);

$allowedCategories = [
    'TRAINING'       => 'Dojo Training',
    'TOURNAMENTS'    => 'Tournaments & Championships',
    'BELT_EXAMS'     => 'Belt Grading Exams',
    'AWARDS'         => 'Awards & Honors',
    'CAMPS'          => 'Summer & Winter Camps',
    'BLACK_BELT'     => 'Black Belts & Masters',
    'ACADEMY_EVENTS' => 'Academy Events',
];

// ─── POST Action Dispatcher ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();

    $action  = trim($_POST['action'] ?? '');
    $imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);

    if ($action === 'upload_image') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $isVisible   = isset($_POST['is_visible']) && $_POST['is_visible'] === '1' ? 1 : 0;

        if (!array_key_exists($category, $allowedCategories)) {
            flashSet('error', 'Please select a valid gallery category.');
            safeRedirect(BASE_PATH . '/admin/gallery');
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            flashSet('error', 'Please select an image file to upload.');
            safeRedirect(BASE_PATH . '/admin/gallery');
        }

        $uploadRes = validateImageUpload($_FILES['photo'], 5 * 1024 * 1024);
        if (!$uploadRes['ok']) {
            flashSet('error', 'Image validation error: ' . $uploadRes['error']);
            safeRedirect(BASE_PATH . '/admin/gallery');
        }

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }
        $filename = 'gallery_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $destPath = UPLOADS_PATH . '/gallery/' . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
            $imageUrl = uploadUrl('gallery/' . $filename);

            $stmt = $pdo->prepare(
                "INSERT INTO gallery_images (image_url, title, description, category, is_visible, created_by_admin_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $imageUrl,
                $title ?: 'Dojo Photograph',
                $description ?: null,
                $category,
                $isVisible,
                $adminId,
            ]);
            $newId = (int) $pdo->lastInsertId();

            writeAuditLog($pdo, 'GALLERY_IMAGE_ADDED', 'GalleryImage', $newId, [
                'title'      => $title,
                'category'   => $category,
                'is_visible' => $isVisible,
            ], $adminId);

            flashSet('success', 'Photograph uploaded to gallery successfully.');
        } else {
            flashSet('error', 'Failed to store uploaded image on server.');
        }

        safeRedirect(BASE_PATH . '/admin/gallery');

    } elseif ($action === 'update_image' && $imageId && $imageId > 0) {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $isVisible   = isset($_POST['is_visible']) && $_POST['is_visible'] === '1' ? 1 : 0;

        if (!array_key_exists($category, $allowedCategories)) {
            flashSet('error', 'Please select a valid gallery category.');
            safeRedirect(BASE_PATH . '/admin/gallery');
        }

        $curStmt = $pdo->prepare("SELECT image_url FROM gallery_images WHERE id = ?");
        $curStmt->execute([$imageId]);
        $current = $curStmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            flashSet('error', 'Gallery photograph not found.');
            safeRedirect(BASE_PATH . '/admin/gallery');
        }

        $imageUrl = $current['image_url'];

        // Check if replacing image
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = validateImageUpload($_FILES['photo'], 5 * 1024 * 1024);
            if (!$uploadRes['ok']) {
                flashSet('error', 'Image validation error: ' . $uploadRes['error']);
                safeRedirect(BASE_PATH . '/admin/gallery');
            }

            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = 'gallery_' . bin2hex(random_bytes(10)) . '.' . $ext;
            $destPath = UPLOADS_PATH . '/gallery/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                if (!empty($current['image_url'])) {
                    $oldName = basename(parse_url($current['image_url'], PHP_URL_PATH));
                    $oldFile = UPLOADS_PATH . '/gallery/' . $oldName;
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $imageUrl = uploadUrl('gallery/' . $filename);
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE gallery_images 
             SET image_url = ?, title = ?, description = ?, category = ?, is_visible = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$imageUrl, $title ?: 'Dojo Photograph', $description ?: null, $category, $isVisible, $imageId]);

        writeAuditLog($pdo, 'GALLERY_IMAGE_UPDATED', 'GalleryImage', $imageId, [
            'title'      => $title,
            'category'   => $category,
            'is_visible' => $isVisible,
        ], $adminId);

        flashSet('success', 'Photograph details updated successfully.');
        safeRedirect(BASE_PATH . '/admin/gallery');

    } elseif ($action === 'toggle_visibility' && $imageId && $imageId > 0) {
        $curStmt = $pdo->prepare("SELECT is_visible FROM gallery_images WHERE id = ?");
        $curStmt->execute([$imageId]);
        $row = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $newVis = $row['is_visible'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE gallery_images SET is_visible = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newVis, $imageId]);

            writeAuditLog($pdo, 'GALLERY_VISIBILITY_CHANGED', 'GalleryImage', $imageId, ['is_visible' => $newVis], $adminId);
            flashSet('success', 'Visibility updated.');
        }
        safeRedirect(BASE_PATH . '/admin/gallery');

    } elseif ($action === 'delete_image' && $imageId && $imageId > 0) {
        $curStmt = $pdo->prepare("SELECT title, image_url FROM gallery_images WHERE id = ?");
        $curStmt->execute([$imageId]);
        $img = $curStmt->fetch(PDO::FETCH_ASSOC);

        if ($img) {
            $delStmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = ?");
            $delStmt->execute([$imageId]);

            if (!empty($img['image_url'])) {
                $oldName = basename(parse_url($img['image_url'], PHP_URL_PATH));
                $oldFile = UPLOADS_PATH . '/gallery/' . $oldName;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }

            writeAuditLog($pdo, 'GALLERY_IMAGE_DELETED', 'GalleryImage', $imageId, ['title' => $img['title']], $adminId);
            flashSet('success', 'Photograph removed from gallery.');
        }
        safeRedirect(BASE_PATH . '/admin/gallery');
    }
}

// ─── Filters & Fetching ───────────────────────────────────────────────────────

$filterCategory = trim($_GET['category'] ?? 'ALL');
if (!in_array($filterCategory, array_merge(['ALL'], array_keys($allowedCategories)), true)) {
    $filterCategory = 'ALL';
}

$filterVisibility = trim($_GET['visibility'] ?? 'ALL');
if (!in_array($filterVisibility, ['ALL', '1', '0'], true)) {
    $filterVisibility = 'ALL';
}

$searchQuery = trim($_GET['q'] ?? '');

$sql = "SELECT g.*, u.name as admin_name 
        FROM gallery_images g 
        LEFT JOIN users u ON u.id = g.created_by_admin_id 
        WHERE 1=1";
$params = [];

if ($filterCategory !== 'ALL') {
    $sql .= " AND g.category = ?";
    $params[] = $filterCategory;
}

if ($filterVisibility !== 'ALL') {
    $sql .= " AND g.is_visible = ?";
    $params[] = (int) $filterVisibility;
}

if ($searchQuery !== '') {
    $sql .= " AND (g.title LIKE ? OR g.description LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY g.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$countTotal   = (int) $pdo->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
$countVisible = (int) $pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_visible = 1")->fetchColumn();
$countHidden  = (int) $pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_visible = 0")->fetchColumn();

$pageTitle  = 'Photo Gallery Management';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3">
        <div>
            <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
                Dojo Photo Gallery Management
            </h1>
            <p class="text-secondary small mb-0">
                Upload and organize authentic training, tournament, belt exam, and camp photography for the public website.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-2 px-3 py-2 fw-bold text-uppercase"
                    data-bs-toggle="modal" data-bs-target="#uploadPhotoModal" style="font-size: 0.8rem; letter-spacing: 0.04em;">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                </svg>
                Upload Photo
            </button>
        </div>
    </div>

    <!-- Stats Metric Cards -->
    <div class="row g-3">
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Total Photographs</span>
                <span class="fs-4 fw-black text-dark mt-1"><?= $countTotal ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Published (Public)</span>
                <span class="fs-4 fw-black text-success mt-1"><?= $countVisible ?></span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Hidden / Archived</span>
                <span class="fs-4 fw-black text-warning mt-1"><?= $countHidden ?></span>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white p-3">
        <form method="GET" action="<?= BASE_PATH ?>/admin/gallery" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" class="form-control bg-light border-start-0"
                           placeholder="Search photo caption or description..."
                           value="<?= escAttr($searchQuery) ?>">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">All Categories</option>
                    <?php foreach ($allowedCategories as $catKey => $catLabel): ?>
                        <option value="<?= escAttr($catKey) ?>" <?= $filterCategory === $catKey ? 'selected' : '' ?>><?= esc($catLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="visibility" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL">All Visibility</option>
                    <option value="1" <?= $filterVisibility === '1' ? 'selected' : '' ?>>Visible</option>
                    <option value="0" <?= $filterVisibility === '0' ? 'selected' : '' ?>>Hidden</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">Filter</button>
                <?php if ($filterCategory !== 'ALL' || $filterVisibility !== 'ALL' || $searchQuery !== ''): ?>
                    <a href="<?= BASE_PATH ?>/admin/gallery" class="btn btn-sm btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Gallery Grid Cards -->
    <?php if (empty($photos)): ?>
        <div class="card border-0 shadow-sm rounded-3 bg-white p-5 text-center text-muted">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 opacity-50">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <p class="mb-2 fw-bold text-dark">No Photographs Found</p>
            <p class="small text-secondary mb-3">No images match your search or filters. Click below to add the first dojo photo.</p>
            <div>
                <button type="button" class="btn btn-sm btn-danger px-3" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
                    Upload Photograph
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($photos as $ph): ?>
                <?php
                $catLabel = $allowedCategories[$ph['category']] ?? $ph['category'];
                $isVisible = (int) $ph['is_visible'] === 1;
                ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 h-100 overflow-hidden bg-white">
                        <div class="position-relative" style="height: 190px; background: #111;">
                            <img src="<?= escAttr($ph['image_url']) ?>" alt="<?= escAttr($ph['title']) ?>"
                                 class="w-100 h-100 object-fit-cover" loading="lazy">
                            <span class="position-absolute top-0 start-0 m-2 badge <?= $isVisible ? 'bg-success' : 'bg-warning text-dark' ?>" style="font-size: 0.68rem;">
                                <?= $isVisible ? 'Visible' : 'Hidden' ?>
                            </span>
                            <span class="position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white" style="font-size: 0.68rem;">
                                <?= esc($catLabel) ?>
                            </span>
                        </div>
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-1 text-truncate" title="<?= escAttr($ph['title']) ?>">
                                    <?= esc($ph['title']) ?>
                                </h6>
                                <?php if (!empty($ph['description'])): ?>
                                    <p class="text-secondary small mb-2 text-truncate" title="<?= escAttr($ph['description']) ?>">
                                        <?= esc($ph['description']) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="text-muted small" style="font-size: 0.72rem;">
                                    Uploaded: <?= formatDate($ph['created_at'], 'd M Y') ?>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between border-top pt-2 mt-3">
                                <form method="POST" action="<?= BASE_PATH ?>/admin/gallery" class="m-0">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_visibility">
                                    <input type="hidden" name="image_id" value="<?= (int) $ph['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none <?= $isVisible ? 'text-secondary' : 'text-success fw-bold' ?>" style="font-size: 0.75rem;">
                                        <?= $isVisible ? 'Hide Photo' : 'Make Visible' ?>
                                    </button>
                                </form>

                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" title="Edit"
                                            onclick="openEditModal(<?= escAttr(json_encode($ph)) ?>)">
                                        <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" title="Delete"
                                            onclick="confirmDelete(<?= (int)$ph['id'] ?>, <?= escJs($ph['title']) ?>)">
                                        <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ─── Upload Photo Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Upload Dojo Photograph</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/gallery" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="upload_image">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small">Image File <span class="text-danger">*</span></label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                            <span class="form-text small text-muted">JPEG, PNG or WebP up to 5MB.</span>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <?php foreach ($allowedCategories as $catKey => $catLabel): ?>
                                    <option value="<?= escAttr($catKey) ?>"><?= esc($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Title / Caption</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Kata Demonstration at State Games">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief note about the tournament, venue, or performers..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="add_visible" checked>
                                <label class="form-check-label fw-bold small" for="add_visible">Publish immediately to public gallery</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Upload Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Edit Photo Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="editPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold fs-6 text-uppercase" style="letter-spacing: 0.05em;">Edit Photograph Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/gallery" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_image">
                <input type="hidden" name="image_id" id="edit_image_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold small">Title / Caption</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Category <span class="text-danger">*</span></label>
                            <select name="category" id="edit_category" class="form-select" required>
                                <?php foreach ($allowedCategories as $catKey => $catLabel): ?>
                                    <option value="<?= escAttr($catKey) ?>"><?= esc($catLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Replace Image (Optional)</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="edit_visible">
                                <label class="form-check-label fw-bold small" for="edit_visible">Visible in public gallery</label>
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
<div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold fs-6">Delete Photo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/gallery">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_image">
                <input type="hidden" name="image_id" id="delete_image_id">
                <div class="modal-body p-3 text-center">
                    <p class="mb-1 text-dark">Are you sure you want to permanently delete this photo from the gallery?</p>
                    <p class="fw-bold text-danger mb-0" id="delete_image_title"></p>
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
function openEditModal(photo) {
    document.getElementById('edit_image_id').value = photo.id;
    document.getElementById('edit_title').value = photo.title || '';
    document.getElementById('edit_description').value = photo.description || '';
    document.getElementById('edit_category').value = photo.category;
    document.getElementById('edit_visible').checked = photo.is_visible == 1;

    const modal = new bootstrap.Modal(document.getElementById('editPhotoModal'));
    modal.show();
}

function confirmDelete(id, title) {
    document.getElementById('delete_image_id').value = id;
    document.getElementById('delete_image_title').textContent = title || 'Photograph';
    const modal = new bootstrap.Modal(document.getElementById('deletePhotoModal'));
    modal.show();
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
