<?php
// Sidebar component - reusable across admin pages

// Determine current page if not set
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

// Get current tab from URL hash (for client-side tab detection)
$current_tab = '';

// Helper function to check if menu item is active
function isActive($page, $current, $tab = '')
{
    // Check if it's a dashboard tab link
    if ($tab && $current === 'dashboard.php') {
        return 'bg-primary/10 text-primary';
    }

    // Check for projects page and related pages
    if ($page === 'projects.php') {
        $projectPages = ['projects.php', 'project_phases.php', 'edit_project.php', 'project_details.php', 'add_task.php', 'edit_task.php', 'task_details.php'];
        if (in_array($current, $projectPages)) {
            return 'bg-primary/10 text-primary';
        }
    }

    return $page === $current ? 'bg-primary/10 text-primary' : 'text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700';
}

function isActiveIcon($page, $current, $tab = '')
{
    if ($tab && $current === 'dashboard.php') {
        return 'filled';
    }

    // Check for projects page and related pages
    if ($page === 'projects.php') {
        $projectPages = ['projects.php', 'project_phases.php', 'edit_project.php', 'project_details.php', 'add_task.php', 'edit_task.php', 'task_details.php'];
        if (in_array($current, $projectPages)) {
            return 'filled';
        }
    }

    return $page === $current ? 'filled' : '';
}

function getIconClass($page, $current, $tab = '')
{
    if ($tab && $current === 'dashboard.php') {
        return '';
    }

    // Check for projects page and related pages
    if ($page === 'projects.php') {
        $projectPages = ['projects.php', 'project_phases.php', 'edit_project.php', 'project_details.php', 'add_task.php', 'edit_task.php', 'task_details.php'];
        if (in_array($current, $projectPages)) {
            return '';
        }
    }

    return $page === $current ? '' : 'text-text-secondary group-hover:text-primary transition-colors';
}
?>
<!-- Side Navigation -->
<aside class="w-64 flex-shrink-0 bg-surface-light dark:bg-surface-dark border-r border-border-light dark:border-border-dark flex flex-col justify-between transition-colors duration-200 hidden md:flex">
    <div class="flex flex-col h-full">
        <div class="p-6">
            <div class="flex items-center gap-2 mb-8">
                <div class="text-primary">
                    <span class="material-symbols-outlined filled" style="font-size: 32px;">schedule</span>
                </div>
                <div>
                    <h1 class="text-text-main dark:text-white text-lg font-bold leading-tight">TimeTrack Pro</h1>
                    <p class="text-text-secondary text-xs font-medium">
                        <?php
                        $biz_stmt = $db->prepare("SELECT name FROM business WHERE id = ?");
                        $biz_stmt->bind_param("i", $business_id);
                        $biz_stmt->execute();
                        $biz_result = $biz_stmt->get_result()->fetch_assoc();
                        echo htmlspecialchars($biz_result['name'] ?? 'Business');
                        ?>
                    </p>
                </div>
            </div>
            <nav class="flex flex-col gap-2">
                <a class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('scanner.php', $current_page); ?> transition-colors group" href="scanner.php" data-page="scanner.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('scanner.php', $current_page); ?> <?php echo getIconClass('scanner.php', $current_page); ?>">barcode_reader</span>
                    <span class="text-sm <?php echo $current_page === 'scanner.php' ? 'font-semibold' : 'font-medium'; ?>">Scanner</span>
                </a>
                <a class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('teams.php', $current_page); ?> transition-colors group" href="teams.php" data-page="teams.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('teams.php', $current_page); ?> <?php echo getIconClass('teams.php', $current_page); ?>">group</span>
                    <span class="text-sm <?php echo $current_page === 'teams.php' ? 'font-semibold' : 'font-medium'; ?>">Teams</span>
                </a>
                <a class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('projects.php', $current_page); ?> transition-colors group" href="projects.php" data-page="projects.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('projects.php', $current_page); ?> <?php echo getIconClass('projects.php', $current_page); ?>">work</span>
                    <span class="text-sm <?php echo $current_page === 'projects.php' ? 'font-semibold' : 'font-medium'; ?>">Projects</span>
                </a>
                <a class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('dashboard.php', $current_page, 'reports'); ?> transition-colors group" href="dashboard.php" data-page="dashboard.php" data-tab="reports">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('dashboard.php', $current_page, 'reports'); ?> <?php echo getIconClass('dashboard.php', $current_page, 'reports'); ?>">assessment</span>
                    <span class="text-sm <?php echo $current_page === 'dashboard.php' ? 'font-semibold' : 'font-medium'; ?>">Reports</span>
                </a>
                <a class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('dashboard.php', $current_page, 'users'); ?> transition-colors group" href="dashboard.php#users" data-page="dashboard.php" data-tab="users">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('dashboard.php', $current_page, 'users'); ?> <?php echo getIconClass('dashboard.php', $current_page, 'users'); ?>">people</span>
                    <span class="text-sm <?php echo $current_page === 'dashboard.php' ? 'font-semibold' : 'font-medium'; ?>">Users</span>
                </a>
                <a class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('dashboard.php', $current_page, 'settings'); ?> transition-colors group" href="dashboard.php#settings" data-page="dashboard.php" data-tab="settings">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('dashboard.php', $current_page, 'settings'); ?> <?php echo getIconClass('dashboard.php', $current_page, 'settings'); ?>">settings</span>
                    <span class="text-sm <?php echo $current_page === 'dashboard.php' ? 'font-semibold' : 'font-medium'; ?>">Settings</span>
                </a>
            </nav>
        </div>
    </div>
</aside>