<?php
require_once 'config.php';
checkAuth();

$user_id = $_SESSION['user_id'];

// Fetch user language preference
$user_stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
$lang = $user_info['language'] ?? 'bn';

if (isset($_GET['date'])) {
    $date = $_GET['date'];

    $stmt = $pdo->prepare("SELECT * FROM amals WHERE user_id = ? AND amal_date = ? ORDER BY id ASC");
    $stmt->execute([$user_id, $date]);
    $amals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($amals) {
        echo json_encode(['status' => 'success', 'data' => $amals]);
    } else {
        $msg = ($lang === 'bn') ? 'কোনো আমল পাওয়া যায়নি।' : 'No amals found for this date.';
        echo json_encode(['status' => 'error', 'message' => $msg]);
    }
    exit;
}

$invalid_msg = ($lang === 'bn') ? 'অকার্যকর অনুরোধ।' : 'Invalid request.';
echo json_encode(['status' => 'error', 'message' => $invalid_msg]);