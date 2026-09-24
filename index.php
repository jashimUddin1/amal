<?php
require_once 'config.php';
checkAuth();

// ajker diner amal dekhar list
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM amals WHERE user_id = ? AND amal_date = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id'], $today]);
$today_amals = $stmt->fetchAll();

// Total Duration Calculation (Minutes and Seconds Convert kora)
$total_seconds = 0;
foreach ($today_amals as $amal) {
    $duration_str = strtolower($amal['duration_time']);

    $mins = 0;
    $secs = 0;

    // Minute extract kora
    if (preg_match('/(\d+)\s*min/', $duration_str, $m_match)) {
        $mins = (int)$m_match[1];
    }
    // Second extract kora
    if (preg_match('/(\d+)\s*sec/', $duration_str, $s_match)) {
        $secs = (int)$s_match[1];
    }

    // Jodi shudhu number thake (default minute hisebe)
    if ($mins == 0 && $secs == 0 && is_numeric(trim($duration_str))) {
        $mins = (int)trim($duration_str);
    }

    $total_seconds += ($mins * 60) + $secs;
}

// Formatted Total Duration string create (PHP floor() function bebohar kora hoyechhe)
$total_hours = floor($total_seconds / 3600);
$remaining_secs = $total_seconds % 3600;
$total_minutes = floor($remaining_secs / 60);
$final_secs = $remaining_secs % 60;

