<?php
/**
 * Role-Based Authentication Guards
 * app/includes/auth.php
 *
 * Provides route-level access control functions that must be called
 * at the top of every protected page.
 *
 * Enforces:
 *  - Unauthenticated users cannot access protected routes
 *  - Students are gated by account status (PENDING/ACTIVE/INACTIVE/REJECTED)
 *  - Admins cannot access student portal pages
 *  - IDOR protection: students can only access their own data
 */

declare(strict_types=1);

// ─── Admin Guards ─────────────────────────────────────────────────────────────

/**
 * Require the current user to be an authenticated admin.
 * Redirects to /admin/login if not authenticated or not an admin.
 *
 * Usage: Call at the top of every admin page.
 */
function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        safeRedirect(BASE_PATH . '/admin/login');
    }
}

// ─── Student Guards ───────────────────────────────────────────────────────────

/**
 * Require the current user to be an authenticated, ACTIVE student.
 *
 * Status gate logic (from original Next.js middleware):
 *  PENDING  → /registration-pending
 *  REJECTED → /registration-rejected
 *  INACTIVE → /account-inactive
 *  ACTIVE   → allowed through
 *
 * Usage: Call at the top of every student portal page.
 *
 * @return array  The current student profile row (for convenience)
 */
function requireStudent(): array
{
    if (!isLoggedIn() || !isStudent()) {
        safeRedirect(BASE_PATH . '/login');
    }

    // Check student status from session (set on login)
    $status = $_SESSION['student_status'] ?? 'PENDING';

    return match ($status) {
        'PENDING'  => safeRedirect(BASE_PATH . '/registration-pending'),
        'REJECTED' => safeRedirect(BASE_PATH . '/registration-rejected'),
        'INACTIVE' => safeRedirect(BASE_PATH . '/account-inactive'),
        'ACTIVE'   => getSessionStudentProfile(),
        default    => safeRedirect(BASE_PATH . '/login'),
    };
}

/**
 * Return the current student profile from session data.
 * Only call after requireStudent() has passed.
 *
 * @return array
 */
function getSessionStudentProfile(): array
{
    return [
        'user_id'    => (int) ($_SESSION['user_id']            ?? 0),
        'profile_id' => (int) ($_SESSION['student_profile_id'] ?? 0),
        'student_id' =>        $_SESSION['student_id']          ?? '',
        'full_name'  =>        $_SESSION['student_name']        ?? '',
        'email'      =>        $_SESSION['student_email']       ?? '',
        'status'     =>        $_SESSION['student_status']      ?? 'PENDING',
        'belt_name'  =>        $_SESSION['current_belt_name']   ?? null,
        'belt_color' =>        $_SESSION['current_belt_color']  ?? null,
        'photo_url'  =>        $_SESSION['student_photo_url']   ?? null,
    ];
}

// ─── Guest Guard ──────────────────────────────────────────────────────────────

/**
 * Require the current user to be a guest (not logged in).
 * Redirects authenticated users to their dashboard.
 *
 * Usage: Call on login/register pages to prevent authenticated users
 *        from re-visiting the login form.
 */
function requireGuest(): void
{
    if (!isLoggedIn()) {
        return;
    }

    if (isAdmin()) {
        safeRedirect(BASE_PATH . '/admin/dashboard');
    } elseif (isStudent()) {
        safeRedirect(BASE_PATH . '/student/dashboard');
    }
}

// ─── IDOR Protection ──────────────────────────────────────────────────────────

/**
 * Verify that a student profile belongs to the currently logged-in student.
 *
 * Prevents Insecure Direct Object Reference (IDOR) attacks where a student
 * might try to access another student's data by guessing IDs.
 *
 * @param int $profileUserId  The user_id stored on the student profile row.
 */
function requireStudentOwnership(int $profileUserId): void
{
    $currentUserId = currentUserId();

    if ($currentUserId === null || $currentUserId !== $profileUserId) {
        appLog(
            'SECURITY',
            "IDOR attempt: user {$currentUserId} tried to access profile owned by {$profileUserId}. IP: " . getClientIp()
        );
        http_response_code(403);
        require APP_ROOT . '/app/views/pages/errors/403.php';
        exit;
    }
}

// ─── Password Utilities ───────────────────────────────────────────────────────

/**
 * Hash a plain-text password using bcrypt.
 *
 * @param string $password  Plain-text password
 * @return string           bcrypt hash
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a plain-text password against a stored hash.
 *
 * @param string $password  Plain-text candidate
 * @param string $hash      Stored bcrypt hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Check if a password hash needs to be rehashed (e.g. after cost increase).
 *
 * @param string $hash  Stored hash
 * @return bool
 */
function needsRehash(string $hash): bool
{
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ─── Session Population (called after successful login) ───────────────────────

/**
 * Populate session variables after a successful student login.
 * Called from the login handler (Stage 4).
 *
 * @param array $user     Row from `users` table
 * @param array $profile  Row from `student_profiles` table
 * @param array|null $belt Row from `belts` table (current belt, may be null)
 */
function loginStudent(array $user, array $profile, ?array $belt = null): void
{
    regenerateSession(); // prevent session fixation

    $_SESSION['user_id']            = (int) $user['id'];
    $_SESSION['user_role']          = 'STUDENT';
    $_SESSION['student_profile_id'] = (int) $profile['id'];
    $_SESSION['student_id']         = $profile['student_id'];
    $_SESSION['student_name']       = $profile['full_name'];
    $_SESSION['student_email']      = $user['email'];
    $_SESSION['student_status']     = $profile['status'];
    $_SESSION['current_belt_name']  = $belt['name']  ?? null;
    $_SESSION['current_belt_color'] = $belt['color'] ?? null;
    $_SESSION['student_photo_url']  = $profile['profile_photo_url'] ?? $user['image'] ?? null;
    $_SESSION['_last_active']       = time();

    appLog('INFO', "Student login: user_id={$user['id']}, student_id={$profile['student_id']}. IP: " . getClientIp());
}

/**
 * Populate session variables after a successful admin login.
 * Called from the admin login handler (Stage 4).
 *
 * @param array $user  Row from `users` table (role = ADMIN)
 */
function loginAdmin(array $user): void
{
    regenerateSession(); // prevent session fixation

    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role'] = 'ADMIN';
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['_last_active'] = time();

    appLog('INFO', "Admin login: user_id={$user['id']}. IP: " . getClientIp());
}
