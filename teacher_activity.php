<?php
require_once 'lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    // For testing, set default session values
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Teacher';
    $_SESSION['lastname'] = 'User';
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: index.php');
    exit;
}

// Get user info
$user = ['role' => 'teacher', 'firstname' => 'User', 'lastname' => ''];
try {
    $stmt = $db->prepare("SELECT category as role, firstname, lastname FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        $user = $result;
        $user['role'] = $result['role'];
    }
} catch (Exception $e) {
    // Use defaults if query fails
}

// Handle activity completion
if (isset($_POST['complete_activity'])) {
    $activity_id = $_POST['activity_id'];
    $completion_note = trim($_POST['completion_note'] ?? '');
    
    $stmt = $db->prepare("UPDATE teacher_activities SET status = 'completed', completed_at = NOW(), completion_note = ? WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("sii", $completion_note, $activity_id, $user_id);
    
    if ($stmt->execute()) {
        header('Location: teacher_activity.php?msg=Activity marked as completed');
        exit;
    }
}

// Handle activity undo
if (isset($_POST['undo_activity'])) {
    $activity_id = $_POST['activity_id'];
    
    $stmt = $db->prepare("UPDATE teacher_activities SET status = 'pending', completed_at = NULL, completion_note = NULL WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $activity_id, $user_id);
    
    if ($stmt->execute()) {
        header('Location: teacher_activity.php?msg=Activity status reverted');
        exit;
    }
}

// Get today's activities
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT ta.*, 
           CASE 
               WHEN ta.start_time <= CURTIME() AND ta.status = 'pending' THEN 'current'
               WHEN ta.start_time > CURTIME() THEN 'upcoming'
               ELSE ta.status
           END as display_status
    FROM teacher_activities ta 
    WHERE ta.business_id = ? AND ta.teacher_id = ? AND ta.activity_date = ?
    ORDER BY ta.start_time ASC
");
$stmt->bind_param("iis", $business_id, $user_id, $today);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate progress
$total_activities = count($activities);
$completed_activities = count(array_filter($activities, fn($a) => $a['status'] === 'completed'));
$progress_percentage = $total_activities > 0 ? round(($completed_activities / $total_activities) * 100) : 0;

// Calculate time logged
$total_minutes = array_sum(array_map(fn($a) => $a['duration'], array_filter($activities, fn($a) => $a['status'] === 'completed')));
$remaining_minutes = array_sum(array_map(fn($a) => $a['duration'], array_filter($activities, fn($a) => $a['status'] !== 'completed')));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Teacher Activity Fulfilment - TimeTrack Pro</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
</style>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "primary-hover": "#0f4bc4",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                        "card-light": "#ffffff",
                        "card-dark": "#1a2233",
                        "text-main": "#0d121b",
                        "text-secondary": "#4c669a",
                        "border-light": "#e7ebf3",
                        "border-dark": "#2a3447",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
</script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-gray-100 overflow-hidden">
<div class="flex h-screen flex-col overflow-hidden">
<!-- Top Navigation -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-border-light dark:border-border-dark px-6 py-3 bg-card-light dark:bg-card-dark shrink-0 z-20">
<div class="flex items-center gap-4 text-text-main dark:text-white">
<div class="size-6 text-primary">
<svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" fill="currentColor"></path>
</svg>
</div>
<h2 class="text-text-main dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">TimeTrack Pro</h2>
</div>
<div class="flex flex-1 justify-end gap-6 items-center">
<!-- Date Display -->
<div class="hidden md:flex items-center gap-2 text-sm text-text-secondary dark:text-gray-400">
<span class="material-symbols-outlined text-[20px]">calendar_today</span>
<span><?php echo date('F j, Y'); ?></span>
</div>
<div class="h-8 w-[1px] bg-border-light dark:bg-border-dark hidden md:block"></div>
<button class="relative p-2 text-text-secondary hover:text-primary transition-colors">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-1.5 right-1.5 size-2 bg-red-500 rounded-full border-2 border-card-light dark:border-card-dark"></span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 border-2 border-border-light dark:border-border-dark cursor-pointer hover:border-primary transition-colors" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuC8SWdaqrTPADjFN3A2yN-zTUfCJPhIRH1N1T-bAkavzOH7t6a_6PkaLH3KRvlRWFGTyKeSWWYVl9qSPhPxYtKqeDUVgF4YV9MlVvf1gEFoy-aLsh3cE_DGvoDPQqvBl3JKs0pbpa66Pw9RSxTUUwXM3oHCJ-k5s66JwRkIuv4Cmz8qBmWC-E9twE7gdI-nigTSIY-4fcz2vM15Qy09Zy8zg-lP2kJRRlf4U5y0rKPBvmqampwNapaR7QB48mMI87pv7EAFnkpP_GU");'></div>
</div>
</header>

<div class="flex flex-1 overflow-hidden relative">
<!-- Side Navigation -->
<aside class="w-64 flex-col bg-card-light dark:bg-card-dark border-r border-border-light dark:border-border-dark hidden md:flex shrink-0">
<div class="flex flex-col gap-4 p-4">
<div class="flex flex-col px-2">
<h1 class="text-text-main dark:text-white text-base font-medium leading-normal">Main Menu</h1>
<p class="text-text-secondary dark:text-gray-400 text-xs font-normal leading-normal uppercase mt-1">Teacher Portal</p>
</div>
<nav class="flex flex-col gap-1">
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="index.php">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary">dashboard</span>
<span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary dark:bg-primary/20" href="teacher_activity.php">
<span class="material-symbols-outlined text-[20px] fill">calendar_month</span>
<span class="text-sm font-medium">Schedule</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="admin/team_assignment.php">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary">groups</span>
<span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Students</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="admin/reports.php">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary">bar_chart</span>
<span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Reports</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="admin/projects.php">
<span class="material-symbols-outlined text-[20px] group-hover:text-primary">assignment</span>
<span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Projects</span>
</a>
</nav>
<div class="h-[1px] bg-border-light dark:bg-border-dark my-2"></div>
<div class="px-2">
<p class="text-text-secondary dark:text-gray-400 text-xs font-normal leading-normal uppercase mb-2">Filters</p>
<div class="flex flex-col gap-2">
<label class="flex items-center gap-2 text-sm text-text-main dark:text-gray-300 cursor-pointer">
<input checked="" class="rounded border-border-light text-primary focus:ring-primary/30" type="checkbox"/>
<span>Grade 10</span>
</label>
<label class="flex items-center gap-2 text-sm text-text-main dark:text-gray-300 cursor-pointer">
<input checked="" class="rounded border-border-light text-primary focus:ring-primary/30" type="checkbox"/>
<span>Grade 11</span>
</label>
<label class="flex items-center gap-2 text-sm text-text-main dark:text-gray-300 cursor-pointer">
<input class="rounded border-border-light text-primary focus:ring-primary/30" type="checkbox"/>
<span>Extra-curricular</span>
</label>
</div>
</div>
</div>
<div class="mt-auto p-4 border-t border-border-light dark:border-border-dark">
<a class="flex items-center gap-3 px-3 py-2 text-text-secondary hover:text-text-main dark:hover:text-white transition-colors" href="admin/settings.php">
<span class="material-symbols-outlined text-[20px]">settings</span>
<span class="text-sm font-medium">Settings</span>
</a>
</div>
</aside>

<!-- Main Content Area -->
<main class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark p-6 lg:p-10 relative">
<div class="mx-auto max-w-[1000px] flex flex-col gap-6 pb-20">
<!-- Page Header & Action -->
<div class="flex flex-wrap justify-between items-end gap-4">
<div class="flex flex-col gap-1">
<h1 class="text-text-main dark:text-white text-3xl font-bold tracking-tight">Daily Schedule</h1>
<p class="text-text-secondary dark:text-gray-400 text-sm">View and manage your classes for today, <?php echo date('M j'); ?>.</p>
</div>
<div class="flex items-center gap-3">
<!-- Segmented Button -->
<div class="flex h-9 items-center rounded-lg bg-border-light dark:bg-border-dark p-1">
<label class="flex cursor-pointer h-full items-center justify-center rounded-md px-3 bg-white dark:bg-card-dark shadow-sm text-primary text-xs font-semibold transition-all">
<span>Day</span>
<input checked="" class="hidden" name="view" type="radio" value="day"/>
</label>
<label class="flex cursor-pointer h-full items-center justify-center rounded-md px-3 text-text-secondary hover:text-text-main dark:text-gray-400 dark:hover:text-white text-xs font-medium transition-all">
<span>Week</span>
<input class="hidden" name="view" type="radio" value="week"/>
</label>
</div>
<button onclick="openAddModal()" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 h-9 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary/20">
<span class="material-symbols-outlined text-[18px]">add</span>
<span>Add Activity</span>
</button>
</div>
</div>

<!-- Progress Summary Card -->
<div class="bg-card-light dark:bg-card-dark rounded-xl p-5 border border-border-light dark:border-border-dark shadow-sm flex flex-col sm:flex-row gap-6 items-center">
<div class="flex-1 w-full">
<div class="flex justify-between items-end mb-2">
<div>
<p class="text-text-main dark:text-white text-base font-semibold">Daily Progress</p>
<p class="text-text-secondary dark:text-gray-400 text-xs mt-0.5">Keep it up! You are on track.</p>
</div>
<div class="text-right">
<span class="text-2xl font-bold text-primary"><?php echo $completed_activities; ?></span>
<span class="text-text-secondary dark:text-gray-500 text-sm font-medium">/ <?php echo $total_activities; ?> Classes</span>
</div>
</div>
<div class="relative h-2.5 w-full rounded-full bg-background-light dark:bg-gray-700 overflow-hidden">
<div class="absolute top-0 left-0 h-full rounded-full bg-primary transition-all duration-500 ease-out" style="width: <?php echo $progress_percentage; ?>%;"></div>
</div>
</div>
<div class="w-px h-12 bg-border-light dark:bg-border-dark hidden sm:block"></div>
<div class="flex gap-8 shrink-0 w-full sm:w-auto justify-around sm:justify-start">
<div class="flex flex-col items-center sm:items-start">
<span class="text-text-secondary dark:text-gray-500 text-xs font-medium uppercase tracking-wider">Hours Logged</span>
<span class="text-text-main dark:text-white text-lg font-semibold font-mono"><?php echo floor($total_minutes/60) . 'h ' . ($total_minutes%60) . 'm'; ?></span>
</div>
<div class="flex flex-col items-center sm:items-start">
<span class="text-text-secondary dark:text-gray-500 text-xs font-medium uppercase tracking-wider">Remaining</span>
<span class="text-text-main dark:text-white text-lg font-semibold font-mono"><?php echo floor($remaining_minutes/60) . 'h ' . ($remaining_minutes%60) . 'm'; ?></span>
</div>
</div>
</div>

<!-- Timeline / Schedule -->
<div class="flex flex-col gap-4 mt-2">
<!-- Header for timeline -->
<div class="flex justify-between items-center px-2">
<h3 class="text-sm font-semibold text-text-secondary dark:text-gray-400 uppercase tracking-wider">Today's Timeline</h3>
<button class="text-primary text-sm font-medium hover:underline">View Full Calendar</button>
</div>

<?php foreach ($activities as $activity): 
    $is_completed = $activity['status'] === 'completed';
    $is_current = $activity['display_status'] === 'current';
    $is_upcoming = $activity['display_status'] === 'upcoming';
?>
<div class="group flex gap-4 relative <?php echo $is_completed ? 'opacity-60 hover:opacity-100' : ''; ?> transition-opacity">
<div class="flex flex-col items-center pt-2 w-16 shrink-0">
<span class="text-sm font-<?php echo $is_current ? 'bold text-primary' : 'medium text-text-secondary dark:text-gray-500'; ?>"><?php echo date('H:i', strtotime($activity['start_time'])); ?></span>
<div class="h-full w-0.5 <?php echo $is_current ? 'bg-primary/20 border-l-2 border-dashed border-primary' : 'bg-border-light dark:bg-border-dark'; ?> mt-2 group-last:hidden"></div>
</div>

<div class="flex-1 bg-<?php echo $is_current ? 'card-light dark:bg-card-dark border-2 border-primary/30 shadow-lg shadow-primary/5 ring-4 ring-primary/5' : ($is_upcoming ? 'background-light dark:bg-background-dark border border-border-light dark:border-border-dark' : 'card-light dark:bg-card-dark border border-border-light dark:border-border-dark'); ?> rounded-xl p-<?php echo $is_current ? '5' : '4'; ?> flex flex-col md:flex-row gap-4 justify-between items-start md:items-center relative overflow-hidden">

<?php if ($is_completed): ?>
<div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500"></div>
<?php elseif ($is_current): ?>
<div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary"></div>
<?php endif; ?>

<div class="flex flex-col gap-<?php echo $is_current ? '2' : '1'; ?> z-10">
<div class="flex items-center gap-2">
<h4 class="text-text-main dark:text-<?php echo $is_current ? 'white' : ($is_completed ? 'gray-300' : 'gray-300'); ?> font-<?php echo $is_current ? 'bold text-lg' : 'semibold'; ?> <?php echo $is_completed ? 'line-through' : ''; ?>"><?php echo htmlspecialchars($activity['activity_name']); ?></h4>
<span class="px-<?php echo $is_current ? '2.5' : '2'; ?> py-<?php echo $is_current ? '1' : '0.5'; ?> rounded-full 
    <?php if ($is_completed): ?>bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800
    <?php elseif ($is_current): ?>bg-primary/10 text-primary border border-primary/20 animate-pulse
    <?php else: ?>bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700<?php endif; ?>
    text-[10px] font-bold uppercase tracking-wide">
    <?php echo $is_completed ? 'Completed' : ($is_current ? 'In Progress' : 'Pending'); ?>
</span>
</div>
<p class="text-sm text-text-secondary dark:text-gray-<?php echo $is_current ? '400' : '500'; ?> flex items-center gap-1<?php echo $is_current ? '.5' : ''; ?>">
<span class="material-symbols-outlined text-[<?php echo $is_current ? '18' : '16'; ?>px]"><?php echo $activity['icon'] ?? 'meeting_room'; ?></span>
<?php echo htmlspecialchars($activity['location'] . ' • ' . $activity['grade_level']); ?>
</p>
<?php if ($is_current && !empty($activity['tags'])): ?>
<div class="flex gap-2 mt-1">
<?php foreach (explode(',', $activity['tags']) as $tag): ?>
<span class="text-xs bg-background-light dark:bg-background-dark text-text-secondary px-2 py-1 rounded border border-border-light dark:border-border-dark"><?php echo trim($tag); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<div class="flex items-center gap-3 z-10 w-full md:w-auto <?php echo $is_current ? 'mt-2 md:mt-0' : 'justify-end'; ?> <?php echo $is_upcoming ? 'opacity-50 pointer-events-none grayscale' : ''; ?>">
<?php if ($is_completed): ?>
<span class="text-xs text-text-secondary dark:text-gray-500 italic mr-2">Marked at <?php echo date('h:i A', strtotime($activity['completed_at'])); ?></span>
<button onclick="editNote(<?php echo $activity['id']; ?>)" class="text-text-secondary hover:text-text-main dark:hover:text-white p-2 rounded-lg hover:bg-background-light dark:hover:bg-gray-700 transition-colors" title="Edit Note">
<span class="material-symbols-outlined text-[20px]">edit_note</span>
</button>
<form method="POST" style="display: inline;">
<input type="hidden" name="undo_activity" value="1">
<input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
<button type="submit" class="text-text-secondary hover:text-red-500 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Undo">
<span class="material-symbols-outlined text-[20px]">undo</span>
</button>
</form>
<?php elseif ($is_current): ?>
<form method="POST" style="display: inline;">
<input type="hidden" name="complete_activity" value="1">
<input type="hidden" name="activity_id" value="<?php echo $activity['id']; ?>">
<button type="submit" class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg active:scale-95">
<span class="material-symbols-outlined text-[20px]">check_circle</span>
<span>Mark Fulfilled</span>
</button>
</form>
<?php else: ?>
<button class="flex items-center justify-center gap-2 bg-white dark:bg-gray-800 border border-border-light dark:border-border-dark text-text-main dark:text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm">
<span>Mark Fulfilled</span>
</button>
<?php endif; ?>
</div>
</div>
</div>
<?php endforeach; ?>

<!-- Empty State Filler -->
<div class="flex flex-col items-center justify-center py-8 mt-4 border-t border-dashed border-border-light dark:border-border-dark">
<div class="size-12 rounded-full bg-background-light dark:bg-gray-800 flex items-center justify-center mb-3">
<span class="material-symbols-outlined text-text-secondary dark:text-gray-500">bedtime</span>
</div>
<p class="text-sm text-text-secondary dark:text-gray-500">No more activities scheduled for today.</p>
</div>
</div>
</div>
</main>
</div>

<!-- Add Activity Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
<div class="flex items-center justify-center min-h-screen p-4">
<div class="bg-card-light dark:bg-card-dark rounded-xl shadow-xl max-w-md w-full">
<div class="p-6 border-b border-border-light dark:border-border-dark">
<h3 class="text-lg font-semibold text-text-main dark:text-white">Add New Activity</h3>
</div>
<form method="POST" class="p-6 space-y-4">
<input type="hidden" name="add_activity" value="1">
<div>
<label class="block text-sm font-medium text-text-main dark:text-gray-300 mb-2">Activity Name</label>
<input type="text" name="activity_name" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary dark:bg-background-dark dark:text-white">
</div>
<div>
<label class="block text-sm font-medium text-text-main dark:text-gray-300 mb-2">Location</label>
<input type="text" name="location" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary dark:bg-background-dark dark:text-white">
</div>
<div class="flex gap-3 pt-4">
<button type="button" onclick="closeAddModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary dark:text-gray-300 rounded-lg hover:bg-background-light dark:hover:bg-gray-800 transition-colors">Cancel</button>
<button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">Add Activity</button>
</div>
</form>
</div>
</div>
</div>

<!-- Toast Notification -->
<?php if (isset($_GET['msg'])): ?>
<div id="toast" class="fixed bottom-6 right-6 z-50 animate-bounce" style="animation-duration: 3s; animation-iteration-count: 1;">
<div class="bg-card-light dark:bg-gray-800 text-text-main dark:text-white px-4 py-3 rounded-lg shadow-lg border border-border-light dark:border-gray-700 flex items-center gap-3">
<span class="material-symbols-outlined text-green-500">check_circle</span>
<div class="flex flex-col">
<span class="text-sm font-semibold">Activity Updated</span>
<span class="text-xs text-text-secondary dark:text-gray-400"><?php echo htmlspecialchars($_GET['msg']); ?></span>
</div>
<button onclick="closeToast()" class="ml-1 text-text-secondary hover:text-text-main">
<span class="material-symbols-outlined text-[18px]">close</span>
</button>
</div>
</div>
<?php endif; ?>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function closeToast() {
    const toast = document.getElementById('toast');
    if (toast) toast.remove();
}

function editNote(activityId) {
    // Implementation for editing notes
    alert('Edit note functionality for activity ' + activityId);
}

// Auto-hide toast after 5 seconds
setTimeout(() => {
    const toast = document.getElementById('toast');
    if (toast) toast.remove();
}, 5000);
</script>
</body>
</html>