<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Account & Password Security Settings
 * app/views/pages/student/account.php
 *
 * Faithfully recreates the original Next.js student account page:
 *  - Left column: Secure password change form with current password verification
 *  - Enforces strong password rules (min 8 chars, 1 uppercase, 1 digit)
 *  - Session ID regeneration & password reset token invalidation upon update
 *  - Right column: Authentication provider connection status & dojo retention notice
 *  - Rate limiting, CSRF protection, and IDOR defense
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'Account & Security Settings';
$portalType = 'student';

$errorMessage   = null;
$successMessage = null;

// Handle Password Change (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    $csrfTokenSubmitted = $_POST['_csrf_token'] ?? '';
    $csrfTokenExpected  = $_SESSION['_csrf_token'] ?? '';

    if (empty($csrfTokenSubmitted) || empty($csrfTokenExpected) || !hash_equals($csrfTokenExpected, $csrfTokenSubmitted)) {
        appLog('SECURITY', 'CSRF mismatch on password change attempt. IP: ' . getClientIp());
        $errorMessage = 'Invalid or expired session security token. Please refresh and try again.';
        goto renderView;
    }

    // 2. Persistent Rate Limiting (5 attempts per minute)
    $rateCheck = checkRateLimit('change_password', getClientIp(), 5, 60);
    if (!$rateCheck['allowed']) {
        appLog('SECURITY', 'Password change rate limit exceeded by IP: ' . getClientIp());
        $errorMessage = 'Too many attempts. Please wait a minute before trying again.';
        goto renderView;
    }

    // 3. Capture Inputs
    $currentPassword = (string)($_POST['currentPassword'] ?? '');
    $newPassword     = (string)($_POST['newPassword'] ?? '');
    $confirmPassword = (string)($_POST['confirmPassword'] ?? '');

    // 4. Validate Inputs
    $validator = new Validator([
        'current_password' => $currentPassword,
        'new_password'     => $newPassword,
        'confirm_password' => $confirmPassword,
    ]);
    $validator->required('current_password', 'Current password')
              ->required('new_password', 'New password')
              ->passwordStrength('new_password', 'New password')
              ->required('confirm_password', 'Password confirmation')
              ->matches('confirm_password', 'new_password', 'Password confirmation');

    if ($validator->fails()) {
        $errorMessage = $validator->firstError();
        goto renderView;
    }

    // 5. Verify Current Password from DB
    $stmtUser = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$sessionStudent['user_id']]);
    $user = $stmtUser->fetch();

    if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
        appLog('SECURITY', "Failed password change for user ID: {$sessionStudent['user_id']} (incorrect current password). IP: " . getClientIp());
        $errorMessage = 'Your current password was entered incorrectly.';
        goto renderView;
    }

    // 6. Update Password inside Transaction
    try {
        $pdo->beginTransaction();

        $newHash = hashPassword($newPassword);

        // Update password hash strictly for this user
        $stmtUpd = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpd->execute([$newHash, $sessionStudent['user_id']]);

        // Invalidate any open password reset tokens for this user
        $stmtTokens = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ? AND used = 0");
        $stmtTokens->execute([$sessionStudent['user_id']]);

        $pdo->commit();

        // Regenerate session ID for security
        regenerateSession();

        $successMessage = 'Password updated successfully! Your account credentials have been securely updated.';
        appLog('SECURITY', "Password successfully changed for student user ID: {$sessionStudent['user_id']}. IP: " . getClientIp());

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        appLog('ERROR', 'Error updating student password: ' . $e->getMessage());
        $errorMessage = 'An error occurred while updating your password. Please try again.';
    }
}

