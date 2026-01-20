<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['super_admin_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM super_admins WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['super_admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if (password_verify($current_password, $admin['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $db->prepare("UPDATE super_admins SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['super_admin_id']);

            if ($update_stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to update password';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Get admin details
$admin_stmt = $db->prepare("SELECT * FROM super_admins WHERE id = ?");
$admin_stmt->bind_param("i", $_SESSION['super_admin_id']);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$admin = $admin_result->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Settings - Super Admin</title>
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
        $current_page = 'settings.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-4xl mx-auto px-6 py-8">
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
                        <p class="text-gray-600 mt-1">Manage your account settings and preferences</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-3">
                            <span class="material-symbols-outlined">error</span>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Account Information -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <span class="material-symbols-outlined">account_circle</span>
                                <span>Account Information</span>
                            </h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900">
                                    <?php echo htmlspecialchars($admin['name']); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900">
                                    <?php echo htmlspecialchars($admin['email']); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Login</label>
                                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900">
                                    <?php echo $admin['last_login'] ? date('M d, Y h:i A', strtotime($admin['last_login'])) : 'Never'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <span class="material-symbols-outlined">lock</span>
                                <span>Change Password</span>
                            </h2>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="space-y-5">
                                <input type="hidden" name="change_password" value="1">

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">lock</span>
                                        <input type="password" name="current_password" required class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Enter current password">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">key</span>
                                        <input type="password" name="new_password" required minlength="6" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Enter new password">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Must be at least 6 characters</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">key</span>
                                        <input type="password" name="confirm_password" required minlength="6" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Confirm new password">
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                        <span class="material-symbols-outlined">save</span>
                                        <span>Update Password</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>