<?php
/**
 * IP-based Rate Limiter
 * app/includes/rate_limit.php
 *
 * Implements persistent IP-based sliding-window rate limiting.
 * Stored in logs/rate_limits.json with flock concurrency protection.
 * Survives browser cookie resets and private browsing sessions.
 * Requires zero database schema modifications.
 */

declare(strict_types=1);

/**
 * Check if the given IP has exceeded rate limits for an action.
 *
 * @param string $action        e.g. 'contact', 'login'
 * @param string $ip            Client IP address
 * @param int    $maxAttempts   Maximum requests allowed in window (default 3)
 * @param int    $windowSeconds Time window in seconds (default 60)
 * @return array{allowed: bool, remaining: int, retry_after: int}
 */
function checkRateLimit(string $action, string $ip, int $maxAttempts = 3, int $windowSeconds = 60): array
{
    $logDir = APP_ROOT . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $filePath = $logDir . '/rate_limits.json';
    $fp = @fopen($filePath, 'c+');

    if (!$fp) {
        // Fallback: if file open fails, allow request but log warning
        appLog('WARNING', "Could not open rate limit file: {$filePath}");
        return ['allowed' => true, 'remaining' => 1, 'retry_after' => 0];
    }

    // Acquire exclusive lock
    flock($fp, LOCK_EX);

    $filesize = filesize($filePath);
    $data = [];
    if ($filesize > 0) {
        $content = fread($fp, $filesize);
        $data = json_decode($content, true) ?: [];
    }

    $now = time();
    $key = $action . ':' . $ip;

    // Prune expired records across all entries to prevent unbounded file growth
    foreach ($data as $k => $entry) {
        if (!isset($entry['reset_at']) || $now > $entry['reset_at']) {
            unset($data[$k]);
        }
    }

    $entry = $data[$key] ?? null;

    if (!$entry || $now > $entry['reset_at']) {
        // New window
        $data[$key] = [
            'count'    => 1,
            'reset_at' => $now + $windowSeconds,
        ];
        $result = [
            'allowed'     => true,
            'remaining'   => $maxAttempts - 1,
            'retry_after' => 0,
        ];
    } else {
        // Within window
        if ($entry['count'] >= $maxAttempts) {
            $retryAfter = max(1, $entry['reset_at'] - $now);
            $result = [
                'allowed'     => false,
                'remaining'   => 0,
                'retry_after' => $retryAfter,
            ];
        } else {
            $data[$key]['count']++;
            $result = [
                'allowed'     => true,
                'remaining'   => max(0, $maxAttempts - $data[$key]['count']),
                'retry_after' => 0,
            ];
        }
    }

    // Rewind, truncate, and write
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
    fflush($fp);

    // Release lock and close
    flock($fp, LOCK_UN);
    fclose($fp);

    return $result;
}
