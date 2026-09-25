<?php //registration.php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Email check
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "এই ইমেইলটি ইতিমধ্যেই নিবন্ধিত!";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashedPassword])) {
                $success = "রেজিস্ট্রেশন সফল হয়েছে! অনুগ্রহ করে লগইন করুন।";
            } else {
                $error = "কোথাও ভুল হয়েছে, আবার চেষ্টা করুন।";
            }
        }
    } else {
        $error = "সকল ঘর পূরণ করুন!";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রেজিস্ট্রেশন - আমল ট্র্যাকার</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-emerald-600">নতুন অ্যাকাউন্ট তৈরি করুন</h2>
        
        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">নাম</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">ইমেইল</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-1">পাসওয়ার্ড</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-lg hover:bg-emerald-700 transition">সাইন আপ</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">
            অ্যাাকাউন্ট আছে? <a href="login.php" class="text-emerald-600 font-bold">লগইন করুন</a>
        </p>
    </div>
</body>
</html>