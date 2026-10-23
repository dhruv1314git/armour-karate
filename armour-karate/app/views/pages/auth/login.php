<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Portal Login
 * app/views/pages/auth/login.php
 *
 * Faithfully recreates the original Next.js student login page:
 *  - Left martial brand panel with Goju-Ryu typography and badge
 *  - Right form panel with dark card aesthetic
 *  - Dual identifier login: accepts registered Email OR assigned Student ID
 *  - Password visibility toggle
 *  - Generic enumeration-safe error reporting
 *  - Status-based routing: ACTIVE -> /student/dashboard, PENDING -> /registration-pending,
 *    REJECTED -> /registration-rejected, INACTIVE -> /account-inactive
 *  - IP rate limiting & CSRF protection
 */

declare(strict_types=1);

requireGuest();

$pageTitle       = 'Student Sign In | Armour Karate Academy';
$metaDescription = 'Access your Armour Martial Arts student portal to track your belt journey, view fees, and register for tournaments.';

$errorMessage = null;
$identifierInput = '';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || (!empty($_POST['is_ajax']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on student login attempt. IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid or expired session security token. Please refresh and try again.']);
            exit;
        }

        $errorMessage = 'Invalid or expired session security token. Please refresh and try again.';
        goto renderView;
    }

    // 2. IP Rate Limiting (5 attempts per minute)
    $rateCheck = checkRateLimit('student_login', getClientIp(), 5, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Student login rate limit exceeded by IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Too many login attempts. Please wait a minute before trying again.']);
            exit;
        }

        $errorMessage = 'Too many login attempts. Please wait a minute before trying again.';
        goto renderView;
    }

    // 3. Capture input
    $identifierInput = trim((string)($_POST['identifier'] ?? ''));
    $passwordInput   = (string)($_POST['password'] ?? '');

    if ($identifierInput === '' || $passwordInput === '') {
        $errorMessage = 'Please enter both your email/student ID and password.';
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 4. Identifier resolution and user lookup
    $pdo = db();
    $userData = null;

    try {
        if (str_contains($identifierInput, '@')) {
            // Lookup by registered email
            $stmt = $pdo->prepare(
                "SELECT u.*, 
                        sp.id AS student_profile_id, 
                        sp.student_id, 
                        sp.full_name AS student_name, 
                        sp.status AS student_status, 
                        sp.current_belt_id,
                        sp.profile_photo_url,
                        b.name AS belt_name, 
                        b.color AS belt_color
                 FROM users u
                 LEFT JOIN student_profiles sp ON sp.user_id = u.id
                 LEFT JOIN belts b ON b.id = sp.current_belt_id
                 WHERE LOWER(u.email) = LOWER(?) AND u.role = 'STUDENT'
                 LIMIT 1"
            );
            $stmt->execute([$identifierInput]);
            $userData = $stmt->fetch();
        } else {
            // Lookup by assigned Student ID (e.g. KAR-2024-0001)
            $cleanStudentId = strtoupper($identifierInput);
            $stmt = $pdo->prepare(
                "SELECT u.*, 
                        sp.id AS student_profile_id, 
                        sp.student_id, 
                        sp.full_name AS student_name, 
                        sp.status AS student_status, 
                        sp.current_belt_id,
                        sp.profile_photo_url,
                        b.name AS belt_name, 
                        b.color AS belt_color
                 FROM student_profiles sp
                 JOIN users u ON u.id = sp.user_id
                 LEFT JOIN belts b ON b.id = sp.current_belt_id
                 WHERE UPPER(sp.student_id) = ? AND u.role = 'STUDENT'
                 LIMIT 1"
            );
            $stmt->execute([$cleanStudentId]);
            $userData = $stmt->fetch();
        }
    } catch (PDOException $e) {
        appLog('ERROR', 'Database error during student login lookup: ' . $e->getMessage());
        $errorMessage = 'An error occurred during authentication. Please try again.';
        goto renderView;
    }

    // 5. Verify credentials (constant-time verification)
    $authSuccess = false;
    if ($userData && !empty($userData['password_hash'])) {
        $authSuccess = verifyPassword($passwordInput, $userData['password_hash']);
    } else {
        // Run dummy verification to resist timing attacks
        verifyPassword('dummy_password_timing_defense', '$2y$12$e80y6lqG1mYy50Qp3z0k0.abcdefghijklmnopqrstuvwxyz1234567890');
    }

    if (!$authSuccess || !$userData) {
        appLog('SECURITY', "Failed student login attempt for identifier: '{$identifierInput}'. IP: " . getClientIp());
        $errorMessage = 'Invalid credentials. Please verify your email/student ID and password.';

        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }

        goto renderView;
    }

    // 6. Login student into session
    $profileData = [
        'id'                => (int)($userData['student_profile_id'] ?? 0),
        'student_id'        => $userData['student_id'] ?? '',
        'full_name'         => $userData['student_name'] ?? $userData['name'],
        'status'            => $userData['student_status'] ?? 'PENDING',
        'profile_photo_url' => $userData['profile_photo_url'] ?? $userData['image'] ?? null,
    ];

    $beltData = null;
    if (!empty($userData['belt_name'])) {
        $beltData = [
            'name'  => $userData['belt_name'],
            'color' => $userData['belt_color'] ?? '#ffffff',
        ];
    }

    loginStudent($userData, $profileData, $beltData);

    // 7. Route according to student account status
    $status = $profileData['status'];
    $redirectUrl = match ($status) {
        'ACTIVE'   => BASE_PATH . '/student/dashboard',
        'PENDING'  => BASE_PATH . '/registration-pending',
        'REJECTED' => BASE_PATH . '/registration-rejected',
        'INACTIVE' => BASE_PATH . '/account-inactive',
        default    => BASE_PATH . '/login',
    };

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect_url' => $redirectUrl]);
        exit;
    }

    safeRedirect($redirectUrl);
}

