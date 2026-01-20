<!-- Top Navigation -->
<header class="flex-shrink-0 bg-surface-light dark:bg-surface-dark border-b border-border-light dark:border-border-dark px-6 py-3 flex items-center justify-between z-10">
    <div class="flex items-center gap-4 md:hidden">
        <button class="text-text-secondary">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <span class="text-lg font-bold">TimeTrack Pro</span>
    </div>
    <div class="flex items-center gap-4 ml-auto">
        <div class="text-right">
            <div class="text-lg font-bold text-text-main dark:text-white" id="current-time"></div>
            <div class="text-xs text-text-secondary" id="current-date"></div>
        </div>
        <a href="../logout.php" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div>
</header>