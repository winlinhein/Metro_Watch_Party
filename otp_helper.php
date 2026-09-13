<?php

const NEXUS_OTP_DURATION = 180;

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