renderView:
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="min-vh-100 d-flex flex-column flex-md-row" style="background:#0A0A0A; color:#FFFFFF;">

    <!-- Left Martial Brand Panel (Desktop) -->
    <div class="d-none d-md-flex col-md-5 col-lg-6 flex-column justify-content-between p-5 position-relative overflow-hidden"
         style="background: linear-gradient(135deg, #120505 0%, #1a0808 50%, #0A0A0A 100%); border-right: 1px solid #1f1f23;">

        <!-- Ambient Red Glow -->
        <div class="position-absolute top-0 end-0 rounded-circle pointer-events-none"
             style="width: 380px; height: 380px; background: rgba(220, 38, 38, 0.12); filter: blur(140px); pointer-events: none;"></div>

        <!-- Brand Header -->
        <div class="position-relative z-1">
            <a href="<?= BASE_PATH ?>/" class="d-inline-flex align-items-center gap-3 text-decoration-none">
                <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="56" height="56" class="rounded-circle"
                     style="border: 2px solid rgba(220, 38, 38, 0.4);">
                <div>
                    <span class="fs-5 fw-black text-uppercase text-white d-block" style="letter-spacing: 0.05em;">Armour Karate</span>
                    <span class="text-danger fw-bold text-uppercase d-block" style="font-size: 0.72rem; letter-spacing: 0.14em;">Goju-Ryu Karate-Do</span>
                </div>
            </a>
        </div>

        <!-- Center Martial Philosophy -->
        <div class="position-relative z-1 my-auto py-4" style="max-width: 480px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                 style="background: rgba(127, 29, 29, 0.4); border: 1px solid rgba(220, 38, 38, 0.4); color: #f87171; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/>
                </svg>
                Registered Student Portal
            </div>

            <h1 class="text-uppercase fw-black text-white mb-3" style="font-size: 2.6rem; line-height: 1.15; letter-spacing: -0.02em;">
                Discipline. Honor. <br>
                <span class="text-danger">Warrior Spirit.</span>
            </h1>

            <p style="color: #a3a3a3; font-size: 0.95rem; line-height: 1.7;">
                Access your personalized student dashboard, monitor belt journey examinations, verify monthly fee clearances, and register for upcoming national tournaments.
            </p>
        </div>

        <!-- Footer Copyright -->
        <div class="position-relative z-1 pt-3 border-top" style="border-color: rgba(255, 255, 255, 0.08) !important; font-size: 0.75rem; color: #737373;">
            &copy; <?= date('Y') ?> Armour Martial Arts Gujju Karate Association. All rights reserved.
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="col-12 col-md-7 col-lg-6 d-flex flex-column justify-content-center align-items-center p-4 p-sm-5 position-relative">
        
        <div class="w-100" style="max-width: 440px;">

            <!-- Mobile Brand Header -->
            <div class="d-md-none text-center mb-4">
                <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="64" height="64" class="rounded-circle mb-2"
                     style="border: 2px solid rgba(220, 38, 38, 0.4);">
                <h2 class="fs-4 fw-black text-uppercase text-white mb-0">Student Portal</h2>
                <p class="text-secondary small mb-0">Armour Martial Arts Gujju Karate Association</p>
            </div>

            <!-- Form Header (Desktop) -->
            <div class="d-none d-md-block mb-4">
                <h2 class="fs-3 fw-black text-uppercase text-white mb-1">Student Sign In</h2>
                <p style="color: #a3a3a3; font-size: 0.88rem;">Enter your registered credentials to access your karate portal.</p>
            </div>

            <!-- Error Banner -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 p-3 mb-4 rounded-3 border-0"
                     style="background: rgba(127, 29, 29, 0.5); border: 1px solid rgba(220, 38, 38, 0.6) !important; color: #fca5a5; font-size: 0.85rem;" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                    <div><?= esc($errorMessage) ?></div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= BASE_PATH ?>/login" method="POST" id="studentLoginForm" class="d-flex flex-column gap-3">
                <?= csrfField() ?>

                <!-- Identifier Input -->
                <div>
                    <label for="identifier" class="form-label text-uppercase fw-bold text-light" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Email Address or Student ID
                    </label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background:#141416; border-color:#2a2a2e; color:#737373;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                        </span>
                        <input type="text"
                               class="form-control text-white border-start-0"
                               id="identifier"
                               name="identifier"
                               required
                               autocomplete="username"
                               value="<?= escAttr($identifierInput) ?>"
                               placeholder="student@example.com or KAR-2024-0001"
                               style="background:#141416; border-color:#2a2a2e; font-size:0.9rem; padding: 0.75rem;">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label text-uppercase fw-bold text-light mb-0" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            Password
                        </label>
                        <a href="<?= BASE_PATH ?>/forgot-password" class="text-decoration-none text-danger fw-semibold" style="font-size: 0.75rem;">
                            Forgot password?
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background:#141416; border-color:#2a2a2e; color:#737373;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                            </svg>
                        </span>
                        <input type="password"
                               class="form-control text-white border-start-0 border-end-0"
                               id="password"
                               name="password"
                               required
                               autocomplete="current-password"
                               placeholder="••••••••"
                               style="background:#141416; border-color:#2a2a2e; font-size:0.9rem; padding: 0.75rem;">
                        <button type="button"
                                class="btn border-start-0"
                                id="btnTogglePassword"
                                onclick="togglePasswordVisibility()"
                                aria-label="Toggle password visibility"
                                style="background:#141416; border-color:#2a2a2e; color:#737373;">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                            <svg id="eyeSlashIcon" class="d-none" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
                                <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                                <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        id="btnSubmit"
                        class="btn btn-danger w-100 py-3 fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2 mt-2"
                        style="background: #DC2626; border-color: #DC2626; font-size: 0.85rem; letter-spacing: 0.05em; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.35);">
                    <span id="btnSubmitText">Sign In to Portal</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </button>
            </form>

            <!-- Register Navigation Link -->
            <div class="text-center pt-4 mt-3 border-top" style="border-color: #222 !important; font-size: 0.85rem; color: #a3a3a3;">
                Not registered yet?
                <a href="<?= BASE_PATH ?>/register" class="fw-bold text-danger text-decoration-none ms-1">
                    Enroll in Academy
                </a>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-3" style="font-size: 0.78rem;">
                <a href="<?= BASE_PATH ?>/" class="text-secondary text-decoration-none">
                    &larr; Back to Public Website
                </a>
                <span class="text-secondary mx-2">&middot;</span>
                <a href="<?= BASE_PATH ?>/admin/login" class="text-secondary text-decoration-none">
                    Admin Portal
                </a>
            </div>

        </div>

    </div>

</div>

<script>
function togglePasswordVisibility() {
    const pwd = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeSlashIcon = document.getElementById('eyeSlashIcon');
    
    if (pwd.type === 'password') {
        pwd.type = 'text';
        eyeIcon.classList.add('d-none');
        eyeSlashIcon.classList.remove('d-none');
    } else {
        pwd.type = 'password';
        eyeIcon.classList.remove('d-none');
        eyeSlashIcon.classList.add('d-none');
    }
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
