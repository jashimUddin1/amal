<?php
require_once 'config.php';
checkAuth();

// নির্বাচিত মাস এবং বছর (ডিফল্ট বর্তমান মাস)
$selected_month = $_GET['month'] ?? date('Y-m');

$stmt = $pdo->prepare("SELECT * FROM amals WHERE user_id = ? AND DATE_FORMAT(amal_date, '%Y-%m') = ? ORDER BY amal_date DESC, id DESC");
$stmt->execute([$_SESSION['user_id'], $selected_month]);
$monthly_amals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্যাশবোর্ড - আমল ট্র্যাকার</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header Navigation -->
    <nav class="bg-emerald-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">দৈনন্দিন আমল ট্র্যাকার</h1>
            <div class="flex gap-4 items-center">
                <a href="index.php" class="hover:underline">হোম / আজকের আমল</a>
                <a href="dashboard.php" class="hover:underline font-semibold">ড্যাশবোর্ড (মাসিক হিসাব)</a>
                <span class="text-sm bg-emerald-700 px-3 py-1 rounded-full"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="login.php?action=logout" class="bg-red-500 px-3 py-1 rounded text-sm hover:bg-red-600">লগআউট</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4 max-w-5xl">
        
        <!-- Filter Header -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">মাসিক আমল বিবরণী</h2>
                <p class="text-gray-500 text-sm">আপনার নির্বাচিত মাসের সকল আমল একনজরে দেখুন</p>
            </div>

            <!-- Month Filter Form -->
            <form method="GET" class="flex items-center gap-2">
                <input type="month" name="month" value="<?php echo $selected_month; ?>" class="border p-2 rounded focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded hover:bg-emerald-700">ফিল্টার করুন</button>
            </form>
        </div>

        <!-- Monthly Data List Table -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b text-gray-700">
                            <th class="p-3">তারিখ</th>
                            <th class="p-3">আমলের নাম</th>
                            <th class="p-3">সময়সীমা (Range)</th>
                            <th class="p-3">নির্দিষ্ট সময়</th>
                            <th class="p-3 text-center">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($monthly_amals) > 0): ?>
                            <?php foreach($monthly_amals as $amal): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3 font-semibold text-gray-600"><?php echo date('d-M-Y', strtotime($amal['amal_date'])); ?></td>
                                    <td class="p-3 font-medium text-emerald-800"><?php echo htmlspecialchars($amal['amal_name']); ?></td>
                                    <td class="p-3"><span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($amal['time_range']); ?></span></td>
                                    <td class="p-3"><?php echo htmlspecialchars($amal['duration_time']); ?></td>
                                    <td class="p-3 text-center">
                                        <a href="add_core.php?action=delete&id=<?php echo $amal['id']; ?>" onclick="return confirm('আপনি কি নিশ্চিত এটি মুছে ফেলতে চান?')" class="text-red-500 hover:text-red-700 font-semibold text-sm">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">এই মাসে কোনো আমলের তথ্য পাওয়া যায়নি।</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>