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

// Handle class creation
if (isset($_POST['create_class'])) {
    $class_name = trim($_POST['class_name']);
    $room = trim($_POST['room']);
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $duration = $_POST['duration'];
    $subject = trim($_POST['subject']);
    
    $stmt = $db->prepare("INSERT INTO teacher_classes (business_id, teacher_id, class_name, room, day_of_week, start_time, duration, subject) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssiis", $business_id, $user_id, $class_name, $room, $day_of_week, $start_time, $duration, $subject);
    
    if ($stmt->execute()) {
        header('Location: teacher_timetable.php?msg=Class added successfully');
        exit;
    }
}

// Get current week's classes
$current_week = date('Y-m-d', strtotime('monday this week'));
$stmt = $db->prepare("
    SELECT tc.*, 
           CASE tc.day_of_week 
               WHEN 1 THEN 'Monday'
               WHEN 2 THEN 'Tuesday' 
               WHEN 3 THEN 'Wednesday'
               WHEN 4 THEN 'Thursday'
               WHEN 5 THEN 'Friday'
           END as day_name
    FROM teacher_classes tc 
    WHERE tc.business_id = ? AND tc.teacher_id = ?
    ORDER BY tc.day_of_week, tc.start_time
");
$stmt->bind_param("ii", $business_id, $user_id);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Organize classes by day and time
$timetable = [];
$time_slots = ['08:00', '09:00', '10:00', '11:00', '12:00'];
$days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];

