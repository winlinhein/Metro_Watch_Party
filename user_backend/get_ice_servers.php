<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (empty($_SESSION['authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.', 'iceServers' => []]);
    exit;
}

session_write_close();
require_once __DIR__ . '/../ice_servers_helper.php';

$iceServers = nexusIceServers();
echo json_encode([
    'success' => true,
    'iceServers' => $iceServers,
    'hasTurn' => (bool)array_filter($iceServers, static function ($server) {
        $urls = $server['urls'] ?? '';
        $list = is_array($urls) ? $urls : [$urls];
        foreach ($list as $url) {
            if (stripos((string)$url, 'turn:') === 0 || stripos((string)$url, 'turns:') === 0) {
                return true;
            }
        }
        return false;
    }),
], JSON_UNESCAPED_SLASHES);
