<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['super_admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle business creation
if (isset($_POST['create_business'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO business (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        header('Location: businesses.php?msg=Business created successfully');
        exit;
    } else {
        $error = 'Error creating business. Email may already exist.';
    }
}

// Handle business deletion
if (isset($_GET['delete'])) {
    $business_id = $_GET['delete'];

    // Delete business (cascade will handle related records)
    $stmt = $db->prepare("DELETE FROM business WHERE id = ?");
    $stmt->bind_param("i", $business_id);

    if ($stmt->execute()) {
        header('Location: businesses.php?msg=Business deleted successfully');
        exit;
    }
}

// Get all businesses
$businesses = $db->query("
    SELECT b.*, 
           COUNT(DISTINCT u.id) as user_count,
           COUNT(DISTINCT p.id) as project_count,
           COUNT(DISTINCT t.id) as team_count
    FROM business b
    LEFT JOIN users u ON b.id = u.business_id
    LEFT JOIN projects p ON b.id = p.business_id
    LEFT JOIN teams t ON b.id = t.business_id
    GROUP BY b.id
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Manage Businesses - Super Admin</title>
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
        $current_page = 'businesses.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto px-6 py-8">
                    <?php if (isset($_GET['msg'])): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-8 flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Manage Businesses</h1>
                            <p class="text-gray-600 mt-1">Create and manage business accounts</p>
                        </div>
                        <button onclick="openCreateModal()" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg font-semibold transition-colors">
                            <span class="material-symbols-outlined">add</span>
                            <span>Add Business</span>
                        </button>
                    </div>

                    <!-- Businesses Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($business = $businesses->fetch_assoc()): ?>
                            <div class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-semibold">
                                            <?php echo strtoupper(substr($business['name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($business['name']); ?></h3>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($business['email']); ?></p>
                                        </div>
                                    </div>
                                    <button onclick="deleteBusiness(<?php echo $business['id']; ?>)" class="text-red-600 hover:text-red-700">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Users</span>
                                        <span class="font-semibold text-gray-900"><?php echo $business['user_count']; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Projects</span>
                                        <span class="font-semibold text-gray-900"><?php echo $business['project_count']; ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">Teams</span>
                                        <span class="font-semibold text-gray-900"><?php echo $business['team_count']; ?></span>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <p class="text-xs text-gray-500">Created <?php echo date('M d, Y', strtotime($business['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Business Modal -->
    <div id="createModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Create New Business</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="create_business" value="1">
                    <div>
                        <label class="block text-sm font-medium mb-2">Business Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function deleteBusiness(id) {
            if (confirm('Are you sure you want to delete this business? This will delete all associated data including users, projects, and teams. This action cannot be undone.')) {
                window.location.href = 'businesses.php?delete=' + id;
            }
        }
    </script>
</body>

</html>