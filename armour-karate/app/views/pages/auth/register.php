<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Registration (4-Step Wizard)
 * app/views/pages/auth/register.php
 *
 * Faithfully recreates the original Next.js 4-step registration page:
 *  - Left martial brand panel with stepper indicators
 *  - Step 1: Personal Details (Full name, DOB, Gender, Mobile)
 *  - Step 2: Contact & Residential Address (Email, Address)
 *  - Step 3: Parent / Guardian Details (Parent name, Parent contact)
 *  - Step 4: Preferred Dojo & Password Security
 *  - Dual AJAX and standard POST processing
 *  - CSRF protection, IP rate limiting, input validation
 *  - Atomic transaction: creates `users` (STUDENT) & `student_profiles` (PENDING)
 *  - Student ID strictly remains NULL until administrator approval
 */

declare(strict_types=1);

requireGuest();

$pageTitle       = 'Student Admission & Registration | Armour Karate Academy';
$metaDescription = 'Enroll in Armour Martial Arts Gujju Karate Association. 4-step admission application for traditional Goju-Ryu karate training.';

$pdo = db();

// Fetch active class locations for dropdown (restricted to verified active locations)
$activeLocations = [];
try {
    $stmtLoc = $pdo->query("SELECT id, location_name, address FROM class_locations WHERE is_active = 1 ORDER BY location_name ASC");
    $activeLocations = $stmtLoc->fetchAll();
} catch (PDOException $e) {
    appLog('WARNING', 'Failed to query active locations for registration: ' . $e->getMessage());
    $activeLocations = [];
}

$errorMessage = null;
$formSuccess  = false;
$formData     = [
    'fullName'            => '',
    'dateOfBirth'         => '',
    'gender'              => 'MALE',
    'mobileNumber'        => '',
    'email'               => '',
    'address'             => '',
    'parentName'          => '',
    'parentContact'       => '',
    'preferredLocationId' => '',
];

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || (!empty($_POST['is_ajax']));

