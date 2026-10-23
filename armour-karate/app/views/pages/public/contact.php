<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Contact Us & Head Office — Public Page
 * app/views/pages/public/contact.php
 *
 * Faithfully recreates the original Next.js Contact page:
 *  - Hero banner with badge
 *  - 2-Column layout:
 *      Left: Head Office Information (verified DB fields only)
 *      Right: Online Inquiry Form (Name, Phone, Email, Subject, Message)
 *  - POST-only submission handler:
 *      * CSRF token validation
 *      * Honeypot spam defense
 *      * Persistent IP rate limiting (survives session resets)
 *      * Duplicate submission prevention
 *      * Strict server-side input validation
 *      * PDO prepared statement insertion into `contact_messages`
 *      * Safe error logging with zero exposed internal/SQL details
 *      * AJAX and standard POST redirect/state support
 *
 * Data source:
 *  - Reads: `head_office` singleton row (id = 1)
 *  - Writes: `contact_messages` (strictly scoped to legitimate enquiries)
 */

declare(strict_types=1);

$pageTitle       = 'Contact Us & Head Office | Armour Karate Academy';
$metaDescription = 'Get in touch with the Armour Karate Academy administrative team for admissions, dojo center inquiries, tournament registrations, and master camps.';

$pdo = db();

// ─── Fetch Head Office Info (Verified DB only) ────────────────────────────────
$headOffice = null;
try {
    $stmtHo = $pdo->prepare("SELECT id, address, phone, email, office_timing FROM head_office WHERE id = 1");
    $stmtHo->execute();
    $headOffice = $stmtHo->fetch() ?: null;
} catch (PDOException $e) {
    appLog('ERROR', 'Contact page head_office query error: ' . $e->getMessage());
    $headOffice = null;
}

$officeAddress = ($headOffice && !empty($headOffice['address']))
    ? $headOffice['address']
    : 'Gujarat, India';
$officePhone   = ($headOffice && !empty($headOffice['phone']))
    ? $headOffice['phone']
    : null;
$officeEmail   = ($headOffice && !empty($headOffice['email']))
    ? $headOffice['email']
    : null;
$officeTiming  = ($headOffice && !empty($headOffice['office_timing']))
    ? $headOffice['office_timing']
    : null;

