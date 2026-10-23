<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Front Controller — index.php
 *
 * All HTTP requests are routed through this file by .htaccess.
 * This file bootstraps the application and dispatches to the
 * appropriate page handler.
 *
 * URL structure:
 *   /armour-karate/           → public/home
 *   /armour-karate/about      → public/about
 *   /armour-karate/admin/dashboard → admin/dashboard
 *   etc.
 */

declare(strict_types=1);

// ─── Bootstrap ───────────────────────────────────────────────────────────────

define('APP_ROOT', __DIR__);
define('APP_VERSION', '1.0.0-stage3');

// Load centralized bootstrap (loads configuration, error handling, logger,
// helpers, session, database, CSRF, auth, validation, and starts secure session)
require_once APP_ROOT . '/app/bootstrap.php';

// ─── Router ──────────────────────────────────────────────────────────────────

/**
 * Resolve the current route from the request URI.
 * Strips the base path (/armour-karate/) and query string.
 *
 * @return string  e.g. "", "about", "admin/dashboard", "student/profile"
 */
function resolveRoute(): string
{
    // 1. Support explicit query parameter (e.g., from ErrorDocument or direct query)
    if (!empty($_GET['route']) && is_string($_GET['route'])) {
        $cleanRoute = trim($_GET['route'], '/');
        return preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $cleanRoute);
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // 2. Remove query string
    $path = strtok($uri, '?');

    // 3. Remove base path prefix (/armour-karate)
    $base = '/armour-karate';
    if (str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }

    // 4. Normalize and trim slashes
    $path = trim($path, '/');

    // 5. Treat index.php or index as the home route
    if ($path === 'index.php' || $path === 'index' || $path === '') {
        return '';
    }

    // 6. Strip optional trailing .php extension if requested directly
    if (str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }

    // 7. Sanitize: only allow alphanumeric, dash, underscore, forward-slash
    $path = preg_replace('/[^a-zA-Z0-9\-_\/]/', '', $path);

    return $path;
}

/**
 * Route definitions.
 *
 * Maps URL segments → PHP page handler files inside app/views/pages/.
 * Handlers receive $routeParams for dynamic segments (e.g. ?id=5).
 *
 * Format:  'route/segment' => 'subdirectory/filename'
 *
 * All 40 routes from the migration report are listed here.
 * Routes not yet implemented serve the "coming-soon" stub.
 */
$routes = [

    // ── Public pages (Stage 2 & 3) ──────────────────────────────
    ''                      => 'public/home',
    'about'                 => 'public/about',
    'belts'                 => 'public/belts',
    'fees'                  => 'public/fees',
    'events'                => 'public/events',
    'black-belts'           => 'public/black-belts',
    'gallery'               => 'public/gallery',
    'locations'             => 'public/locations',
    'contact'               => 'public/contact',
    'join'                  => 'public/join',

    // ── Authentication pages (Stage 4) ──────────────────────────
    'login'                 => 'auth/login',
    'register'              => 'auth/register',
    'forgot-password'       => 'auth/forgot-password',
    'reset-password'        => 'auth/reset-password',
    'logout'                => 'auth/logout',
    'registration-pending'  => 'auth/registration-pending',
    'registration-rejected' => 'auth/registration-rejected',
    'account-inactive'      => 'auth/account-inactive',
    'admin/login'           => 'auth/admin-login',
    'admin/setup'           => 'auth/admin-setup',        // CLI-only; page redirects away

    // ── Student portal pages (Stage 5) ──────────────────────────
    'student/dashboard'     => 'student/dashboard',
    'student/belt-journey'  => 'student/belt-journey',
    'student/fees'          => 'student/fees',
    'student/exams'         => 'student/exams',
    'student/events'        => 'student/events',
    'student/class-info'    => 'student/class-info',
    'student/profile'       => 'student/profile',
    'student/account'       => 'student/account',

    // ── Admin panel pages (Stage 6–8) ────────────────────────────
    'admin/dashboard'       => 'admin/dashboard',
    'admin/students'        => 'admin/students',
    'admin/students/view'   => 'admin/student-detail',   // ?id=X
    'admin/student'         => 'admin/student-detail',   // ?id=X
    'admin/fees'            => 'admin/fees',
    'admin/belts'           => 'admin/belts',
    'admin/exams'           => 'admin/exams',
    'admin/events'          => 'admin/events',
    'admin/gallery'         => 'admin/gallery',
    'admin/black-belt'      => 'admin/black-belt',
    'admin/locations'       => 'admin/locations',
    'admin/contact'         => 'admin/contact',
    'admin/audit-logs'      => 'admin/audit-logs',
    'admin/settings'        => 'admin/settings',

    // ── Error pages ──────────────────────────────────────────────
    'error/403'             => 'errors/403',
    'error/404'             => 'errors/404',
];

// ─── Dispatch ─────────────────────────────────────────────────────────────────

$route  = resolveRoute();
$target = $routes[$route] ?? null;

// Handle unknown routes
if ($target === null) {
    http_response_code(404);
    $target = 'errors/404';
}

$pageFile = APP_ROOT . '/app/views/pages/' . $target . '.php';

if (!file_exists($pageFile)) {
    // Page handler exists in route table but file not yet created (future stages)
    http_response_code(200); // not an error — it's a planned route
    $pageFile = APP_ROOT . '/app/views/pages/errors/coming-soon.php';
}

// Pass the resolved route to the page file
define('CURRENT_ROUTE', $route);

require_once $pageFile;
