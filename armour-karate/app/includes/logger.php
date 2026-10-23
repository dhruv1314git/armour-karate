<?php
/**
 * Application Logger
 * app/includes/logger.php
 *
 * Writes structured log entries to logs/app.log.
 * Never displays sensitive data in the browser.
 * Log file is protected from browser access by logs/.htaccess.
 */

declare(strict_types=1);

/**
 * Append a log entry to the application log file.
 *
 * @param string $level    'INFO' | 'WARNING' | 'ERROR' | 'SECURITY' | 'DEBUG'
 * @param string $message  Log message (do not include credentials or PII unnecessarily)
 * @param array  $context  Optional structured data (will be JSON-encoded)
 */
function appLog(string $level, string $message, array $context = []): void
{
    // Always suppress logs in testing if the log path isn't defined yet
    if (!defined('LOG_PATH')) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip        = function_exists('getClientIp') ? getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $line      = "[{$timestamp}] [{$level}] [IP:{$ip}] {$message}";

    if (!empty($context)) {
        $line .= ' | context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }

    $line .= PHP_EOL;

    // Write to log file (append mode, create if not exists)
    $logDir = dirname(LOG_PATH);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0750, true);
    }

    // Suppress file write errors — logging must never crash the application
    @file_put_contents(LOG_PATH, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Write an audit log entry for admin actions.
 * This is a high-level wrapper; the DB audit_logs table is populated in Stage 6.
 *
 * @param PDO    $pdo        Database connection
 * @param string $action     Action identifier (e.g. 'STUDENT_APPROVED')
 * @param string $entityType Entity type (e.g. 'StudentProfile')
 * @param mixed  $entityId   Entity ID
 * @param array  $metadata   Additional context
 * @param int    $adminId    Admin user ID performing the action
 */
function writeAuditLog(PDO $pdo, string $action, string $entityType, mixed $entityId, array $metadata = [], int $adminId = 0): void
{
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $action,
            $entityType,
            (string) $entityId,
            json_encode($metadata, JSON_UNESCAPED_UNICODE),
            $adminId,
        ]);
    } catch (PDOException $e) {
        // Audit log failure must NOT block the main action
        appLog('ERROR', 'Audit log write failed: ' . $e->getMessage(), [
            'action'     => $action,
            'entityType' => $entityType,
            'entityId'   => $entityId,
        ]);
    }
}