// ─── POST Handler ─────────────────────────────────────────────────────────────
$formErrors  = [];
$formSuccess = false;
$formData    = [
    'name'    => '',
    'phone'   => '',
    'email'   => '',
    'subject' => '',
    'message' => '',
];

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || (!empty($_POST['is_ajax']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Honeypot check: Bots usually fill hidden fields
    if (!empty($_POST['contact_hp_check'])) {
        usleep(300000); // 300ms delay
        appLog('SECURITY', 'Contact form honeypot triggered from IP: ' . getClientIp());

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Thank you for reaching out to Armour Karate Academy. Our team will contact you shortly.',
            ]);
            exit;
        }

        $formSuccess = true;
        goto renderPage;
    }

    // 2. CSRF Token Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on contact form submission. IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid or expired security token. Please refresh the page and try again.']);
            exit;
        }

        $formErrors[] = 'Invalid or expired security token. Please refresh the page and try again.';
        goto renderPage;
    }

    // 3. Persistent IP Rate Limiting (survives session resets, 3 attempts per 60 seconds)
    $rateCheck = checkRateLimit('contact', getClientIp(), 3, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Contact form rate limit exceeded by IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many contact messages submitted. Please try again in a few minutes.']);
            exit;
        }

        $formErrors[] = 'Too many contact messages submitted. Please try again in a few minutes.';
        goto renderPage;
    }

    // 4. Capture & trim input values
    $formData['name']    = trim((string)($_POST['name'] ?? ''));
    $formData['phone']   = trim((string)($_POST['phone'] ?? ''));
    $formData['email']   = trim((string)($_POST['email'] ?? ''));
    $formData['subject'] = trim((string)($_POST['subject'] ?? ''));
    $formData['message'] = trim((string)($_POST['message'] ?? ''));

    // 5. Server-Side Validation (matches Zod schema from original project)
    if (mb_strlen($formData['name']) < 2 || mb_strlen($formData['name']) > 100) {
        $formErrors[] = 'Name must be between 2 and 100 characters.';
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $formData['phone']);
    if (strlen($cleanPhone) !== 10) {
        $formErrors[] = 'Please enter a valid 10-digit mobile number.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($formData['email']) > 255) {
        $formErrors[] = 'Please enter a valid email address.';
    }

    if (mb_strlen($formData['subject']) < 3 || mb_strlen($formData['subject']) > 200) {
        $formErrors[] = 'Subject must be between 3 and 200 characters.';
    }

    if (mb_strlen($formData['message']) < 10 || mb_strlen($formData['message']) > 2000) {
        $formErrors[] = 'Message must be between 10 and 2000 characters.';
    }

    if (!empty($formErrors)) {
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => implode(' ', $formErrors)]);
            exit;
        }
        goto renderPage;
    }

    // 6. Duplicate Submission Protection (same email + subject within 120 seconds)
    try {
        $stmtDup = $pdo->prepare(
            "SELECT id FROM contact_messages
              WHERE email = :email
                AND subject = :subject
                AND created_at >= (NOW() - INTERVAL 2 MINUTE)
              LIMIT 1"
        );
        $stmtDup->execute([
            ':email'   => strtolower($formData['email']),
            ':subject' => $formData['subject'],
        ]);
        if ($stmtDup->fetch()) {
            // Already received recently — acknowledge smoothly without re-inserting
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Thank you for reaching out to Armour Karate Academy. Your inquiry was already received and our team will get in touch shortly.',
                ]);
                exit;
            }
            $formSuccess = true;
            $formData    = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'message' => ''];
            goto renderPage;
        }
    } catch (PDOException $e) {
        // Non-fatal duplicate check error: log and continue to insert
        appLog('WARNING', 'Contact form duplicate check error: ' . $e->getMessage());
    }

    // 7. Database Insertion using PDO Prepared Statement
    try {
        $stmtInsert = $pdo->prepare(
            "INSERT INTO contact_messages (name, phone, email, subject, message, status, created_at, updated_at)
             VALUES (:name, :phone, :email, :subject, :message, 'UNREAD', NOW(), NOW())"
        );
        $stmtInsert->execute([
            ':name'    => $formData['name'],
            ':phone'   => $cleanPhone,
            ':email'   => strtolower($formData['email']),
            ':subject' => $formData['subject'],
            ':message' => $formData['message'],
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Safe logging: no sensitive body data dumped
        appLog('INFO', "Contact enquiry recorded (ID: {$newId}) from IP: " . getClientIp());

        // Rotate CSRF token after successful submission
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));

        if ($isAjax) {
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Thank you for reaching out to Armour Karate Academy. Our admissions and dojo coordination team will get in touch with you shortly.',
                'new_csrf' => $_SESSION['_csrf_token'],
            ]);
            exit;
        }

        $formSuccess = true;
        $formData    = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'message' => ''];

    } catch (PDOException $e) {
        appLog('ERROR', 'Contact enquiry insert failed: ' . $e->getMessage());

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Something went wrong while submitting your enquiry. Please try again.']);
            exit;
        }

        $formErrors[] = 'Something went wrong while submitting your enquiry. Please try again.';
    }
}

renderPage:

