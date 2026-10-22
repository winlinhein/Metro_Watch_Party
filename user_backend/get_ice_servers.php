<?php
session_start();
header('Content-Type: application/json');
// Private 30 s cache: fresh enough for TURN credential rotation, fast enough for
// the prefetch link in watch_party.php to prime the cache before JS loads it.
header('Cache-Control: private, max-age=30');

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
