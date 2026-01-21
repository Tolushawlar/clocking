<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];

// Get business settings
$stmt = $db->prepare("SELECT * FROM business WHERE id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();

// Handle settings update
if (isset($_POST['update_settings'])) {
    $clocking_enabled = isset($_POST['clocking_enabled']) ? 1 : 0;
    $reporting_enabled = isset($_POST['reporting_enabled']) ? 1 : 0;

    $stmt = $db->prepare("UPDATE business SET clocking_enabled = ?, reporting_enabled = ? WHERE id = ?");
    $stmt->bind_param("iii", $clocking_enabled, $reporting_enabled, $business_id);
    $stmt->execute();

    header('Location: settings.php?msg=Settings updated successfully');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Settings - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "primary-hover": "#1d4ed8",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1e293b",
                        "text-main": "#0d121b",
                        "text-secondary": "#4c669a",
                        "border-light": "#e7ebf3",
                        "border-dark": "#334155",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-white overflow-hidden selection:bg-primary selection:text-white">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        // Include sidebar component
        $current_page = 'settings.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-6xl mx-auto px-6 py-8">
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Header Section -->
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight">System Settings</h1>
                        <p class="text-text-secondary text-sm mt-1">Configure system preferences and features</p>
                    </div>

                    <!-- Settings Card -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 max-w-2xl">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Business Settings</h3>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="space-y-6">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                        <div>
                                            <h4 class="text-sm font-medium text-slate-900 dark:text-white">Enable Clocking</h4>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">Allow staff to clock in and out</p>
                                        </div>
                                        <input type="checkbox" name="clocking_enabled" <?php echo $business['clocking_enabled'] ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-slate-300 rounded">
                                    </div>
                                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                        <div>
                                            <h4 class="text-sm font-medium text-slate-900 dark:text-white">Enable Reporting</h4>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">Allow staff to submit reports</p>
                                        </div>
                                        <input type="checkbox" name="reporting_enabled" <?php echo $business['reporting_enabled'] ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-slate-300 rounded">
                                    </div>
                                </div>
                                <button type="submit" name="update_settings" class="px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors font-semibold flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[20px]">save</span>
                                    <span>Update Settings</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>

</html>