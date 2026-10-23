<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Password Reset Request (Forgot Password)
 * app/views/pages/auth/forgot-password.php
 *
 * Faithfully recreates the original Next.js forgot-password page:
 *  - Centered dark modal container
 *  - Enumeration-safe: Identical response whether email exists or not
 *  - 64-character cryptographically secure token stored as SHA-256 hash
 *  - 1-hour expiration with invalidation of prior unused tokens
 *  - Mail dispatch via transactional Mailer
 *  - IP rate limiting & CSRF protection
 */

declare(strict_types=1);

requireGuest();

$pageTitle       = 'Reset Password | Armour Karate Academy';
$metaDescription = 'Request password recovery instructions for your Armour Karate student or administrator account.';

$submitted    = false;
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
        appLog('SECURITY', 'CSRF mismatch on forgot password attempt. IP: ' . getClientIp());

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
    $rateCheck = checkRateLimit('forgot_password', getClientIp(), 5, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Forgot password rate limit exceeded by IP: ' . getClientIp());

        if ($isAjax) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait a few minutes before trying again.']);
            exit;
        }

        $errorMessage = 'Too many requests. Please wait a few minutes before trying again.';
        goto renderView;
    }

    // 3. Email Input
    $emailInput = trim((string)($_POST['email'] ?? ''));

    if ($emailInput === '' || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid registered email address.';
        if ($isAjax) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        }
        goto renderView;
    }

    // 4. Token Generation & Storage
    $pdo = db();
    try {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$emailInput]);
        $user = $stmt->fetch();

        if ($user) {
            $userId    = (int) $user['id'];
            $rawToken  = bin2hex(random_bytes(32)); // 64 hex characters
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $pdo->beginTransaction();

            // Invalidate any older unused tokens for this user
            $stmtRevoke = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
            $stmtRevoke->execute([$userId]);

            // Insert new token hash
            $stmtInsert = $pdo->prepare(
                "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, used, created_at)
                 VALUES (?, ?, ?, 0, NOW())"
            );
            $stmtInsert->execute([$userId, $tokenHash, $expiresAt]);

            $pdo->commit();

            // Construct reset link
            $resetUrl = APP_URL . '/reset-password?token=' . urlencode($rawToken);

            // Dispatch email via Mailer service
            $mailer = new \Armour\Mail\Mailer();
            $subject = 'Armour Karate Academy – Password Reset Instructions';
            $htmlBody = '
                <div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 24px; background: #111; color: #fff; border-radius: 8px;">
                    <h2 style="color: #dc2626; margin-top: 0;">Armour Karate Academy</h2>
                    <p style="color: #ccc; font-size: 14px;">Hello ' . esc($user['name']) . ',</p>
                    <p style="color: #ccc; font-size: 14px; line-height: 1.6;">
                        A request was received to reset the password for your account. Click the button below to establish a new password:
                    </p>
                    <p style="text-align: center; margin: 28px 0;">
                        <a href="' . escAttr($resetUrl) . '" style="background: #dc2626; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block;">
                            Reset Your Password
                        </a>
                    </p>
                    <p style="color: #888; font-size: 12px; line-height: 1.6;">
                        This link is valid for 60 minutes. If you did not request a password reset, you may safely ignore this message. Your password will remain unchanged.
                    </p>
                    <hr style="border: 0; border-top: 1px solid #222; margin: 24px 0;">
                    <p style="color: #666; font-size: 11px; margin-bottom: 0;">
                        Armour Martial Arts Gujju Karate Association &middot; Automated Security System
                    </p>
                </div>';

            $mailer->send($user['email'], $user['name'], $subject, $htmlBody);
            appLog('INFO', "Password reset token generated and sent for user ID: {$userId}. IP: " . getClientIp());
        } else {
            // Log attempt on non-existent account without disclosing to user
            appLog('SECURITY', "Password reset requested for non-existent email: '{$emailInput}'. IP: " . getClientIp());
        }
    } catch (\Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        appLog('ERROR', 'Error processing password reset request: ' . $e->getMessage());
    }

    // Always report identical success message to prevent user enumeration
    $submitted = true;

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'   => true,
            'submitted' => true,
            'message'   => 'If an account with that email exists, password recovery instructions have been sent.',
        ]);
        exit;
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
                Reset Password
            </h1>
            <p class="text-secondary small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                Enter your registered email address to receive password recovery instructions.
            </p>
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

        <?php if ($submitted): ?>
            <!-- Instructions Sent Confirmation -->
            <div class="p-4 rounded-3 text-center mb-4"
                 style="background: #0d0d0f; border: 1px solid #27272a;">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                     style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <h2 class="fs-5 fw-bold text-white mb-2">Instructions Sent</h2>
                <p style="color: #a3a3a3; font-size: 0.85rem; line-height: 1.6;" class="mb-4">
                    If an account with <span class="text-white fw-semibold"><?= esc($emailInput) ?></span> exists in our system, you will receive a secure password reset link shortly.
                </p>
                <a href="<?= BASE_PATH ?>/login" class="btn btn-outline-danger btn-sm text-uppercase fw-bold px-3 py-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    &larr; Back to Student Login
                </a>
            </div>

        <?php else: ?>

            <!-- Form -->
            <form action="<?= BASE_PATH ?>/forgot-password" method="POST" id="forgotPasswordForm" class="d-flex flex-column gap-3">
                <?= csrfField() ?>

                <div>
                    <label for="email" class="form-label text-uppercase fw-bold text-light" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                        Registered Email
                    </label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0" style="background: #0d0d0f; border-color: #27272a; color: #71717a;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                        </span>
                        <input type="email"
                               class="form-control text-white border-start-0"
                               id="email"
                               name="email"
                               required
                               autocomplete="email"
                               value="<?= escAttr($emailInput) ?>"
                               placeholder="student@example.com"
                               style="background: #0d0d0f; border-color: #27272a; font-size: 0.88rem; padding: 0.75rem;">
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-danger w-100 py-3 fw-bold text-uppercase d-flex align-items-center justify-content-center gap-2 mt-2"
                        style="background: #DC2626; border-color: #DC2626; font-size: 0.82rem; letter-spacing: 0.06em; box-shadow: 0 4px 16px rgba(220, 38, 38, 0.35);">
                    <span>Send Reset Link</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </button>

                <div class="text-center pt-2">
                    <a href="<?= BASE_PATH ?>/login" class="text-secondary text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 0.8rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                        </svg>
                        <span>Return to Login</span>
                    </a>
                </div>
            </form>

        <?php endif; ?>

    </div>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
