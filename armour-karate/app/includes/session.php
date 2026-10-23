<?php
/**
 * Secure Session Initialization
 * app/includes/session.php
 *
 * Configures PHP sessions with security hardening before any output.
 * Must be called exactly once, before any session access.
 */

declare(strict_types=1);

// Ensure logger functions are available for session event logging
if (!function_exists('appLog') && defined('APP_ROOT') && file_exists(APP_ROOT . '/app/includes/logger.php')) {
    require_once APP_ROOT . '/app/includes/logger.php';
}

/**
 * Initialize a secure PHP session.
 *
 * Security measures:
 *  - HttpOnly cookie (JS cannot read session cookie)
 *  - SameSite=Lax (CSRF mitigation)
 *  - Secure flag (HTTPS only — enabled in production)
 *  - Strict mode (rejects unknown session IDs)
 *  - Session hijacking fingerprint stored in session
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // already started
    }

    $isHttps    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

    $secureCookie = APP_ENV === 'production' && $isHttps;

    session_set_cookie_params([
        'lifetime' => 0,            // session cookie (expires on browser close)
        'path'     => '/',
        'domain'   => '',           // current domain only
        'secure'   => $secureCookie,
        'httponly' => true,         // not accessible via JavaScript
        'samesite' => 'Lax',        // CSRF protection
    ]);

    // Prevent session ID fixation
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    // Garbage collection
    ini_set('session.gc_maxlifetime', (string) (60 * 60 * 4)); // 4 hours idle timeout

    session_name('ARMOUR_SESS');
    session_start();

    // ── Browser fingerprint check (anti-hijacking) ────────────────
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . 'armour-salt');

    if (isset($_SESSION['_fingerprint'])) {
        if (!hash_equals($_SESSION['_fingerprint'], $fingerprint)) {
            // Fingerprint mismatch — possible session hijacking attempt
            if (function_exists('appLog')) {
                appLog('SECURITY', 'Session fingerprint mismatch. Destroying session. IP: ' . getClientIp());
            } else {
                error_log('[armour-karate] SECURITY: Session fingerprint mismatch. Destroying session. IP: ' . getClientIp());
            }
            destroySession();
            return;
        }
    } else {
        $_SESSION['_fingerprint'] = $fingerprint;
    }

    // ── Session timeout check ─────────────────────────────────────
    $idleTimeout = 60 * 60 * 4; // 4 hours

    if (isset($_SESSION['_last_active'])) {
        if ((time() - $_SESSION['_last_active']) > $idleTimeout) {
            if (function_exists('appLog')) {
                appLog('INFO', 'Session expired due to inactivity for user: ' . ($_SESSION['user_id'] ?? 'unknown'));
            } else {
                error_log('[armour-karate] INFO: Session expired due to inactivity for user: ' . ($_SESSION['user_id'] ?? 'unknown'));
            }
            destroySession();
            return;
        }
    }

    $_SESSION['_last_active'] = time();
}

/**
 * Regenerate the session ID.
 *
 * MUST be called after successful login to prevent session fixation.
 */
function regenerateSession(): void
{
    session_regenerate_id(true); // delete the old session file
}

/**
 * Completely destroy the current session.
 */
function destroySession(): void
{
    $_SESSION = [];

    // Remove the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 3600,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Check if there is a logged-in user.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if the logged-in user is an admin.
 */
function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['user_role'] === 'ADMIN';
}

/**
 * Check if the logged-in user is an active student.
 */
function isStudent(): bool
{
    return isLoggedIn() && $_SESSION['user_role'] === 'STUDENT';
}

/**
 * Get the current user ID from the session.
 */
function currentUserId(): int|null
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get the client IP address.
 */
function getClientIp(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            // Take the first IP in a comma-separated list
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return 'unknown';
}
