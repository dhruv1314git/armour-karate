<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Profile & Contact Settings
 * app/views/pages/student/profile.php
 *
 * Faithfully recreates the original Next.js student profile page:
 *  - Left column: Locked official academy credentials (ID, Belt, Email, DOB, Gender, Status)
 *  - Right column: Permitted contact editing (Phone, Address, Parent/Guardian, Preferred Dojo)
 *  - Secure profile photo upload (JPEG, PNG, WebP with finfo & getimagesize validation)
 *  - Safe removal of previous photo with path-traversal protection
 *  - Strict IDOR isolation: Only authenticated student's profile is updated
 *  - CSRF validation, prepared statements, and XSS output escaping
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'Student Profile';
$portalType = 'student';

$errorMessage   = null;
$successMessage = null;

// Fetch current student profile & belt info (IDOR-scoped)
$stmtStudent = $pdo->prepare(
    "SELECT sp.*, u.email, u.image AS user_image,
            b.name AS belt_name, b.color AS belt_color, b.rank AS belt_rank
     FROM student_profiles sp
     JOIN users u ON u.id = sp.user_id
     LEFT JOIN belts b ON b.id = sp.current_belt_id
     WHERE sp.id = ? AND sp.user_id = ?
     LIMIT 1"
);
$stmtStudent->execute([$sessionStudent['profile_id'], $sessionStudent['user_id']]);
$student = $stmtStudent->fetch();

if (!$student) {
    destroySession();
    safeRedirect(BASE_PATH . '/login');
}

// Fetch active class locations for dropdown
$stmtLocations = $pdo->query("SELECT id, location_name FROM class_locations WHERE is_active = 1 ORDER BY location_name ASC");
$locations = $stmtLocations->fetchAll();

// Handle Form Submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? '';
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on student profile update attempt. IP: ' . getClientIp());
        $errorMessage = 'Invalid or expired session security token. Please refresh and try again.';
        goto renderView;
    }

    $action = $_POST['action'] ?? '';

    // ── Action A: Profile Photo Upload ───────────────────────────────────────
    if ($action === 'upload_photo') {
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            $errorMessage = 'Please select an image file to upload.';
            goto renderView;
        }

        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = 'Failed to upload image. Please try again.';
            goto renderView;
        }

        // Max file size: 2MB
        $maxBytes = 2 * 1024 * 1024;
        if ($_FILES['photo']['size'] > $maxBytes) {
            $errorMessage = 'Image size must not exceed 2MB.';
            goto renderView;
        }

        $tmpPath = $_FILES['photo']['tmp_name'];
        if (!is_uploaded_file($tmpPath)) {
            $errorMessage = 'Security error: Invalid upload source.';
            goto renderView;
        }

        // Detect real MIME type using Fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!array_key_exists($mimeType, $allowedMimes)) {
            $errorMessage = 'Only JPG, PNG, and WebP images are permitted.';
            goto renderView;
        }

        // Content verification using getimagesize()
        $imageInfo = @getimagesize($tmpPath);
        if (!$imageInfo || $imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            $errorMessage = 'Uploaded file is not a valid image.';
            goto renderView;
        }

        // Target directory: public/uploads/profiles
        $uploadDir = APP_ROOT . '/public/uploads/profiles';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $extension = $allowedMimes[$mimeType];
        $randomFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destPath = $uploadDir . '/' . $randomFilename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errorMessage = 'Could not save the uploaded image. Please check directory permissions.';
            goto renderView;
        }

        // Set safe permissions
        @chmod($destPath, 0644);

        $relativeDbPath = 'uploads/profiles/' . $randomFilename;
        $oldPhoto = $student['profile_photo_url'] ?? '';

        try {
            $pdo->beginTransaction();

            $stmtUpdSp = $pdo->prepare(
                "UPDATE student_profiles SET profile_photo_url = ?, updated_at = NOW() WHERE id = ? AND user_id = ?"
            );
            $stmtUpdSp->execute([$relativeDbPath, $student['id'], $sessionStudent['user_id']]);

            $stmtUpdUsr = $pdo->prepare(
                "UPDATE users SET image = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmtUpdUsr->execute([$relativeDbPath, $sessionStudent['user_id']]);

            $pdo->commit();

            // Safely remove previous photo if local and owned
            if (!empty($oldPhoto) && str_starts_with($oldPhoto, 'uploads/profiles/')) {
                $oldFile = APP_ROOT . '/public/' . $oldPhoto;
                $oldReal = realpath($oldFile);
                $dirReal = realpath($uploadDir);
                if ($oldReal && $dirReal && str_starts_with($oldReal, $dirReal) && is_file($oldReal)) {
                    @unlink($oldReal);
                }
            }

            $_SESSION['student_photo_url'] = $relativeDbPath;
            $student['profile_photo_url']  = $relativeDbPath;
            $successMessage = 'Profile photo updated successfully!';
            appLog('INFO', "Profile photo updated for student ID: {$student['id']}. IP: " . getClientIp());

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            @unlink($destPath);
            appLog('ERROR', 'Failed to update profile photo in DB: ' . $e->getMessage());
            $errorMessage = 'An error occurred saving your profile photo.';
        }
    }

    // ── Action B: Contact & Emergency Details Update ─────────────────────────
    elseif ($action === 'update_contact') {
        $mobileNumber   = trim((string)($_POST['mobileNumber'] ?? ''));
        $parentName     = trim((string)($_POST['parentName'] ?? ''));
        $parentContact  = trim((string)($_POST['parentContact'] ?? ''));
        $address        = trim((string)($_POST['address'] ?? ''));
        $locationIdRaw  = trim((string)($_POST['preferredLocationId'] ?? ''));

        // Clean digits
        $mobileNumber  = preg_replace('/[^0-9]/', '', $mobileNumber);
        $parentContact = preg_replace('/[^0-9]/', '', $parentContact);

        // Validation
        if ($mobileNumber !== '' && (strlen($mobileNumber) !== 10 || !preg_match('/^[6-9]\d{9}$/', $mobileNumber))) {
            $errorMessage = 'Please enter a valid 10-digit Indian mobile number.';
            goto renderView;
        }

        if ($parentContact !== '' && (strlen($parentContact) !== 10 || !preg_match('/^[6-9]\d{9}$/', $parentContact))) {
            $errorMessage = 'Parent emergency contact must be a valid 10-digit Indian mobile number.';
            goto renderView;
        }

        $preferredLocationId = null;
        if ($locationIdRaw !== '') {
            $locIdInt = (int) $locationIdRaw;
            // Verify location exists in active list
            $stmtLocCheck = $pdo->prepare("SELECT id FROM class_locations WHERE id = ? AND is_active = 1 LIMIT 1");
            $stmtLocCheck->execute([$locIdInt]);
            if ($stmtLocCheck->fetch()) {
                $preferredLocationId = $locIdInt;
            }
        }

        try {
            $stmtUpdate = $pdo->prepare(
                "UPDATE student_profiles 
                 SET mobile_number = ?, 
                     parent_name = ?, 
                     parent_contact = ?, 
                     address = ?, 
                     preferred_location_id = ?,
                     updated_at = NOW()
                 WHERE id = ? AND user_id = ?"
            );
            $stmtUpdate->execute([
                $mobileNumber ?: null,
                $parentName ?: null,
                $parentContact ?: null,
                $address ?: null,
                $preferredLocationId,
                $student['id'],
                $sessionStudent['user_id']
            ]);

            // Refresh student data in view
            $student['mobile_number']        = $mobileNumber;
            $student['parent_name']          = $parentName;
            $student['parent_contact']       = $parentContact;
            $student['address']              = $address;
            $student['preferred_location_id'] = $preferredLocationId;

            $successMessage = 'Contact details updated successfully!';
            appLog('INFO', "Contact details updated for student ID: {$student['id']}. IP: " . getClientIp());

        } catch (\Throwable $e) {
            appLog('ERROR', 'Error updating student contact info: ' . $e->getMessage());
            $errorMessage = 'An error occurred while saving your contact details.';
        }
    }
}

