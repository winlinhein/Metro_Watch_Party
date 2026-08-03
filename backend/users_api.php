<?php
session_start();

// Authentication & Admin Authorization Check
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) || 
    $_SESSION['user_role'] !== 'admin'
) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied: Admin permissions required']);
    exit();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';

$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// GET: Fetch users dynamically joined with roles table
// -------------------------------------------------------------
if ($method === 'GET') {
    try {
        $sql = "
            SELECT 
                u.user_id AS id,
                u.user_name AS name,
                u.email,
                u.status,
                u.is_premium,
                u.points,
                u.role_id,
                r.role AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            ORDER BY u.user_id DESC
        ";

        $stmt = $conn->query($sql);
        $rawUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $users = array_map(function($u) {
            // Capitalize DB status ('active' -> 'Active', 'pending' -> 'Pending', 'banned' -> 'Banned')
            $status = ucfirst(strtolower($u['status']));

            // Retrieve role dynamically from roles table
            $dbRole = !empty($u['role_name']) ? ucfirst(strtolower($u['role_name'])) : 'Standard';
            
            // Prioritize 'Premium' label if is_premium flag is set on standard user account
            if ((bool)$u['is_premium'] && strtolower($dbRole) === 'user') {
                $role = 'Premium';
            } else {
                $role = $dbRole;
            }

            return [
                'id'     => (int) $u['id'],
                'name'   => $u['name'],
                'email'  => $u['email'],
                'status' => $status,
                'role'   => $role,
                'points' => (int) ($u['points'] ?? 0)
            ];
        }, $rawUsers);

        echo json_encode($users);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// POST: Execute user directives (Ban / Status Update)
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data payload or missing action']);
        exit();
    }

    $action = $input['action'];
    $userId = $input['id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit();
    }

    // Process Ban Directive
    if ($action === 'ban') {
        $reason = trim($input['reason'] ?? 'Manual Suspension');
        $notes  = trim($input['notes'] ?? '');

        try {
            $stmt = $conn->prepare("UPDATE users SET status = 'banned' WHERE user_id = ?");
            $stmt->execute([$userId]);

            echo json_encode([
                'success' => true, 
                'message' => "User ID {$userId} has been suspended.",
                'user_id' => (int)$userId
            ]);
            exit();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to suspend user: ' . $e->getMessage()]);
            exit();
        }
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action directive']);
    exit();
}
?>