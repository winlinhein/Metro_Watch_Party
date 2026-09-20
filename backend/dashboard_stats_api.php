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

function rescaleChartSeries(array $series, string $type): array
{
    if (!$series) {
        return $series;
    }
    $values = array_map(static fn($point) => (float)($point['value'] ?? 0), $series);
    $max = max($values) ?: 1;
    foreach ($series as $i => $point) {
        $value = (float)($point['value'] ?? 0);
        $series[$i]['height'] = $value > 0
            ? max(8, (int)round(($value / $max) * 100))
            : 3;
    }
    return $series;
}

try {
    $userRow = $conn->query("
        SELECT
            SUM(role_id = 2) AS total_users,
            SUM(role_id = 2 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS users_last_30,
            SUM(role_id = 2 AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)) AS users_prev_30
        FROM users
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $roomRow = $conn->query("
        SELECT
            SUM(status = 'active') AS active_sessions,
            SUM(status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS sessions_last_7,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)) AS sessions_prev_7
        FROM rooms
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $payRow = $conn->query("
        SELECT
            COALESCE(SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) AS revenue_last_30,
            COALESCE(SUM(CASE WHEN status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) AS revenue_prev_30
        FROM payment_transactions
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $movieRow = $conn->query("
        SELECT
            COUNT(*) AS total_movies,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS movies_last_30,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)) AS movies_prev_30
        FROM movies
    ")->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalUsers = (int)($userRow['total_users'] ?? 0);
    $usersLast30 = (int)($userRow['users_last_30'] ?? 0);
    $usersPrev30 = (int)($userRow['users_prev_30'] ?? 0);
    $activeSessions = (int)($roomRow['active_sessions'] ?? 0);
    $sessionsLast7 = (int)($roomRow['sessions_last_7'] ?? 0);
    $sessionsPrev7 = (int)($roomRow['sessions_prev_7'] ?? 0);
    $totalRevenue = paymentAmountToDollars((float)($payRow['total_revenue'] ?? 0));
    $revenueLast30 = paymentAmountToDollars((float)($payRow['revenue_last_30'] ?? 0));
    $revenuePrev30 = paymentAmountToDollars((float)($payRow['revenue_prev_30'] ?? 0));
    $totalMovies = (int)($movieRow['total_movies'] ?? 0);
    $moviesLast30 = (int)($movieRow['movies_last_30'] ?? 0);
    $moviesPrev30 = (int)($movieRow['movies_prev_30'] ?? 0);

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
            'label'  => 'Total Movies',
            'value'  => number_format($totalMovies),
            'change' => formatChange((float) $moviesLast30, (float) $moviesPrev30),
            'icon'   => 'movie',
        ],
    ];

    $revenue30 = buildDailyChartSeries($conn, 30, 'revenue');
    $logins30 = buildDailyChartSeries($conn, 30, 'logins');
    $charts = [
        '7' => [
            'revenue' => rescaleChartSeries(array_slice($revenue30, -7), 'revenue'),
            'logins' => rescaleChartSeries(array_slice($logins30, -7), 'logins'),
        ],
        '30' => [
            'revenue' => $revenue30,
            'logins' => $logins30,
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