// ─── Layout ───────────────────────────────────────────────────────────────────
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="page-section" style="background:#0A0A0A; color:#fff; padding: 5rem 0 4rem;">
    <div class="container" style="max-width:1280px;">

        <!-- ── Hero Header ────────────────────────────────────────────────── -->
        <div class="text-center mb-5 pb-2" style="max-width:760px; margin-left:auto; margin-right:auto;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                 style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171; font-size:0.7rem; font-weight:800; letter-spacing:0.12em; text-transform:uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                </svg>
                Direct Communication Channel
            </div>
            <h1 class="fw-black text-uppercase text-white mb-3"
                style="font-size: clamp(2.2rem, 5vw, 3.6rem); letter-spacing:-0.03em; line-height:1.05;">
                Contact <span style="color:#DC2626;">The Academy</span>
            </h1>
            <p style="color:#d4d4d4; font-size:0.925rem; line-height:1.7;">
                Have questions regarding new admissions, batch schedules, belt examinations, or tournament participation? Our dojo coordination staff is here to help.
            </p>
        </div>

        <!-- ── 2-Column Layout: Office Info & Form ─────────────────────────── -->
        <div class="row g-4 justify-content-center" style="max-width:1150px; margin-left:auto; margin-right:auto;">

            <!-- ── Left Column: Head Office Information (5 cols) ──────────── -->
            <div class="col-12 col-lg-5">
                <div class="h-100 rounded-4 p-4 p-sm-5 d-flex flex-column justify-content-between"
                     style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a;">

                    <div>
                        <div class="pb-3 mb-4" style="border-bottom:1px solid #262626;">
                            <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#DC2626; display:block; margin-bottom:4px;">
                                Head Office Information
                            </span>
                            <h3 class="fw-black text-uppercase text-white mb-0" style="font-size:1.25rem;">
                                Central Dojo Headquarters
                            </h3>
                        </div>

                        <div class="d-flex flex-column gap-4" style="font-size:0.8rem;">

                            <!-- Physical Address -->
                            <div class="d-flex align-items-start gap-3">
                                <div class="p-2 rounded-3 flex-shrink-0"
                                     style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="fw-bold text-white d-block mb-1">Physical Location</span>
                                    <p class="mb-0" style="color:#a3a3a3; line-height:1.6;">
                                        <?= esc($officeAddress) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Phone: Only if verified in DB -->
                            <?php if ($officePhone): ?>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 rounded-3 flex-shrink-0"
                                         style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-white d-block mb-1">Telephone Helpline</span>
                                        <a href="tel:<?= escAttr(preg_replace('/[^0-9+]/', '', $officePhone)) ?>"
                                           class="text-decoration-none" style="color:#d4d4d4; font-family:monospace;"
                                           onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#d4d4d4'">
                                            <?= esc($officePhone) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Email: Only if verified in DB -->
                            <?php if ($officeEmail): ?>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 rounded-3 flex-shrink-0"
                                         style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-white d-block mb-1">Official Inquiries</span>
                                        <a href="mailto:<?= escAttr($officeEmail) ?>"
                                           class="text-decoration-none" style="color:#d4d4d4;"
                                           onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#d4d4d4'">
                                            <?= esc($officeEmail) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Timing: Only if verified in DB -->
                            <?php if ($officeTiming): ?>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="p-2 rounded-3 flex-shrink-0"
                                         style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-white d-block mb-1">Operating Hours</span>
                                        <p class="mb-0" style="color:#a3a3a3;">
                                            <?= esc($officeTiming) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- Security Badge Note -->
                    <div class="p-3 rounded-3 mt-4 d-flex align-items-center gap-3"
                         style="background:#0a0a0a; border:1px solid #222; font-size:0.75rem; color:#737373;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                            <path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/>
                        </svg>
                        <span>All inquiries stored securely in MySQL and reviewed directly by the Sensei.</span>
                    </div>

                </div>
            </div>

            <!-- ── Right Column: Online Inquiry Form (7 cols) ─────────── -->
            <div class="col-12 col-lg-7">
                <div class="rounded-4 p-4 p-sm-5"
                     style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a; box-shadow:0 24px 48px rgba(0,0,0,0.5);">

                    <div class="pb-3 mb-4" style="border-bottom:1px solid #262626;">
                        <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#DC2626; display:block; margin-bottom:4px;">
                            Online Inquiry Form
                        </span>
                        <h3 class="fw-black text-uppercase text-white mb-0" style="font-size:1.25rem;">
                            Send A Message To The Dojo
                        </h3>
                    </div>

                    <!-- ── Success State Container ────────────────────────── -->
                    <div id="contactSuccessState"
                         class="p-5 rounded-4 text-center"
                         style="background:#111; border:1px solid #2a2a2a; <?= $formSuccess ? 'display:block;' : 'display:none;' ?>">
                        <div class="mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#10B981" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                        </div>
                        <h3 class="fw-black text-uppercase text-white mb-2" style="font-size:1.25rem;">
                            Enquiry Received!
                        </h3>
                        <p style="color:#a3a3a3; font-size:0.82rem; line-height:1.7; max-width:380px; margin:0 auto 1.5rem;">
                            Thank you for reaching out to Armour Karate Academy. Our admissions and dojo coordination team will get in touch with you shortly.
                        </p>
                        <button type="button"
                                id="btnSendAnother"
                                class="btn px-4 py-2 rounded-3 text-white fw-bold text-uppercase"
                                style="font-size:0.75rem; letter-spacing:0.08em; background:#262626; border:1px solid #3a3a3a;">
                            Send Another Message
                        </button>
                    </div>

                    <!-- ── Active Form Container ──────────────────────────── -->
                    <div id="contactFormWrap" style="<?= $formSuccess ? 'display:none;' : 'display:block;' ?>">

                        <!-- Error Alert -->
                        <div id="contactAlert"
                             class="p-3 rounded-3 mb-4 d-flex align-items-center gap-2"
                             style="background:rgba(127,29,29,0.5); border:1px solid #dc2626; color:#fca5a5; font-size:0.8rem; <?= !empty($formErrors) ? 'display:flex;' : 'display:none;' ?>"
                             role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#fca5a5" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                            <span id="contactAlertText"><?= esc(implode(' ', $formErrors)) ?></span>
                        </div>

                        <form id="contactForm" method="POST" action="<?= BASE_PATH ?>/contact" novalidate>
                            <!-- CSRF Field -->
                            <?= csrfField() ?>

                            <!-- Honeypot Field (hidden from genuine users) -->
                            <div style="display:none !important;" aria-hidden="true">
                                <label for="contact_hp_check">Leave this field blank</label>
                                <input type="text" name="contact_hp_check" id="contact_hp_check" tabindex="-1" autocomplete="off" value="">
                            </div>

                            <!-- Full Name -->
                            <div class="mb-3">
                                <label for="form_name" class="d-block fw-bold text-uppercase text-white mb-1" style="font-size:0.7rem; letter-spacing:0.08em;">
                                    Your Full Name <span style="color:#DC2626;">*</span>
                                </label>
                                <input type="text"
                                       id="form_name"
                                       name="name"
                                       required
                                       maxlength="100"
                                       value="<?= escAttr($formData['name']) ?>"
                                       placeholder="e.g. Ramesh Patel"
                                       class="form-control bg-transparent text-white border-secondary rounded-3 px-3 py-2"
                                       style="background:rgba(10,10,10,0.8) !important; border-color:#333 !important; font-size:0.875rem;">
                            </div>

                            <!-- Phone & Email (2 cols on sm+) -->
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label for="form_phone" class="d-block fw-bold text-uppercase text-white mb-1" style="font-size:0.7rem; letter-spacing:0.08em;">
                                        Mobile Number <span style="color:#DC2626;">*</span>
                                    </label>
                                    <input type="tel"
                                           id="form_phone"
                                           name="phone"
                                           required
                                           maxlength="10"
                                           pattern="[0-9]{10}"
                                           value="<?= escAttr($formData['phone']) ?>"
                                           placeholder="10-digit phone"
                                           class="form-control bg-transparent text-white border-secondary rounded-3 px-3 py-2"
                                           style="background:rgba(10,10,10,0.8) !important; border-color:#333 !important; font-size:0.875rem;">
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="form_email" class="d-block fw-bold text-uppercase text-white mb-1" style="font-size:0.7rem; letter-spacing:0.08em;">
                                        Email Address <span style="color:#DC2626;">*</span>
                                    </label>
                                    <input type="email"
                                           id="form_email"
                                           name="email"
                                           required
                                           maxlength="255"
                                           value="<?= escAttr($formData['email']) ?>"
                                           placeholder="name@example.com"
                                           class="form-control bg-transparent text-white border-secondary rounded-3 px-3 py-2"
                                           style="background:rgba(10,10,10,0.8) !important; border-color:#333 !important; font-size:0.875rem;">
                                </div>
                            </div>

                            <!-- Subject -->
                            <div class="mb-3">
                                <label for="form_subject" class="d-block fw-bold text-uppercase text-white mb-1" style="font-size:0.7rem; letter-spacing:0.08em;">
                                    Subject of Enquiry <span style="color:#DC2626;">*</span>
                                </label>
                                <input type="text"
                                       id="form_subject"
                                       name="subject"
                                       required
                                       maxlength="200"
                                       value="<?= escAttr($formData['subject']) ?>"
                                       placeholder="e.g. Admission inquiry for 8-year-old child"
                                       class="form-control bg-transparent text-white border-secondary rounded-3 px-3 py-2"
                                       style="background:rgba(10,10,10,0.8) !important; border-color:#333 !important; font-size:0.875rem;">
                            </div>

                            <!-- Message -->
                            <div class="mb-4">
                                <label for="form_message" class="d-block fw-bold text-uppercase text-white mb-1" style="font-size:0.7rem; letter-spacing:0.08em;">
                                    Your Message <span style="color:#DC2626;">*</span>
                                </label>
                                <textarea id="form_message"
                                          name="message"
                                          rows="4"
                                          required
                                          maxlength="2000"
                                          placeholder="Mention preferred location, prior martial arts experience, or specific questions..."
                                          class="form-control bg-transparent text-white border-secondary rounded-3 px-3 py-2"
                                          style="background:rgba(10,10,10,0.8) !important; border-color:#333 !important; font-size:0.875rem;"><?= esc($formData['message']) ?></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit"
                                    id="btnContactSubmit"
                                    class="w-100 py-3 rounded-3 fw-bold text-uppercase text-white d-flex align-items-center justify-content-center gap-2"
                                    style="font-size:0.8rem; letter-spacing:0.08em; background:#DC2626; border:none; box-shadow:0 6px 20px rgba(220,38,38,0.35); transition:all 0.2s;">
                                <span id="btnSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none;"></span>
                                <span id="btnText">Submit Enquiry</span>
                                <svg id="btnIcon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.493-7.493Z"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        </div><!-- /.row -->

    </div><!-- /.container -->
