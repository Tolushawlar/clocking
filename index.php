<?php
require_once 'lib/constant.php';
session_start();

if (isset($_SESSION['business_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
    exit;
}

$error = '';

if ($_POST) {
    $business_code = trim($_POST['business_code'] ?? '');
    $staff_id = trim($_POST['staff_id']);
    $password = $_POST['password'];
    
    // Try business login first
    $stmt = $db->prepare("SELECT id, name, password FROM business WHERE email = ?");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['business_id'] = $row['id'];
            $_SESSION['business_name'] = $row['name'];
            header('Location: admin/dashboard.php');
            exit;
        }
    }
    
    // Try user login
    $stmt = $db->prepare("SELECT u.id, u.firstname, u.lastname, u.password, u.business_id, u.category, u.can_clock_others FROM users u WHERE u.email = ? AND u.is_active = 1");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['firstname'] . ' ' . $row['lastname'];
            $_SESSION['business_id'] = $row['business_id'];
            $_SESSION['category'] = $row['category'];
            $_SESSION['can_clock_others'] = $row['can_clock_others'] ?? 0;
            header('Location: user/dashboard.php');
            exit;
        }
    }
    
    $error = 'Invalid email or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TimeTrack Pro - Login</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#3b82f6",
                        "primary-hover": "#2563eb",
                        "background-light": "#f8fafc",
                        "card-bg": "#ffffff",
                        "text-main": "#0f172a",
                        "text-muted": "#64748b",
                        "input-border": "#e2e8f0",
                        "input-focus-ring": "rgba(59, 130, 246, 0.4)",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "2xl": "1rem", "full": "9999px"},
                    boxShadow: {
                        'glow': '0 4px 20px rgba(59, 130, 246, 0.3)',
                        'soft': '0 10px 40px -10px rgba(0,0,0,0.05)',
                    }
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-background-light min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-[10%] -left-[10%] w-[600px] h-[600px] bg-blue-100/50 rounded-full blur-[100px] opacity-60"></div>
        <div class="absolute top-[20%] right-[5%] w-[400px] h-[400px] bg-indigo-100/50 rounded-full blur-[80px] opacity-50"></div>
        <div class="absolute -bottom-[10%] left-[20%] w-[500px] h-[500px] bg-sky-100/50 rounded-full blur-[100px] opacity-60"></div>
    </div>
    <div class="w-full max-w-[460px] mx-auto flex flex-col relative z-10">
        <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-full flex justify-center opacity-0 animate-[fadeIn_0.5s_ease-out_forwards]" style="animation-delay: 0.1s; opacity: 1;">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200 shadow-sm">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wide">System Online v2.4</span>
            </div>
        </div>
        <div class="bg-card-bg rounded-2xl shadow-soft border border-white/50 overflow-hidden flex flex-col w-full relative">
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-400 via-primary to-indigo-500"></div>
            <div class="px-8 py-10 flex flex-col items-center">
                <div class="mb-6 relative group">
                    <div class="h-20 w-20 rounded-2xl bg-white p-1 shadow-lg border border-slate-100 flex items-center justify-center overflow-hidden">
                        <div class="w-full h-full bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center relative overflow-hidden">
                            <div class="absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuD1AppNKEJJxhgl8upvb_2BxeZuXrJrscVWDDSbFEeNA1ZcIN4M8fgGshckdTWGLrDSHlCfKKO_7tY8dAy4HlfvwRLP1fhrD9x1O0pABmNlMeArf5rYcNQof_IogzDSaQcxmZkUxCAkx5fJaxfuQrClP1JFk89OIi6iyPTVi6WlzuNfOuJD7-BlmBxq0AeuOwRNKzrCYc71Xqx9Jt43aF3tojZpZJTJIazLgVZNs3y387nY1qZ1BLI4Od5Jpr-cIEO6gcgq-LeROOg');"></div>
                            <span class="material-symbols-outlined text-4xl text-white relative z-10 drop-shadow-md">schedule</span>
                        </div>
                    </div>
                </div>
                <div class="text-center mb-8 w-full">
                    <h2 class="text-text-main tracking-tight text-2xl font-bold mb-2">TimeTrack Pro</h2>
                    <p class="text-text-muted text-sm font-medium">Enter your credentials to clock in.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message w-full"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['msg'])): ?>
                    <div class="success-message w-full"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
                
                <form method="POST" class="w-full flex flex-col gap-5">
                        <!-- <div class="flex flex-col gap-1.5">
                            <label class="text-slate-700 text-sm font-semibold ml-1" for="business_code">
                                Business Code <span class="text-slate-400 font-normal">(Optional)</span>
                            </label>
                            <div class="relative flex items-center group">
                                <span class="absolute left-4 text-slate-400 group-focus-within:text-primary transition-colors material-symbols-outlined text-[20px]">domain</span>
                                <input class="w-full h-11 pl-11 pr-4 rounded-lg bg-white border border-input-border text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-input-focus-ring focus:border-primary transition-all text-sm font-medium" id="business_code" name="business_code" placeholder="e.g. BIZ-123" type="text" value="<?php echo htmlspecialchars($_POST['business_code'] ?? ''); ?>"/>
                            </div>
                        </div> -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-slate-700 text-sm font-semibold ml-1" for="staff_id">Email or Staff ID</label>
                        <div class="relative flex items-center group/input">
                            <span class="absolute left-4 text-slate-400 group-focus-within/input:text-primary transition-colors material-symbols-outlined text-[20px]">badge</span>
                            <input autofocus="" class="w-full h-11 pl-11 pr-12 rounded-lg bg-white border border-input-border text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-input-focus-ring focus:border-primary transition-all text-sm font-medium" id="staff_id" name="staff_id" placeholder="Scan badge or enter email" type="text" value="<?php echo htmlspecialchars($_POST['staff_id'] ?? ''); ?>" required/>
                            <span class="absolute right-4 text-slate-400/70 material-symbols-outlined text-[20px]" title="Scanner Ready">barcode_reader</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center ml-1">
                            <label class="text-slate-700 text-sm font-semibold" for="password">Password</label>
                            <a class="text-primary hover:text-primary-hover text-xs font-semibold transition-colors" href="#">Forgot Password?</a>
                        </div>
                        <div class="relative flex items-center group">
                            <span class="absolute left-4 text-slate-400 group-focus-within:text-primary transition-colors material-symbols-outlined text-[20px]">lock</span>
                            <input class="w-full h-11 pl-11 pr-11 rounded-lg bg-white border border-input-border text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-input-focus-ring focus:border-primary transition-all text-sm font-medium" id="password" name="password" placeholder="••••••••" type="password" required/>
                            <button class="absolute right-3 p-1 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors flex items-center justify-center" type="button" onclick="togglePassword()">
                                <span class="material-symbols-outlined text-[20px]" id="password-toggle-icon">visibility</span>
                            </button>
                        </div>
                    </div>
                    <button class="mt-4 w-full h-12 bg-primary hover:bg-primary-hover text-white font-bold rounded-lg shadow-glow hover:shadow-lg transition-all duration-200 transform active:scale-[0.98] flex items-center justify-center gap-2" type="submit">
                        <span class="material-symbols-outlined text-[20px]">login</span>
                        Sign In
                    </button>
                </form>
                <div class="mt-8 pt-6 border-t border-slate-100 w-full text-center">
                    <p class="text-sm text-text-muted">
                        Don't have an account? 
                        <a class="text-primary hover:text-primary-hover font-semibold transition-colors" href="register.php">Register Business</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-8 text-center px-4">
            <div class="flex items-center justify-center gap-4 text-xs text-slate-400 font-medium">
                <a class="hover:text-slate-600 transition-colors" href="#">Privacy Policy</a>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <a class="hover:text-slate-600 transition-colors" href="#">Terms of Service</a>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <a class="hover:text-slate-600 transition-colors" href="#">Help</a>
            </div>
            <p class="mt-3 text-[10px] text-slate-300">© 2024 TimeTrack Pro Inc.</p>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility';
            }
        }
    </script>
</body>
</html>