// ─── POST Submission Handler ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF Token Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on student registration attempt. IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid or expired session security token. Please refresh and try again.']);
            exit;
        }

        $errorMessage = 'Invalid or expired session security token. Please refresh and try again.';
        goto renderView;
    }

    // 2. Persistent Rate Limiting (survives session resets, 5 attempts per minute)
    $rateCheck = checkRateLimit('register', getClientIp(), 5, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Registration rate limit exceeded by IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many registration attempts. Please wait a few minutes before trying again.']);
            exit;
        }

        $errorMessage = 'Too many registration attempts. Please wait a few minutes before trying again.';
        goto renderView;
    }

    // 3. Capture & normalize form inputs
    $formData['fullName']            = trim((string)($_POST['fullName'] ?? ''));
    $formData['dateOfBirth']         = trim((string)($_POST['dateOfBirth'] ?? ''));
    $formData['gender']              = in_array($_POST['gender'] ?? '', ['MALE', 'FEMALE', 'OTHER'], true) ? $_POST['gender'] : 'MALE';
    $formData['mobileNumber']        = preg_replace('/[^0-9]/', '', (string)($_POST['mobileNumber'] ?? ''));
    $formData['email']               = strtolower(trim((string)($_POST['email'] ?? '')));
    $formData['address']             = trim((string)($_POST['address'] ?? ''));
    $formData['parentName']          = trim((string)($_POST['parentName'] ?? ''));
    $formData['parentContact']       = preg_replace('/[^0-9]/', '', (string)($_POST['parentContact'] ?? ''));
    $formData['preferredLocationId'] = trim((string)($_POST['preferredLocationId'] ?? ''));
    $password                        = (string)($_POST['password'] ?? '');
    $confirmPassword                 = (string)($_POST['confirmPassword'] ?? '');

    // 4. Server-Side Validation
    if (mb_strlen($formData['fullName']) < 2 || mb_strlen($formData['fullName']) > 100) {
        $errorMessage = 'Please enter your full legal name (between 2 and 100 characters).';
    } elseif (empty($formData['dateOfBirth']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData['dateOfBirth'])) {
        $errorMessage = 'Please provide a valid date of birth (YYYY-MM-DD).';
    } elseif (strlen($formData['mobileNumber']) !== 10) {
        $errorMessage = 'Please enter a valid 10-digit mobile phone number.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($formData['email']) > 255) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (mb_strlen($formData['address']) < 10 || mb_strlen($formData['address']) > 500) {
        $errorMessage = 'Please enter a complete residential address (at least 10 characters).';
    } elseif (mb_strlen($formData['parentName']) < 2 || mb_strlen($formData['parentName']) > 100) {
        $errorMessage = 'Parent / Guardian name is required (minimum 2 characters).';
    } elseif (strlen($formData['parentContact']) !== 10) {
        $errorMessage = 'Please enter a valid 10-digit parent emergency contact number.';
    } elseif (mb_strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errorMessage = 'Password must contain at least one uppercase letter and one number.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';
    }

    if ($errorMessage !== null) {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 5. Duplicate Email Check
    try {
        $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtEmail->execute([':email' => $formData['email']]);
        if ($stmtEmail->fetch()) {
            $errorMessage = 'An account with this email address already exists. Please sign in or use another email.';
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => $errorMessage]);
                exit;
            }
            goto renderView;
        }
    } catch (PDOException $e) {
        appLog('ERROR', 'Registration duplicate email check failed: ' . $e->getMessage());
        $errorMessage = 'An unexpected database error occurred. Please try again.';
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 6. Verify Location ID if supplied
    $locationIdToSave = null;
    if (!empty($formData['preferredLocationId']) && is_numeric($formData['preferredLocationId'])) {
        try {
            $stmtLocCheck = $pdo->prepare("SELECT id FROM class_locations WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmtLocCheck->execute([':id' => (int)$formData['preferredLocationId']]);
            if ($stmtLocCheck->fetch()) {
                $locationIdToSave = (int)$formData['preferredLocationId'];
            }
        } catch (PDOException) {
            $locationIdToSave = null;
        }
    }

    // 7. Atomic Registration via Database Transaction
    try {
        $pdo->beginTransaction();

        $passwordHash = hashPassword($password);

        // A. Insert into users table
        $stmtUser = $pdo->prepare(
            "INSERT INTO users (name, email, email_verified, password_hash, role, created_at, updated_at)
             VALUES (:name, :email, 0, :password_hash, 'STUDENT', NOW(), NOW())"
        );
        $stmtUser->execute([
            ':name'          => $formData['fullName'],
            ':email'         => $formData['email'],
            ':password_hash' => $passwordHash,
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        // B. Query White Belt (initial belt rank) if exists
        $initialBeltId = null;
        $stmtBelt = $pdo->query("SELECT id FROM belts ORDER BY rank ASC LIMIT 1");
        $firstBelt = $stmtBelt->fetch();
        if ($firstBelt) {
            $initialBeltId = (int)$firstBelt['id'];
        }

        // C. Insert into student_profiles (student_id MUST be NULL until approved by admin!)
        $stmtProfile = $pdo->prepare(
            "INSERT INTO student_profiles (
                user_id, student_id, full_name, date_of_birth, gender,
                mobile_number, email, address, parent_name, parent_contact,
                joining_date, status, preferred_location_id, current_belt_id,
                created_at, updated_at
            ) VALUES (
                :user_id, NULL, :full_name, :date_of_birth, :gender,
                :mobile_number, :email, :address, :parent_name, :parent_contact,
                CURDATE(), 'PENDING', :preferred_location_id, :current_belt_id,
                NOW(), NOW()
            )"
        );
        $stmtProfile->execute([
            ':user_id'               => $newUserId,
            ':full_name'             => $formData['fullName'],
            ':date_of_birth'         => $formData['dateOfBirth'],
            ':gender'                => $formData['gender'],
            ':mobile_number'         => $formData['mobileNumber'],
            ':email'                 => $formData['email'],
            ':address'               => $formData['address'],
            ':parent_name'           => $formData['parentName'],
            ':parent_contact'        => $formData['parentContact'],
            ':preferred_location_id' => $locationIdToSave,
            ':current_belt_id'       => $initialBeltId,
        ]);

        $pdo->commit();

        appLog('INFO', "New student registration submitted: user_id={$newUserId}, email={$formData['email']}. Status: PENDING.");

        // Rotate CSRF token
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));

        if ($isAjax) {
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode([
                'success'      => true,
                'redirect_url' => BASE_PATH . '/registration-pending',
                'student_name' => $formData['fullName'],
            ]);
            exit;
        }

        safeRedirect(BASE_PATH . '/registration-pending');

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        appLog('ERROR', 'Student registration transaction failed: ' . $e->getMessage());

        $errorMessage = 'A database error occurred during registration. Please try again.';
        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $errorMessage]);
            exit;
        }
    }
}

