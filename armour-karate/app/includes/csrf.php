<?php
/**
 * CSRF Token Protection
 * app/includes/csrf.php
 *
 * Provides CSRF token generation and validation for all forms.
 * Every POST handler must call csrfValidate() before processing.
 * Every HTML form must include csrfField() inside the <form> tag.
 *
 * Token is stored in the session and changes after each successful
 * validation to provide one-time-token behavior.
 */

declare(strict_types=1);

// ─── Token Generation ─────────────────────────────────────────────────────────

/**
 * Get (or create) the CSRF token for the current session.
 *
 * @return string  64-character hex token
 */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Render a hidden form field with the CSRF token.
 * Include this inside every <form> element.
 *
 * @return string  HTML hidden input element
 */
function csrfField(): string
{
    $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

// ─── Token Validation ─────────────────────────────────────────────────────────

/**
 * Validate the CSRF token submitted in a POST request.
 *
 * Uses constant-time comparison (hash_equals) to prevent timing attacks.
 * Rotates the token after validation for added security.
 *
 * @param bool $rotateAfter  Generate a new token after validation (default true)
 * @throws RuntimeException  if the token is missing or invalid
 */
function csrfValidate(bool $rotateAfter = true): void
{
    $submitted = $_POST['_csrf_token'] ?? '';
    $expected  = $_SESSION['_csrf_token'] ?? '';

    if (
        $submitted === '' ||
        $expected  === '' ||
        !hash_equals($expected, $submitted)
    ) {
        appLog(
            'SECURITY',
            'CSRF token mismatch on POST ' . ($_SERVER['REQUEST_URI'] ?? '') .
            '. IP: ' . getClientIp()
        );

        http_response_code(403);
        die(esc('Invalid or expired request. Please go back and try again.'));
    }

    if ($rotateAfter) {
        // Issue a new token for the next request
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Validate the CSRF token from an AJAX request (sent in X-CSRF-Token header).
 *
 * @throws RuntimeException if invalid
 */
function csrfValidateAjax(): void
{
    $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf_token'] ?? '');
    $expected  = $_SESSION['_csrf_token'] ?? '';

    if ($submitted === '' || $expected === '' || !hash_equals($expected, $submitted)) {
        appLog('SECURITY', 'CSRF token mismatch on AJAX request. IP: ' . getClientIp());
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid or expired request.']);
        exit;
    }
}
