<?php
function renderAdminLayout($title, $currentPage, $content) {
    $current_user = $_SESSION['firstname'] ?? 'Admin';
    $current_role = 'Admin';
    
    $nav_items = [
        'dashboard' => ['icon' => 'dashboard', 'label' => 'Dashboard', 'url' => 'dashboard.php'],
        'teams' => ['icon' => 'groups', 'label' => 'Teams', 'url' => 'teams.php'],
        'projects' => ['icon' => 'folder', 'label' => 'Projects', 'url' => 'projects.php'],
        'reports' => ['icon' => 'analytics', 'label' => 'Reports', 'url' => 'reports.php'],
        'settings' => ['icon' => 'settings', 'label' => 'Settings', 'url' => 'settings.php']
    ];
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - TimeTrack Pro</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    </head>
    <body class="bg-gray-50 font-['Inter']">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <div class="w-64 bg-white shadow-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <span class="material-icons text-white text-xl">schedule</span>
                        </div>
                        <div>
                            <h1 class="font-bold text-gray-900">TimeTrack Pro</h1>
                            <p class="text-xs text-gray-500">Admin Panel</p>
                        </div>
                    </div>
                    
                    <nav class="space-y-2">
                        <?php foreach ($nav_items as $key => $item): ?>
                            <a href="<?php echo $item['url']; ?>" class="flex items-center gap-3 px-3 py-2 <?php echo $currentPage === $key ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'; ?> rounded-lg transition-colors">
                                <span class="material-icons text-xl"><?php echo $item['icon']; ?></span>
                                <span><?php echo $item['label']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                
                <!-- User Info -->
                <div class="absolute bottom-0 left-0 right-0 w-64 p-4 border-t bg-white">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 text-sm font-bold"><?php echo strtoupper(substr($current_user, 0, 1)); ?></span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($current_user); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $current_role; ?></p>
                        </div>
                        <a href="../logout.php" class="text-gray-400 hover:text-gray-600">
                            <span class="material-icons text-sm">logout</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col">
                <!-- Header -->
                <header class="bg-white border-b px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($title); ?></h2>
                        </div>
                        <div class="flex items-center gap-4">
                            <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <span class="material-icons">notifications</span>
                            </button>
                            <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <span class="material-icons">help_outline</span>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-6">
                    <?php echo $content; ?>
                </main>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>