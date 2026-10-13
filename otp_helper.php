<?php

const NEXUS_OTP_DURATION = 180;
const NEXUS_OTP_MAX_FAILURES = 5;
const NEXUS_OTP_LOCK_SECONDS = 600;

/**
 * Has this email/OTP-type pair exceeded its failed-verification allowance?
 * Used to stop 6-digit codes being brute-forced inside their lifetime.
 */
function nexusOtpIsLockedOut(PDO $conn, string $email, string $otpType): bool
{
    try {
        $stmt = $conn->prepare("
            SELECT failed_login_attempts, lock_out_until
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $lockUntil = (int)($row['lock_out_until'] ?? 0);
        $attempts  = (int)($row['failed_login_attempts'] ?? 0);

        if ($lockUntil > 0 && $lockUntil > time()) {
            return true;
        }
        // Lockout expired: clear it and start a fresh allowance.
        if ($lockUntil > 0) {
            $conn->prepare("UPDATE users SET failed_login_attempts = 0, lock_out_until = NULL WHERE email = ?")
                 ->execute([$email]);
            return false;
        }
        return $attempts >= NEXUS_OTP_MAX_FAILURES;
    } catch (Throwable $e) {
        error_log('nexusOtpIsLockedOut: ' . $e->getMessage());
        return false;
    }
}

/**
 * Record a failed OTP verification and lock the account once the allowance is
 * exhausted. The pending OTP is deleted on lockout so a fresh code is required.
 */
function nexusOtpRegisterFailure(PDO $conn, string $email, string $otpType): void
{
    try {
        $stmt = $conn->prepare("
            UPDATE users
            SET failed_login_attempts = failed_login_attempts + 1,
                lock_out_until = CASE
                    WHEN failed_login_attempts + 1 >= ? THEN ?
                    ELSE lock_out_until
                END
            WHERE email = ?
        ");
        $stmt->execute([NEXUS_OTP_MAX_FAILURES, time() + NEXUS_OTP_LOCK_SECONDS, $email]);

        $check = $conn->prepare("SELECT failed_login_attempts FROM users WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ((int)$check->fetchColumn() >= NEXUS_OTP_MAX_FAILURES) {
            $conn->prepare("DELETE FROM otp_verification WHERE email = ? AND otp_type = ?")
                 ->execute([$email, $otpType]);
        }
    } catch (Throwable $e) {
        error_log('nexusOtpRegisterFailure: ' . $e->getMessage());
    }
}

/**
 * Clear the failure counter after a successful verification.
 */
function nexusOtpClearFailures(PDO $conn, string $email): void
{
    try {
        $conn->prepare("
            UPDATE users
            SET failed_login_attempts = 0, lock_out_until = NULL, last_failed_login = NULL
            WHERE email = ?
        ")->execute([$email]);
    } catch (Throwable $e) {
        error_log('nexusOtpClearFailures: ' . $e->getMessage());
    }
}

function nexusCurrentOtpStatus(PDO $conn, string $email, ?string $otpType = null): array
{
    $now = time();
    $status = [
        'success' => false,
        'expires_at' => 0,
        'server_now' => $now,
        'remaining' => 0,
        'duration' => NEXUS_OTP_DURATION,
        'otp_type' => $otpType ?: '',
    ];

    if ($email === '') {
        return $status;
    }

    try {
        if ($otpType) {
            $stmt = $conn->prepare("
                SELECT otp_type, expires_at
                FROM otp_verification
                WHERE email = ? AND otp_type = ?
                ORDER BY expires_at DESC
                LIMIT 1
            ");
            $stmt->execute([$email, $otpType]);
        } else {
            $stmt = $conn->prepare("
                SELECT otp_type, expires_at
                FROM otp_verification
                WHERE email = ?
                ORDER BY expires_at DESC
                LIMIT 1
            ");
            $stmt->execute([$email]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $status;
        }

        $expires = (int)$row['expires_at'];
        $status['success'] = true;
        $status['expires_at'] = $expires;
        $status['remaining'] = max(0, $expires - $now);
        $status['otp_type'] = (string)$row['otp_type'];
    } catch (Throwable $e) {
        error_log('nexusCurrentOtpStatus: ' . $e->getMessage());
    }

    return $status;
}
