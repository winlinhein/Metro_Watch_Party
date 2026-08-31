<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$_SESSION['contact_count'] = (int) ($_SESSION['contact_count'] ?? 0);
$_SESSION['contact_last'] = (int) ($_SESSION['contact_last'] ?? 0);

if (time() - $_SESSION['contact_last'] < 8) {
    echo json_encode(['success' => false, 'message' => 'Please wait a moment before sending again.']);
    exit;
}
if ($_SESSION['contact_count'] >= 5) {
    echo json_encode(['success' => false, 'message' => 'Message limit reached for this session.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!empty($data['website'])) {
    echo json_encode(['success' => true, 'message' => 'Signal received.']);
    exit;
}

function contact_clean($value) {
    return htmlspecialchars(trim(stripslashes((string) $value)), ENT_QUOTES, 'UTF-8');
}

$name = contact_clean($data['name'] ?? '');
$email = contact_clean($data['email'] ?? '');
$topic = contact_clean($data['topic'] ?? 'general');
$message = contact_clean($data['message'] ?? '');

$allowedTopics = ['general', 'support', 'partnership', 'feedback', 'bug'];
if (!in_array($topic, $allowedTopics, true)) {
    $topic = 'general';
}

if (strlen($name) < 2) {
    echo json_encode(['success' => false, 'message' => 'Please enter your name.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']);
    exit;
}
if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please write a slightly longer message.']);
    exit;
}
if (strlen($message) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long.']);
    exit;
}

$smtpUser = getenv('SMTP_USER') ?: 'koz51751@gmail.com';
$smtpPass = getenv('SMTP_PASS') ?: 'kfnc dyla izdh zmpd';
$to = getenv('CONTACT_TO') ?: $smtpUser;
$topicLabel = ucfirst($topic);

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom($smtpUser, 'Nexus Contact');
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);
    $mail->isHTML(true);
    $mail->Subject = "Nexus contact — {$topicLabel} — {$name}";
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; background:#050505; color:#ffffff; padding:24px; border-radius:12px;'>
            <h2 style='color:#ef4444; margin:0 0 12px;'>New Nexus signal</h2>
            <p style='color:#cccccc; margin:0 0 8px;'><strong>From:</strong> {$name} ({$email})</p>
            <p style='color:#cccccc; margin:0 0 16px;'><strong>Topic:</strong> {$topicLabel}</p>
            <div style='background:#111; border:1px solid #222; border-radius:8px; padding:16px; color:#ddd; white-space:pre-wrap;'>{$message}</div>
        </div>
    ";
    $mail->AltBody = "From: {$name} ({$email})\nTopic: {$topicLabel}\n\n{$message}";
    $mail->send();

    $_SESSION['contact_count']++;
    $_SESSION['contact_last'] = time();

    echo json_encode(['success' => true, 'message' => 'Signal received. We will reply shortly.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Could not send right now. Try again in a moment.']);
}
