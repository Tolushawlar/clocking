<?php
// Determine current page if not set
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

function isActive($page, $current)
{
    return $page === $current ? 'bg-primary/10 text-primary' : 'text-gray-700 hover:bg-gray-100';
}

function isActiveIcon($page, $current)
{
    return $page === $current ? 'filled' : '';
}
?>
<!-- Side Navigation -->
<aside class="w-64 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col justify-between">
    <div class="flex flex-col h-full">
        <div class="p-6">
            <div class="flex items-center gap-2 mb-8">
                <div class="text-primary">
                    <span class="material-symbols-outlined filled" style="font-size: 32px;">admin_panel_settings</span>
                </div>
                <div>
                    <h1 class="text-gray-900 text-lg font-bold leading-tight">Super Admin</h1>
                    <p class="text-gray-500 text-xs font-medium">TimeTrack Pro</p>
                </div>
            </div>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('dashboard.php', $current_page); ?> transition-colors group" href="dashboard.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('dashboard.php', $current_page); ?>">dashboard</span>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('businesses.php', $current_page); ?> transition-colors group" href="businesses.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('businesses.php', $current_page); ?>">business</span>
                    <span class="text-sm font-medium">Businesses</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('contact_submissions.php', $current_page); ?> transition-colors group" href="contact_submissions.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('contact_submissions.php', $current_page); ?>">mail</span>
                    <span class="text-sm font-medium">Contact Submissions</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?php echo isActive('settings.php', $current_page); ?> transition-colors group" href="settings.php">
                    <span class="material-symbols-outlined <?php echo isActiveIcon('settings.php', $current_page); ?>">settings</span>
                    <span class="text-sm font-medium">Settings</span>
                </a>
            </nav>
        </div>
    </div>
</aside>