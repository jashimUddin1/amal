<?php
require_once 'config.php';
checkAuth();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// 1. ADD AMAL
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $fozilot = trim($_POST['fozilot'] ?? '');

    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'আমলের শিরোনাম প্রদান করা আবশ্যক।']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO daily_amals (user_id, title, fozilot) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $fozilot]);

        echo json_encode(['status' => 'success', 'message' => 'আমল সফলভাবে যোগ করা হয়েছে।']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'ডাটাবেজ সমস্যা: ' . $e->getMessage()]);
    }
    exit;
}

// 2. UPDATE AMAL
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $fozilot = trim($_POST['fozilot'] ?? '');

    if ($id <= 0 || empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'আমলের শিরোনাম ও আইডি তথ্য সঠিক নয়!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE daily_amals SET title = ?, fozilot = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $fozilot, $id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'আমল সফলভাবে আপডেট করা হয়েছে।']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'ডাটাবেজ সমস্যা: ' . $e->getMessage()]);
    }
    exit;
}

// 3. DELETE AMAL
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'অকার্যকর আইডি!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM daily_amals WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => 'আমল সফলভাবে মুছে ফেলা হয়েছে।']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'ডাটাবেজ সমস্যা: ' . $e->getMessage()]);
    }
    exit;
}

// 4. SAVE SORTING ORDER
if ($action === 'save_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderData = json_decode($_POST['order'] ?? '[]', true);

    if (!empty($orderData)) {
        try {
            $stmt = $pdo->prepare("UPDATE daily_amals SET sort_order = ? WHERE id = ? AND user_id = ?");
            foreach ($orderData as $item) {
                $stmt->execute([(int)$item['sort_order'], (int)$item['id'], $user_id]);
            }
            echo json_encode(['status' => 'success', 'message' => 'ক্রম সফলভাবে সংরক্ষিত হয়েছে।']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'ডাটাবেজ সমস্যা: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'কোনো অর্ডার ডাটা পাওয়া যায়নি!']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'অকার্যকর রিকোয়েস্ট!']);
exit;