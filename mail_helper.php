<?php

/**
 * Send mail via Resend (HTTPS) when RESEND_API_KEY is set.
 * Falls back to Gmail SMTP for local development.
 *
 * Render blocks outbound SMTP for many plans; Resend uses HTTPS and works there.
 *
 * Env:
 *   RESEND_API_KEY   required on Render
 *   MAIL_FROM        e.g. Nexus <noreply@yourdomain.com>  (must be a verified Resend domain)
 *   MAIL_FROM_NAME   optional, used with SMTP fallback
 *   SMTP_USER        Gmail address for local SMTP
 *   SMTP_PASS        Gmail app password for local SMTP
 *   CONTACT_TO       inbox for contact form
 */

function loadMailDotEnv(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    $path = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, "\"'");
        if ($name === '') {
            continue;
        }
        if (getenv($name) === false || getenv($name) === '') {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function mailEnv(string $key, string $default = ''): string
{
    loadMailDotEnv();
    foreach ([getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null, $_SERVER['REDIRECT_' . $key] ?? null] as $value) {
        if (is_string($value) && $value !== '') {
            return $value;
        }
    }
    return $default;
}

function isRenderHost(): bool
{
    return mailEnv('RENDER') === 'true' || mailEnv('RENDER_SERVICE_ID') !== '';
}

function mailIdentityEmail(): string
{
    $from = mailEnv('MAIL_FROM');
    if ($from !== '' && preg_match('/<([^>]+)>/', $from, $m) && filter_var(trim($m[1]), FILTER_VALIDATE_EMAIL)) {
        return trim($m[1]);
    }
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return $from;
    }
    $contact = mailEnv('CONTACT_TO');
    if ($contact !== '' && filter_var($contact, FILTER_VALIDATE_EMAIL)) {
        return $contact;
    }
    return mailEnv('SMTP_USER', 'wailinhtun338@gmail.com');
}

function mailFromAddress(): string
{
    $from = mailEnv('MAIL_FROM');
    if ($from !== '') {
        return $from;
    }
    return 'Nexus <' . mailIdentityEmail() . '>';
}

function mailFromName(): string
{
    return mailEnv('MAIL_FROM_NAME', 'Nexus');
}

function otpEmailHtml(string $code, string $purpose): string
{
    $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $purpose = htmlspecialchars($purpose, ENT_QUOTES, 'UTF-8');
    return "
        <div style='font-family: Arial, sans-serif; background: #050505; color: #ffffff; padding: 24px; border-radius: 12px;'>
            <h2 style='color: #4f46e5; margin: 0 0 12px;'>Nexus Verification</h2>
            <p style='color: #cccccc; margin: 0 0 8px;'>{$purpose}</p>
            <h1 style='color: #dc2626; letter-spacing: 6px; font-size: 32px; margin: 12px 0;'>{$code}</h1>
            <p style='color: #888888; font-size: 12px; margin: 0;'>Valid for 3 minutes. If you did not request this, you can ignore the email.</p>
        </div>
    ";
}

function otpPurposeForType(string $otpType): string
{
    switch ($otpType) {
        case 'login':
            return 'Your login verification code is:';
        case 'forgot':
            return 'Your password reset code is:';
        default:
            return 'Your verification code is:';
    }
}

function otpSubjectForType(string $otpType): string
{
    switch ($otpType) {
        case 'login':
            return 'Nexus Login — verification code';
        case 'forgot':
            return 'Nexus Password Reset — verification code';
        default:
            return 'Nexus Registration — verification code';
    }
}

function mailLogFailure(Throwable $e): void
{
    $line = date('c') . ' ' . $e->getMessage() . "\n";
    @file_put_contents(__DIR__ . '/cache/mail_error.log', $line, FILE_APPEND);
    error_log('Nexus mail error: ' . $e->getMessage());
}

function mailUserError(Throwable $e): string
{
    $msg = trim(preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $e->getMessage()) ?? '');
    if (stripos($msg, 'RESEND_API_KEY') !== false) {
        return 'Email is not configured on the server. Add RESEND_API_KEY in Render Environment, then redeploy.';
    }
    if (preg_match('/only send testing emails|verify a domain|from address is not verified/i', $msg)) {
        return 'Resend is still in test mode. Codes can only go to your Resend account email until you verify a domain and set MAIL_FROM to that address.';
    }
    if ($msg !== '') {
        return 'Could not send a verification code. ' . $msg;
    }
    return 'Could not send a verification code. Please try again.';
}

