<?php
require_once 'config.php';
checkAuth();

// Database connection-e UTF-8 execution nishchit kora
$pdo->exec("SET NAMES utf8mb4");

$action = $_REQUEST['action'] ?? '';

// 1. Amal Jog Kora (ADD)
if ($action === 'add') {
    // JSON response header set kora
    header('Content-Type: application/json; charset=utf-8');

    $date = $_POST['amal_date'] ?? '';
    $name = trim($_POST['amal_name'] ?? '');
    $range = trim($_POST['time_range'] ?? '');
    $duration = trim($_POST['duration_time'] ?? '');

    if (!empty($date) && !empty($name) && !empty($range) && !empty($duration)) {
        $stmt = $pdo->prepare("INSERT INTO amals (user_id, amal_date, amal_name, time_range, duration_time) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $date, $name, $range, $duration])) {
            $_SESSION['flash_msg'] = "Amal sofolbhabe jukto hoyeche!";
            echo json_encode(['status' => 'success', 'message' => 'Sofolbhabe shongrokkhto hoyeche.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database-e tothyo shongrokkhon byartho hoyeche.'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Shobgulo ghor puron korun!'], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// 2. Amal Muche Fela (DELETE)
if ($action === 'delete') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM amals WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, $_SESSION['user_id']])) {
        $_SESSION['flash_msg'] = "Amal muche fela hoyeche!";
    }
    header("Location: index.php");
    exit();
}
?>