renderView:
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="min-vh-100 d-flex flex-column flex-lg-row" style="background:#0A0A0A; color:#fff;">

    <!-- ── Left Martial Brand Panel (Desktop) ────────────────────────────── -->
    <div class="d-none d-lg-flex flex-column justify-content-between p-5 position-relative overflow-hidden"
         style="width:42%; background:linear-gradient(135deg, #140606 0%, #1a0808 50%, #0A0A0A 100%); border-right:1px solid #1f1f1f;">

        <div class="position-absolute top-0 end-0 rounded-circle"
             style="width:380px; height:380px; background:rgba(220,38,38,0.1); filter:blur(120px); pointer-events:none;"></div>

        <!-- Brand Crest -->
        <div class="position-relative z-1">
            <a href="<?= BASE_PATH ?>/" class="d-inline-flex align-items-center gap-3 text-decoration-none">
                <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="56" height="56" class="rounded-circle" style="border:1px solid rgba(220,38,38,0.4);">
                <div>
                    <span class="d-block fw-black text-white text-uppercase" style="letter-spacing:0.08em; font-size:1.1rem;">
                        Armour Karate
                    </span>
                    <span class="d-block text-danger fw-semibold text-uppercase" style="letter-spacing:0.14em; font-size:0.7rem;">
                        Goju-Ryu Karate-Do
                    </span>
                </div>
            </a>
        </div>

        <!-- Martial Pitch & Stepper Indicators -->
        <div class="position-relative z-1 my-auto py-5" style="max-width:380px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                 style="background:rgba(127,29,29,0.4); border:1px solid rgba(185,28,28,0.3); color:#f87171; font-size:0.7rem; font-weight:800; letter-spacing:0.12em; text-transform:uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/>
                </svg>
                Academy Admission
            </div>

            <h1 class="fw-black text-uppercase text-white mb-3" style="font-size:2.4rem; line-height:1.15; letter-spacing:-0.02em;">
                Begin The <br><span style="color:#DC2626;">Martial Way</span>
            </h1>

            <p style="font-size:0.85rem; color:#d4d4d4; line-height:1.7;">
                Fill out the 4-step registration form. Your admission details will be reviewed by the Sensei, and upon approval, your unique student ID card will be issued.
            </p>

            <!-- Desktop Stepper Navigation -->
            <div class="d-flex flex-column gap-3 pt-4" id="desktopStepList">
                <div class="step-nav-item active d-flex align-items-center gap-3 text-uppercase fw-bold" data-step="1" style="font-size:0.75rem; letter-spacing:0.08em; color:#DC2626;">
                    <div class="step-nav-circle d-flex align-items-center justify-content-center rounded-circle" style="width:26px; height:26px; background:#DC2626; color:#fff; font-size:0.75rem;">1</div>
                    <span>Personal Details</span>
                </div>
                <div class="step-nav-item d-flex align-items-center gap-3 text-uppercase fw-bold" data-step="2" style="font-size:0.75rem; letter-spacing:0.08em; color:#737373;">
                    <div class="step-nav-circle d-flex align-items-center justify-content-center rounded-circle" style="width:26px; height:26px; background:#1c1c1e; color:#737373; font-size:0.75rem;">2</div>
                    <span>Contact &amp; Address</span>
                </div>
                <div class="step-nav-item d-flex align-items-center gap-3 text-uppercase fw-bold" data-step="3" style="font-size:0.75rem; letter-spacing:0.08em; color:#737373;">
                    <div class="step-nav-circle d-flex align-items-center justify-content-center rounded-circle" style="width:26px; height:26px; background:#1c1c1e; color:#737373; font-size:0.75rem;">3</div>
                    <span>Parent / Guardian</span>
                </div>
                <div class="step-nav-item d-flex align-items-center gap-3 text-uppercase fw-bold" data-step="4" style="font-size:0.75rem; letter-spacing:0.08em; color:#737373;">
                    <div class="step-nav-circle d-flex align-items-center justify-content-center rounded-circle" style="width:26px; height:26px; background:#1c1c1e; color:#737373; font-size:0.75rem;">4</div>
                    <span>Dojo &amp; Security</span>
                </div>
            </div>
        </div>

        <div class="position-relative z-1 pt-4" style="border-top:1px solid #1f1f1f; font-size:0.75rem; color:#737373;">
            Already a student? <a href="<?= BASE_PATH ?>/login" class="text-danger fw-bold text-decoration-none">Sign In to Portal</a>
        </div>
    </div>

    <!-- ── Right Form Panel (Wizard) ────────────────────────────────────── -->
    <div class="flex-grow-1 d-flex flex-column justify-content-center align-items-center p-4 p-sm-5 py-5">
        <div style="width:100%; max-width:580px;">

            <!-- Mobile Stepper Header -->
            <div class="d-lg-none mb-4 pb-3 text-center" style="border-bottom:1px solid #222;">
                <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="54" height="54" class="rounded-circle mb-2" style="border:1px solid rgba(220,38,38,0.4);">
                <h2 class="fw-black text-uppercase text-white mb-0" style="font-size:1.4rem;">
                    Student Admission
                </h2>
                <span class="text-danger fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.1em;">
                    Armour Martial Arts Gujju Karate Association
                </span>
            </div>

            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2 fw-bold text-uppercase" style="font-size:0.7rem; letter-spacing:0.08em; color:#a3a3a3;">
                    <span id="stepProgressLabel">Step 1 of 4</span>
                    <span id="stepProgressPercent">25% Complete</span>
                </div>
                <div class="w-100 rounded-pill overflow-hidden" style="height:6px; background:#222;">
                    <div id="stepProgressBar" class="h-100 rounded-pill" style="width:25%; background:#DC2626; transition:width 0.3s ease;"></div>
                </div>
            </div>

            <!-- Error Banner -->
            <div id="registerErrorAlert" class="p-3 rounded-3 mb-4 d-flex align-items-center gap-2"
                 style="background:rgba(127,29,29,0.5); border:1px solid #dc2626; color:#fca5a5; font-size:0.8rem; <?= $errorMessage ? 'display:flex;' : 'display:none;' ?>" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fca5a5" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                </svg>
                <span id="registerErrorText"><?= esc($errorMessage ?? '') ?></span>
            </div>

            <!-- Wizard Form -->
            <form id="studentRegisterForm" method="POST" action="<?= BASE_PATH ?>/register" novalidate>
                <?= csrfField() ?>

                <!-- ── STEP 1: Personal Information ──────────────────────── -->
                <div class="wizard-step" id="wizardStep1">
                    <div class="mb-4">
                        <h2 class="fw-black text-uppercase text-white mb-1" style="font-size:1.4rem;">
                            1. Personal Information
                        </h2>
                        <p style="font-size:0.8rem; color:#a3a3a3;">
                            Enter student's legal name and identity as it will appear on official certificates.
                        </p>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="reg_fullName" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Full Legal Name <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="text"
                               id="reg_fullName"
                               name="fullName"
                               required
                               maxlength="100"
                               value="<?= escAttr($formData['fullName']) ?>"
                               placeholder="e.g. Aarav D. Patel"
                               class="form-control text-white rounded-3 px-3 py-2.5"
                               style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                    </div>

                    <!-- DOB & Gender -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="reg_dateOfBirth" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                                Date of Birth <span style="color:#DC2626;">*</span>
                            </label>
                            <input type="date"
                                   id="reg_dateOfBirth"
                                   name="dateOfBirth"
                                   required
                                   value="<?= escAttr($formData['dateOfBirth']) ?>"
                                   class="form-control text-white rounded-3 px-3 py-2.5"
                                   style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="reg_gender" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                                Gender <span style="color:#DC2626;">*</span>
                            </label>
                            <select id="reg_gender"
                                    name="gender"
                                    class="form-select text-white rounded-3 px-3 py-2.5"
                                    style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                                <option value="MALE" <?= $formData['gender'] === 'MALE' ? 'selected' : '' ?>>Male</option>
                                <option value="FEMALE" <?= $formData['gender'] === 'FEMALE' ? 'selected' : '' ?>>Female</option>
                                <option value="OTHER" <?= $formData['gender'] === 'OTHER' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div class="mb-4">
                        <label for="reg_mobileNumber" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Mobile Phone Number <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="tel"
                               id="reg_mobileNumber"
                               name="mobileNumber"
                               required
                               maxlength="10"
                               pattern="[0-9]{10}"
                               value="<?= escAttr($formData['mobileNumber']) ?>"
                               placeholder="10-digit mobile number"
                               class="form-control text-white rounded-3 px-3 py-2.5"
                               style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                    </div>
                </div>

                <!-- ── STEP 2: Contact & Address ─────────────────────────── -->
                <div class="wizard-step d-none" id="wizardStep2">
                    <div class="mb-4">
                        <h2 class="fw-black text-uppercase text-white mb-1" style="font-size:1.4rem;">
                            2. Contact &amp; Residential Address
                        </h2>
                        <p style="font-size:0.8rem; color:#a3a3a3;">
                            Used for student portal communications and official academy registry records.
                        </p>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="reg_email" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Email Address <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="email"
                               id="reg_email"
                               name="email"
                               required
                               maxlength="255"
                               value="<?= escAttr($formData['email']) ?>"
                               placeholder="student@example.com"
                               class="form-control text-white rounded-3 px-3 py-2.5"
                               style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                    </div>

                    <!-- Residential Address -->
                    <div class="mb-4">
                        <label for="reg_address" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Full Residential Address <span style="color:#DC2626;">*</span>
                        </label>
                        <textarea id="reg_address"
                                  name="address"
                                  required
                                  rows="3"
                                  maxlength="500"
                                  placeholder="House/Flat No., Street, Area, City, Pincode"
                                  class="form-control text-white rounded-3 px-3 py-2.5"
                                  style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;"><?= esc($formData['address']) ?></textarea>
                    </div>
                </div>

                <!-- ── STEP 3: Parent / Guardian Details ─────────────────── -->
                <div class="wizard-step d-none" id="wizardStep3">
                    <div class="mb-4">
                        <h2 class="fw-black text-uppercase text-white mb-1" style="font-size:1.4rem;">
                            3. Parent / Guardian Details
                        </h2>
                        <p style="font-size:0.8rem; color:#a3a3a3;">
                            Required for minors and for all emergency dojo records.
                        </p>
                    </div>

                    <!-- Parent Name -->
                    <div class="mb-3">
                        <label for="reg_parentName" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Parent / Guardian Full Name <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="text"
                               id="reg_parentName"
                               name="parentName"
                               required
                               maxlength="100"
                               value="<?= escAttr($formData['parentName']) ?>"
                               placeholder="e.g. Ramesh Patel"
                               class="form-control text-white rounded-3 px-3 py-2.5"
                               style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                    </div>

                    <!-- Parent Contact -->
                    <div class="mb-4">
                        <label for="reg_parentContact" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Parent Emergency Contact Phone <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="tel"
                               id="reg_parentContact"
                               name="parentContact"
                               required
                               maxlength="10"
                               pattern="[0-9]{10}"
                               value="<?= escAttr($formData['parentContact']) ?>"
                               placeholder="10-digit emergency contact number"
                               class="form-control text-white rounded-3 px-3 py-2.5"
                               style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                    </div>
                </div>

                <!-- ── STEP 4: Preferred Dojo & Password Security ────────── -->
                <div class="wizard-step d-none" id="wizardStep4">
                    <div class="mb-4">
                        <h2 class="fw-black text-uppercase text-white mb-1" style="font-size:1.4rem;">
                            4. Preferred Dojo &amp; Security
                        </h2>
                        <p style="font-size:0.8rem; color:#a3a3a3;">
                            Select your training center preferences and establish a strong student password.
                        </p>
                    </div>

                    <!-- Preferred Location -->
                    <div class="mb-3">
                        <label for="reg_preferredLocationId" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Preferred Training Center / Dojo
                        </label>
                        <select id="reg_preferredLocationId"
                                name="preferredLocationId"
                                class="form-select text-white rounded-3 px-3 py-2.5"
                                style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                            <option value="">-- Inquire With Academy During Verification --</option>
                            <?php foreach ($activeLocations as $loc): ?>
                                <option value="<?= (int)$loc['id'] ?>" <?= $formData['preferredLocationId'] == $loc['id'] ? 'selected' : '' ?>>
                                    <?= esc($loc['location_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="reg_password" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Create Password <span style="color:#DC2626;">*</span>
                            <span class="text-secondary fw-normal text-lowercase">(min 8 chars, 1 uppercase, 1 digit)</span>
                        </label>
                        <div class="position-relative">
                            <input type="password"
                                   id="reg_password"
                                   name="password"
                                   required
                                   minlength="8"
                                   placeholder="••••••••"
                                   class="form-control text-white rounded-3 px-3 py-2.5 pe-5"
                                   style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                            <button type="button" class="btn btn-sm btn-link position-absolute end-0 top-50 translate-middle-y text-secondary pe-3"
                                    onclick="togglePasswordVisibility('reg_password', this)">
                                Show
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="reg_confirmPassword" class="d-block fw-bold text-uppercase mb-1" style="font-size:0.7rem; color:#d4d4d4; letter-spacing:0.08em;">
                            Confirm Password <span style="color:#DC2626;">*</span>
                        </label>
                        <div class="position-relative">
                            <input type="password"
                                   id="reg_confirmPassword"
                                   name="confirmPassword"
                                   required
                                   minlength="8"
                                   placeholder="••••••••"
                                   class="form-control text-white rounded-3 px-3 py-2.5 pe-5"
                                   style="background:rgba(18,18,20,0.8); border:1px solid #333; font-size:0.9rem;">
                            <button type="button" class="btn btn-sm btn-link position-absolute end-0 top-50 translate-middle-y text-secondary pe-3"
                                    onclick="togglePasswordVisibility('reg_confirmPassword', this)">
                                Show
                            </button>
                        </div>
                    </div>

                    <!-- Admission Policy Note -->
                    <div class="p-3 rounded-3 mb-4" style="background:rgba(127,29,29,0.25); border:1px solid rgba(185,28,28,0.4); font-size:0.75rem; color:#d4d4d4;">
                        <span class="text-danger fw-bold d-block mb-1 text-uppercase">Admission Policy Note:</span>
                        Submitting this form records your enrollment application with status <span class="text-warning fw-bold">PENDING</span>. Following Sensei verification and batch allotment, your official permanent Student ID (e.g. KAR-YYYY-NNNN) will be assigned.
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="d-flex align-items-center justify-content-between pt-3" style="border-top:1px solid #222;">
                    <button type="button" id="btnPrevStep" class="btn px-4 py-2.5 rounded-3 fw-bold text-uppercase text-white d-none"
                            style="font-size:0.75rem; letter-spacing:0.08em; background:#222; border:1px solid #333;">
                        &larr; Previous
                    </button>
                    <div class="ms-auto d-flex gap-2">
                        <button type="button" id="btnNextStep" class="btn px-4 py-2.5 rounded-3 fw-bold text-uppercase text-white"
                                style="font-size:0.75rem; letter-spacing:0.08em; background:#DC2626; border:none; box-shadow:0 4px 16px rgba(220,38,38,0.35);">
                            Continue &rarr;
                        </button>
                        <button type="submit" id="btnSubmitReg" class="btn px-4 py-2.5 rounded-3 fw-bold text-uppercase text-white d-none"
                                style="font-size:0.75rem; letter-spacing:0.08em; background:#DC2626; border:none; box-shadow:0 4px 16px rgba(220,38,38,0.45);">
                            <span id="btnRegSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                            <span id="btnRegText">Submit Registration</span>
                        </button>
                    </div>
                </div>

            </form>

            <div class="text-center pt-3 mt-3 text-secondary" style="font-size:0.75rem;">
                Already registered? <a href="<?= BASE_PATH ?>/login" class="text-danger fw-bold text-decoration-none">Student Sign In</a>
            </div>

        </div>
    </div>

</div>

<script>
(function () {
    'use strict';

    let currentStep = 1;
    const form       = document.getElementById('studentRegisterForm');
    const alertBox   = document.getElementById('registerErrorAlert');
    const alertText  = document.getElementById('registerErrorText');
    const btnPrev    = document.getElementById('btnPrevStep');
    const btnNext    = document.getElementById('btnNextStep');
    const btnSubmit  = document.getElementById('btnSubmitReg');
    const btnSpinner = document.getElementById('btnRegSpinner');
    const btnText    = document.getElementById('btnRegText');
    const progBar    = document.getElementById('stepProgressBar');
    const progLabel  = document.getElementById('stepProgressLabel');
    const progPct    = document.getElementById('stepProgressPercent');

    function showError(msg) {
        alertText.textContent = msg;
        alertBox.style.display = 'flex';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideError() {
        alertBox.style.display = 'none';
        alertText.textContent = '';
    }

    function updateStepUI() {
        for (let i = 1; i <= 4; i++) {
            const stepEl = document.getElementById('wizardStep' + i);
            if (stepEl) {
                stepEl.classList.toggle('d-none', i !== currentStep);
            }
        }

        // Update progress bar
        const pct = (currentStep / 4) * 100;
        progBar.style.width = pct + '%';
        progLabel.textContent = 'Step ' + currentStep + ' of 4';
        progPct.textContent   = Math.round(pct) + '% Complete';

        // Update desktop nav pills
        document.querySelectorAll('.step-nav-item').forEach(function (el) {
            const stepNum = parseInt(el.dataset.step, 10);
            const circle = el.querySelector('.step-nav-circle');
            if (stepNum === currentStep) {
                el.style.color = '#DC2626';
                if (circle) { circle.style.background = '#DC2626'; circle.style.color = '#fff'; }
            } else if (stepNum < currentStep) {
                el.style.color = '#d4d4d4';
                if (circle) { circle.style.background = '#2a2a2a'; circle.style.color = '#d4d4d4'; }
            } else {
                el.style.color = '#525252';
                if (circle) { circle.style.background = '#1c1c1e'; circle.style.color = '#525252'; }
            }
        });

        // Toggle buttons
        btnPrev.classList.toggle('d-none', currentStep === 1);
        btnNext.classList.toggle('d-none', currentStep === 4);
        btnSubmit.classList.toggle('d-none', currentStep !== 4);
    }

    function validateCurrentStep() {
        hideError();
        if (currentStep === 1) {
            const name = form.fullName.value.trim();
            const dob  = form.dateOfBirth.value.trim();
            const mob  = form.mobileNumber.value.trim().replace(/[^0-9]/g, '');

            if (!name || name.length < 2) {
                showError('Please enter your full legal name (minimum 2 characters).');
                form.fullName.focus();
                return false;
            }
            if (!dob) {
                showError('Please provide a valid date of birth.');
                form.dateOfBirth.focus();
                return false;
            }
            if (mob.length !== 10) {
                showError('Please enter a valid 10-digit mobile phone number.');
                form.mobileNumber.focus();
                return false;
            }
        } else if (currentStep === 2) {
            const email = form.email.value.trim();
            const addr  = form.address.value.trim();

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('Please enter a valid email address.');
                form.email.focus();
                return false;
            }
            if (!addr || addr.length < 10) {
                showError('Please enter your residential address (minimum 10 characters).');
                form.address.focus();
                return false;
            }
        } else if (currentStep === 3) {
            const parentName = form.parentName.value.trim();
            const parentMob  = form.parentContact.value.trim().replace(/[^0-9]/g, '');

            if (!parentName || parentName.length < 2) {
                showError('Parent / Guardian name is required.');
                form.parentName.focus();
                return false;
            }
            if (parentMob.length !== 10) {
                showError('Please enter a valid 10-digit parent emergency contact number.');
                form.parentContact.focus();
                return false;
            }
        }
        return true;
    }

    btnNext.addEventListener('click', function () {
        if (validateCurrentStep()) {
            currentStep = Math.min(currentStep + 1, 4);
            updateStepUI();
        }
    });

    btnPrev.addEventListener('click', function () {
        hideError();
        currentStep = Math.max(currentStep - 1, 1);
        updateStepUI();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideError();

        if (!validateCurrentStep()) return;

        const pwd     = form.password.value;
        const confPwd = form.confirmPassword.value;

        if (pwd.length < 8) {
            showError('Password must be at least 8 characters long.');
            form.password.focus();
            return;
        }
        if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd)) {
            showError('Password must contain at least one uppercase letter and one number.');
            form.password.focus();
            return;
        }
        if (pwd !== confPwd) {
            showError('Passwords do not match.');
            form.confirmPassword.focus();
            return;
        }

        btnSubmit.disabled = true;
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Submitting Application...';

        try {
            const formDataObj = new FormData(form);
            formDataObj.append('is_ajax', '1');

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formDataObj
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                showError(data.error || 'Registration failed. Please review your details and try again.');
                btnSubmit.disabled = false;
                btnSpinner.classList.add('d-none');
                btnText.textContent = 'Submit Registration';
                return;
            }

            // Success redirect to pending screen
            window.location.href = data.redirect_url || '<?= BASE_PATH ?>/registration-pending';

        } catch (err) {
            showError('A network error occurred. Please check your connection and try again.');
            btnSubmit.disabled = false;
            btnSpinner.classList.add('d-none');
            btnText.textContent = 'Submit Registration';
        }
    });

    window.togglePasswordVisibility = function (fieldId, btn) {
        const input = document.getElementById(fieldId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = 'Hide';
        } else {
            input.type = 'password';
            btn.textContent = 'Show';
        }
    };

    updateStepUI();
}());
</script>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
