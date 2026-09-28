<?php
require_once 'config.php';
checkAuth();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['status' => 'error', 'message' => 'তারিখ প্রদান করা হয়নি।']);
    exit;
}

try {
    // Fetch User Target Minutes (Default 120 minutes if not set in DB)
    $user_stmt = $pdo->prepare("SELECT daily_target_minutes FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $target_minutes = (int)($user_info['daily_target_minutes'] ?? 120); 

    // Fetch Amals for the selected date
    $stmt = $pdo->prepare("SELECT amal_name, duration_time, time_range FROM amals WHERE user_id = ? AND amal_date = ?");
    $stmt->execute([$user_id, $date]);
    $amals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_seconds = 0;

    foreach ($amals as $item) {
        $duration_str = strtolower(trim($item['duration_time']));
        $hrs = 0; $mins = 0; $secs = 0;

        if (preg_match('/(\d+)\s*(?:hour|hr|h)/', $duration_str, $h_match)) {
            $hrs = (int)$h_match[1];
        }
        if (preg_match('/(\d+)\s*(?:minute|min|m)/', $duration_str, $m_match)) {
            $mins = (int)$m_match[1];
        }
        if (preg_match('/(\d+)\s*(?:second|sec|s)/', $duration_str, $s_match)) {
            $secs = (int)$s_match[1];
        }

        if ($hrs == 0 && $mins == 0 && $secs == 0 && is_numeric($duration_str)) {
            $mins = (int)$duration_str;
        }

        $total_seconds += ($hrs * 3600) + ($mins * 60) + $secs;
    }

    $total_minutes = floor($total_seconds / 60);
    $hours = floor($total_seconds / 3600);
    $rem_minutes = floor(($total_seconds % 3600) / 60);

    echo json_encode([
        'status' => 'success',
        'data' => $amals,
        'total_minutes' => $total_minutes,
        'target_minutes' => $target_minutes,
        'hours' => $hours,
        'rem_minutes' => $rem_minutes
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'ডাটাবেজ সমস্যা: ' . $e->getMessage()]);
}
exit;