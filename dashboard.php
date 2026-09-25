<?php //dashboard.php
require_once 'config.php';
checkAuth();

$user_id =$_SESSION['user_id'];

// 1. User Language fetch kora
$user_stmt =$pdo->prepare("SELECT language FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info =$user_stmt->fetch(PDO::FETCH_ASSOC);
$lang =$user_info['language'] ?? 'bn';

// Monthly or overall records grouped by date
$stmt =$pdo->prepare("
    SELECT 
        amal_date,
        COUNT(id) as total_amals,
        GROUP_CONCAT(duration_time SEPARATOR '||') as durations
    FROM amals 
    WHERE user_id = ? 
    GROUP BY amal_date 
    ORDER BY amal_date DESC
");
$stmt->execute([$user_id]);
$daily_records =$stmt->fetchAll();

// Grand Total Calculation Variables
$grand_total_seconds = 0;
$grand_total_amals = 0;
$total_days_count = count($daily_records);

// Process daily summary
$processed_data = [];

foreach ($daily_records as $record) {$durations_array = explode('||', $record['durations']);$day_seconds = 0;

    foreach ($durations_array as $duration_str) {$duration_str = strtolower(trim($duration_str));$hrs = 0;
        $mins = 0;
        $secs = 0;

        // Hour (h, hr, hour) check
        if (preg_match('/(\d+)\s*(?:hour|hr|h)/', $duration_str,$h_match)) {
            $hrs = (int)$h_match[1];
        }

        // Minute (min, minute, m) check
        if (preg_match('/(\d+)\s*(?:minute|min|m)/', $duration_str,$m_match)) {
            $mins = (int)$m_match[1];
        }

        // Second (sec, second, s) check
        if (preg_match('/(\d+)\s*(?:second|sec|s)/', $duration_str,$s_match)) {
            $secs = (int)$s_match[1];
        }

        // Fallback for raw numbers without unit
        if ($hrs == 0 &&$mins == 0 && $secs == 0 && is_numeric($duration_str)) {
            $mins = (int)$duration_str;
        }

        // Calculate total seconds for this single entry
        $day_seconds += ($hrs * 3600) + ($mins * 60) +$secs;
    }

    $grand_total_seconds +=$day_seconds;
    $grand_total_amals +=$record['total_amals'];

    // Format daily duration cleanly using Array
    $d_hours = floor($day_seconds / 3600);
    $d_rem_secs =$day_seconds % 3600;
    $d_mins = floor($d_rem_secs / 60);
    $d_secs =$d_rem_secs % 60;

    $parts = [];
    if ($d_hours > 0) {$parts[] = $d_hours . ($lang === 'bn' ? " ঘণ্টা" : " Hour");
    }
    if ($d_mins > 0) {$parts[] = $d_mins . ($lang === 'bn' ? " মিনিট" : " Min");
    }
    if ($d_secs > 0 || empty($parts)) {$parts[] = $d_secs . ($lang === 'bn' ? " সেকেণ্ড" : " Sec");
    }
    $daily_formatted = implode(" ", $parts);

    $processed_data[] = [
        'amal_date' => $record['amal_date'],
        'total_amals' => $record['total_amals'],
        'formatted_duration' => $daily_formatted
    ];
}

// Grand Total Formatting Function
function formatSecondsToReadable(int $total_seconds, string$lang): string {
    $hours = floor($total_seconds / 3600);
    $rem_secs =$total_seconds % 3600;
    $minutes = floor($rem_secs / 60);
    $seconds =$rem_secs % 60;

    $parts = [];
    if ($hours > 0) {$parts[] = $hours . ($lang === 'bn' ? " ঘণ্টা" : " Hour");
    }
    if ($minutes > 0) {$parts[] = $minutes . ($lang === 'bn' ? " মিনিট" : " Min");
    }
    if ($seconds > 0 || empty($parts)) {$parts[] = $seconds . ($lang === 'bn' ? " সেকেণ্ড" : " Sec");
    }

    return implode(" ", $parts);
}

$grand_total_duration_str = formatSecondsToReadable($grand_total_seconds,$lang);

// Average Daily Time Calculation
$avg_seconds_per_day = ($total_days_count > 0) ? floor($grand_total_seconds / $total_days_count) : 0;
$avg_duration_str = formatSecondsToReadable($avg_seconds_per_day,$lang);

function windowDateFormatter(string $date_str): string {
    return date('d M Y, D', strtotime($date_str));
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Amal Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen antialiased text-gray-800">

    <!-- Responsive Navigation Header -->
    <nav class="bg-emerald-600 text-white shadow-md sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-lg font-bold tracking-wide">Amal Tracker</a>
            
            <button id="mobileMenuBtn" class="sm:hidden p-2 text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <div class="hidden sm:flex items-center gap-4 text-sm font-medium">
                <a href="index.php" class="hover:text-emerald-200 transition">Home</a>
                <a href="dashboard.php" class="hover:text-emerald-200 transition font-bold border-b-2 border-white pb-0.5">Dashboard</a>
                <a href="profile.php" class="hover:text-emerald-200 transition">
                    <span class="bg-emerald-700 px-3 py-1 rounded-full text-xs font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </a>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold transition">Logout</a>
            </div>
        </div>

        <div id="mobileMenu" class="hidden sm:hidden bg-emerald-700 px-4 pt-2 pb-4 space-y-2 border-t border-emerald-500">
            <a href="index.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium">Home / Ajker Amal</a>
            <a href="dashboard.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium">Dashboard (Masik Hisab)</a>
            <a href="profile.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium">Profile Settings</a>
            <div class="pt-2 border-t border-emerald-600 flex justify-between items-center">
                <span class="text-xs bg-emerald-800 px-2.5 py-1 rounded-full font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 py-6">

        <!-- Top Summary Cards Widget -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?php echo $lang === 'bn' ? 'মোট দিন' : 'Total Days'; ?></p>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1"><?php echo $total_days_count; ?> <?php echo$lang === 'bn' ? 'দিন' : 'Days'; ?></h3>
                </div>
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?php echo $lang === 'bn' ? 'মোট আমল' : 'Total Amals'; ?></p>
                    <h3 class="text-2xl font-black text-blue-600 mt-1"><?php echo $grand_total_amals; ?> <?php echo$lang === 'bn' ? 'টি' : ''; ?></h3>
                </div>
                <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider"><?php echo $lang === 'bn' ? 'গড়ে প্রতিদিন' : 'Daily Average'; ?></p>
                    <h3 class="text-lg font-black text-green-600 mt-1"><?php echo $avg_duration_str; ?></h3>
                </div>
                <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Dashboard Table Container -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
            <h2 class="text-lg sm:text-xl font-bold mb-4 text-gray-800 border-b border-gray-100 pb-2">
                <?php echo $lang === 'bn' ? 'দৈনিক আমলের হিসাব বিবরণী' : 'Daily Amal Statement'; ?>
            </h2>

            <!-- Mobile View: Responsive Cards -->
            <div class="block sm:hidden space-y-3">
                <?php if (count($processed_data) > 0): ?>
                    <?php foreach ($processed_data as$data): ?>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center gap-3">
                            <div class="space-y-1">
                                <p class="font-bold text-gray-900 text-sm">
                                    <?php echo windowDateFormatter($data['amal_date']); ?>
                                </p>
                                <div class="flex flex-wrap gap-2 items-center text-xs">
                                    <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-semibold">
                                        <?php echo $lang === 'bn' ? 'আমল: ' . $data['total_amals'] . ' টি' : 'Amals: ' . $data['total_amals']; ?>
                                    </span>
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-semibold">
                                        <?php echo $lang === 'bn' ? 'সময়: ' : 'Time: '; ?><?php echo $data['formatted_duration']; ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <button data-date="<?php echo $data['amal_date']; ?>" class="viewDetailBtn bg-emerald-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm hover:bg-emerald-700 transition block text-center">
                                    <?php echo $lang === 'bn' ? 'দেখুন' : 'View'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center py-6 text-gray-500 text-sm"><?php echo $lang === 'bn' ? 'এখনো কোনো ডাটা পাওয়া যায়নি।' : 'No data found yet.'; ?></p>
                <?php endif; ?>
            </div>

            <!-- Desktop View: Clean Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-xs uppercase tracking-wider">
                            <th class="p-3"><?php echo $lang === 'bn' ? 'তারিখ' : 'Date'; ?></th>
                            <th class="p-3"><?php echo $lang === 'bn' ? 'আমলের সংখ্যা' : 'Total Amals'; ?></th>
                            <th class="p-3"><?php echo $lang === 'bn' ? 'মোট সময়' : 'Total Time'; ?></th>
                            <th class="p-3 text-center"><?php echo $lang === 'bn' ? 'অ্যাকশন' : 'Action'; ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (count($processed_data) > 0): ?>
                            <?php foreach ($processed_data as$data): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 font-semibold text-gray-800">
                                        <?php echo windowDateFormatter($data['amal_date']); ?>
                                    </td>
                                    <td class="p-3">
                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full font-medium">
                                            <?php echo $data['total_amals']; ?> <?php echo$lang === 'bn' ? 'টি' : ''; ?>
                                        </span>
                                    </td>
                                    <td class="p-3 font-medium text-blue-600">
                                        <?php echo $data['formatted_duration']; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button data-date="<?php echo $data['amal_date']; ?>" class="viewDetailBtn bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-600 hover:text-white transition px-3 py-1 rounded text-xs font-bold">
                                            <?php echo $lang === 'bn' ? 'বিস্তারিত' : 'Details'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500"><?php echo $lang === 'bn' ? 'এখনো কোনো ডাটা পাওয়া যায়নি।' : 'No data found yet.'; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grand Total Summary Card -->
        <div class="bg-gradient-to-br from-emerald-800 to-teal-900 text-white p-5 sm:p-6 rounded-2xl shadow-md">
            <h3 class="text-lg font-bold border-b border-emerald-600/60 pb-2 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <?php echo $lang === 'bn' ? 'সর্বমোট সারসংক্ষেপ (Overall Summary)' : 'Overall Summary'; ?>
            </h3>

            <div class="space-y-2 text-sm sm:text-base leading-relaxed text-emerald-100">
                <?php if ($lang === 'bn'): ?>
                    <p>
                        আপনি গত <span class="font-extrabold text-white underline decoration-emerald-400 decoration-2"><?php echo $total_days_count; ?> দিনে</span> 
                        সর্বমোট <span class="font-extrabold text-amber-300"><?php echo $grand_total_duration_str; ?></span> আমল করেছেন।
                    </p>
                    <p>
                        গড়ে প্রতিদিন আপনার আমলের সময় ছিল <span class="font-extrabold text-amber-300"><?php echo $avg_duration_str; ?></span>।
                    </p>
                <?php else: ?>
                    <p>
                        In the last <span class="font-extrabold text-white underline decoration-emerald-400 decoration-2"><?php echo $total_days_count; ?> days</span>, 
                        you have performed total <span class="font-extrabold text-amber-300"><?php echo $grand_total_duration_str; ?></span> of amals.
                    </p>
                    <p>
                        Your daily average duration was <span class="font-extrabold text-amber-300"><?php echo $avg_duration_str; ?></span>.
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Details Pop-up Modal -->
    <div id="detailModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-emerald-600 text-white px-5 py-4 flex justify-between items-center">
                <h3 class="font-bold text-lg" id="modalDateTitle"><?php echo $lang === 'bn' ? 'আমলের বিস্তারিত' : 'Amal Details'; ?></h3>
                <button id="closeDetailModal" class="text-white/80 hover:text-white text-2xl font-bold focus:outline-none">&times;</button>
            </div>

            <!-- Modal Content Body -->
            <div class="p-5 max-h-[70vh] overflow-y-auto" id="modalContent">
                <p class="text-center text-gray-500 py-4"><?php echo $lang === 'bn' ? 'ডাটা লোড হচ্ছে...' : 'Loading data...'; ?></p>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex justify-end">
                <button id="closeDetailModalBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold px-4 py-1.5 rounded-lg transition">
                    <?php echo $lang === 'bn' ? 'বন্ধ করুন' : 'Close'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript Handling Pop-up AJAX -->
    <script>
        $(document).ready(function() {
            const userLang = "<?php echo $lang; ?>";

            $('#mobileMenuBtn').on('click', function() {
                $('#mobileMenu').toggleClass('hidden');
            });

            // Open Pop-up Detail Modal
            $('.viewDetailBtn').on('click', function() {
                let dateStr = $(this).data('date');$('#modalDateTitle').text((userLang === 'bn' ? 'তারিখ: ' : 'Date: ') + dateStr);
                $('#modalContent').html('<p class="text-center text-gray-500 py-6">' + (userLang === 'bn' ? 'ডাটা লোড হচ্ছে...' : 'Loading data...') + '</p>');
                $('#detailModal').removeClass('hidden').addClass('flex');

                $.ajax({
                    url: 'get_daily_details.php?date=' + dateStr,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let html = '<div class="space-y-3">';
                            $.each(response.data, function(index, item) {
                                html += `
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex justify-between items-start mb-1">
                                            <h4 class="font-bold text-gray-800 text-base">${item.amal_name}</h4>
                                            <span class="text-xs bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">${item.duration_time}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 flex justify-between">
                                            <span>${userLang === 'bn' ? 'সময়সীমা: ' : 'Time Range: '}${item.time_range}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            $('#modalContent').html(html);
                        } else {
                            $('#modalContent').html('<p class="text-center text-red-500 py-4">' + response.message + '</p>');
                        }
                    },
                    error: function() {
                        $('#modalContent').html('<p class="text-center text-red-500 py-4">' + (userLang === 'bn' ? 'ডাটা লোড করতে সমস্যা হয়েছে।' : 'Error loading data.') + '</p>');
                    }
                });
            });

            // Close Modal Events
            $('#closeDetailModal, #closeDetailModalBtn').on('click', function() {
                $('#detailModal').addClass('hidden').removeClass('flex');
            });
        });
    </script>
</body>
</html>