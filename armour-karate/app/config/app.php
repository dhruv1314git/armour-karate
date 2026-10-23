<?php
/**
 * Application Configuration
 * app/config/app.php
 *
 * Loads .env file, defines application constants, and configures
 * PHP runtime settings. Must be loaded before anything else.
 *
 * NEVER hardcode credentials here. All secrets go in .env.
 */

declare(strict_types=1);

// ─── .env loader (simple, no external dependency) ────────────────────────────

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        // In production, fail hard if .env is missing
        // In dev, allow graceful degradation with defaults
        error_log('[armour-karate] WARNING: .env file not found at ' . $path);
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Parse KEY=VALUE
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Only set if not already defined (allows environment override)
        if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Load .env from project root
loadEnv(APP_ROOT . '/.env');

// ─── Helper to read env values safely ────────────────────────────────────────

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    // Type coercion for common boolean strings
    return match (strtolower((string) $value)) {
        'true',  '1', 'yes', 'on'  => true,
        'false', '0', 'no',  'off' => false,
        default => $value,
    };
}

// ─── Application constants ────────────────────────────────────────────────────

// Environment: 'development' | 'production'
define('APP_ENV', env('APP_ENV', 'development'));

// Base URL (used to build links and redirects)
define('APP_URL', env('APP_URL', 'http://localhost/armour-karate'));

// Base path for assets (same as APP_URL in XAMPP subdirectory setup)
define('BASE_PATH', '/armour-karate');

// Path to public assets
define('ASSETS_URL', APP_URL . '/public/assets');

// Path to uploaded files (URL)
define('UPLOADS_URL', APP_URL . '/public/uploads');

// Path to uploaded files (filesystem)
define('UPLOADS_PATH', APP_ROOT . '/public/uploads');

// Log file path
define('LOG_PATH', APP_ROOT . '/logs/app.log');

// ─── PHP runtime configuration ────────────────────────────────────────────────

// Disable public stack trace display to protect sensitive system details
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Enable comprehensive private error logging to logs/app.log
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH);
error_reporting(E_ALL);

// Register custom global exception handler
set_exception_handler(function (Throwable $e): void {
    $msg = sprintf(
        "Uncaught %s: %s in %s on line %d\nStack trace:\n%s",
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );

    if (function_exists('appLog')) {
        appLog('ERROR', 'Uncaught Exception: ' . $e->getMessage(), [
            'type'  => get_class($e),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    } else {
        error_log('[armour-karate] ' . $msg);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (PHP_SAPI !== 'cli') {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'A temporary server error occurred. Please try again later.']);
            exit;
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>System Notice | Armour Karate Academy</title>'
            . '<style>'
            . 'body{background:#0a0a0a;color:#fff;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem;text-align:center;}'
            . '.box{max-width:480px;background:#141414;border:1px solid #262626;padding:2.5rem;border-radius:1.5rem;box-shadow:0 24px 48px rgba(0,0,0,0.6);}'
            . 'h1{color:#dc2626;font-size:1.4rem;text-transform:uppercase;margin:0 0 1rem;letter-spacing:0.04em;}'
            . 'p{color:#a3a3a3;font-size:0.875rem;line-height:1.6;margin:0 0 1.75rem;}'
            . 'a{display:inline-block;padding:0.75rem 1.75rem;background:#dc2626;color:#fff;text-decoration:none;font-weight:700;border-radius:0.75rem;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;}'
            . '</style></head><body><div class="box">'
            . '<h1>Temporary Service Disruption</h1>'
            . '<p>Our dojo technical system encountered an unexpected condition. The incident has been logged privately for technical review.</p>'
            . '<a href="/armour-karate/">Return to Homepage</a>'
            . '</div></body></html>';
    }
    exit(1);
});

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

// Character encoding
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
