<?php
require_once 'config.php';
checkAuth();

date_default_timezone_set('Asia/Dhaka');

$user_id = $_SESSION['user_id'];

// Fetch user settings and language
$user_stmt = $pdo->prepare("SELECT language FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
$lang = $user_info['language'] ?? 'bn';

// Fetch all daily amals ordered by sort_order
$stmt = $pdo->prepare("SELECT * FROM daily_amals WHERE user_id = ? ORDER BY sort_order ASC, id DESC");
$stmt->execute([$user_id]);
$daily_amals = $stmt->fetchAll(PDO::FETCH_ASSOC);

function convertToBanglaNumber($number = '')
{
    if ($number === null || $number === '') return '';
    $en_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bn_digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($en_digits, $bn_digits, (string)$number);
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'bn' ? 'দৈনিক আমল ও ফজিলত' : 'Daily Amal & Virtues'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- HTML5 Sortable for Easy Drag and Drop Sorting -->
    <script src="https://cdn.jsdelivr.net/npm/html5sortable@0.13.3/dist/html5sortable.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen antialiased text-gray-800">

    <!-- Navigation Header -->
    <nav class="bg-emerald-600 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-lg font-bold tracking-wide"><?php echo $lang === 'bn' ? 'আমল ট্র্যাকার' : 'Amal Tracker'; ?></a>

            <button id="mobileMenuBtn" class="sm:hidden p-2 text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <div class="hidden sm:flex items-center gap-4 text-sm font-medium">
                <a href="index.php" class="hover:text-emerald-200 transition"><?php echo $lang === 'bn' ? 'হোম' : 'Home'; ?></a>
                <a href="daily_amal.php" class="hover:text-emerald-200 transition font-bold"><?php echo $lang === 'bn' ? 'দৈনিক আমল' : 'Daily Amal'; ?></a>
                <a href="dashboard.php" class="hover:text-emerald-200 transition"><?php echo $lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard'; ?></a>
                <a href="profile.php" class="hover:text-emerald-200 transition">
                    <span class="bg-emerald-700 px-3 py-1 rounded-full text-xs font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                </a>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold transition"><?php echo $lang === 'bn' ? 'লগআউট' : 'Logout'; ?></a>
            </div>
        </div>

        <div id="mobileMenu" class="hidden sm:hidden bg-emerald-700 px-4 pt-2 pb-4 space-y-2 border-t border-emerald-500">
            <a href="index.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium"><?php echo $lang === 'bn' ? 'হোম' : 'Home'; ?></a>
            <a href="daily_amal.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium"><?php echo $lang === 'bn' ? 'দৈনিক আমল' : 'Daily Amal'; ?></a>
            <a href="dashboard.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium"><?php echo $lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard'; ?></a>
            <a href="profile.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium"><?php echo $lang === 'bn' ? 'প্রোফাইল সেটিং' : 'Profile Settings'; ?></a>
            <div class="pt-2 border-t border-emerald-600 flex justify-between items-center">
                <span class="text-xs bg-emerald-800 px-2.5 py-1 rounded-full font-medium"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold"><?php echo $lang === 'bn' ? 'লগআউট' : 'Logout'; ?></a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 py-6">

        <!-- Header Title & Action Buttons -->
        <div class="flex flex-row justify-between items-center mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-gray-800"><?php echo $lang === 'bn' ? 'আমল ও ফজিলত' : 'Amal & Virtues'; ?></h2>

            </div>

            <div class="flex items-center gap-2">
                <!-- Add Amal Button -->
                <button type="button" id="openAddModalBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-3.5 py-2 rounded-lg text-xs sm:text-sm transition flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <!-- <span><?php echo $lang === 'bn' ? 'আমল যোগ করুন' : 'Add Amal'; ?></span> -->
                </button>

                <!-- Settings Button -->
                <button type="button" id="openSettingsModalBtn" title="Settings" class="bg-gray-100 hover:bg-gray-200 text-gray-700 p-2.5 rounded-lg text-sm transition flex items-center justify-center border border-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>

            </div>
        </div>

        <!-- Sorting Info Alert -->
        <div id="sortingAlert" class="hidden bg-blue-50 border border-blue-200 text-blue-800 text-xs p-3 rounded-lg mb-4 flex justify-between items-center">
            <span><?php echo $lang === 'bn' ? '💡 আমলগুলো মাউস দিয়ে টেনে বা ড্র্যাগ করে সিরিয়াল পরিবর্তন করতে পারেন।' : '💡 Drag and drop cards to rearrange order.'; ?></span>
            <button type="button" id="saveOrderBtn" class="bg-blue-600 text-white px-3 py-1 rounded font-semibold text-xs hover:bg-blue-700 transition">
                <?php echo $lang === 'bn' ? 'ক্রম সংরক্ষণ করুন' : 'Save Order'; ?>
            </button>
        </div>

        <!-- Daily Amal List Cards Container -->
        <div id="amalListContainer" class="space-y-4">
            <?php if (count($daily_amals) > 0): ?>
                <?php foreach ($daily_amals as $index => $item): ?>
                    <div class="amal-card bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition relative" data-id="<?php echo $item['id']; ?>">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex items-start gap-3">
                                <!-- Drag Handle Icon (visible during sorting) -->
                                <span class="drag-handle hidden cursor-move text-gray-400 hover:text-gray-600 mt-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </span>
                                <span class="serial-number bg-emerald-100 text-emerald-800 font-bold text-xs px-2.5 py-1 rounded-full mt-0.5">
                                    <?php echo $lang === 'bn' ? convertToBanglaNumber($index + 1) : ($index + 1); ?>
                                </span>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-base sm:text-lg card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <?php if (!empty($item['fozilot'])): ?>
                                        <div class="mt-2 text-sm text-gray-600 bg-amber-50/60 border-l-4 border-amber-400 p-3 rounded-r-lg leading-relaxed card-fozilot">
                                            <span class="font-semibold text-amber-900 text-xs uppercase block mb-1"><?php echo $lang === 'bn' ? 'ফজিলত:' : 'Virtue:'; ?></span>
                                            <span class="fozilot-text"><?php echo nl2br(htmlspecialchars($item['fozilot'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Buttons Container -->
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="editBtn hidden text-blue-600 hover:text-blue-800 text-xs font-semibold bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded border border-blue-100 transition" data-id="<?php echo $item['id']; ?>" data-title="<?php echo htmlspecialchars($item['title']); ?>" data-fozilot="<?php echo htmlspecialchars($item['fozilot']); ?>">
                                    <?php echo $lang === 'bn' ? 'সম্পাদনা' : 'Edit'; ?>
                                </button>
                                <button type="button" class="deleteBtn hidden text-red-500 hover:text-red-700 text-xs font-semibold bg-red-50 hover:bg-red-100 px-2.5 py-1.5 rounded border border-red-100 transition" data-id="<?php echo $item['id']; ?>">
                                    <?php echo $lang === 'bn' ? 'মুছুন' : 'Delete'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500 text-sm">
                    <?php echo $lang === 'bn' ? 'এখনো কোনো আমল যোগ করা হয়নি। ওপরের বাটনে ক্লিক করে নতুন আমল যোগ করুন।' : 'No daily amals added yet. Click the button above to add one.'; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- 1. Settings Drawer/Modal -->
    <div id="settingsModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-sm w-full p-5 relative">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-md font-bold text-gray-800 flex items-center gap-2">
                    <span>⚙️</span> <?php echo $lang === 'bn' ? 'সেটিংস অপশন' : 'Settings Options'; ?>
                </h3>
                <button type="button" id="closeSettingsModalBtn" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>

            <div class="space-y-4 text-sm">
                <!-- Edit Option Toggle -->
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                    <label for="toggleEdit" class="font-medium text-gray-700 cursor-pointer flex items-center gap-2">
                        <span>✏️</span> <?php echo $lang === 'bn' ? 'সম্পাদনা (Edit) অপশন' : 'Edit Option'; ?>
                    </label>
                    <input type="checkbox" id="toggleEdit" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer">
                </div>

                <!-- Delete Option Toggle -->
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                    <label for="toggleDelete" class="font-medium text-gray-700 cursor-pointer flex items-center gap-2">
                        <span>🗑️</span> <?php echo $lang === 'bn' ? 'মুছে ফেলা (Delete) অপশন' : 'Delete Option'; ?>
                    </label>
                    <input type="checkbox" id="toggleDelete" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer">
                </div>

                <!-- Sorting Option Toggle -->
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                    <label for="toggleSorting" class="font-medium text-gray-700 cursor-pointer flex items-center gap-2">
                        <span>🔀</span> <?php echo $lang === 'bn' ? 'সিরিয়াল পরিবর্তন (Sorting)' : 'Sorting Option'; ?>
                    </label>
                    <input type="checkbox" id="toggleSorting" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer">
                </div>
            </div>

            <div class="mt-6 pt-3 border-t flex justify-end">
                <button type="button" id="closeSettingsModalBtn2" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">
                    <?php echo $lang === 'bn' ? 'সম্পন্ন' : 'Done'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Add Amal Modal -->
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 relative">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2"><?php echo $lang === 'bn' ? 'নতুন আমল যোগ করুন' : 'Add New Daily Amal'; ?></h3>

            <form id="addDailyAmalForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1"><?php echo $lang === 'bn' ? 'আমলের নাম/শিরোনাম' : 'Amal Title'; ?></label>
                    <input type="text" name="title" placeholder="<?php echo $lang === 'bn' ? 'যেমন: আয়াতুল কুরসী পাঠ' : 'E.g., Ayatul Kursi Recitation'; ?>" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1"><?php echo $lang === 'bn' ? 'আমলের ফজিলত' : 'Virtue/Benefits'; ?></label>
                    <textarea name="fozilot" rows="4" placeholder="<?php echo $lang === 'bn' ? 'আমলটির হাদিস বা ফজিলত লিখুন...' : 'Write virtue or Hadith details...'; ?>" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div id="modalMsg" class="text-xs font-semibold hidden"></div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="closeAddModalBtn px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition"><?php echo $lang === 'bn' ? 'বাতিল' : 'Cancel'; ?></button>
                    <button type="submit" id="saveBtn" class="px-5 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition flex items-center gap-2">
                        <span><?php echo $lang === 'bn' ? 'সংরক্ষণ করুন' : 'Save Amal'; ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Edit Amal Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 relative">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2"><?php echo $lang === 'bn' ? 'আমল সম্পাদনা করুন' : 'Edit Daily Amal'; ?></h3>

            <form id="editDailyAmalForm" class="space-y-4">
                <input type="hidden" id="edit_id" name="id">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1"><?php echo $lang === 'bn' ? 'আমলের নাম/শিরোনাম' : 'Amal Title'; ?></label>
                    <input type="text" id="edit_title" name="title" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1"><?php echo $lang === 'bn' ? 'আমলের ফজিলত' : 'Virtue/Benefits'; ?></label>
                    <textarea id="edit_fozilot" name="fozilot" rows="4" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="closeEditModalBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300 transition"><?php echo $lang === 'bn' ? 'বাতিল' : 'Cancel'; ?></button>
                    <button type="submit" id="updateBtn" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                        <span><?php echo $lang === 'bn' ? 'হালনাগাদ করুন' : 'Update Amal'; ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS Logic -->
    <script>
        $(document).ready(function() {
            let sortableInstance = null;

            // Mobile menu toggle
            $('#mobileMenuBtn').on('click', function() {
                $('#mobileMenu').toggleClass('hidden');
            });

            // Settings Modal Open/Close
            $('#openSettingsModalBtn').on('click', function() {
                $('#settingsModal').removeClass('hidden').addClass('flex');
            });

            $('#closeSettingsModalBtn, #closeSettingsModalBtn2').on('click', function() {
                $('#settingsModal').addClass('hidden').removeClass('flex');
            });

            // Toggle Edit Buttons Visibility
            $('#toggleEdit').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.editBtn').removeClass('hidden');
                } else {
                    $('.editBtn').addClass('hidden');
                }
            });

            // Toggle Delete Buttons Visibility
            $('#toggleDelete').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.deleteBtn').removeClass('hidden');
                } else {
                    $('.deleteBtn').addClass('hidden');
                }
            });

            // Toggle Drag & Drop Sorting Feature
            $('#toggleSorting').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.drag-handle').removeClass('hidden');
                    $('#sortingAlert').removeClass('hidden');

                    // Initialize Sortable
                    if (!sortableInstance) {
                        sortableInstance = sortable('#amalListContainer', {
                            items: '.amal-card',
                            handle: '.drag-handle',
                            forcePlaceholderSize: true
                        });
                    }
                } else {
                    $('.drag-handle').addClass('hidden');
                    $('#sortingAlert').addClass('hidden');
                    if (sortableInstance) {
                        sortable('destroy', '#amalListContainer');
                        sortableInstance = null;
                    }
                }
            });

            // Save Order Serial via AJAX
            $('#saveOrderBtn').on('click', function() {
                let orderData = [];
                $('.amal-card').each(function(index) {
                    orderData.push({
                        id: $(this).data('id'),
                        sort_order: index + 1
                    });
                });

                $.ajax({
                    url: 'daily_amal_core.php?action=save_order',
                    type: 'POST',
                    data: {
                        order: JSON.stringify(orderData)
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });

            // Add Modal Open & Close
            $('#openAddModalBtn').on('click', function() {
                $('#addDailyAmalForm')[0].reset();
                $('#modalMsg').addClass('hidden').removeClass('text-red-600 text-green-600');
                $('#addModal').removeClass('hidden').addClass('flex');
            });

            $('.closeAddModalBtn').on('click', function() {
                $('#addModal').addClass('hidden').removeClass('flex');
            });

            // Submit Add Form via AJAX
            $('#addDailyAmalForm').on('submit', function(e) {
                e.preventDefault();
                $('#saveBtn').attr('disabled', true);

                $.ajax({
                    url: 'daily_amal_core.php?action=add',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        $('#saveBtn').attr('disabled', false);
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            $('#modalMsg').removeClass('hidden text-green-600').addClass('text-red-600').html(response.message);
                        }
                    }
                });
            });

            // Open Edit Modal
            $(document).on('click', '.editBtn', function() {
                let id = $(this).data('id');
                let title = $(this).data('title');
                let fozilot = $(this).data('fozilot');

                $('#edit_id').val(id);
                $('#edit_title').val(title);
                $('#edit_fozilot').val(fozilot);

                $('#editModal').removeClass('hidden').addClass('flex');
            });

            $('#closeEditModalBtn').on('click', function() {
                $('#editModal').addClass('hidden').removeClass('flex');
            });

            // Submit Edit Form via AJAX
            $('#editDailyAmalForm').on('submit', function(e) {
                e.preventDefault();
                $('#updateBtn').attr('disabled', true);

                $.ajax({
                    url: 'daily_amal_core.php?action=update',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        $('#updateBtn').attr('disabled', false);
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });

            // Delete Amal via AJAX
            $(document).on('click', '.deleteBtn', function() {
                if (!confirm('<?php echo $lang === 'bn' ? 'আপনি কি নিশ্চিত এটি মুছে ফেলতে চান?' : 'Are you sure you want to delete this?'; ?>')) {
                    return;
                }

                let id = $(this).data('id');
                $.ajax({
                    url: 'daily_amal_core.php?action=delete',
                    type: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>