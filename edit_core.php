<?php
require_once 'config.php';
checkAuth();

// Database connection-e UTF-8 execution nishchit kora
$pdo->exec("SET NAMES utf8mb4");

$action = $_REQUEST['action'] ?? '';

// ১. Edit Modal-er jonno data fetch kora
if ($action === 'get') {
    header('Content-Type: application/json; charset=utf-8');
    
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM amals WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $amal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($amal) {
        echo json_encode(['status' => 'success', 'data' => $amal], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data pawoya jayni!'], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// ২. Update kora
if ($action === 'update') {
    header('Content-Type: application/json; charset=utf-8');

    $id = $_POST['id'] ?? 0;
    $date = $_POST['amal_date'] ?? '';
    $name = trim($_POST['amal_name'] ?? '');
    $range = trim($_POST['time_range'] ?? '');
    $duration = trim($_POST['duration_time'] ?? '');

    if (!empty($id) && !empty($date) && !empty($name) && !empty($range) && !empty($duration)) {
        $stmt = $pdo->prepare("UPDATE amals SET amal_date = ?, amal_name = ?, time_range = ?, duration_time = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$date, $name, $range, $duration, $id, $_SESSION['user_id']])) {
            $_SESSION['flash_msg'] = "Amal sofolbhabe update hoyeche!";
            echo json_encode(['status' => 'success', 'message' => 'Update hoyeche.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update byartho hoyeche.'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Shobgulo ghor puron korun!'], JSON_UNESCAPED_UNICODE);
    }
    exit();
}
?>