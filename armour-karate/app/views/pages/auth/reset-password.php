<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Password Reset Completion
 * app/views/pages/auth/reset-password.php
 *
 * Faithfully recreates the original Next.js reset-password page:
 *  - Verifies 64-char token against SHA-256 hash in password_reset_tokens
 *  - Enforces unexpired (expires_at > NOW()) and unused (used = 0) criteria
 *  - Password strength validation (min 8 chars, 1 uppercase, 1 number)
 *  - Atomic database transaction updating password_hash and marking token used
 *  - Success screen with redirection to /login
 *  - IP rate limiting & CSRF protection
 */

declare(strict_types=1);

requireGuest();

$pageTitle       = 'Create New Password | Armour Karate Academy';
$metaDescription = 'Establish a new secure password for your Armour Karate student or administrator account.';

$pdo = db();

$tokenParam   = trim((string)($_GET['token'] ?? ($_POST['token'] ?? '')));
$tokenValid   = false;
$tokenRow     = null;
$errorMessage = null;
$success      = false;

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || (!empty($_POST['is_ajax']));

// Verify Token
if ($tokenParam !== '') {
    $tokenHash = hash('sha256', $tokenParam);
    try {
        $stmtToken = $pdo->prepare(
            "SELECT prt.*, u.id AS uid, u.name, u.email 
             FROM password_reset_tokens prt
             JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = ? AND prt.used = 0 AND prt.expires_at > NOW()
             LIMIT 1"
        );
        $stmtToken->execute([$tokenHash]);
        $tokenRow = $stmtToken->fetch();
        if ($tokenRow) {
            $tokenValid = true;
        }
    } catch (PDOException $e) {
        appLog('ERROR', 'Error verifying password reset token: ' . $e->getMessage());
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on reset password attempt. IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid or expired session security token. Please refresh and try again.']);
            exit;
        }

        $errorMessage = 'Invalid or expired session security token. Please refresh and try again.';
        goto renderView;
    }

    // 2. IP Rate Limiting
    $rateCheck = checkRateLimit('reset_password', getClientIp(), 5, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Reset password rate limit exceeded by IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Too many attempts. Please wait a minute before trying again.']);
            exit;
        }

        $errorMessage = 'Too many attempts. Please wait a minute before trying again.';
        goto renderView;
    }

    // 3. Token Check
    if (!$tokenValid || !$tokenRow) {
        $errorMessage = 'Invalid or expired reset token. Please request a new password reset link.';
        if ($isAjax) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 4. Validate Passwords
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirmPassword'] ?? '');

    $validator = new Validator(['password' => $password, 'confirm_password' => $confirmPassword]);
    $validator->required('password', 'New password')
              ->passwordStrength('password', 'New password')
              ->required('confirm_password', 'Password confirmation')
              ->matches('confirm_password', 'password', 'Password confirmation');

    if ($validator->fails()) {
        $errorMessage = $validator->firstError();
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 5. Update Password inside Transaction
    try {
        $pdo->beginTransaction();

        $newHash = hashPassword($password);
        $userId  = (int) $tokenRow['uid'];
        $tokenId = (int) $tokenRow['id'];

        // Update user's password
        $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$newHash, $userId]);

        // Mark token as used
        $stmtMark = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
        $stmtMark->execute([$tokenId]);

        // Revoke any other unexpired reset tokens for this user
        $stmtRevokeAll = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
        $stmtRevokeAll->execute([$userId]);

        $pdo->commit();

        appLog('SECURITY', "Password successfully reset for user ID: {$userId} ({$tokenRow['email']}). IP: " . getClientIp());
        $success = true;

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect_url' => BASE_PATH . '/login']);
            exit;
        }

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        appLog('ERROR', 'Failed to update password: ' . $e->getMessage());
        $errorMessage = 'An error occurred while updating your password. Please try again.';

        if ($isAjax) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
    }
}

