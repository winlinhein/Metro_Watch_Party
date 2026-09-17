<?php
session_start();

$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !in_array($role, ['admin', 'moderator'], true)
) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied: Admin permissions required']);
    exit();
}

header('Content-Type: application/json');
session_write_close();
require_once __DIR__ . '/../conn.php';

function formatChange(float $current, float $previous): string
{
    if ($previous == 0.0) {
        return $current > 0 ? '+100%' : '0%';
    }

    $pct = (($current - $previous) / $previous) * 100;
    $sign = $pct >= 0 ? '+' : '';

    return $sign . round($pct) . '%';
}

function scalarCount(PDO $conn, string $sql, array $params = []): int
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function scalarSum(PDO $conn, string $sql, array $params = []): float
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return (float) $stmt->fetchColumn();
}

function paymentAmountToDollars(float $amount): float
{
    if ($amount >= 50 && abs($amount - round($amount)) < 0.001) {
        return $amount / 100;
    }
    return $amount;
}

function buildDailyChartSeries(PDO $conn, int $days, string $type): array
{
    $map = [];
    $span = max(0, $days - 1);
    try {
        if ($type === 'revenue') {
            $stmt = $conn->query(
                "SELECT DATE(created_at) AS bucket, COALESCE(SUM(amount), 0) AS total
                 FROM payment_transactions
                 WHERE status = 'success'
                   AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$span} DAY)
                 GROUP BY DATE(created_at)"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(string)$row['bucket']] = paymentAmountToDollars((float)$row['total']);
            }
        } else {
            $stmt = $conn->query(
                "SELECT DATE(created_at) AS bucket, COUNT(*) AS total
                 FROM persistent_session
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$span} DAY)
                 GROUP BY DATE(created_at)"
            );
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(string)$row['bucket']] = (float)$row['total'];
            }
        }
    } catch (Throwable $e) {
        $map = [];
    }

    $series = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = (new DateTimeImmutable('today'))->modify('-' . $i . ' days');
        $key = $date->format('Y-m-d');
        $value = (float)($map[$key] ?? 0);
        $values[] = $value;
        $series[] = [
            'label' => $days <= 7 ? $date->format('D') : $date->format('j M'),
            'value' => $value,
        ];
    }

    $max = max($values) ?: 1;
    foreach ($series as $i => $point) {
        $value = $point['value'];
        $series[$i]['display'] = $type === 'revenue'
            ? '$' . number_format($value, $value >= 100 ? 0 : 2)
            : (string)(int)round($value);
        $series[$i]['height'] = $value > 0
            ? max(8, (int)round(($value / $max) * 100))
            : 3;
    }

    return $series;
}

try {
    // role_id 2 = regular user (exclude admins)
    $userRoleFilter = 'role_id = 2';

    $totalUsers = scalarCount($conn, "SELECT COUNT(*) FROM users WHERE {$userRoleFilter}");

    $usersLast30 = scalarCount(
        $conn,
        "SELECT COUNT(*) FROM users WHERE {$userRoleFilter} AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $usersPrev30 = scalarCount(
        $conn,
        "SELECT COUNT(*) FROM users
         WHERE {$userRoleFilter}
           AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
           AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    $activeSessions = scalarCount(
        $conn,
        "SELECT COUNT(*) FROM rooms WHERE status = 'active'"
    );

    $sessionsLast7 = scalarCount(
        $conn,
        "SELECT COUNT(*) FROM rooms
         WHERE status = 'active'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    $sessionsPrev7 = scalarCount(
        $conn,
        "SELECT COUNT(*) FROM rooms
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
           AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );

    $totalRevenue = paymentAmountToDollars(scalarSum(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE status = 'success'"
    ));

    $revenueLast30 = paymentAmountToDollars(scalarSum(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) FROM payment_transactions
         WHERE status = 'success'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    ));
    $revenuePrev30 = paymentAmountToDollars(scalarSum(
        $conn,
        "SELECT COALESCE(SUM(amount), 0) FROM payment_transactions
         WHERE status = 'success'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
           AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    ));

    $activeConnections = scalarCount(
        $conn,
        'SELECT COUNT(*) FROM persistent_session WHERE expired_at > UNIX_TIMESTAMP()'
    );
    $connectionsLast7 = scalarCount(
        $conn,
        'SELECT COUNT(*) FROM persistent_session
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
    );
    $connectionsPrev7 = scalarCount(
        $conn,
        'SELECT COUNT(*) FROM persistent_session
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
           AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)'
    );

    $serverLoad = $totalUsers > 0
        ? min(100, (int) round(($activeConnections / $totalUsers) * 100))
        : 0;

    $loadLast7 = $totalUsers > 0
        ? min(100, (int) round(($connectionsLast7 / max($totalUsers, 1)) * 100))
        : 0;
    $loadPrev7 = $totalUsers > 0
        ? min(100, (int) round(($connectionsPrev7 / max($totalUsers, 1)) * 100))
        : 0;

    $stats = [
        [
            'label'  => 'Total Users',
            'value'  => number_format($totalUsers),
            'change' => formatChange((float) $usersLast30, (float) $usersPrev30),
            'icon'   => 'group',
        ],
        [
            'label'  => 'Active Sessions',
            'value'  => number_format($activeSessions),
            'change' => formatChange((float) $sessionsLast7, (float) $sessionsPrev7),
            'icon'   => 'live_tv',
        ],
        [
            'label'  => 'Revenue',
            'value'  => '$' . number_format($totalRevenue),
            'change' => formatChange($revenueLast30, $revenuePrev30),
            'icon'   => 'payments',
        ],
        [
            'label'  => 'Server Load',
            'value'  => $serverLoad . '%',
            'change' => formatChange((float) $loadLast7, (float) $loadPrev7),
            'icon'   => 'memory',
        ],
    ];

    $charts = [
        '7' => [
            'revenue' => buildDailyChartSeries($conn, 7, 'revenue'),
            'logins' => buildDailyChartSeries($conn, 7, 'logins'),
        ],
        '30' => [
            'revenue' => buildDailyChartSeries($conn, 30, 'revenue'),
            'logins' => buildDailyChartSeries($conn, 30, 'logins'),
        ],
    ];

    echo json_encode([
        'stats' => $stats,
        'charts' => $charts,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load dashboard stats: ' . $e->getMessage()]);
}
