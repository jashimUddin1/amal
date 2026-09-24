<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Logout handling
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    setcookie('remember_me', '', time() - 3600, "/");
    session_destroy();
    header("Location: login.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            // Remember Me (Cookie 30 Days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), "/"); // 30 days
                
                $updateStmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $updateStmt->execute([$token, $user['id']]);
            }

            header("Location: index.php");
            exit();
        } else {
            $error = "ইমেইল অথবা পাসওয়ার্ড ভুল!";
        }
    } else {
        $error = "সবগুলো ফিল্ড পূরণ করুন!";
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন - আমল ট্র্যাকার</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-emerald-600">লগইন করুন</h2>
        
        <?php if($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">ইমেইল</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-1">পাসওয়ার্ড</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="mr-2 rounded text-emerald-600 focus:ring-emerald-500">
                    আমাকে ৩০ দিন মনে রাখো (Remember Me)
                </label>
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-lg hover:bg-emerald-700 transition">লগইন</button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-600">
            অ্যাকাউন্ট নেই? <a href="registration.php" class="text-emerald-600 font-bold">রেজিস্ট্রেশন করুন</a>
        </p>
    </div>
</body>
</html>