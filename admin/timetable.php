<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];

// Handle timetable creation
if (isset($_POST['create_timetable'])) {
    $name = trim($_POST['name']);
    $academic_year = trim($_POST['academic_year']);
    $semester = trim($_POST['semester']);
    
    $stmt = $db->prepare("INSERT INTO timetables (business_id, user_id, name, academic_year, semester) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $business_id, $user_id, $name, $academic_year, $semester);
    
    if ($stmt->execute()) {
        $timetable_id = $db->insert_id;
        header("Location: timetable.php?id=$timetable_id&msg=Timetable created successfully");
        exit;
    }
}

// Handle slot creation
if (isset($_POST['create_slot'])) {
    $timetable_id = $_POST['timetable_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $subject = trim($_POST['subject']);
    $class_name = trim($_POST['class_name']);
    $room = trim($_POST['room']);
    $activity_type = $_POST['activity_type'];
    $notes = trim($_POST['notes']);
    
    $stmt = $db->prepare("INSERT INTO timetable_slots (timetable_id, day_of_week, start_time, end_time, subject, class_name, room, activity_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $timetable_id, $day_of_week, $start_time, $end_time, $subject, $class_name, $room, $activity_type, $notes);
    
    if ($stmt->execute()) {
        header("Location: timetable.php?id=$timetable_id&msg=Class added successfully");
        exit;
    }
}

// Get user's timetables
$timetables_query = "SELECT * FROM timetables WHERE business_id = ? AND user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($timetables_query);
$stmt->bind_param("ii", $business_id, $user_id);
$stmt->execute();
$timetables = $stmt->get_result();

// Get active timetable
$active_timetable = null;
$timetable_id = $_GET['id'] ?? null;

if ($timetable_id) {
    $stmt = $db->prepare("SELECT * FROM timetables WHERE id = ? AND business_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $timetable_id, $business_id, $user_id);
    $stmt->execute();
    $active_timetable = $stmt->get_result()->fetch_assoc();
}

// Get timetable slots if we have an active timetable
$slots = [];
if ($active_timetable) {
    $slots_query = "SELECT * FROM timetable_slots WHERE timetable_id = ? ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), start_time";
    $stmt = $db->prepare($slots_query);
    $stmt->bind_param("i", $active_timetable['id']);
    $stmt->execute();
    $slots_result = $stmt->get_result();
    
    while ($slot = $slots_result->fetch_assoc()) {
        $slots[$slot['day_of_week']][] = $slot;
    }
}

// Get user info
$stmt = $db->prepare("SELECT firstname, lastname, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
$time_slots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
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
                    <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
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
                <a class="text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="dashboard.php">Dashboard</a>
                <a class="text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="reports.php">Reports</a>
                <a class="text-primary font-semibold text-sm leading-normal bg-primary/10 px-3 py-1.5 rounded-lg" href="timetable.php">Timetable</a>
                <a class="text-slate-600 dark:text-slate-300 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="settings.php">Settings</a>
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
            <h3 class="text-slate-900 dark:text-white font-bold text-lg mb-4">My Timetables</h3>
            <div class="space-y-3 mb-6">
                <?php while ($tt = $timetables->fetch_assoc()): ?>
                <a href="timetable.php?id=<?php echo $tt['id']; ?>" class="block p-3 <?php echo $active_timetable && $active_timetable['id'] == $tt['id'] ? 'bg-primary/10 border-primary/20 text-primary' : 'bg-slate-50 dark:bg-slate-800 border-slate-100 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'; ?> border rounded-lg transition-colors">
                    <div class="font-semibold text-sm"><?php echo htmlspecialchars($tt['name']); ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($tt['academic_year'] . ' - ' . $tt['semester']); ?></div>
                </a>
                <?php endwhile; ?>
            </div>
            
            <button onclick="openTimetableModal()" class="w-full p-3 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg text-slate-500 hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">add</span>
                <span class="text-sm font-medium">New Timetable</span>
            </button>

            <div class="mt-8 border-t border-slate-200 dark:border-slate-800 pt-6">
                <h3 class="text-slate-900 dark:text-white font-bold text-sm mb-3">Quick Add Classes</h3>
                <p class="text-slate-500 dark:text-slate-400 text-xs mb-4">Common class types for quick scheduling</p>
                <div class="space-y-2">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg cursor-move hover:shadow-md transition-shadow text-xs">
                        <div class="font-semibold text-blue-800 dark:text-blue-300">Math Class</div>
                        <div class="text-blue-600 dark:text-blue-400">60 min • Room 104</div>
                    </div>
                    <div class="p-2 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 rounded-lg cursor-move hover:shadow-md transition-shadow text-xs">
                        <div class="font-semibold text-green-800 dark:text-green-300">Science Lab</div>
                        <div class="text-green-600 dark:text-green-400">90 min • Lab 2</div>
                    </div>
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 rounded-lg cursor-move hover:shadow-md transition-shadow text-xs">
                        <div class="font-semibold text-purple-800 dark:text-purple-300">English Lit</div>
                        <div class="text-purple-600 dark:text-purple-400">45 min • Room 201</div>
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
                        <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?php echo $active_timetable ? htmlspecialchars($active_timetable['name']) : 'Teacher Timetable'; ?>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400">
                            <?php if ($active_timetable): ?>
                                Manage your weekly classes and activities for <?php echo htmlspecialchars($active_timetable['academic_year'] . ' - ' . $active_timetable['semester']); ?>
                            <?php else: ?>
                                Create or select a timetable to get started
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($active_timetable): ?>
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
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($active_timetable): ?>
            <!-- Timetable Grid Container -->
            <div class="flex-1 overflow-auto custom-scrollbar px-6 pb-6 md:px-8">
                <div class="bg-white dark:bg-[#1a202c] rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 min-w-[800px]">
                    <!-- Table Header -->
                    <div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr] border-b border-slate-200 dark:border-slate-800 sticky top-0 bg-white dark:bg-[#1a202c] z-10 rounded-t-xl">
                        <div class="p-4 border-r border-slate-100 dark:border-slate-800 flex items-center justify-center">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Time</span>
                        </div>
                        <?php foreach ($days as $day): ?>
                        <div class="p-3 text-center border-r border-slate-100 dark:border-slate-800 last:border-r-0">
                            <div class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase mb-1"><?php echo ucfirst(substr($day, 0, 3)); ?></div>
                            <div class="text-xl font-bold text-slate-900 dark:text-white"><?php echo date('j', strtotime("next $day")); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Table Body -->
                    <div class="relative">
                        <?php foreach ($time_slots as $time): ?>
                        <div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr] border-b border-slate-100 dark:border-slate-800 min-h-[100px] last:border-b-0">
                            <div class="p-3 text-right text-xs text-slate-400 font-medium border-r border-slate-100 dark:border-slate-800">
                                <?php echo date('h:i A', strtotime($time)); ?>
                            </div>
                            
                            <?php foreach ($days as $day): ?>
                            <div class="p-2 border-r border-slate-100 dark:border-slate-800 relative group last:border-r-0">
                                <?php
                                $day_slots = $slots[$day] ?? [];
                                $current_slot = null;
                                foreach ($day_slots as $slot) {
                                    $slot_hour = date('H:i', strtotime($slot['start_time']));
                                    if ($slot_hour === $time) {
                                        $current_slot = $slot;
                                        break;
                                    }
                                }
                                
                                if ($current_slot):
                                    $colors = [
                                        'class' => 'bg-blue-50 dark:bg-blue-900/30 border-blue-500 text-blue-700 dark:text-blue-300',
                                        'lab' => 'bg-green-50 dark:bg-green-900/30 border-green-500 text-green-700 dark:text-green-300',
                                        'meeting' => 'bg-purple-50 dark:bg-purple-900/30 border-purple-500 text-purple-700 dark:text-purple-300',
                                        'break' => 'bg-orange-50 dark:bg-orange-900/30 border-orange-500 text-orange-700 dark:text-orange-300',
                                        'planning' => 'bg-slate-50 dark:bg-slate-800/50 border-slate-400 text-slate-600 dark:text-slate-300',
                                        'assembly' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-500 text-amber-700 dark:text-amber-300'
                                    ];
                                    $color_class = $colors[$current_slot['activity_type']] ?? $colors['class'];
                                ?>
                                <div class="w-full h-full <?php echo $color_class; ?> border-l-4 rounded-r-md p-2 cursor-pointer hover:shadow-md transition-all shadow-sm">
                                    <div class="text-xs font-bold mb-0.5"><?php echo htmlspecialchars($current_slot['subject'] ?: $current_slot['activity_type']); ?></div>
                                    <?php if ($current_slot['class_name']): ?>
                                    <div class="text-[11px] opacity-80"><?php echo htmlspecialchars($current_slot['class_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($current_slot['room']): ?>
                                    <div class="text-[11px] flex items-center gap-1 opacity-75">
                                        <span class="material-symbols-outlined text-[12px]">location_on</span>
                                        <?php echo htmlspecialchars($current_slot['room']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <button onclick="openSlotModal('<?php echo $day; ?>', '<?php echo $time; ?>')" class="w-full h-full bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-md p-2 cursor-pointer border-dashed flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                                    <span class="material-symbols-outlined text-slate-400 text-lg mb-1">add</span>
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Add Class</div>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- No Timetable Selected -->
            <div class="flex-1 flex items-center justify-center p-8">
                <div class="text-center max-w-md">
                    <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-slate-400 text-4xl">calendar_month</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Timetable Selected</h3>
                    <p class="text-slate-500 dark:text-slate-400 mb-6">Create a new timetable or select an existing one to start managing your classes.</p>
                    <button onclick="openTimetableModal()" class="bg-primary hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        Create New Timetable
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Create Timetable Modal -->
    <div id="timetableModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-[#1a202c] rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Create New Timetable</h3>
                <button onclick="closeTimetableModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Timetable Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="e.g., Fall 2023 Schedule">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Academic Year</label>
                        <input type="text" name="academic_year" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="2023-2024">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Semester</label>
                        <select name="semester" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                            <option value="">Select Semester</option>
                            <option value="Fall">Fall</option>
                            <option value="Spring">Spring</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeTimetableModal()" class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="create_timetable" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Create Timetable
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Class Slot Modal -->
    <div id="slotModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-[#1a202c] rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Add Class</h3>
                <button onclick="closeSlotModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="create_slot" value="1">
                <input type="hidden" name="timetable_id" value="<?php echo $active_timetable['id'] ?? ''; ?>">
                <input type="hidden" name="day_of_week" id="slot_day">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Start Time</label>
                        <input type="time" name="start_time" id="slot_start_time" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">End Time</label>
                        <input type="time" name="end_time" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Subject/Activity</label>
                    <input type="text" name="subject" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="e.g., Mathematics, Physics Lab">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Class/Grade</label>
                        <input type="text" name="class_name" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="e.g., Grade 10A">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Room</label>
                        <input type="text" name="room" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="e.g., Room 104">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Activity Type</label>
                    <select name="activity_type" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                        <option value="class">Regular Class</option>
                        <option value="lab">Laboratory</option>
                        <option value="meeting">Meeting</option>
                        <option value="break">Break</option>
                        <option value="planning">Planning Time</option>
                        <option value="assembly">Assembly</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="Additional notes..."></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeSlotModal()" class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Add Class
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openTimetableModal() {
        document.getElementById('timetableModal').classList.remove('hidden');
        document.getElementById('timetableModal').classList.add('flex');
    }

    function closeTimetableModal() {
        document.getElementById('timetableModal').classList.add('hidden');
        document.getElementById('timetableModal').classList.remove('flex');
    }

    function openSlotModal(day, time) {
        document.getElementById('slot_day').value = day;
        document.getElementById('slot_start_time').value = time + ':00';
        document.getElementById('slotModal').classList.remove('hidden');
        document.getElementById('slotModal').classList.add('flex');
    }

    function closeSlotModal() {
        document.getElementById('slotModal').classList.add('hidden');
        document.getElementById('slotModal').classList.remove('flex');
    }

    function openCreateModal() {
        <?php if ($active_timetable): ?>
        openSlotModal('monday', '09:00');
        <?php else: ?>
        openTimetableModal();
        <?php endif; ?>
    }

    // Close modals when clicking outside
    document.getElementById('timetableModal').addEventListener('click', function(e) {
        if (e.target === this) closeTimetableModal();
    });

    document.getElementById('slotModal').addEventListener('click', function(e) {
        if (e.target === this) closeSlotModal();
    });
    </script>
</body>
</html>