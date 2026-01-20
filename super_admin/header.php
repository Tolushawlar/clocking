<!-- Top Navigation -->
<header class="flex-shrink-0 bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <h2 class="text-xl font-semibold text-gray-900">Super Admin Dashboard</h2>
    </div>
    <div class="flex items-center gap-4">
        <div class="text-right">
            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['super_admin_name']); ?></div>
            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($_SESSION['super_admin_email']); ?></div>
        </div>
        <a href="logout.php" class="p-2 text-red-600 hover:bg-red-50 rounded-full transition-colors">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div>
</header>