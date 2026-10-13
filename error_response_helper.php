<?php
/**
 * Shared error-response helper.
 *
 * API endpoints must never echo raw exception text back to the browser: PDO
 * messages disclose table/column names and SQL structure. Log the real error
 * server-side and return a generic message to the client.
 */

if (!function_exists('nexusSafeError')) {
    /**
     * Log an exception with request context. Always call this before returning
     * a generic error to the client.
     */
    function nexusSafeError(Throwable $e, string $context = ''): void
    {
        $where = $context !== '' ? $context : (string)($_SERVER['SCRIPT_NAME'] ?? 'unknown');
        error_log(sprintf(
            '[%s] %s: %s in %s:%d',
            $where,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }
}
