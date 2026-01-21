<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    // For testing, set default session values
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
}

$business_id = $_SESSION['business_id'];

// Handle user update
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $barcode = trim($_POST['barcode']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $category = $_POST['category'];

    $stmt = $db->prepare("UPDATE users SET barcode = ?, firstname = ?, lastname = ?, email = ?, category = ? WHERE id = ? AND business_id = ?");
    $stmt->bind_param("sssssii", $barcode, $firstname, $lastname, $email, $category, $user_id, $business_id);

    if ($stmt->execute()) {
        header('Location: users.php?msg=User updated successfully');
    } else {
        $error = 'Error updating user. Barcode or email may already exist.';
    }
}

// Handle user creation
if (isset($_POST['create_user'])) {
    $barcode = trim($_POST['barcode']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $category = $_POST['category'];

    $stmt = $db->prepare("INSERT INTO users (business_id, barcode, firstname, lastname, email, password, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $business_id, $barcode, $firstname, $lastname, $email, $password, $category);

    if ($stmt->execute()) {
        header('Location: users.php?msg=User created successfully');
    } else {
        $error = 'Error creating user. Barcode or email may already exist.';
    }
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // First, delete related records from reports table
    $stmt = $db->prepare("DELETE FROM reports WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Then delete the user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND business_id = ?");
    $stmt->bind_param("ii", $user_id, $business_id);

    if ($stmt->execute()) {
        header('Location: users.php?msg=User deleted successfully');
        exit();
    } else {
        $error = 'Error deleting user.';
    }
}

// Get users
$stmt = $db->prepare("SELECT * FROM users WHERE business_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Staff Directory - TimeTrack Pro</title>
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
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1e293b",
                        "text-main": "#0d121b",
                        "text-secondary": "#4c669a",
                        "border-light": "#e7ebf3",
                        "border-dark": "#334155",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "full": "9999px"
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

        /* Toast Notifications */
        .toast {
            animation: slideIn 0.3s ease-out;
        }

        .toast.hide {
            animation: slideOut 0.3s ease-in forwards;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Modal */
        .modal {
            animation: fadeIn 0.2s ease-out;
        }

        .modal-content {
            animation: scaleIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-white overflow-hidden selection:bg-primary selection:text-white">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        // Include sidebar component
        $current_page = 'users.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-6xl mx-auto px-6 py-8">
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

                    <!-- Header Section -->
                    <div class="mb-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h1 class="text-text-primary text-3xl font-bold tracking-tight">Staff Directory</h1>
                                <p class="text-text-secondary text-sm mt-1">Manage employees, roles, and access permissions.</p>
                            </div>
                            <button onclick="toggleCreateForm()" class="flex cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-5 bg-primary hover:bg-primary/90 transition-colors text-white gap-2 text-sm font-semibold shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">add</span>
                                <span>Add New Staff</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filters & Toolbar -->
                    <div class="mb-6">
                        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-4 justify-between items-center shadow-sm">
                            <div class="flex flex-1 w-full sm:w-auto gap-3">
                                <div class="relative flex-1 max-w-md">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">search</span>
                                    <input class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 placeholder:text-slate-400" placeholder="Search by name or ID..." type="text" />
                                </div>
                                <div class="relative min-w-[140px] hidden sm:block">
                                    <select class="w-full appearance-none pl-4 pr-10 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 cursor-pointer">
                                        <option value="">All Categories</option>
                                        <option value="staff">Staff</option>
                                        <option value="student">Student</option>
                                    </select>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-outlined text-[20px]">expand_more</span>
                                </div>
                                <div class="relative min-w-[140px] hidden sm:block">
                                    <select class="w-full appearance-none pl-4 pr-10 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 cursor-pointer">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-outlined text-[20px]">expand_more</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                <button class="flex items-center gap-2 px-3 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors border border-transparent hover:border-slate-200 text-sm font-medium">
                                    <span class="material-symbols-outlined text-[18px]">download</span>
                                    <span class="hidden sm:inline">Export</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Create User Form (Hidden by default) -->
                    <div id="create-user-form" class="hidden mb-6">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-slate-900">Create New User</h3>
                                <button onclick="toggleCreateForm()" class="text-slate-400 hover:text-slate-600">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                                    <input type="text" name="firstname" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Last Name</label>
                                    <input type="text" name="lastname" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                    <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                                    <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Barcode</label>
                                    <input type="text" name="barcode" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                                    <select name="category" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="staff">Staff</option>
                                        <option value="student">Student</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" name="create_user" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">
                                        Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Staff Table -->
                    <?php if ($users->num_rows > 0): ?>
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                                <table class="w-full text-left border-collapse min-w-[800px]">
                                    <thead class="sticky top-0 bg-slate-50 z-10">
                                        <tr class="border-b border-slate-200">
                                            <th class="py-3 px-4 w-12">
                                                <input class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" type="checkbox" />
                                            </th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[200px]">Employee</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[120px]">Staff ID</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[100px]">Role</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[100px]">Status</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center min-w-[100px]">Barcode</th>
                                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right min-w-[120px]">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                                <td class="py-4 px-4">
                                                    <input class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" type="checkbox" />
                                                </td>
                                                <td class="py-4 px-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="size-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                                            <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                                                            <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <span class="text-sm font-medium text-slate-500 font-mono"><?php echo htmlspecialchars($user['barcode']); ?></span>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $user['category'] == 'staff' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-orange-50 text-orange-700 border border-orange-100'; ?>">
                                                        <?php echo ucfirst($user['category']); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $user['is_active'] ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                                                        <span class="size-1.5 rounded-full <?php echo $user['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4 text-center">
                                                    <button class="text-slate-400 hover:text-slate-600 transition-colors inline-flex flex-col items-center group/barcode" title="View Barcode">
                                                        <span class="material-symbols-outlined text-[20px]">qr_code_2</span>
                                                        <span class="text-[10px] opacity-0 group-hover/barcode:opacity-100 transition-opacity">View</span>
                                                    </button>
                                                </td>
                                                <td class="py-4 px-4 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <a href="user.php?id=<?php echo $user['id']; ?>" class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="View Details">
                                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                        </a>
                                                        <button onclick="toggleEdit(<?php echo $user['id']; ?>)" class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="Edit Staff">
                                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                                        </button>
                                                        <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname'], ENT_QUOTES); ?>')" class="size-8 flex items-center justify-center rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-600 transition-colors" title="Delete Staff">
                                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <!-- Edit Form Row -->
                                            <tr id="edit-row-<?php echo $user['id']; ?>" class="hidden">
                                                <td colspan="7" class="p-0">
                                                    <div class="bg-slate-50 p-6 border-t border-slate-200">
                                                        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-700 mb-2">Barcode</label>
                                                                <input type="text" name="barcode" value="<?php echo htmlspecialchars($user['barcode']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                                                                <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-700 mb-2">Last Name</label>
                                                                <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                                                                <select name="category" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                                    <option value="staff" <?php echo $user['category'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                                    <option value="student" <?php echo $user['category'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                                </select>
                                                            </div>
                                                            <div class="flex items-end gap-2">
                                                                <button type="submit" name="update_user" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors text-sm">
                                                                    Update
                                                                </button>
                                                                <button type="button" onclick="toggleEdit(<?php echo $user['id']; ?>)" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors text-sm">
                                                                    Cancel
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="flex flex-col items-center justify-center py-16 px-4">
                                <div class="w-32 h-32 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-full flex items-center justify-center mb-6 shadow-lg">
                                    <span class="material-symbols-outlined text-primary dark:text-blue-400" style="font-size: 64px;">person_add</span>
                                </div>
                                <h3 class="text-2xl font-bold text-text-main dark:text-white mb-2">No Staff Members Yet</h3>
                                <p class="text-text-secondary text-center max-w-md mb-8">
                                    Get started by adding your first staff member. Build your team directory to manage attendance, track activities, and streamline your workforce management.
                                </p>
                                <button onclick="toggleCreateForm()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all shadow-lg shadow-blue-200 dark:shadow-none">
                                    <span class="material-symbols-outlined text-lg">add</span>
                                    Add Your First Staff Member
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 modal">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 modal-content">
            <div class="flex items-start gap-4 mb-4">
                <div class="size-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-red-600 text-2xl">warning</span>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Delete User</h3>
                    <p class="text-sm text-slate-600">Are you sure you want to delete <strong id="delete-user-name"></strong>? This action cannot be undone.</p>
                </div>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors text-sm font-medium">
                    Cancel
                </button>
                <form id="delete-form" method="POST" class="inline">
                    <input type="hidden" name="user_id" id="delete-user-id">
                    <button type="submit" name="delete_user" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm font-medium">
                        Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast pointer-events-auto bg-white dark:bg-slate-800 rounded-lg shadow-2xl border-l-4 p-4 flex items-start gap-3 ${type === 'error' ? 'border-red-500' : 'border-green-500'}`;

            const iconBg = type === 'error' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30';
            const iconColor = type === 'error' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
            const icon = type === 'error' ? 'error' : 'check_circle';

            toast.innerHTML = `
                <div class="flex-shrink-0 size-10 rounded-full ${iconBg} flex items-center justify-center">
                    <span class="material-symbols-outlined ${iconColor} text-xl">${icon}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-text-main dark:text-white mb-1">${type === 'error' ? 'Error' : 'Success'}</p>
                    <p class="text-sm text-text-secondary">${message}</p>
                </div>
                <button onclick="this.parentElement.classList.add('hide')" class="flex-shrink-0 text-text-secondary hover:text-text-main transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            `;

            container.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Show toast if there's a message in URL
        <?php if (isset($_GET['msg'])): ?>
            showToast('<?php echo addslashes($_GET['msg']); ?>', 'success');
        <?php endif; ?>

        <?php if (isset($error)): ?>
            showToast('<?php echo addslashes($error); ?>', 'error');
        <?php endif; ?>

        // Delete confirmation
        function confirmDelete(userId, userName) {
            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
        }

        // Close modal on backdrop click
        document.getElementById('delete-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle create form
        function toggleCreateForm() {
            const form = document.getElementById('create-user-form');
            form.classList.toggle('hidden');
        }

        // Toggle edit form
        function toggleEdit(userId) {
            const editRow = document.getElementById('edit-row-' + userId);
            editRow.classList.toggle('hidden');
        }
    </script>
</body>

</html>