$formatted_total_duration = "";
if ($total_hours > 0) {
    $formatted_total_duration .= $total_hours . " Hour ";
}
if ($total_minutes > 0) {
    $formatted_total_duration .= $total_minutes . " Min ";
}
if ($final_secs > 0 || empty($formatted_total_duration)) {
    $formatted_total_duration .= $final_secs . " Sec";
}
?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajker Amal - Amal Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-100 min-h-screen antialiased text-gray-800">

    <!-- Mobile-First Responsive Navigation Header -->
    <nav class="bg-emerald-600 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-lg font-bold tracking-wide">Amal Tracker</a>

            <button id="mobileMenuBtn" class="sm:hidden p-2 text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <div class="hidden sm:flex items-center gap-4 text-sm font-medium">
                <a href="index.php" class="hover:text-emerald-200 transition">Home</a>
                <a href="dashboard.php" class="hover:text-emerald-200 transition">Dashboard</a>
                <span class="bg-emerald-700 px-3 py-1 rounded-full text-xs font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold transition">Logout</a>
            </div>
        </div>

        <div id="mobileMenu" class="hidden sm:hidden bg-emerald-700 px-4 pt-2 pb-4 space-y-2 border-t border-emerald-500">
            <a href="index.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium">Home / Ajker Amal</a>
            <a href="dashboard.php" class="block py-1.5 px-3 rounded hover:bg-emerald-800 font-medium">Dashboard (Masik Hisab)</a>
            <div class="pt-2 border-t border-emerald-600 flex justify-between items-center">
                <span class="text-xs bg-emerald-800 px-2.5 py-1 rounded-full font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="login.php?action=logout" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-xs font-semibold">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 py-6">

        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash_msg'])): ?>
            <div id="flash-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm">
                <?php
                echo $_SESSION['flash_msg'];
                unset($_SESSION['flash_msg']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Add Amal Form Container -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
            <h2 class="text-lg sm:text-xl font-bold mb-4 text-emerald-600 border-b border-gray-100 pb-2">Notun Amal Jog Korun</h2>

            <div class="mb-5 p-4 bg-gray-900 text-white rounded-xl shadow-inner flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-center sm:text-left">
                    <span class="text-xs text-gray-400 font-medium uppercase tracking-wider block">Amal Timer</span>
                    <div class="flex items-center justify-center sm:justify-start gap-2 mt-1">
                        <span id="redDot" class="h-2.5 w-2.5 bg-red-500 rounded-full hidden animate-ping"></span>
                        <span id="timerDisplay" class="text-3xl font-mono font-bold tracking-widest text-emerald-400">00:00:00</span>
                    </div>
                </div>

                <div class="flex gap-3 w-full sm:w-auto">
                    <button type="button" id="startBtn" class="flex-1 sm:flex-none bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-semibold py-2.5 px-6 rounded-lg text-sm transition">Start</button>
                    <button type="button" id="endBtn" class="flex-1 sm:flex-none bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-gray-900 font-bold py-2.5 px-6 rounded-lg text-sm transition disabled:opacity-40 disabled:cursor-not-allowed" disabled>End</button>
                </div>
            </div>

            <form id="addAmalForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tarikh</label>
                        <input type="date" id="amal_date" name="amal_date" value="<?php echo date('Y-m-d'); ?>" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Amaler Nam</label>
                        <input type="text" name="amal_name" placeholder="Jemon: Quran Telawat, Zikir" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Shomoy Shima (Time Range)</label>
                        <input type="text" id="time_range" name="time_range" placeholder="Jemon: 8:15 PM - 8:45 PM" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sthaitwo (Duration Time)</label>
                        <input type="text" id="duration_time" name="duration_time" placeholder="Jemon: 30 Min" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-emerald-500 outline-none" required>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submitBtn" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold px-8 py-3 rounded-lg text-sm transition flex items-center justify-center gap-2">
                        <span>Shongrokkhon Korun</span>
                        <svg id="loader" class="hidden animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <span id="responseMsg" class="block sm:inline-block mt-2 sm:mt-0 sm:ml-4 text-xs font-semibold"></span>
                </div>
            </form>
        </div>

        <!-- Summary Widget (Total Time & Total Amal) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Ajker Mot Amal</p>
                    <h3 class="text-2xl font-black text-emerald-700 mt-1"><?php echo count($today_amals); ?> Ti</h3>
                </div>
                <div class="p-3 bg-emerald-100 rounded-lg text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-800 uppercase tracking-wider">Mot Somoy (Total Duration)</p>
                    <h3 class="text-2xl font-black text-blue-700 mt-1"><?php echo $formatted_total_duration; ?></h3>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's List Container -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-lg sm:text-xl font-bold mb-4 text-gray-800 border-b border-gray-100 pb-2">
                Ajker Sompadito Amal List <span class="text-xs font-normal text-gray-500 block sm:inline">(<?php echo date('d-M-Y'); ?>)</span>
            </h2>

            <!-- Mobile View: Card Layout -->
            <div class="block sm:hidden space-y-3">
                <?php if (count($today_amals) > 0): ?>
                    <?php foreach ($today_amals as $index => $amal): ?>
                        <div class="p-3.5 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-start gap-3">
                            <div class="space-y-1">
                                <p class="font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($amal['amal_name']); ?></p>
                                <div class="flex flex-wrap gap-2 items-center text-xs text-gray-600">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-medium"><?php echo htmlspecialchars($amal['time_range']); ?></span>
                                    <span class="text-gray-500">• <?php echo htmlspecialchars($amal['duration_time']); ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="editBtn text-blue-600 hover:text-blue-800 text-xs font-semibold bg-blue-50 px-2 py-1 rounded border border-blue-100" data-id="<?php echo $amal['id']; ?>">Edit</button>
                                <a href="add_core.php?action=delete&id=<?php echo $amal['id']; ?>" onclick="return confirm('Apni ki nishchit eti muche felte chan?')" class="text-red-500 hover:text-red-700 text-xs font-semibold bg-red-50 px-2 py-1 rounded border border-red-100">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center py-6 text-gray-500 text-sm">Ajke ekhono kono amal jog kora hoyni.</p>
                <?php endif; ?>
            </div>

            <!-- Desktop View: Clean Table Layout -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-xs uppercase tracking-wider">
                            <th class="p-3">#</th>
                            <th class="p-3">Amaler Nam</th>
                            <th class="p-3">Shomoy Shima</th>
                            <th class="p-3">Shomoy</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (count($today_amals) > 0): ?>
                            <?php foreach ($today_amals as $index => $amal): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 text-gray-500"><?php echo $index + 1; ?></td>
                                    <td class="p-3 font-semibold text-gray-800"><?php echo htmlspecialchars($amal['amal_name']); ?></td>
                                    <td class="p-3"><span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full font-medium"><?php echo htmlspecialchars($amal['time_range']); ?></span></td>
                                    <td class="p-3 text-gray-600"><?php echo htmlspecialchars($amal['duration_time']); ?></td>
                                    <td class="p-3 text-center space-x-2">
                                        <button type="button" class="editBtn text-blue-600 hover:text-blue-800 font-semibold text-xs bg-blue-50 px-2.5 py-1 rounded border border-blue-100" data-id="<?php echo $amal['id']; ?>">Edit</button>
                                        <a href="add_core.php?action=delete&id=<?php echo $amal['id']; ?>" onclick="return confirm('Apni ki nishchit eti muche felte chan?')" class="text-red-500 hover:text-red-700 font-medium text-xs bg-red-50 px-2.5 py-1 rounded border border-red-100">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">Ajke ekhono kono amal jog kora hoyni.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (count($today_amals) > 0): ?>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold text-gray-800">
                            <tr>
                                <td colspan="3" class="p-3 text-right">Mot Somoy:</td>
                                <td class="p-3 text-blue-600 font-extrabold"><?php echo $formatted_total_duration; ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Amal Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 relative">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Amal Edit Korun</h3>

            <form id="editAmalForm" class="space-y-4">
                <input type="hidden" id="edit_id" name="id">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tarikh</label>
                    <input type="date" id="edit_amal_date" name="amal_date" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Amaler Nam</label>
                    <input type="text" id="edit_amal_name" name="amal_name" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Shomoy Shima (Time Range)</label>
                    <input type="text" id="edit_time_range" name="time_range" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sthaitwo (Duration Time)</label>
                    <input type="text" id="edit_duration_time" name="duration_time" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-gray-50 outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="closeModalBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-300">Cancel</button>
                    <button type="submit" id="updateSubmitBtn" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        $(document).ready(function() {
            let timerInterval = null;
            let startTime = null;
            let endTime = null;
            let secondsElapsed = 0;

            $('#mobileMenuBtn').on('click', function() {
                $('#mobileMenu').toggleClass('hidden');
            });

            function formatAMPM(date) {
                let hours = date.getHours();
                let minutes = date.getMinutes();
                let ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                return hours + ':' + minutes + ' ' + ampm;
            }

            function formatDuration(totalSeconds) {
                if (isNaN(totalSeconds) || totalSeconds <= 0) return '';
                let mins = Math.floor(totalSeconds / 60);
                let secs = totalSeconds % 60;

                if (mins === 0) {
                    return secs + ' Sec';
                } else if (secs === 0) {
                    return mins + ' Min';
                } else {
                    return mins + ' Min ' + secs + ' Sec';
                }
            }

            function calculateManualRange(targetInputId, durationInputId) {
                let rangeStr = $(targetInputId).val().trim();
                let parts = rangeStr.split(/[-–—]/);

                if (parts.length === 2) {
                    let startStr = parts[0].trim();
                    let endStr = parts[1].trim();

                    let dummyDate = '1970-01-01 ';
                    let startMs = window.Date.parse(dummyDate + startStr);
                    let endMs = window.Date.parse(dummyDate + endStr);

                    if (!isNaN(startMs) && !isNaN(endMs)) {
                        let diffMs = endMs - startMs;
                        if (diffMs < 0) {
                            diffMs += 24 * 60 * 60 * 1000;
                        }
                        let totalSecs = Math.floor(diffMs / 1000);
                        $(durationInputId).val(formatDuration(totalSecs));
                    }
                }
            }

            $('#time_range').on('input change blur', function() {
                calculateManualRange('#time_range', '#duration_time');
            });

            $('#edit_time_range').on('input change blur', function() {
                calculateManualRange('#edit_time_range', '#edit_duration_time');
            });

            // Start & End Timer
            $('#startBtn').on('click', function() {
                startTime = new window.Date();
                secondsElapsed = 0;

                $('#redDot').removeClass('hidden');
                $('#startBtn').attr('disabled', true).addClass('opacity-40');
                $('#endBtn').attr('disabled', false).removeClass('opacity-40');

                timerInterval = setInterval(function() {
                    secondsElapsed++;
                    let hrs = Math.floor(secondsElapsed / 3600);
                    let mins = Math.floor((secondsElapsed % 3600) / 60);
                    let secs = secondsElapsed % 60;

                    let formattedTime =
                        (hrs < 10 ? "0" + hrs : hrs) + ":" +
                        (mins < 10 ? "0" + mins : mins) + ":" +
                        (secs < 10 ? "0" + secs : secs);

                    $('#timerDisplay').text(formattedTime);
                }, 1000);
            });

            $('#endBtn').on('click', function() {
                endTime = new window.Date();
                clearInterval(timerInterval);

                $('#redDot').addClass('hidden');
                $('#startBtn').attr('disabled', false).removeClass('opacity-40');
                $('#endBtn').attr('disabled', true).addClass('opacity-40');
                $('#timerDisplay').text('00:00:00');

                let startFormatted = formatAMPM(startTime);
                let endFormatted = formatAMPM(endTime);
                let durationText = formatDuration(secondsElapsed);

                $('#time_range').val(startFormatted + ' – ' + endFormatted);
                $('#duration_time').val(durationText);
            });

            // AJAX Add
            $('#addAmalForm').on('submit', function(e) {
                e.preventDefault();
                $('#loader').removeClass('hidden');
                $('#submitBtn').attr('disabled', true);

                $.ajax({
                    url: 'add_core.php?action=add',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        $('#loader').addClass('hidden');
                        $('#submitBtn').attr('disabled', false);
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            $('#responseMsg').removeClass('text-green-600').addClass('text-red-600').html(response.message);
                        }
                    }
                });
            });

            // OPEN EDIT MODAL (FETCH DATA VIA AJAX)
            $(document).on('click', '.editBtn', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: 'edit_core.php?action=get&id=' + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let data = response.data;
                            $('#edit_id').val(data.id);
                            $('#edit_amal_date').val(data.amal_date);
                            $('#edit_amal_name').val(data.amal_name);
                            $('#edit_time_range').val(data.time_range);
                            $('#edit_duration_time').val(data.duration_time);
                            $('#editModal').removeClass('hidden').addClass('flex');
                        } else {
                            alert(response.message);
                        }
                    }
                });
            });

            // CLOSE EDIT MODAL
            $('#closeModalBtn').on('click', function() {
                $('#editModal').addClass('hidden').removeClass('flex');
            });

            // AJAX UPDATE
            $('#editAmalForm').on('submit', function(e) {
                e.preventDefault();
                $('#updateSubmitBtn').attr('disabled', true).text('Updating...');

                $.ajax({
                    url: 'edit_core.php?action=update',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert(response.message);
                            $('#updateSubmitBtn').attr('disabled', false).text('Update');
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>