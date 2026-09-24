<?php
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

// ১. Edit Modal-er jonno data fetch kora
if ($action === 'get') {
    header('Content-Type: application/json; charset=utf-8');
    
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM amals WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $amal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($amal) {
        echo json_encode(['status' => 'success', 'data' => $amal], JSON_UNESCAPED_UNICODE);
    } else {
        $not_found_msg = ($lang === 'bn') 
            ? 'কোনো তথ্য পাওয়া যায়নি!' 
            : 'Data not found!';
        echo json_encode(['status' => 'error', 'message' => $not_found_msg], JSON_UNESCAPED_UNICODE);
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
        if ($stmt->execute([$date, $name, $range, $duration, $id, $user_id])) {
            $_SESSION['flash_msg'] = ($lang === 'bn') 
                ? "আমল সফলভাবে আপডেট হয়েছে!" 
                : "Amal updated successfully!";

            $success_msg = ($lang === 'bn') 
                ? "আপডেট সফল হয়েছে।" 
                : "Updated successfully.";

            echo json_encode(['status' => 'success', 'message' => $success_msg], JSON_UNESCAPED_UNICODE);
        } else {
            $error_msg = ($lang === 'bn') 
                ? "আপডেট ব্যর্থ হয়েছে।" 
                : "Update failed.";

            echo json_encode(['status' => 'error', 'message' => $error_msg], JSON_UNESCAPED_UNICODE);
        }
    } else {
        $fill_msg = ($lang === 'bn') 
            ? "সবগুলো ঘর পূরণ করুন!" 
            : "Please fill in all fields!";

        echo json_encode(['status' => 'error', 'message' => $fill_msg], JSON_UNESCAPED_UNICODE);
    }
    exit();
}
?>