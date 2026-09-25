<?php //profile.php
require_once 'config.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$error_msg = '';

// Number to Bangla Convert Helper Function
function convertToBanglaNumber($number = '') {
    if ($number === null || $number === '') {
        return '';
    }
    $en_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bn_digits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($en_digits, $bn_digits, (string)$number);
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $daily_target = isset($_POST['daily_target_minutes']) ? (int)$_POST['daily_target_minutes'] : 120;
    $language = isset($_POST['language']) && in_array($_POST['language'], ['bn', 'en']) ? $_POST['language'] : 'bn';

    try {
        $stmt = $pdo->prepare("UPDATE users SET daily_target_minutes = ?, language = ? WHERE id = ?");
        $stmt->execute([$daily_target, $language, $user_id]);
        
        $_SESSION['language'] = $language;
        $_SESSION['flash_msg'] = $language === 'bn' ? "প্রোফাইল তথ্য সফলভাবে আপডেট হয়েছে!" : "Profile updated successfully!";
        
        // Success payload for JavaScript handling/redirect
        $success_msg = $_SESSION['flash_msg'];
    } catch (PDOException $e) {
        $error_msg = ($language ?? 'bn') === 'bn' ? "আপডেট করতে সমস্যা হয়েছে: " . $e->getMessage() : "Update failed: " . $e->getMessage();
    }
}

// Fetch Current User Data
$stmt = $pdo->prepare("SELECT daily_target_minutes, language FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_target = (int)($user_data['daily_target_minutes'] ?? 120);
$current_lang = $user_data['language'] ?? 'bn';

// Format Target Helper
$hrs = floor($current_target / 60);
$mns = $current_target % 60;

if ($current_lang === 'bn') {
    $hrs_text = $hrs > 0 ? convertToBanglaNumber($hrs) . " ঘণ্টা " : "";
    $mns_text = convertToBanglaNumber($mns) . " মিনিট";
    $formatted_target = "বর্তমান টার্গেট: " . $hrs_text . $mns_text;
} else {
    $hrs_text = $hrs > 0 ? "{$hrs} h " : "";
    $mns_text = "{$mns} min";
    $formatted_target = "Current Target: " . $hrs_text . $mns_text;
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'bn' ? 'প্রোফাইল সেটিংস - আমল ট্র্যাকার' : 'Profile Settings - Amal Tracker'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800 antialiased">

    <!-- Navigation Header -->
    <nav class="bg-emerald-600 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-lg font-bold tracking-wide"><?php echo $current_lang === 'bn' ? 'আমল ট্র্যাকার' : 'Amal Tracker'; ?></a>

            <button id="mobileMenuBtn" class="sm:hidden p-2 text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <div class="hidden sm:flex items-center gap-4 text-sm font-medium">
                <a href="index.php" class="hover:text-emerald-200 transition"><?php echo $current_lang === 'bn' ? 'হোম' : 'Home'; ?></a>
                <a href="dashboard.php" class="hover:text-emerald-200 transition"><?php echo $current_lang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard'; ?></a>
                <a href="profile.php" class="hover:text-emerald-200 transition font-bold underline">
                    <span class="bg-emerald-700 px-3 py-1 rounded-full text-xs font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                </a>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold transition"><?php echo $current_lang === 'bn' ? 'লগআউট' : 'Logout'; ?></a>
            </div>
        </div>

        <div id="mobileMenu" class="hidden sm:hidden bg-emerald-700 px-4 pt-2 pb-4 space-y-2 border-t border-emerald-500">
            <a href="index.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium"><?php echo $current_lang === 'bn' ? 'হোম / আজকের আমল' : 'Home / Today\'s Amal'; ?></a>
            <a href="dashboard.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium"><?php echo $current_lang === 'bn' ? 'ড্যাশবোর্ড (মাসিক হিসাব)' : 'Dashboard (Monthly Summary)'; ?></a>
            <a href="profile.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium font-bold bg-emerald-800"><?php echo $current_lang === 'bn' ? 'প্রোফাইল সেটিং' : 'Profile Settings'; ?></a>
            <div class="pt-2 border-t border-emerald-600 flex justify-between items-center">
                <span class="text-xs bg-emerald-800 px-2.5 py-1 rounded-full font-medium"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold"><?php echo $current_lang === 'bn' ? 'লগআউট' : 'Logout'; ?></a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="max-w-xl mx-auto px-4 py-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-3">
                <?php echo $current_lang === 'bn' ? 'প্রোফাইল সেটিংস' : 'Profile Settings'; ?>
            </h2>

            <!-- Flash Message Display -->
            <?php if (isset($success_msg)): ?>
                <div id="flash-message" class="mb-5 p-3.5 bg-green-100 border border-green-300 text-green-800 text-sm rounded-lg flex justify-between items-center transition-opacity duration-500">
                    <span><?php echo htmlspecialchars($success_msg); ?></span>
                    <button type="button" id="closeFlashBtn" class="text-green-800 hover:text-green-950 font-bold ml-4 focus:outline-none text-lg leading-none">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="mb-5 p-3.5 bg-red-100 border border-red-300 text-red-800 text-sm rounded-lg">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="profile.php" class="space-y-5">
                <!-- Daily Target Minutes -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">
                        <?php echo $current_lang === 'bn' ? 'দৈনিক আমলের টার্গেট (মিনিটে)' : 'Daily Target Time (in Minutes)'; ?>
                    </label>
                    <input type="number" min="1" name="daily_target_minutes" value="<?php echo htmlspecialchars((string)$current_target); ?>" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none text-sm">
                    <p class="text-xs text-gray-500 mt-1.5 font-medium">
                        <?php echo htmlspecialchars($formatted_target); ?>
                    </p>
                </div>

                <!-- Language Selection -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-gray-700">
                        <?php echo $current_lang === 'bn' ? 'ভাষা নির্বাচন করুন' : 'Select Language'; ?>
                    </label>
                    <select name="language" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none text-sm bg-white">
                        <option value="bn" <?php echo $current_lang === 'bn' ? 'selected' : ''; ?>>বাংলা (Bangla)</option>
                        <option value="en" <?php echo $current_lang === 'en' ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold py-3 rounded-lg transition text-sm shadow-sm">
                    <?php echo $current_lang === 'bn' ? 'পরিবর্তন সংরক্ষণ করুন' : 'Save Changes'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        $(document).ready(function() {
            // Mobile Menu Toggle
            $('#mobileMenuBtn').on('click', function() {
                $('#mobileMenu').toggleClass('hidden');
            });

            // Flash Message Behavior
            if ($('#flash-message').length > 0) {
                $('#closeFlashBtn').on('click', function() {
                    $('#flash-message').fadeOut(300, function() {
                        $(this).remove();
                    });
                });

                // Auto vanish and redirect back to index.php
                setTimeout(function() {
                    $('#flash-message').fadeOut(500, function() {
                        $(this).remove();
                        window.location.href = 'profile.php';
                    });
                }, 2500);
            }
        });
    </script>
</body>
</html>