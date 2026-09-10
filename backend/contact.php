<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../mail_helper.php';

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

$to = mailEnv('CONTACT_TO', mailIdentityEmail());
$topicLabel = ucfirst($topic);

try {
    sendNexusMail(
        $to,
        "Nexus contact — {$topicLabel} — {$name}",
        "
        <div style='font-family: Arial, sans-serif; background:#050505; color:#ffffff; padding:24px; border-radius:12px;'>
            <h2 style='color:#ef4444; margin:0 0 12px;'>New Nexus signal</h2>
            <p style='color:#cccccc; margin:0 0 8px;'><strong>From:</strong> {$name} ({$email})</p>
            <p style='color:#cccccc; margin:0 0 16px;'><strong>Topic:</strong> {$topicLabel}</p>
            <div style='background:#111; border:1px solid #222; border-radius:8px; padding:16px; color:#ddd; white-space:pre-wrap;'>{$message}</div>
        </div>
        ",
        "From: {$name} ({$email})\nTopic: {$topicLabel}\n\n{$message}",
        ['reply_to' => ['email' => $email, 'name' => $name]]
    );

    $_SESSION['contact_count']++;
    $_SESSION['contact_last'] = time();

    echo json_encode(['success' => true, 'message' => 'Signal received. We will reply shortly.']);
} catch (Throwable $e) {
    error_log('Contact mail error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not send right now. Try again in a moment.']);
}
