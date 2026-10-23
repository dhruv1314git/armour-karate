<?php
/**
 * Application Helper Functions
 * app/includes/helpers.php
 *
 * General-purpose utility functions used across all pages.
 * Includes: output escaping, redirects, flash messages, date formatting,
 * asset URLs, student ID generation, payment ref generation, and pagination.
 */

declare(strict_types=1);

// ─── Output Escaping ──────────────────────────────────────────────────────────

/**
 * Escape a value for safe HTML output.
 * Always use this when rendering untrusted data in templates.
 *
 * @param mixed $value
 * @return string
 */
function esc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape a value for use inside a HTML attribute.
 *
 * @param mixed $value
 * @return string
 */
function escAttr(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape a value for use inside a JavaScript string literal.
 *
 * @param mixed $value
 * @return string
 */
function escJs(mixed $value): string
{
    return json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

// ─── Redirects ────────────────────────────────────────────────────────────────

/**
 * Perform a safe HTTP redirect and exit.
 *
 * Validates that the target is a relative path or matches the current host
 * to prevent open-redirect attacks.
 *
 * @param string $url   Target URL (relative path preferred)
 * @param int    $code  HTTP status code (301 | 302 | 303)
 * @return never
 */
function safeRedirect(string $url, int $code = 302): never
{
    // If relative path — prepend host to validate, but send as-is
    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        header("Location: {$url}", true, $code);
        exit;
    }

    // For absolute URLs — only allow same host
    $allowedHost = parse_url(APP_URL, PHP_URL_HOST);
    $targetHost  = parse_url($url, PHP_URL_HOST);

    if ($allowedHost !== $targetHost) {
        appLog('SECURITY', "Open redirect attempt blocked: {$url}");
        header('Location: ' . BASE_PATH . '/', true, $code);
        exit;
    }

    header("Location: {$url}", true, $code);
    exit;
}

// ─── Flash Messages ───────────────────────────────────────────────────────────

/**
 * Set a flash message to display on the next page load.
 *
 * @param string $type     'success' | 'error' | 'warning' | 'info'
 * @param string $message  Human-readable message text
 */
function flashSet(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear all pending flash messages.
 *
 * @return array  Array of ['type' => ..., 'message' => ...]
 */
function flashGet(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/**
 * Render pending flash messages as Bootstrap alerts.
 * Call this in the layout template, just below the navbar.
 *
 * @return string  HTML string
 */
function flashRender(): string
{
    $messages = flashGet();

    if (empty($messages)) {
        return '';
    }

    $map = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];

    $html = '<div class="flash-container" role="alert">';

    foreach ($messages as $msg) {
        $bsClass = $map[$msg['type']] ?? 'alert-secondary';
        $html   .= '<div class="alert ' . $bsClass . ' alert-dismissible fade show" role="alert">';
        $html   .= esc($msg['message']);
        $html   .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html   .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

// ─── Asset URL Helpers ────────────────────────────────────────────────────────

/**
 * Return the full URL for a public asset.
 *
 * @param string $path  Relative path under /public/assets/ (e.g. 'css/custom.css')
 * @return string
 */
function asset(string $path): string
{
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Return the full URL for an uploaded file.
 *
 * @param string $path  Relative path under /public/uploads/ (e.g. 'profiles/abc.jpg')
 * @return string
 */
function uploadUrl(string $path): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $clean = ltrim($path, '/');
    if (str_starts_with($clean, 'uploads/')) {
        $clean = substr($clean, 8);
    }
    return UPLOADS_URL . '/' . $clean;
}

/**
 * Return the URL for the academy logo.
 */
function logoUrl(): string
{
    return asset('images/academy-logo.png');
}

/**
 * Generate a versioned asset URL (cache-busting).
 *
 * @param string $path  Relative path under /public/assets/
 * @return string
 */
function assetVersioned(string $path): string
{
    $fullPath = APP_ROOT . '/public/assets/' . ltrim($path, '/');
    $version  = file_exists($fullPath) ? filemtime($fullPath) : APP_VERSION;
    return asset($path) . '?v=' . $version;
}

// ─── Date Formatting ──────────────────────────────────────────────────────────

/**
 * Format a date string for display.
 *
 * @param string|null $date    MySQL date string (Y-m-d or Y-m-d H:i:s)
 * @param string      $format  Output format (default: '15 Oct 2024')
 * @return string
 */
function formatDate(?string $date, string $format = 'd M Y'): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    try {
        $dt = new DateTimeImmutable($date);
        return $dt->format($format);
    } catch (Exception) {
        return '—';
    }
}

/**
 * Format currency (Indian Rupees).
 *
 * @param float|string|null $amount
 * @return string  e.g. "₹1,200.00"
 */
function formatCurrency(float|string|null $amount): string
{
    if ($amount === null || $amount === '') {
        return '₹0.00';
    }

    return '₹' . number_format((float) $amount, 2);
}

// ─── Student ID Generation ────────────────────────────────────────────────────

/**
 * Generate the next available student ID in KAR-YYYY-NNNN format.
 *
 * Uses a transaction-safe counter table (`student_id_counter`) rather
 * than a fragile MAX(student_id) approach.
 *
 * Implementation is deferred to Stage 1 when the DB schema is created.
 * This stub documents the intended behavior.
 *
 * @param PDO $pdo
 * @return string  e.g. "KAR-2024-0042"
 */
function generateStudentId(PDO $pdo): string
{
    $year = (int) date('Y');
    $ownsTx = false;

    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $ownsTx = true;
    }

    try {
        // Atomic counter increment using LAST_INSERT_ID
        $stmt = $pdo->prepare(
            "INSERT INTO student_id_counter (year, last_seq)
             VALUES (?, 1)
             ON DUPLICATE KEY UPDATE last_seq = LAST_INSERT_ID(last_seq + 1)"
        );
        $stmt->execute([$year]);

        // If newly inserted row, last_seq was 1; if updated, LAST_INSERT_ID() returns the incremented value
        $seq = (int) $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        if ($seq === 0) {
            $seq = 1;
        }

        if ($ownsTx) {
            $pdo->commit();
        }

        // Note: sprintf with %04d enforces minimum 4 digits with zero-padding (e.g., KAR-2024-0001).
        // When sequence exceeds 9999 (e.g., 10000), %04d naturally outputs 5 digits (e.g., KAR-2024-10000),
        // which safely fits within the VARCHAR(20) column without overflow or breakage.
        return sprintf('KAR-%04d-%04d', $year, $seq);
    } catch (PDOException $e) {
        if ($ownsTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        appLog('ERROR', 'Failed to generate student ID: ' . $e->getMessage());
        throw new RuntimeException('Failed to generate student ID. Please try again.');
    }
}

// ─── Payment Reference Generation ────────────────────────────────────────────

/**
 * Generate a unique payment reference.
 *
 * Format: PAY-YYYYMMDD-XXXX
 * Where XXXX is a random 4-digit suffix.
 *
 * @return string  e.g. "PAY-20240115-7382"
 */
function generatePaymentRef(): string
{
    return 'PAY-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

// ─── Password Reset Token ─────────────────────────────────────────────────────

/**
 * Generate a cryptographically secure password reset token.
 *
 * @return string  64-character hex token
 */
function generateResetToken(): string
{
    return bin2hex(random_bytes(32));
}

// ─── JSON Utilities ───────────────────────────────────────────────────────────

/**
 * Safely decode a JSON string from the database.
 *
 * @param string|null $json
 * @param mixed       $default  Value to return on failure
 * @return mixed
 */
function jsonDecode(?string $json, mixed $default = []): mixed
{
    if ($json === null || $json === '') {
        return $default;
    }

    $decoded = json_decode($json, true);

    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
}

// ─── Pagination ───────────────────────────────────────────────────────────────

/**
 * Calculate pagination parameters.
 *
 * @param int $totalItems   Total number of records
 * @param int $perPage      Records per page (default 20)
 * @param int $currentPage  Current page number (1-indexed)
 * @return array{totalItems: int, perPage: int, currentPage: int, totalPages: int, offset: int}
 */
function paginate(int $totalItems, int $perPage = 20, int $currentPage = 1): array
{
    $totalPages  = max(1, (int) ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset      = ($currentPage - 1) * $perPage;

    return [
        'totalItems'  => $totalItems,
        'perPage'     => $perPage,
        'currentPage' => $currentPage,
        'totalPages'  => $totalPages,
        'offset'      => $offset,
    ];
}

// ─── Active Route Helper ──────────────────────────────────────────────────────

/**
 * Return 'active' CSS class if the given route matches the current route.
 * Used in navigation templates.
 *
 * @param string $route  Route to check (e.g. 'about', 'admin/students')
 * @return string  'active' or ''
 */
function activeClass(string $route): string
{
    return (defined('CURRENT_ROUTE') && CURRENT_ROUTE === $route) ? 'active' : '';
}

/**
 * Return 'active' CSS class if the current route starts with the given prefix.
 * Useful for sidebar sections that group multiple routes.
 *
 * @param string $prefix  Route prefix (e.g. 'admin', 'student')
 * @return string  'active' or ''
 */
function activeClassPrefix(string $prefix): string
{
    return (defined('CURRENT_ROUTE') && str_starts_with(CURRENT_ROUTE, $prefix)) ? 'active' : '';
}