function sendOtpEmail(string $to, string $code, string $otpType = 'register'): void
{
    $purpose = otpPurposeForType($otpType);
    sendNexusMail(
        $to,
        otpSubjectForType($otpType),
        otpEmailHtml($code, $purpose),
        "Your Nexus code is {$code}. It expires in 3 minutes."
    );
}

function sendNexusMail(string $to, string $subject, string $html, string $text = '', array $options = []): void
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid recipient email.');
    }

    $apiKey = mailEnv('RESEND_API_KEY');
    if ($apiKey !== '') {
        try {
            sendMailViaResend($apiKey, $to, $subject, $html, $text, $options);
            return;
        } catch (Throwable $e) {
            mailLogFailure($e);
            // Local/dev: keep Gmail working if Resend test mode rejects other inboxes.
            if (!isRenderHost()) {
                sendMailViaSmtp($to, $subject, $html, $text, $options);
                return;
            }
            throw $e;
        }
    }

    // Render blocks outbound SMTP. Require Resend there instead of hanging on Gmail.
    if (isRenderHost()) {
        throw new RuntimeException('Set RESEND_API_KEY (and MAIL_FROM) in the Render environment.');
    }

    sendMailViaSmtp($to, $subject, $html, $text, $options);
}

function sendMailViaResend(string $apiKey, string $to, string $subject, string $html, string $text, array $options = []): void
{
    $identity = mailIdentityEmail();
    $from = mailFromAddress();
    $replyTo = '';
    if (!empty($options['reply_to'])) {
        $reply = $options['reply_to'];
        $replyTo = is_array($reply) ? (string)($reply['email'] ?? '') : (string)$reply;
    }
    if ($replyTo === '') {
        $replyTo = $identity;
    }

    $fromEmail = $identity;
    if (preg_match('/@(gmail|googlemail|yahoo|outlook|hotmail|live)\.com$/i', $fromEmail)) {
        // Resend cannot send as a Gmail address. Use the test sender and reply to the Gmail inbox.
        $from = 'Nexus <beth.t@example.com>';
        $replyTo = $fromEmail;
    }

    $payload = [
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
        'reply_to' => $replyTo,
    ];
    if ($text !== '') {
        $payload['text'] = $text;
    }

    postResendEmail($apiKey, $payload);
}

function postResendEmail(string $apiKey, array $payload): void
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $raw = '';
    $status = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.resend.com/emails');
        if ($ch === false) {
            throw new RuntimeException('Could not start Resend request.');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = (string)curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno) {
            throw new RuntimeException('Resend connection failed.');
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $raw = (string)file_get_contents('https://api.resend.com/emails', false, $context);
        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
    }

    $decoded = json_decode((string)$raw, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? $raw) : (string)$raw;
        throw new RuntimeException('Resend rejected the email: ' . $message);
    }
}

function sendMailViaSmtp(string $to, string $subject, string $html, string $text = '', array $options = []): void
{
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    $smtpUser = mailEnv('SMTP_USER');
    $smtpPass = mailEnv('SMTP_PASS');
    // Gmail SMTP must authenticate as the account that owns the app password.
    if ($smtpPass === '') {
        $smtpUser = 'koz51751@gmail.com';
        $smtpPass = 'kfnc dyla izdh zmpd';
    } elseif ($smtpUser === '') {
        $smtpUser = 'koz51751@gmail.com';
    }

    $fromName = mailFromName();
    $fromEmail = $smtpUser;

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host = mailEnv('SMTP_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)mailEnv('SMTP_PORT', '587');
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];
    $mail->setFrom($fromEmail, $fromName);
    $identity = mailIdentityEmail();
    if (strcasecmp($identity, $fromEmail) !== 0) {
        $mail->addReplyTo($identity, $fromName);
    }
    $mail->addAddress($to);
    if (!empty($options['reply_to'])) {
        $reply = $options['reply_to'];
        if (is_array($reply)) {
            $mail->addReplyTo((string)($reply['email'] ?? ''), (string)($reply['name'] ?? ''));
        } else {
            $mail->addReplyTo((string)$reply);
        }
    }
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $text !== '' ? $text : strip_tags($html);
    $mail->send();
}
