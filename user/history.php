<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

// Handle edit submissions
if ($_POST) {
    $report_id = (int)$_POST['report_id'];
    $today = TODAY;
    
    // Check if report belongs to user and is from today and user hasn't clocked out
    $check_stmt = $db->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ? AND report_date = ? AND clock_out_time IS NULL");
    $check_stmt->bind_param("iis", $report_id, $user_id, $today);
    $check_stmt->execute();
    $report = $check_stmt->get_result()->fetch_assoc();
    
    if ($report) {
        if (isset($_POST['edit_plan'])) {
            $plan = trim($_POST['plan']);
            $stmt = $db->prepare("UPDATE reports SET plan = ? WHERE id = ?");
            $stmt->bind_param("si", $plan, $report_id);
            $stmt->execute();
            header('Location: history.php?msg=Plan updated successfully');
            exit;
        }
        
        if (isset($_POST['edit_report'])) {
            $daily_report = trim($_POST['daily_report']);
            $stmt = $db->prepare("UPDATE reports SET daily_report = ? WHERE id = ?");
            $stmt->bind_param("si", $daily_report, $report_id);
            $stmt->execute();
            header('Location: history.php?msg=Report updated successfully');
            exit;
        }
    }
}

// Get user's history
$stmt = $db->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY report_date DESC LIMIT 30");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>History - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
</head>
<body class="bg-gray-50 font-display">
    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto p-6">
            <div class="mb-6">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Work History</h1>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg mb-6">
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-6">
                <?php while ($record = $history->fetch_assoc()): ?>
                <?php 
                $is_today = $record['report_date'] == TODAY;
                $can_edit = $is_today && !$record['clock_out_time'];
                ?>
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo date('l, F j, Y', strtotime($record['report_date'])); ?>
                                <?php if ($is_today): ?>
                                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Today</span>
                                <?php endif; ?>
                            </h3>
                            <div class="flex items-center gap-4 text-sm text-gray-600 mt-1">
                                <?php if ($record['clock_in_time']): ?>
                                    <span>In: <?php echo date('h:i A', strtotime($record['clock_in_time'])); ?></span>
                                <?php endif; ?>
                                <?php if ($record['clock_out_time']): ?>
                                    <span>Out: <?php echo date('h:i A', strtotime($record['clock_out_time'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $record['clock_out_time'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo $record['clock_out_time'] ? 'Completed' : 'In Progress'; ?>
                        </span>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-900">Work Plan</h4>
                                <?php if ($can_edit && $record['plan']): ?>
                                    <button onclick="editPlan(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['plan'])); ?>')" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                <?php endif; ?>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 min-h-[100px]">
                                <?php if ($record['plan']): ?>
                                    <p class="text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($record['plan'])); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm italic">No plan submitted</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-900">Daily Report</h4>
                                <?php if ($can_edit && $record['daily_report']): ?>
                                    <button onclick="editReport(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['daily_report'])); ?>')" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                <?php endif; ?>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 min-h-[100px]">
                                <?php if ($record['daily_report']): ?>
                                    <p class="text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($record['daily_report'])); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm italic">No report submitted</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Plan Modal -->
    <div id="edit-plan-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Plan</h3>
            <form method="POST">
                <input type="hidden" name="report_id" id="plan-report-id">
                <textarea name="plan" id="plan-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required></textarea>
                <div class="flex gap-3 mt-4">
                    <button type="submit" name="edit_plan" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Update Plan</button>
                    <button type="button" onclick="closeModal('edit-plan-modal')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Report Modal -->
    <div id="edit-report-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Report</h3>
            <form method="POST">
                <input type="hidden" name="report_id" id="report-report-id">
                <textarea name="daily_report" id="report-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required></textarea>
                <div class="flex gap-3 mt-4">
                    <button type="submit" name="edit_report" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Update Report</button>
                    <button type="button" onclick="closeModal('edit-report-modal')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editPlan(reportId, planText) {
            document.getElementById('plan-report-id').value = reportId;
            document.getElementById('plan-text').value = planText;
            document.getElementById('edit-plan-modal').classList.remove('hidden');
        }
        
        function editReport(reportId, reportText) {
            document.getElementById('report-report-id').value = reportId;
            document.getElementById('report-text').value = reportText;
            document.getElementById('edit-report-modal').classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>