renderView:
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center p-3 p-sm-4 position-relative overflow-hidden"
     style="background: #0A0A0A; color: #FFFFFF;">

    <!-- Red Ambient Glow -->
    <div class="position-absolute top-50 start-50 translate-middle rounded-circle pointer-events-none"
         style="width: 500px; height: 500px; background: rgba(220, 38, 38, 0.08); filter: blur(140px); pointer-events: none;"></div>

    <div class="rounded-4 p-4 p-sm-5 position-relative z-1 shadow-2xl"
         style="max-width: 440px; width: 100%; background: rgba(20, 20, 22, 0.94); border: 1px solid #27272a; backdrop-filter: blur(16px);">

        <!-- Header -->
        <div class="text-center mb-4">
            <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" width="60" height="60" class="rounded-circle mb-3"
                 style="border: 2px solid rgba(220, 38, 38, 0.4);">
            <h1 class="fs-4 fw-black text-uppercase text-white mb-1" style="letter-spacing: -0.01em;">
                Create New Password
            </h1>
            <p class="text-secondary small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                Choose a strong password to safeguard your karate account.
            </p>
        </div>

        <!-- Success Screen -->
        <?php if ($success): ?>
            <div class="p-4 rounded-3 text-center mb-3"
                 style="background: #0d0d0f; border: 1px solid #27272a;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <h2 class="fs-5 fw-bold text-white mb-2">Password Reset Successful!</h2>
                <p style="color: #a3a3a3; font-size: 0.85rem; line-height: 1.6;" class="mb-4">
                    Your credentials have been securely updated. You can now log into your portal with your new password.
                </p>
                <a href="<?= BASE_PATH ?>/login" class="btn btn-danger w-100 py-2 fw-bold text-uppercase" style="background:#dc2626; border-color:#dc2626; font-size: 0.82rem; letter-spacing: 0.05em;">
                    Proceed to Sign In &rarr;
                </a>
            </div>

        <!-- Invalid or Expired Token State -->
        <?php elseif (!$tokenValid): ?>
            <div class="p-4 rounded-3 text-center mb-3"
                 style="background: #0d0d0f; border: 1px solid #27272a;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width: 48px; height: 48px; background: rgba(220, 38, 38, 0.15); border: 1px solid #dc2626; color: #f87171;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                </div>
                <h2 class="fs-5 fw-bold text-white mb-2">Invalid or Expired Link</h2>
                <p style="color: #a3a3a3; font-size: 0.85rem; line-height: 1.6;" class="mb-4">
                    This password reset link is invalid, has already been used, or has expired. Please request a fresh reset link.
                </p>
                <a href="<?= BASE_PATH ?>/forgot-password" class="btn btn-danger w-100 py-2 fw-bold text-uppercase" style="background:#dc2626; border-color:#dc2626; font-size: 0.82rem; letter-spacing: 0.05em;">
                    Request New Reset Link
                </a>
                <div class="mt-3">
                    <a href="<?= BASE_PATH ?>/login" class="text-secondary text-decoration-none small">
                        &larr; Return to Login
                    </a>
                </div>
            </div>

        <!-- Valid Token Form -->
        <?php else: ?>

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

            <form action="<?= BASE_PATH ?>/reset-password?token=<?= urlencode($tokenParam) ?>" method="POST" id="resetPasswordForm" class="d-flex flex-column gap-3">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= escAttr($tokenParam) ?>">

                <!-- New Password -->
                <div>
                    <label for="password" class="form-label text-uppercase fw-bold text-light" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                        New Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                            </svg>
                        </span>
                        <input type="password"
                               class="form-control text-white border-start-0 border-end-0"
                               id="password"
                               name="password"
                               required
                               placeholder="Min 8 chars, 1 uppercase, 1 digit"
                               style="background: #0d0d0f; border-color: #27272a; font-size: 0.88rem; padding: 0.75rem;">
                        <button type="button"
                                class="btn border-start-0"
                                onclick="toggleFieldVisibility('password')"
                                aria-label="Toggle password visibility"
                                style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirmPassword" class="form-label text-uppercase fw-bold text-light" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                        Confirm New Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
                            </svg>
                        </span>
                        <input type="password"
                               class="form-control text-white border-start-0 border-end-0"
                               id="confirmPassword"
                               name="confirmPassword"
                               required
                               placeholder="Repeat new password"
                               style="background: #0d0d0f; border-color: #27272a; font-size: 0.88rem; padding: 0.75rem;">
                        <button type="button"
                                class="btn border-start-0"
                                onclick="toggleFieldVisibility('confirmPassword')"
                                aria-label="Toggle password visibility"
                                style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-danger w-100 py-3 fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2 mt-2"
                        style="background: #DC2626; border-color: #DC2626; font-size: 0.82rem; letter-spacing: 0.06em; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.35);">
                    <span>Update Password</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </button>

                <div class="text-center pt-2">
                    <a href="<?= BASE_PATH ?>/login" class="text-secondary text-decoration-none small">
                        Cancel and return to Login
                    </a>
                </div>
            </form>

        <?php endif; ?>

    </div>

</div>

<script>
function toggleFieldVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.type = (field.type === 'password') ? 'text' : 'password';
    }
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
