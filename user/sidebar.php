<?php
// Sidebar component - reusable across user pages

// Determine current page if not set
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

// Helper function to check if menu item is active
function isActiveUser($page, $current)
{
    return $page === $current ? 'bg-blue-50 text-primary shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
}

function getFontWeightUser($page, $current)
{
    return $page === $current ? 'font-semibold' : 'font-medium';
}
?>
<!-- Desktop Sidebar -->
<aside class="hidden w-64 flex-col border-r border-border-subtle bg-card md:flex" id="sidebar">
    <div class="flex h-full flex-col justify-between p-4">
        <div class="flex flex-col gap-6">
            <div class="flex items-center gap-3 px-2">
                <div class="bg-blue-50 flex items-center justify-center rounded-lg size-10 text-primary">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div class="flex flex-col">
                    <h1 class="text-base font-bold leading-tight text-slate-800">TimeTrack Pro</h1>
                    <p class="text-slate-500 text-xs font-medium">Staff Portal</p>
                </div>
            </div>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('dashboard.php', $current_page); ?> transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm <?php echo getFontWeightUser('dashboard.php', $current_page); ?>">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('projects.php', $current_page); ?> transition-colors" href="projects.php">
                    <span class="material-symbols-outlined text-[20px]">work</span>
                    <p class="text-sm <?php echo getFontWeightUser('projects.php', $current_page); ?>">My Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('tasks.php', $current_page); ?> transition-colors" href="tasks.php">
                    <span class="material-symbols-outlined text-[20px]">assignment</span>
                    <p class="text-sm <?php echo getFontWeightUser('tasks.php', $current_page); ?>">My Tasks</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('teacher_timetable.php', $current_page); ?> transition-colors" href="../teacher_timetable.php">
                    <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                    <p class="text-sm <?php echo getFontWeightUser('teacher_timetable.php', $current_page); ?>">Timetable</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('teacher_activity.php', $current_page); ?> transition-colors" href="../teacher_activity.php">
                    <span class="material-symbols-outlined text-[20px]">task_alt</span>
                    <p class="text-sm <?php echo getFontWeightUser('teacher_activity.php', $current_page); ?>">Activities</p>
                </a>
                <?php if (isset($_SESSION['can_clock_others']) && $_SESSION['can_clock_others']): ?>
                    <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('clock-others.php', $current_page); ?> transition-colors" href="clock-others.php">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                        <p class="text-sm <?php echo getFontWeightUser('clock-others.php', $current_page); ?>">Clock Others</p>
                    </a>
                <?php endif; ?>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('settings.php', $current_page); ?> transition-colors" href="settings.php">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    <p class="text-sm <?php echo getFontWeightUser('settings.php', $current_page); ?>">Settings</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors" href="../logout.php">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Logout</p>
                </a>
            </nav>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border-subtle p-3 bg-slate-50">
            <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-full size-10 shrink-0 ring-2 ring-white flex items-center justify-center text-white font-semibold text-sm">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
            </div>
            <div class="flex flex-col min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <p class="text-xs text-slate-500 truncate"><?php echo ucfirst($_SESSION['category']); ?></p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden hidden" onclick="toggleSidebar()"></div>

<!-- Mobile Sidebar -->
<aside class="fixed left-0 top-0 h-full w-64 bg-card border-r border-border-subtle z-50 transform -translate-x-full transition-transform duration-300 md:hidden" id="mobile-sidebar">
    <div class="flex h-full flex-col justify-between p-4">
        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-50 flex items-center justify-center rounded-lg size-10 text-primary">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-base font-bold leading-tight text-slate-800">TimeTrack Pro</h1>
                        <p class="text-slate-500 text-xs font-medium">Staff Portal</p>
                    </div>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('dashboard.php', $current_page); ?> transition-colors" href="dashboard.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <p class="text-sm <?php echo getFontWeightUser('dashboard.php', $current_page); ?>">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('projects.php', $current_page); ?> transition-colors" href="projects.php">
                    <span class="material-symbols-outlined text-[20px]">work</span>
                    <p class="text-sm <?php echo getFontWeightUser('projects.php', $current_page); ?>">My Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('teacher_timetable.php', $current_page); ?> transition-colors" href="../teacher_timetable.php">
                    <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                    <p class="text-sm <?php echo getFontWeightUser('teacher_timetable.php', $current_page); ?>">Timetable</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('teacher_activity.php', $current_page); ?> transition-colors" href="../teacher_activity.php">
                    <span class="material-symbols-outlined text-[20px]">task_alt</span>
                    <p class="text-sm <?php echo getFontWeightUser('teacher_activity.php', $current_page); ?>">Activities</p>
                </a>
                <?php if (isset($_SESSION['can_clock_others']) && $_SESSION['can_clock_others']): ?>
                    <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('clock-others.php', $current_page); ?> transition-colors" href="clock-others.php">
                        <span class="material-symbols-outlined text-[20px]">group</span>
                        <p class="text-sm <?php echo getFontWeightUser('clock-others.php', $current_page); ?>">Clock Others</p>
                    </a>
                <?php endif; ?>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg <?php echo isActiveUser('settings.php', $current_page); ?> transition-colors" href="settings.php">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    <p class="text-sm <?php echo getFontWeightUser('settings.php', $current_page); ?>">Settings</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-3 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors" href="../logout.php">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    <p class="text-sm font-medium">Logout</p>
                </a>
            </nav>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-border-subtle p-3 bg-slate-50">
            <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-full size-10 shrink-0 ring-2 ring-white flex items-center justify-center text-white font-semibold text-sm">
                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
            </div>
            <div class="flex flex-col min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                <p class="text-xs text-slate-500 truncate"><?php echo ucfirst($_SESSION['category']); ?></p>
            </div>
        </div>
    </div>
</aside>