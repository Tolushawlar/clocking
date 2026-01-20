<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

$success = '';
$error = '';

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle email change
if (isset($_POST['change_email'])) {
    $new_email = trim($_POST['new_email']);
    $current_password = $_POST['current_password'];

    if (empty($new_email) || empty($current_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($new_email === $user['email']) {
        $error = 'New email is the same as current email';
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Check if email already exists
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows > 0) {
                $error = 'This email is already in use';
            } else {
                // Update email
                $update_stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_email, $user_id);

                if ($update_stmt->execute()) {
                    $success = 'Email changed successfully!';
                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                } else {
                    $error = 'Failed to update email';
                }
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password_pw'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Settings - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#2563eb",
                        "primary-hover": "#1d4ed8",
                        "background": "#f8fafc",
                        "card": "#ffffff",
                        "border-subtle": "#e2e8f0",
                        "text-main": "#0f172a",
                        "text-muted": "#64748b",
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

<body class="bg-background font-display text-text-main antialiased">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        $current_page = 'settings.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-y-auto bg-background">
            <div class="md:hidden flex items-center justify-between p-4 border-b border-border-subtle bg-card">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">schedule</span>
                    <span class="font-bold text-slate-800">TimeTrack Pro</span>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <div class="max-w-4xl mx-auto w-full p-4 md:p-6 lg:p-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-slate-900">Account Settings</h1>
                    <p class="text-text-muted mt-2">Manage your account preferences</p>
                </div>

                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-3">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-3">
                        <span class="material-symbols-outlined">error</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Profile Information (Read-only) -->
                <div class="bg-card rounded-2xl border border-border-subtle shadow-sm mb-6">
                    <div class="px-6 py-4 border-b border-border-subtle">
                        <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined">person</span>
                            <span>Profile Information</span>
                        </h2>
                        <p class="text-sm text-text-muted mt-1">This information is managed by your administrator</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                                <div class="px-4 py-3 bg-slate-50 border border-border-subtle rounded-lg text-slate-900">
                                    <?php echo htmlspecialchars($user['firstname']); ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Last Name</label>
                                <div class="px-4 py-3 bg-slate-50 border border-border-subtle rounded-lg text-slate-900">
                                    <?php echo htmlspecialchars($user['lastname']); ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Barcode</label>
                            <div class="px-4 py-3 bg-slate-50 border border-border-subtle rounded-lg text-slate-900 font-mono">
                                <?php echo htmlspecialchars($user['barcode']); ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                            <div class="px-4 py-3 bg-slate-50 border border-border-subtle rounded-lg">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo $user['category'] === 'staff' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                    <?php echo ucfirst($user['category']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Email -->
                <div class="bg-card rounded-2xl border border-border-subtle shadow-sm mb-6">
                    <div class="px-6 py-4 border-b border-border-subtle">
                        <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined">mail</span>
                            <span>Change Email</span>
                        </h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="change_email" value="1">

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Current Email</label>
                                <div class="px-4 py-3 bg-slate-50 border border-border-subtle rounded-lg text-slate-900">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">New Email</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">mail</span>
                                    <input type="email" name="new_email" required class="w-full pl-10 pr-4 py-3 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Enter new email address">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Current Password</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">lock</span>
                                    <input type="password" name="current_password" required class="w-full pl-10 pr-4 py-3 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Confirm with your password">
                                </div>
                                <p class="mt-1 text-xs text-text-muted">Enter your current password to confirm this change</p>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                    <span class="material-symbols-outlined">save</span>
                                    <span>Update Email</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="bg-card rounded-2xl border border-border-subtle shadow-sm">
                    <div class="px-6 py-4 border-b border-border-subtle">
                        <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined">key</span>
                            <span>Change Password</span>
                        </h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="change_password" value="1">

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Current Password</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">lock</span>
                                    <input type="password" name="current_password_pw" required class="w-full pl-10 pr-4 py-3 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Enter current password">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">New Password</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">key</span>
                                    <input type="password" name="new_password" required minlength="6" class="w-full pl-10 pr-4 py-3 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Enter new password">
                                </div>
                                <p class="mt-1 text-xs text-text-muted">Must be at least 6 characters</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Confirm New Password</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">key</span>
                                    <input type="password" name="confirm_password" required minlength="6" class="w-full pl-10 pr-4 py-3 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Confirm new password">
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                    <span class="material-symbols-outlined">save</span>
                                    <span>Update Password</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (mobileSidebar.classList.contains('-translate-x-full')) {
                mobileSidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                mobileSidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>

</html>