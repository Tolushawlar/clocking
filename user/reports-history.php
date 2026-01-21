<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = 'reports-history.php';

// Get user's reports history
$stmt = $db->prepare("
    SELECT report_date, plan, daily_report, status, 
           clock_in_time, plan_submitted_at, report_submitted_at, clock_out_time
    FROM reports 
    WHERE user_id = ? 
    ORDER BY report_date DESC 
    LIMIT 50
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reports = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Plans & Reports History - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "border-subtle": "#e2e8f0",
                        "card": "#ffffff"
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-slate-50 font-display">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>
        
        <main class="flex-1 overflow-y-auto">
            <div class="p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-slate-900">Plans & Reports History</h1>
                    <p class="text-slate-600 mt-1">View your historical daily plans and reports</p>
                </div>

                <?php if ($reports->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($report = $reports->fetch_assoc()): ?>
                            <div class="bg-white rounded-lg border border-slate-200 p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">
                                            <?php echo date('F j, Y', strtotime($report['report_date'])); ?>
                                        </h3>
                                        <div class="flex items-center gap-4 mt-2 text-sm text-slate-600">
                                            <?php if ($report['clock_in_time']): ?>
                                                <span>Clock In: <?php echo date('g:i A', strtotime($report['clock_in_time'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ($report['clock_out_time']): ?>
                                                <span>Clock Out: <?php echo date('g:i A', strtotime($report['clock_out_time'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full
                                        <?php 
                                        switch($report['status']) {
                                            case 'clocked_out': echo 'bg-green-100 text-green-800'; break;
                                            case 'report_submitted': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'plan_submitted': echo 'bg-yellow-100 text-yellow-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                    </span>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-slate-900 mb-2 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-blue-600 text-sm">assignment</span>
                                            Daily Plan
                                        </h4>
                                        <?php if ($report['plan']): ?>
                                            <div class="bg-blue-50 rounded-lg p-4">
                                                <p class="text-slate-700 text-sm"><?php echo nl2br(htmlspecialchars($report['plan'])); ?></p>
                                                <?php if ($report['plan_submitted_at']): ?>
                                                    <p class="text-xs text-slate-500 mt-2">
                                                        Submitted: <?php echo date('g:i A', strtotime($report['plan_submitted_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-slate-500 text-sm italic">No plan submitted</p>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h4 class="font-medium text-slate-900 mb-2 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-green-600 text-sm">description</span>
                                            Daily Report
                                        </h4>
                                        <?php if ($report['daily_report']): ?>
                                            <div class="bg-green-50 rounded-lg p-4">
                                                <p class="text-slate-700 text-sm"><?php echo nl2br(htmlspecialchars($report['daily_report'])); ?></p>
                                                <?php if ($report['report_submitted_at']): ?>
                                                    <p class="text-xs text-slate-500 mt-2">
                                                        Submitted: <?php echo date('g:i A', strtotime($report['report_submitted_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-slate-500 text-sm italic">No report submitted</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <span class="material-symbols-outlined text-slate-400 text-6xl mb-4">history</span>
                        <h3 class="text-lg font-medium text-slate-900 mb-2">No History Found</h3>
                        <p class="text-slate-600">You haven't submitted any plans or reports yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>