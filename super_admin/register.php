<?php
require_once '../lib/constant.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['super_admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Check if a super admin already exists
$check_stmt = $db->query("SELECT COUNT(*) as count FROM super_admins");
$count = $check_stmt->fetch_assoc()['count'];

// If a super admin already exists, prevent registration
if ($count > 0) {
    $blocked = true;
} else {
    $blocked = false;
}

$error = '';
$success = '';

// Handle registration
if (!$blocked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Double-check no super admin exists (race condition protection)
        $recheck_stmt = $db->query("SELECT COUNT(*) as count FROM super_admins");
        $recount = $recheck_stmt->fetch_assoc()['count'];
        
        if ($recount > 0) {
            $error = 'A super admin already exists in the system';
            $blocked = true;
        } else {
            // Hash password and create super admin
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO super_admins (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Super Admin account created successfully! You can now login.';
                // Wait 2 seconds then redirect to login
                header("refresh:2;url=login.php");
            } else {
                if ($db->errno == 1062) { // Duplicate entry
                    $error = 'This email is already registered';
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Super Admin Registration - TimeTrack Pro</title>
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
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 font-display min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-full mb-4">
                    <span class="material-symbols-outlined text-white text-3xl">admin_panel_settings</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Super Admin Registration</h1>
                <p class="text-gray-500 text-sm mt-2">Initial Setup - TimeTrack Pro</p>
            </div>

            <?php if ($blocked): ?>
                <div class="mb-6 p-6 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="material-symbols-outlined text-amber-600 text-2xl">lock</span>
                        <div>
                            <h3 class="font-semibold text-amber-900 mb-1">Registration Disabled</h3>
                            <p class="text-sm text-amber-800">A Super Admin account already exists in the system. Only one Super Admin is allowed.</p>
                        </div>
                    </div>
                    <a href="login.php" class="flex items-center justify-center gap-2 w-full bg-primary hover:bg-primary-hover text-white font-semibold py-3 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">login</span>
                        <span>Go to Login</span>
                    </a>
                </div>
            <?php else: ?>

                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3">
                        <span class="material-symbols-outlined">check_circle</span>
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($success); ?></p>
                            <p class="text-xs mt-1">Redirecting to login...</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <form method="POST" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">person</span>
                                <input type="text" name="name" required class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">mail</span>
                                <input type="email" name="email" required class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="admin@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">lock</span>
                                <input type="password" name="password" required minlength="6" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="••••••••">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Must be at least 6 characters</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 material-symbols-outlined text-[20px]">lock</span>
                                <input type="password" name="confirm_password" required minlength="6" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="••••••••">
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start gap-2">
                                <span class="material-symbols-outlined text-blue-600 text-xl">info</span>
                                <p class="text-xs text-blue-800">This is a one-time setup. Only one Super Admin account can be created. Choose your credentials carefully.</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-primary-hover text-white font-semibold py-3 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">person_add</span>
                            <span>Create Super Admin Account</span>
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <a href="login.php" class="text-sm text-primary hover:text-primary-hover font-medium">
                            Already have an account? Sign in
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="mt-6 text-center text-xs text-gray-600 bg-white/50 rounded-lg p-3">
            <p class="font-medium">⚠️ Security Notice</p>
            <p class="mt-1">This page is for initial setup only. After creating the Super Admin account, registration will be permanently disabled.</p>
        </div>
    </div>
</body>

</html>