</div>

<script>
(function () {
    'use strict';

    const form         = document.getElementById('contactForm');
    const formWrap     = document.getElementById('contactFormWrap');
    const successWrap  = document.getElementById('contactSuccessState');
    const btnSubmit    = document.getElementById('btnContactSubmit');
    const btnSpinner   = document.getElementById('btnSpinner');
    const btnText      = document.getElementById('btnText');
    const btnIcon      = document.getElementById('btnIcon');
    const btnSendMore  = document.getElementById('btnSendAnother');
    const alertBox     = document.getElementById('contactAlert');
    const alertText    = document.getElementById('contactAlertText');

    function showAlert(msg) {
        alertText.textContent = msg;
        alertBox.style.display = 'flex';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        alertBox.style.display = 'none';
        alertText.textContent = '';
    }

    function setLoading(isLoading) {
        btnSubmit.disabled = isLoading;
        btnSpinner.style.display = isLoading ? 'inline-block' : 'none';
        btnIcon.style.display    = isLoading ? 'none' : 'inline-block';
        btnText.textContent      = isLoading ? 'Sending Message...' : 'Submit Enquiry';
    }

    if (btnSendMore) {
        btnSendMore.addEventListener('click', function () {
            form.reset();
            hideAlert();
            successWrap.style.display = 'none';
            formWrap.style.display    = 'block';
        });
    }

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideAlert();

            const name    = form.name.value.trim();
            const phone   = form.phone.value.trim();
            const email   = form.email.value.trim();
            const subject = form.subject.value.trim();
            const message = form.message.value.trim();

            // Client-side quick checks for usability
            if (!name || name.length < 2) {
                showAlert('Please enter your full name (at least 2 characters).');
                form.name.focus();
                return;
            }

            const cleanPhone = phone.replace(/[^0-9]/g, '');
            if (cleanPhone.length !== 10) {
                showAlert('Please enter a valid 10-digit mobile number.');
                form.phone.focus();
                return;
            }

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showAlert('Please enter a valid email address.');
                form.email.focus();
                return;
            }

            if (!subject || subject.length < 3) {
                showAlert('Please enter a subject (at least 3 characters).');
                form.subject.focus();
                return;
            }

            if (!message || message.length < 10) {
                showAlert('Please enter a message (at least 10 characters).');
                form.message.focus();
                return;
            }

            setLoading(true);

            try {
                const formData = new FormData(form);
                formData.append('is_ajax', '1');

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    showAlert(data.error || 'Failed to submit enquiry. Please check your details and try again.');
                    setLoading(false);
                    return;
                }

                // If new CSRF token returned, update hidden field
                if (data.new_csrf) {
                    const csrfInput = form.querySelector('input[name="_csrf_token"]');
                    if (csrfInput) csrfInput.value = data.new_csrf;
                }

                // Transition to Success state
                formWrap.style.display    = 'none';
                successWrap.style.display = 'block';

            } catch (err) {
                showAlert('Network error occurred. Please check your connection and try again.');
            } finally {
                setLoading(false);
            }
        });
    }
}());
</script>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
