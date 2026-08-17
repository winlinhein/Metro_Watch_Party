<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    ($_SESSION['user_role'] ?? '') !== 'admin'
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'error' => 'Admin privileges required.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Read JSON Request
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents('php://input'),
    true
);

$action = $data['action'] ?? '';

/*
|--------------------------------------------------------------------------
| Only POST requests allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'error' => 'POST requests only.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| PROMOTE USER TO MODERATOR
| role_id = 3
|--------------------------------------------------------------------------
*/

if ($action === 'promote_moderator') {

    $userId = intval($data['id'] ?? 0);

    if ($userId <= 0) {

        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID.'
        ]);

        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE users
         SET role_id = 3
         WHERE user_id = ?"
    );

    if ($stmt->execute([$userId])) {

        echo json_encode([
            'success' => true,
            'message' => 'User promoted to Moderator.'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred while promoting.'
        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| DEMOTE ADMIN TO MODERATOR
| role_id = 3
|--------------------------------------------------------------------------
*/

if ($action === 'demote_admin') {

    $userId = intval($data['id'] ?? 0);

    if ($userId <= 0) {

        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID.'
        ]);

        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE users
         SET role_id = 3
         WHERE user_id = ?"
    );

    if ($stmt->execute([$userId])) {

        echo json_encode([
            'success' => true,
            'message' => 'Admin demoted to Moderator.'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred while demoting the admin.'
        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| DEMOTE MODERATOR TO STANDARD USER
| role_id = 2
|--------------------------------------------------------------------------
*/

if ($action === 'demote_moderator') {

    $userId = intval($data['id'] ?? 0);

    if ($userId <= 0) {

        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID.'
        ]);

        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE users
         SET role_id = 2
         WHERE user_id = ?"
    );

    if ($stmt->execute([$userId])) {

        echo json_encode([
            'success' => true,
            'message' => 'Moderator demoted to User.'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'error' => 'Database error occurred while demoting.'
        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| BAN / SUSPEND USER
|--------------------------------------------------------------------------
*/

if ($action === 'ban') {

    $userId = intval($data['id'] ?? 0);

    $reason = $data['reason'] ?? 'Violation of terms';

    $notes = $data['notes'] ?? '';

    if ($userId <= 0) {

        echo json_encode([
            'success' => false,
            'error' => 'Invalid user ID.'
        ]);

        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE users
         SET status = 'banned'
         WHERE user_id = ?"
    );

    if ($stmt->execute([$userId])) {

        /*
        |--------------------------------------------------------------------------
        | Pusher force logout
        |--------------------------------------------------------------------------
        */

        triggerPusherEvent(
            "user-{$userId}",
            'force_logout',
            [
                'message' =>
                    'Your account has been banned. Reason: ' .
                    htmlspecialchars($reason)
            ]
        );

        echo json_encode([
            'success' => true,
            'message' => 'User banned and disconnected.'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'error' => 'Failed to ban user in the database.'
        ]);
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| Unknown Action
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => false,
    'error' => 'Invalid request method or action.'
]);

?>