renderView:
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            Account &amp; Security Settings
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Manage your login credentials, authentication methods, and security preferences
        </p>
    </div>

    <!-- Alert Messages -->
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
        <!-- Left Column: Change Password Card -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white d-flex flex-column gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2.5 rounded-3 bg-danger bg-opacity-10 text-danger">
                        <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.16 9h.83l.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.793-.793-1.147-1.147H7.16a.5.5 0 0 1-.447-.285A3 3 0 0 0 4 5z"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="fs-6 fw-bold text-dark mb-1">Change Password</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.75rem;">
                            Update your account password regularly to keep your portal secure
                        </p>
                    </div>
                </div>

                <form action="<?= BASE_PATH ?>/student/account" method="POST" class="d-flex flex-column gap-3">
                    <?= csrfField() ?>

                    <!-- Current Password -->
                    <div>
                        <label for="currentPassword" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            Current Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-secondary">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/></svg>
                            </span>
                            <input type="password"
                                   class="form-control border-start-0 border-end-0"
                                   id="currentPassword"
                                   name="currentPassword"
                                   required
                                   autocomplete="current-password"
                                   style="font-size: 0.85rem; padding: 0.65rem 0.85rem;">
                            <button type="button" class="btn btn-light border border-start-0 text-secondary" onclick="toggleVisibility('currentPassword')">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="newPassword" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            New Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-secondary">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.16 9h.83l.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.793-.793-1.147-1.147H7.16a.5.5 0 0 1-.447-.285A3 3 0 0 0 4 5z"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                            </span>
                            <input type="password"
                                   class="form-control border-start-0 border-end-0"
                                   id="newPassword"
                                   name="newPassword"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Min 8 chars, 1 uppercase, 1 digit"
                                   style="font-size: 0.85rem; padding: 0.65rem 0.85rem;">
                            <button type="button" class="btn btn-light border border-start-0 text-secondary" onclick="toggleVisibility('newPassword')">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label for="confirmPassword" class="form-label text-uppercase fw-bold text-secondary" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            Confirm New Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-secondary">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.16 9h.83l.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.793-.793-1.147-1.147H7.16a.5.5 0 0 1-.447-.285A3 3 0 0 0 4 5z"/><path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                            </span>
                            <input type="password"
                                   class="form-control border-start-0 border-end-0"
                                   id="confirmPassword"
                                   name="confirmPassword"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Repeat new password"
                                   style="font-size: 0.85rem; padding: 0.65rem 0.85rem;">
                            <button type="button" class="btn btn-light border border-start-0 text-secondary" onclick="toggleVisibility('confirmPassword')">
                                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-danger text-uppercase fw-bold px-4 py-2.5" style="font-size: 0.75rem; letter-spacing: 0.06em;">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Authentication Providers & Retention Notice -->
        <div class="col-lg-6 d-flex flex-column gap-4">

            <!-- Linked Providers Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white d-flex flex-column gap-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2.5 rounded-3 bg-light text-secondary">
                        <svg width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/></svg>
                    </div>
                    <div>
                        <h2 class="fs-6 fw-bold text-dark mb-1">Authentication Providers</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.75rem;">
                            Connected sign-in methods for your account
                        </p>
                    </div>
                </div>

                <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <svg width="24" height="24" viewBox="0 0 24 24">
                            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.8 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.2 8.9 5 12 5z"/>
                            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.7-.2-2.3H12v4.6h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.9z"/>
                            <path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.7s.1-2 .4-2.7L1.6 6.4C.6 8.3 0 10.1 0 12s.6 3.7 1.6 5.6l3.7-2.9z"/>
                            <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.1-6.7-5l-3.7 2.9C3.5 20.4 7.4 23 12 23z"/>
                        </svg>
                        <div>
                            <span class="fs-6 fw-bold text-dark d-block" style="font-size: 0.85rem;">Google OAuth</span>
                            <span class="text-secondary small" style="font-size: 0.72rem;">Single Sign-On with registered Gmail</span>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border px-2.5 py-1 text-uppercase fw-bold" style="font-size: 0.68rem;">
                        Password Login
                    </span>
                </div>
            </div>

            <!-- Dojo Membership Retention Notice -->
            <div class="rounded-4 p-4 border bg-light">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <svg width="16" height="16" fill="currentColor" class="text-warning flex-shrink-0" viewBox="0 0 16 16"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/><path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/></svg>
                    <h3 class="fs-6 fw-bold text-uppercase text-secondary mb-0" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                        Dojo Membership Retention
                    </h3>
                </div>
                <p class="text-secondary small mb-0" style="font-size: 0.75rem; line-height: 1.6;">
                    To request changes to your primary legal name, date of birth, or account status termination, please contact the academy registrar directly. Student records are maintained according to World Goju-Ryu Federation regulations.
                </p>
            </div>

        </div>
    </div>

</div>

<script>
function toggleVisibility(fieldId) {
    const el = document.getElementById(fieldId);
    if (el) {
        el.type = (el.type === 'password') ? 'text' : 'password';
    }
}
</script>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
