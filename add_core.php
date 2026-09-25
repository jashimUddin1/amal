<?php //add_core.php
require_once 'config.php';
checkAuth();

// Database connection-e UTF-8 execution nishchit kora
$pdo->exec("SET NAMES utf8mb4");

$user_id = $_SESSION['user_id'];

// Fetch user language preference
$user_stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
$lang = $user_info['language'] ?? 'bn';

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
        if ($stmt->execute([$user_id, $date, $name, $range, $duration])) {
            $_SESSION['flash_msg'] = ($lang === 'bn') 
                ? "আমল সফলভাবে যুক্ত হয়েছে!" 
                : "Amal added successfully!";

            $json_msg = ($lang === 'bn') 
                ? "সফলভাবে সংরক্ষিত হয়েছে।" 
                : "Successfully saved.";

            echo json_encode(['status' => 'success', 'message' => $json_msg], JSON_UNESCAPED_UNICODE);
        } else {
            $json_err = ($lang === 'bn') 
                ? "ডাটাবেজে তথ্য সংরক্ষণ ব্যর্থ হয়েছে।" 
                : "Failed to save data in database.";

            echo json_encode(['status' => 'error', 'message' => $json_err], JSON_UNESCAPED_UNICODE);
        }
    } else {
        $field_err = ($lang === 'bn') 
            ? "সবগুলো ঘর পূরণ করুন!" 
            : "Please fill in all fields!";

        echo json_encode(['status' => 'error', 'message' => $field_err], JSON_UNESCAPED_UNICODE);
    }
    exit();
}

// 2. Amal Muche Fela (DELETE)
if ($action === 'delete') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM amals WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, $user_id])) {
        $_SESSION['flash_msg'] = ($lang === 'bn') 
            ? "আমল মুছে ফেলা হয়েছে!" 
            : "Amal deleted successfully!";
    }
    header("Location: index.php");
    exit();
}
?>