foreach ($classes as $class) {
    $timetable[$class['day_of_week']][$class['start_time']] = $class;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Teacher Timetable Management - TimeTrack Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                        "display": ["Lexend", "sans-serif"]
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
<style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display min-h-screen flex flex-col">
<!-- Top Navigation -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 dark:border-slate-800 bg-white dark:bg-[#1a202c] px-6 md:px-10 py-3 sticky top-0 z-50">
<div class="flex items-center gap-8">
<div class="flex items-center gap-3 text-slate-900 dark:text-white">
<div class="size-8 text-primary">
<svg class="w-full h-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" fill="currentColor"></path>
</svg>
</div>
<h2 class="text-xl font-bold leading-tight tracking-[-0.015em]">TimeTrack Pro</h2>
</div>
<div class="hidden lg:flex flex-col min-w-60 h-10">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full bg-slate-100 dark:bg-slate-800 border border-transparent focus-within:border-primary/50 transition-colors">
<div class="text-slate-500 dark:text-slate-400 flex items-center justify-center pl-3">
<span class="material-symbols-outlined text-xl">search</span>
</div>
<input class="w-full bg-transparent border-none text-sm px-3 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-0" placeholder="Search classes, rooms, students..."/>
</div>
</div>
</div>
<div class="flex items-center gap-6">
<nav class="hidden md:flex items-center gap-6">
<a class="text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="index.php">Dashboard</a>
<a class="text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="admin/reports.php">Reports</a>
<a class="text-primary font-semibold text-sm leading-normal bg-primary/10 px-3 py-1.5 rounded-lg" href="teacher_timetable.php">Timetable</a>
<a class="text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="admin/settings.php">Settings</a>
</nav>
<div class="flex items-center gap-3">
<button onclick="openCreateModal()" class="bg-primary hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
<span class="material-symbols-outlined text-[20px]">add</span>
<span class="hidden sm:inline">New Class</span>
</button>
<div class="bg-center bg-no-repeat bg-cover rounded-full size-10 ring-2 ring-slate-100 dark:ring-slate-700 cursor-pointer" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBS1v3wZY2LseE6ZRZ_wxDx2M1ltr2M9oUH4U3jBab9YxBJ1wTxXlwoWneV_maIPYj2ANYzecHX6__k3iOqq0zKtp5jNO1KS86JRPRpMBQLhmS4D6XqSdX-PKUODJ929W-_gJMlFGSKsdm24kX-UnX8vDpuLnbneTATXay_ZZGYOn4YkDmywEzRRO1GTRIqviCAOGMdlCCl43qsa_1MpSlS_jIpTC_kJiX-EYJAov_YwjNYr7pe9M592vxjXxlVEc40hLfpod3uWTU");'></div>
</div>
</div>
</header>

<!-- Main Content -->
<div class="flex flex-1 overflow-hidden">
<!-- Sidebar (Quick Add) -->
<aside class="w-64 bg-white dark:bg-[#1a202c] border-r border-slate-200 dark:border-slate-800 hidden xl:flex flex-col p-5 overflow-y-auto">
<h3 class="text-slate-900 dark:text-white font-bold text-lg mb-4">Quick Draggables</h3>
<p class="text-slate-500 dark:text-slate-400 text-xs mb-6">Drag these items onto the timetable to quickly schedule repetitive tasks.</p>
<div class="space-y-3">
<div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg cursor-move hover:shadow-md transition-shadow group">
<div class="flex justify-between items-start mb-1">
<span class="font-semibold text-blue-800 dark:text-blue-300 text-sm">Homeroom</span>
<span class="material-symbols-outlined text-blue-400 text-sm">drag_indicator</span>
</div>
<div class="text-xs text-blue-600 dark:text-blue-400">Room 302 • 45 min</div>
</div>
<div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-lg cursor-move hover:shadow-md transition-shadow group">
<div class="flex justify-between items-start mb-1">
<span class="font-semibold text-green-800 dark:text-green-300 text-sm">Math 101</span>
<span class="material-symbols-outlined text-green-400 text-sm">drag_indicator</span>
</div>
<div class="text-xs text-green-600 dark:text-green-400">Room 104 • 60 min</div>
</div>
<div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 rounded-lg cursor-move hover:shadow-md transition-shadow group">
<div class="flex justify-between items-start mb-1">
<span class="font-semibold text-purple-800 dark:text-purple-300 text-sm">AP History</span>
<span class="material-symbols-outlined text-purple-400 text-sm">drag_indicator</span>
</div>
<div class="text-xs text-purple-600 dark:text-purple-400">Room 104 • 60 min</div>
</div>
<div class="p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800 rounded-lg cursor-move hover:shadow-md transition-shadow group">
<div class="flex justify-between items-start mb-1">
<span class="font-semibold text-orange-800 dark:text-orange-300 text-sm">English Lit</span>
<span class="material-symbols-outlined text-orange-400 text-sm">drag_indicator</span>
</div>
<div class="text-xs text-orange-600 dark:text-orange-400">Room 201 • 60 min</div>
</div>
<div class="p-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg cursor-move hover:shadow-md transition-shadow group">
<div class="flex justify-between items-start mb-1">
<span class="font-semibold text-slate-800 dark:text-slate-300 text-sm">Planning / Free</span>
<span class="material-symbols-outlined text-slate-400 text-sm">drag_indicator</span>
</div>
<div class="text-xs text-slate-600 dark:text-slate-400">Staff Room • Flexible</div>
</div>
</div>
<div class="mt-8 border-t border-slate-200 dark:border-slate-800 pt-6">
<h3 class="text-slate-900 dark:text-white font-bold text-sm mb-3">Upcoming Events</h3>
<div class="flex items-center gap-3 mb-3">
<div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 font-bold text-xs flex-col leading-none">
<span>OCT</span>
<span class="text-base">27</span>
</div>
<div>
<p class="text-sm font-medium text-slate-900 dark:text-white">Staff Meeting</p>
<p class="text-xs text-slate-500 dark:text-slate-400">3:30 PM - Conference A</p>
</div>
</div>
</div>
</aside>

<!-- Main Timetable Area -->
<main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-background-light dark:bg-background-dark">
<!-- Page Heading & Controls -->
<div class="px-6 py-6 md:px-8">
<div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-6">
<div class="space-y-1">
<h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Teacher Timetable</h1>
<p class="text-slate-500 dark:text-slate-400">Manage your weekly classes and activities for <span class="font-medium text-slate-900 dark:text-slate-200">Oct 23 - 27, 2023</span></p>
</div>
<div class="flex flex-wrap items-center gap-3">
<!-- Segmented Buttons -->
<div class="inline-flex rounded-lg bg-slate-200 dark:bg-slate-700 p-1 h-10">
<label class="cursor-pointer flex items-center justify-center px-4 rounded-[4px] text-sm font-medium transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
<input class="hidden" name="view" type="radio" value="day"/>
                                Day
                            </label>
<label class="cursor-pointer flex items-center justify-center px-4 rounded-[4px] bg-white dark:bg-slate-600 shadow-sm text-slate-900 dark:text-white text-sm font-medium transition-all">
<input checked="" class="hidden" name="view" type="radio" value="week"/>
                                Week
                            </label>
<label class="cursor-pointer flex items-center justify-center px-4 rounded-[4px] text-sm font-medium transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
<input class="hidden" name="view" type="radio" value="list"/>
                                List
                            </label>
</div>
<div class="h-8 w-[1px] bg-slate-300 dark:bg-slate-700 mx-1"></div>
<button class="flex items-center justify-center h-10 px-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors gap-2">
<span class="material-symbols-outlined text-[18px]">print</span>
<span class="hidden sm:inline">Print</span>
</button>
<div class="flex items-center bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 h-10">
<button class="px-3 h-full border-r border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-l-lg text-slate-600 dark:text-slate-400">
<span class="material-symbols-outlined text-[20px]">chevron_left</span>
</button>
<span class="px-4 text-sm font-medium text-slate-900 dark:text-white whitespace-nowrap">This Week</span>
<button class="px-3 h-full border-l border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-r-lg text-slate-600 dark:text-slate-400">
<span class="material-symbols-outlined text-[20px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Filters Chips -->
<div class="flex flex-wrap gap-3 mb-2">
<button class="flex items-center gap-2 h-8 px-3 rounded-full bg-primary/10 text-primary text-sm font-medium border border-primary/20">
<span>My Classes</span>
<span class="material-symbols-outlined text-[16px]">close</span>
</button>
<button class="flex items-center gap-2 h-8 px-3 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:border-slate-300 dark:hover:border-slate-600">
<span>Room: All</span>
<span class="material-symbols-outlined text-[16px]">expand_more</span>
</button>
<button class="flex items-center gap-2 h-8 px-3 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:border-slate-300 dark:hover:border-slate-600">
<span>Grade: 10-12</span>
<span class="material-symbols-outlined text-[16px]">expand_more</span>
</button>
</div>
</div>

<!-- Timetable Grid Container -->
<div class="flex-1 overflow-auto custom-scrollbar px-6 pb-6 md:px-8">
<div class="bg-white dark:bg-[#1a202c] rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 min-w-[800px]">
<!-- Table Header -->
<div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr] border-b border-slate-200 dark:border-slate-800 sticky top-0 bg-white dark:bg-[#1a202c] z-10 rounded-t-xl">
<div class="p-4 border-r border-slate-100 dark:border-slate-800 flex items-center justify-center">
<span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Time</span>
</div>
<?php 
$week_dates = [];
for ($i = 1; $i <= 5; $i++) {
    $date = date('j', strtotime("monday this week +".($i-1)." days"));
    $week_dates[$i] = $date;
    $is_today = date('N') == $i;
    $day_names = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri'];
?>
<div class="p-3 text-center border-r border-slate-100 dark:border-slate-800 <?php echo $is_today ? 'relative bg-primary/5 dark:bg-primary/10' : ''; ?>">
<?php if ($is_today): ?>
<div class="absolute top-0 left-0 w-full h-1 bg-primary"></div>
<?php endif; ?>
<div class="text-xs font-<?php echo $is_today ? 'bold text-primary' : 'medium text-slate-500 dark:text-slate-400'; ?> uppercase mb-1"><?php echo $day_names[$i]; ?></div>
<div class="text-xl font-bold <?php echo $is_today ? 'text-primary' : 'text-slate-900 dark:text-white'; ?>"><?php echo $date; ?></div>
</div>
<?php } ?>
</div>

<!-- Table Body -->
<div class="relative">
<?php foreach ($time_slots as $time): ?>
<div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr] border-b border-slate-100 dark:border-slate-800 min-h-[100px]">
<div class="p-3 text-right text-xs text-slate-400 font-medium border-r border-slate-100 dark:border-slate-800"><?php echo date('h:i A', strtotime($time)); ?></div>
<?php for ($day = 1; $day <= 5; $day++): 
    $is_today = date('N') == $day;
    $class = isset($timetable[$day][$time]) ? $timetable[$day][$time] : null;
?>
<div class="p-2 border-r border-slate-100 dark:border-slate-800 relative <?php echo $is_today ? 'bg-primary/5 dark:bg-primary/10' : ''; ?>">
<?php if ($class): 
    $colors = [
        'Homeroom' => ['bg-blue-50 dark:bg-blue-900/30', 'border-blue-500', 'text-blue-700 dark:text-blue-300', 'text-blue-600 dark:text-blue-400'],
        'Math' => ['bg-green-50 dark:bg-green-900/30', 'border-green-500', 'text-green-700 dark:text-green-300', 'text-green-600 dark:text-green-400'],
        'History' => ['bg-purple-50 dark:bg-purple-900/30', 'border-purple-500', 'text-purple-700 dark:text-purple-300', 'text-purple-600 dark:text-purple-400'],
        'English' => ['bg-orange-50 dark:bg-orange-900/30', 'border-orange-500', 'text-orange-700 dark:text-orange-300', 'text-orange-600 dark:text-orange-400'],
        'Physics' => ['bg-red-50 dark:bg-red-900/30', 'border-red-500', 'text-red-700 dark:text-red-300', 'text-red-600 dark:text-red-400']
    ];
    $subject_key = explode(' ', $class['subject'])[0];
    $color = $colors[$subject_key] ?? $colors['Homeroom'];
?>
<div class="w-full h-full <?php echo $color[0]; ?> border-l-4 <?php echo $color[1]; ?> rounded-r-md p-2 cursor-pointer hover:shadow-md transition-all shadow-sm">
<div class="text-xs font-bold <?php echo $color[2]; ?> mb-0.5"><?php echo htmlspecialchars($class['class_name']); ?></div>
<div class="text-[11px] <?php echo $color[3]; ?> flex items-center gap-1">
<span class="material-symbols-outlined text-[12px]">location_on</span>
<?php echo htmlspecialchars($class['room']); ?>
</div>
</div>
<?php else: ?>
<div class="w-full h-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-md p-2 cursor-pointer border-dashed flex flex-col items-center justify-center opacity-70 hover:opacity-100 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all" onclick="openCreateModal('<?php echo $day; ?>', '<?php echo $time; ?>')">
<span class="material-symbols-outlined text-slate-400 text-lg mb-1">add</span>
<div class="text-xs font-medium text-slate-500 dark:text-slate-400">Add Class</div>
</div>
<?php endif; ?>
</div>
<?php endfor; ?>
</div>
<?php endforeach; ?>

<!-- Lunch Break -->
<div class="grid grid-cols-[80px_1fr] border-b border-slate-100 dark:border-slate-800 min-h-[60px]">
<div class="p-3 text-right text-xs text-slate-400 font-medium border-r border-slate-100 dark:border-slate-800">11:00 AM</div>
<div class="p-1 bg-slate-50 dark:bg-slate-800/30 flex items-center justify-center">
<div class="text-xs font-semibold text-slate-400 tracking-widest uppercase flex items-center gap-2">
<span class="w-12 h-[1px] bg-slate-300 dark:bg-slate-700"></span>
Lunch Break
<span class="w-12 h-[1px] bg-slate-300 dark:bg-slate-700"></span>
</div>
</div>
</div>
</div>
</div>
</div>
</main>
</div>

<!-- Create Class Modal -->
<div id="createModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
<div class="flex items-center justify-center min-h-screen p-4">
<div class="bg-white dark:bg-[#1a202c] rounded-xl shadow-xl max-w-md w-full">
<div class="p-6 border-b border-slate-200 dark:border-slate-800">
<h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add New Class</h3>
</div>
<form method="POST" class="p-6 space-y-4">
<input type="hidden" name="create_class" value="1">
<input type="hidden" id="modal_day" name="day_of_week" value="">
<input type="hidden" id="modal_time" name="start_time" value="">
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Class Name</label>
<input type="text" name="class_name" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary dark:bg-slate-800 dark:text-white">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Subject</label>
<select name="subject" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary dark:bg-slate-800 dark:text-white">
<option value="">Select Subject</option>
<option value="Homeroom">Homeroom</option>
<option value="Math">Mathematics</option>
<option value="English">English Literature</option>
<option value="History">History</option>
<option value="Physics">Physics</option>
<option value="Chemistry">Chemistry</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Room</label>
<input type="text" name="room" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary dark:bg-slate-800 dark:text-white">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Duration (minutes)</label>
<select name="duration" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary dark:bg-slate-800 dark:text-white">
<option value="45">45 minutes</option>
<option value="60">60 minutes</option>
<option value="90">90 minutes</option>
</select>
</div>
<div class="flex gap-3 pt-4">
<button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Cancel</button>
<button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">Add Class</button>
</div>
</form>
</div>
</div>
</div>

<script>
function openCreateModal(day = '', time = '') {
    document.getElementById('createModal').classList.remove('hidden');
    if (day) document.getElementById('modal_day').value = day;
    if (time) document.getElementById('modal_time').value = time;
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});
</script>
</body>
</html>