renderView:
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            Student Profile
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Official enrollment record with Armour Martial Arts Gujju Karate Association
        </p>
    </div>

    <!-- Alert Banners -->
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 p-3 rounded-3 border-0 shadow-sm" role="alert">
            <svg width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
            <div style="font-size: 0.82rem;"><?= esc($errorMessage) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 p-3 rounded-3 border-0 shadow-sm" role="alert">
            <svg width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
            <div style="font-size: 0.82rem;"><?= esc($successMessage) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column: Official Uneditable Academy Record -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white d-flex flex-column gap-4">

                <!-- Avatar Header -->
                <div class="text-center pb-4 border-bottom">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <div class="rounded-4 d-flex align-items-center justify-content-center text-white fw-black shadow-lg overflow-hidden"
                             style="width: 96px; height: 96px; background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); font-size: 2.2rem; border: 3px solid rgba(220, 38, 38, 0.4);">
                            <?php if (!empty($student['profile_photo_url'])): ?>
                                <img src="<?= escAttr(uploadUrl($student['profile_photo_url'])) ?>" alt="<?= escAttr($student['full_name']) ?>" class="w-100 h-100 object-fit-cover">
                            <?php else: ?>
                                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2 class="fs-5 fw-black text-uppercase text-dark mb-1">
                        <?= esc($student['full_name']) ?>
                    </h2>

                    <div class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 font-monospace fw-bold px-3 py-1.5" style="font-size: 0.72rem;">
                        <?= esc(!empty($student['student_id']) ? $student['student_id'] : 'PENDING ASSIGNMENT') ?>
                    </div>
                </div>

                <!-- Profile Photo Upload Widget -->
                <form action="<?= BASE_PATH ?>/student/profile" method="POST" enctype="multipart/form-data" class="p-3 rounded-3 bg-light border">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="upload_photo">
                    <label for="photoInput" class="form-label text-uppercase fw-bold text-secondary mb-1" style="font-size: 0.7rem; letter-spacing: 0.06em;">
                        Update Profile Photo
                    </label>
                    <div class="input-group input-group-sm mb-2">
                        <input type="file" class="form-control" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp" required style="font-size: 0.75rem;">
                        <button class="btn btn-danger fw-bold text-uppercase px-3" type="submit" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            Upload
                        </button>
                    </div>
                    <div class="text-secondary small" style="font-size: 0.68rem;">
                        Allowed formats: JPG, PNG, WebP (Max 2MB).
                    </div>
                </form>

                <!-- Locked Academy Credentials List -->
                <div class="d-flex flex-column gap-2" style="font-size: 0.78rem;">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-secondary">Membership Status</span>
                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 text-uppercase fw-bold" style="font-size: 0.65rem;">
                            <?= esc($student['status']) ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-secondary">Current Karate Rank</span>
                        <span class="fw-bold text-dark d-flex align-items-center gap-1.5">
                            <span class="rounded-circle border" style="width: 12px; height: 12px; background-color: <?= escAttr($student['belt_color'] ?? '#FFFFFF') ?>;"></span>
                            <span><?= esc($student['belt_name'] ?? 'White Belt') ?></span>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-secondary">Joining Date</span>
                        <span class="fw-semibold text-dark"><?= formatDate($student['joining_date']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-secondary">Registered Email</span>
                        <span class="fw-semibold text-dark"><?= esc($student['email']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-secondary">Date of Birth</span>
                        <span class="fw-semibold text-dark"><?= formatDate($student['date_of_birth']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-secondary">Gender</span>
                        <span class="fw-semibold text-dark"><?= esc($student['gender'] ?? '—') ?></span>
                    </div>
                </div>

                <!-- Tamper Prevention Notice -->
                <div class="p-3 rounded-3 bg-light border d-flex align-items-start gap-2 text-secondary" style="font-size: 0.72rem; line-height: 1.5;">
                    <svg width="16" height="16" fill="currentColor" class="text-secondary flex-shrink-0 mt-0.5" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/></svg>
                    <div>
                        Student ID, name, email, joining date, and belt ranks are locked to prevent unauthorized tampering. Contact the Sensei for official corrections.
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Column: Editable Contact & Emergency Details -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
                <div class="mb-4">
                    <h2 class="fs-6 fw-bold text-dark mb-1">Update Contact &amp; Emergency Details</h2>
                    <p class="text-secondary small mb-0" style="font-size: 0.75rem;">
                        Keep your mobile, emergency parent contact, and residential address up to date.
                    </p>
                </div>

                <form action="<?= BASE_PATH ?>/student/profile" method="POST" class="d-flex flex-column gap-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_contact">

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="mobileNumber" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                                Student Mobile (10 Digits)
                            </label>
                            <input type="tel"
                                   class="form-control"
                                   id="mobileNumber"
                                   name="mobileNumber"
                                   maxlength="10"
                                   pattern="[6-9][0-9]{9}"
                                   value="<?= escAttr($student['mobile_number'] ?? '') ?>"
                                   placeholder="9876543210"
                                   style="font-size: 0.82rem; padding: 0.65rem 0.85rem;">
                        </div>

                        <div class="col-sm-6">
                            <label for="preferredLocationId" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                                Preferred Dojo Location
                            </label>
                            <select class="form-select"
                                    id="preferredLocationId"
                                    name="preferredLocationId"
                                    style="font-size: 0.82rem; padding: 0.65rem 0.85rem;">
                                <option value="">-- No preference --</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= escAttr((string)$loc['id']) ?>" <?= ((int)$loc['id'] === (int)($student['preferred_location_id'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= esc($loc['location_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="parentName" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                                Parent / Guardian Name
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="parentName"
                                   name="parentName"
                                   value="<?= escAttr($student['parent_name'] ?? '') ?>"
                                   placeholder="Full name of guardian"
                                   style="font-size: 0.82rem; padding: 0.65rem 0.85rem;">
                        </div>

                        <div class="col-sm-6">
                            <label for="parentContact" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                                Parent Emergency Contact
                            </label>
                            <input type="tel"
                                   class="form-control"
                                   id="parentContact"
                                   name="parentContact"
                                   maxlength="10"
                                   pattern="[6-9][0-9]{9}"
                                   value="<?= escAttr($student['parent_contact'] ?? '') ?>"
                                   placeholder="9876543210"
                                   style="font-size: 0.82rem; padding: 0.65rem 0.85rem;">
                        </div>
                    </div>

                    <div>
                        <label for="address" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            Residential Address
                        </label>
                        <textarea class="form-control"
                                  id="address"
                                  name="address"
                                  rows="3"
                                  placeholder="House/Apartment, Street, Area, City"
                                  style="font-size: 0.82rem;"><?= esc($student['address'] ?? '') ?></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-danger text-uppercase fw-bold d-inline-flex align-items-center gap-2 px-4 py-2.5" style="font-size: 0.75rem; letter-spacing: 0.06em;">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/></svg>
                            <span>Save Contact Info</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
