<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Authentication Logout Handler
 * app/views/pages/auth/logout.php
 *
 * Terminates user sessions securely:
 *  - Supports POST submissions (with CSRF protection)
 *  - Safely clears and destroys PHP session and cookie
 *  - Logs security audit trail
 *  - Redirects to /login
 */

declare(strict_types=1);

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if (isLoggedIn()) {
    $userId = currentUserId();
    $role   = $_SESSION['user_role'] ?? 'UNKNOWN';
    appLog('INFO', "User logged out: ID={$userId}, Role={$role}. IP: " . getClientIp());
}

// Destroy session data and remove cookie
destroySession();

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect_url' => BASE_PATH . '/login']);
    exit;
}

safeRedirect(BASE_PATH . '/login');
