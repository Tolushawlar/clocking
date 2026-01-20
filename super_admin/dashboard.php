<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['super_admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get statistics
$total_businesses_stmt = $db->query("SELECT COUNT(*) as count FROM business");
$total_businesses = $total_businesses_stmt->fetch_assoc()['count'];

$total_users_stmt = $db->query("SELECT COUNT(*) as count FROM users");
$total_users = $total_users_stmt->fetch_assoc()['count'];

$total_projects_stmt = $db->query("SELECT COUNT(*) as count FROM projects");
$total_projects = $total_projects_stmt->fetch_assoc()['count'];

$total_teams_stmt = $db->query("SELECT COUNT(*) as count FROM teams");
$total_teams = $total_teams_stmt->fetch_assoc()['count'];

// Get recent businesses
$recent_businesses = $db->query("SELECT * FROM business ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Super Admin Dashboard - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "primary-hover": "#1d4ed8",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-gray-50 font-display overflow-hidden">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        $current_page = 'dashboard.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto px-6 py-8">
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">System Overview</h1>
                        <p class="text-gray-600 mt-1">Monitor and manage all businesses</p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total Businesses</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $total_businesses; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-blue-600 text-2xl">business</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total Users</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $total_users; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600 text-2xl">people</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total Projects</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $total_projects; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-purple-600 text-2xl">work</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total Teams</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $total_teams; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-orange-600 text-2xl">groups</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Businesses -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Businesses</h2>
                            <a href="businesses.php" class="text-primary hover:text-primary-hover text-sm font-medium">View All →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Business Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php while ($business = $recent_businesses->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-semibold text-sm">
                                                        <?php echo strtoupper(substr($business['name'], 0, 2)); ?>
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($business['name']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($business['email']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($business['created_at'])); ?></td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="businesses.php?view=<?php echo $business['id']; ?>" class="text-primary hover:text-primary-hover text-sm font-medium">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>