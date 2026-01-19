<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

// Check if user has permission to clock others
$stmt = $db->prepare("SELECT can_clock_others FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$permissions = $stmt->get_result()->fetch_assoc();

if (!$permissions['can_clock_others']) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';

// Handle barcode scan for clocking
if (isset($_POST['barcode'])) {
    $barcode = trim($_POST['barcode']);
    
    // Get user by barcode
    $stmt = $db->prepare("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as name, u.can_clock FROM users u WHERE u.barcode = ? AND u.business_id = ? AND u.is_active = 1");
    $stmt->bind_param("si", $barcode, $business_id);
    $stmt->execute();
    $target_user = $stmt->get_result()->fetch_assoc();
    
    if (!$target_user) {
        $error = 'User not found with barcode: ' . $barcode;
    } elseif (!$target_user['can_clock']) {
        $error = 'User does not have clocking permission';
    } else {
        $today = TODAY;
        
        // Check current status
        $stmt = $db->prepare("SELECT id, clock_in_time, report_submitted_at, clock_out_time FROM reports WHERE user_id = ? AND report_date = ?");
        $stmt->bind_param("is", $target_user['id'], $today);
        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
        
        if (!$report || !$report['clock_in_time']) {
            // Clock in
            $stmt = $db->prepare("INSERT INTO reports (user_id, report_date, clock_in_time, status) VALUES (?, ?, NOW(), 'clocked_in')");
            $stmt->bind_param("is", $target_user['id'], $today);
            $stmt->execute();
            
            $message = $target_user['name'] . ' has been clocked in successfully!';
        } elseif ($report['clock_out_time']) {
            $error = $target_user['name'] . ' has already clocked out today';
        } elseif (!$report['report_submitted_at']) {
            $error = $target_user['name'] . ' must submit plan and report before clocking out';
        } else {
            // Clock out
            $stmt = $db->prepare("UPDATE reports SET clock_out_time = NOW(), status = 'clocked_out' WHERE id = ?");
            $stmt->bind_param("i", $report['id']);
            $stmt->execute();
            
            $message = $target_user['name'] . ' has been clocked out successfully!';
        }
    }
}

// Get today's clock-ins made by this user
$today = TODAY;
$stmt = $db->prepare("SELECT r.*, CONCAT(u.firstname, ' ', u.lastname) as name, u.barcode FROM reports r JOIN users u ON r.user_id = u.id WHERE r.report_date = ? AND u.business_id = ? AND (r.clock_in_time IS NOT NULL OR r.clock_out_time IS NOT NULL) ORDER BY COALESCE(r.clock_out_time, r.clock_in_time) DESC");
$stmt->bind_param("si", $today, $business_id);
$stmt->execute();
$today_clockins = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Clock Others - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display min-h-screen flex flex-col overflow-hidden selection:bg-primary/30">
    <!-- Header -->
    <header class="w-full px-8 py-5 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm fixed top-0 left-0 z-50">
        <div class="flex items-center gap-4">
            <div class="size-10 bg-primary rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary/30">
                <span class="material-symbols-outlined" style="font-size: 24px;">schedule</span>
            </div>
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">TimeTrack Pro</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Clock Others Portal</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex flex-col items-end text-right">
                <div class="text-xl font-bold tabular-nums tracking-tight leading-none text-slate-900 dark:text-white" id="current-time"><?php echo date('h:i A'); ?></div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1"><?php echo date('M j, Y'); ?></div>
            </div>
            <a href="dashboard.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                <span class="hidden sm:inline">Back</span>
            </a>
        </div>
    </header>
    
    <!-- Main Content (Centered) -->
    <main class="flex-grow flex flex-col items-center justify-center px-4 py-20 relative w-full h-full">
        <!-- Abstract Background Decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none -z-10 flex items-center justify-center">
            <div class="w-[800px] h-[800px] bg-primary/5 rounded-full blur-[120px]"></div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
        <div class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg shadow-lg">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">check_circle</span>
                <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="fixed top-24 left-1/2 transform -translate-x-1/2 z-50 bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg shadow-lg">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">error</span>
                <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="w-full max-w-2xl flex flex-col items-center gap-8 z-10">
            <!-- Visual Icon/Illustration -->
            <div class="size-28 rounded-full bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/50 flex items-center justify-center border border-slate-100 dark:border-slate-700 mb-2 ring-4 ring-white dark:ring-slate-900">
                <span class="material-symbols-outlined text-primary" style="font-size: 56px;">barcode_reader</span>
            </div>
            
            <!-- Headline & Instructions -->
            <div class="text-center space-y-3 max-w-lg">
                <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900 dark:text-white leading-tight">
                    Scan Staff ID to Clock In/Out
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-lg font-medium">
                    First scan = Clock In | Second scan = Clock Out
                </p>
            </div>
            
            <!-- Scanner Input Area -->
            <div class="w-full max-w-lg relative group mt-4">
                <!-- Outer glow for focus state simulation -->
                <div class="absolute -inset-1 bg-primary rounded-2xl blur opacity-20 group-focus-within:opacity-60 transition duration-500"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl border-2 border-primary group-focus-within:ring-4 ring-primary/20 transition-all duration-200 overflow-hidden flex flex-col">
                    <form method="POST" class="contents">
                        <div class="flex items-center h-24 px-6 relative">
                            <span class="material-symbols-outlined text-slate-300 dark:text-slate-600 absolute left-6 animate-pulse" style="font-size: 32px;">qr_code_scanner</span>
                            <input autofocus name="barcode" class="w-full h-full bg-transparent border-none focus:ring-0 text-3xl font-mono text-center placeholder:text-slate-300 dark:placeholder:text-slate-600 text-slate-900 dark:text-white tracking-widest outline-none font-bold uppercase pl-12" placeholder="Waiting for scan..." type="text" required/>
                            <!-- Success/Status Indicator Spot -->
                            <div class="absolute right-6 size-3 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)] animate-pulse"></div>
                        </div>
                        <!-- Visual Progress/Scan Bar at bottom of input -->
                        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                            <div class="h-full w-full bg-primary origin-left animate-pulse"></div>
                        </div>
                        <input type="submit" style="display: none;">
                    </form>
                </div>
            </div>
            
            <!-- Manual Entry / Fallback -->
            <div class="pt-6">
                <button onclick="toggleHistory()" class="group flex items-center gap-2 px-8 py-3 rounded-full bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 border border-slate-200 dark:border-slate-700 hover:border-primary/30">
                    <span class="material-symbols-outlined group-hover:text-primary transition-colors text-xl">history</span>
                    <span class="font-semibold text-sm">View Today's Activity</span>
                </button>
            </div>
        </div>
    </main>
    
    <!-- History Modal -->
    <div id="history-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-slate-800 rounded-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Today's Clock-ins/outs (<?php echo TODAY; ?>)</h3>
                <button onclick="toggleHistory()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="overflow-y-auto max-h-96">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Barcode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        <?php while ($clockin = $today_clockins->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo $clockin['clock_out_time'] ? date('h:i A', strtotime($clockin['clock_out_time'])) : date('h:i A', strtotime($clockin['clock_in_time'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-300">
                                <?php echo htmlspecialchars($clockin['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-300 font-mono">
                                <?php echo htmlspecialchars($clockin['barcode']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $clockin['clock_out_time'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $clockin['clock_out_time'] ? 'Clock Out' : 'Clock In'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-300">
                                <?php echo ucfirst(str_replace('_', ' ', $clockin['status'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="w-full py-6 text-center z-10 border-t border-slate-200/50 dark:border-slate-800/50 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm">
        <div class="flex flex-col gap-1">
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Clock Others Portal • <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            <p class="text-xs text-slate-400 dark:text-slate-500">Powered by TimeTrack Pro</p>
        </div>
    </footer>
    
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto-submit form when barcode is scanned
        document.querySelector('input[name="barcode"]').addEventListener('input', function(e) {
            if (e.target.value.length >= 5) {
                setTimeout(() => {
                    e.target.form.submit();
                }, 100);
            }
        });
        
        // Keep focus on barcode input
        setInterval(() => {
            if (!document.getElementById('history-modal').classList.contains('hidden')) return;
            document.querySelector('input[name="barcode"]').focus();
        }, 1000);
        
        // Toggle history modal
        function toggleHistory() {
            document.getElementById('history-modal').classList.toggle('hidden');
        }
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed.top-24');
            messages.forEach(msg => msg.remove());
        }, 5000);
    </script>
</body>
</html>