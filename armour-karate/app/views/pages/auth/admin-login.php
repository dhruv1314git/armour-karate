<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * High-Security Admin Portal Login
 * app/views/pages/auth/admin-login.php
 *
 * Faithfully recreates the original Next.js admin login page:
 *  - High security dark aesthetic with red ambient glow
 *  - Centered card with ShieldAlert emblem
 *  - Enforces role === 'ADMIN' verification
 *  - Constant-time verification to prevent timing attacks
 *  - IP rate limiting & CSRF token protection
 *  - Redirects to /admin/dashboard upon successful verification
 */

declare(strict_types=1);

requireGuest();

$pageTitle       = 'Karate Sir / Admin Portal | Armour Karate Academy';
$metaDescription = 'High security administrative command access for Armour Martial Arts Gujju Karate Association.';

$errorMessage = null;
$emailInput   = '';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || (!empty($_POST['is_ajax']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on admin login attempt. IP: ' . getClientIp());

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
    $rateCheck = checkRateLimit('admin_login', getClientIp(), 5, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Admin login rate limit exceeded by IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Too many failed login attempts. Security lock engaged for 60 seconds.']);
            exit;
        }

        $errorMessage = 'Too many failed login attempts. Security lock engaged for 60 seconds.';
        goto renderView;
    }

    // 3. Capture input
    $emailInput    = trim((string)($_POST['email'] ?? ''));
    $passwordInput = (string)($_POST['password'] ?? '');

    if ($emailInput === '' || $passwordInput === '') {
        $errorMessage = 'Please enter both your administrator email and password.';
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 4. Admin lookup
    $pdo = db();
    $adminUser = null;

    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND role = 'ADMIN' LIMIT 1"
        );
        $stmt->execute([$emailInput]);
        $adminUser = $stmt->fetch();
    } catch (PDOException $e) {
        appLog('ERROR', 'Database error during admin login lookup: ' . $e->getMessage());
        $errorMessage = 'An error occurred during authentication. Access denied.';
        goto renderView;
    }

    // 5. Verify credentials (constant-time verification)
    $authSuccess = false;
    if ($adminUser && !empty($adminUser['password_hash'])) {
        $authSuccess = verifyPassword($passwordInput, $adminUser['password_hash']);
    } else {
        // Run dummy verification to resist timing attacks
        verifyPassword('dummy_password_timing_defense', '$2y$12$e80y6lqG1mYy50Qp3z0k0.abcdefghijklmnopqrstuvwxyz1234567890');
    }

    if (!$authSuccess || !$adminUser) {
        appLog('SECURITY', "Failed ADMIN login attempt for email: '{$emailInput}'. IP: " . getClientIp());
        $errorMessage = 'Invalid administrator credentials. Access denied.';

        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }

        goto renderView;
    }

    // 6. Login admin into session
    loginAdmin($adminUser);

    $redirectUrl = BASE_PATH . '/admin/dashboard';

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

<div class="min-vh-100 d-flex align-items-center justify-content-center p-3 p-sm-4 position-relative overflow-hidden"
     style="background: #070708; color: #FFFFFF;">

    <!-- Red Ambient Glow -->
    <div class="position-absolute top-50 start-50 translate-middle rounded-circle pointer-events-none"
         style="width: 500px; height: 500px; background: rgba(220, 38, 38, 0.1); filter: blur(150px); pointer-events: none;"></div>

    <div class="rounded-4 p-4 p-sm-5 position-relative z-1 shadow-2xl"
         style="max-width: 460px; width: 100%; background: rgba(20, 20, 23, 0.94); border: 1px solid #27272a; backdrop-filter: blur(16px);">

        <!-- Header -->
        <div class="text-center mb-4">
            <div class="position-relative d-inline-block mx-auto mb-3" style="width: 72px; height: 72px;">
                <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="72" height="72" class="rounded-circle"
                     style="border: 2px solid #DC2626; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.35);">
                <span class="position-absolute bottom-0 end-0 p-1 rounded-circle d-flex align-items-center justify-content-center"
                      style="background: #DC2626; color: #fff; width: 24px; height: 24px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/>
                    </svg>
                </span>
            </div>

            <h1 class="fs-4 fw-black text-uppercase text-white mb-1" style="letter-spacing: -0.01em;">
                Karate Sir / Admin Portal
            </h1>
            <p class="text-secondary small mb-0" style="font-size: 0.78rem;">
                Authorized Personnel Only &middot; High Security Area
            </p>
        </div>

        <!-- Error Banner -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 p-3 mb-4 rounded-3 border-0"
                 style="background: rgba(127, 29, 29, 0.6); border: 1px solid rgba(220, 38, 38, 0.8) !important; color: #fca5a5; font-size: 0.85rem;" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="flex-shrink-0" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                </svg>
                <div><?= esc($errorMessage) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?= BASE_PATH ?>/admin/login" method="POST" id="adminLoginForm" class="d-flex flex-column gap-3">
            <?= csrfField() ?>

            <!-- Email Input -->
            <div>
                <label for="adminEmail" class="form-label text-uppercase fw-bold text-light" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                    Admin Email
                </label>
                <div class="input-group">
                    <span class="input-group-text border-end-0" style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                        </svg>
                    </span>
                    <input type="email"
                           class="form-control text-white border-start-0"
                           id="adminEmail"
                           name="email"
                           required
                           autocomplete="email"
                           value="<?= escAttr($emailInput) ?>"
                           placeholder="sensei@armourkarate.com"
                           style="background: #0d0d0f; border-color: #27272a; font-size: 0.88rem; padding: 0.75rem;">
                </div>
            </div>

            <!-- Password Input -->
            <div>
                <label for="adminPassword" class="form-label text-uppercase fw-bold text-light" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                    Admin Password
                </label>
                <div class="input-group">
                    <span class="input-group-text border-end-0" style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                        </svg>
                    </span>
                    <input type="password"
                           class="form-control text-white border-start-0 border-end-0"
                           id="adminPassword"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="••••••••••••"
                           style="background: #0d0d0f; border-color: #27272a; font-size: 0.88rem; padding: 0.75rem;">
                    <button type="button"
                            class="btn border-start-0"
                            id="btnToggleAdminPassword"
                            onclick="toggleAdminPasswordVisibility()"
                            aria-label="Toggle password visibility"
                            style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                        <svg id="adminEyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                        </svg>
                        <svg id="adminEyeSlashIcon" class="d-none" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
                            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="btn btn-danger w-100 py-3 fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2 mt-2"
                    style="background: #DC2626; border-color: #DC2626; font-size: 0.85rem; letter-spacing: 0.06em; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.4);">
                <span>Access Admin Command</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
            </button>
        </form>

        <!-- Navigation Links -->
        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: #27272a !important; font-size: 0.78rem;">
            <a href="<?= BASE_PATH ?>/" class="text-secondary text-decoration-none">
                &larr; Back to Public Website
            </a>
            <a href="<?= BASE_PATH ?>/login" class="text-danger text-decoration-none fw-semibold">
                Student Login &rarr;
            </a>
        </div>

    </div>

</div>

<script>
function toggleAdminPasswordVisibility() {
    const pwd = document.getElementById('adminPassword');
    const eyeIcon = document.getElementById('adminEyeIcon');
    const eyeSlashIcon = document.getElementById('adminEyeSlashIcon');
    
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
