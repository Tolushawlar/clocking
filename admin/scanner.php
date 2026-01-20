<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
}

$business_id = $_SESSION['business_id'];

// Get business settings
$stmt = $db->prepare("SELECT * FROM business WHERE id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Scanner - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-white overflow-hidden selection:bg-primary selection:text-white">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        // Include sidebar component
        $current_page = 'scanner.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Top Navigation -->
            <header class="flex-shrink-0 bg-surface-light dark:bg-surface-dark border-b border-border-light dark:border-border-dark px-6 py-3 flex items-center justify-between z-10">
                <div class="flex items-center gap-4 md:hidden">
                    <button class="text-text-secondary">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <span class="text-lg font-bold">TimeTrack Pro</span>
                </div>
                <div class="hidden md:flex flex-1">
                    <h2 class="text-xl font-semibold text-text-main dark:text-white">Staff Clock In/Out</h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-lg font-bold text-text-main dark:text-white" id="current-time"></div>
                        <div class="text-xs text-text-secondary" id="current-date"></div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark flex items-center justify-center">
                <div class="max-w-2xl w-full px-6 text-center">
                    <div class="size-28 rounded-full bg-white dark:bg-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-black/50 flex items-center justify-center border border-slate-100 dark:border-slate-700 mb-8 ring-4 ring-white dark:ring-slate-900 mx-auto">
                        <span class="material-symbols-outlined text-primary" style="font-size: 56px;">barcode_reader</span>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight text-text-main dark:text-white mb-4">Staff Clock In/Out</h1>
                    <p class="text-text-secondary text-lg font-medium mb-8">Scan staff ID to clock in or out</p>

                    <div class="w-full max-w-lg relative group mx-auto">
                        <div class="absolute -inset-1 bg-primary rounded-2xl blur opacity-20 group-focus-within:opacity-60 transition duration-500"></div>
                        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl border-2 border-primary group-focus-within:ring-4 ring-primary/20 transition-all duration-200 overflow-hidden flex flex-col">
                            <div class="flex items-center h-24 px-6 relative">
                                <span class="material-symbols-outlined text-slate-300 dark:text-slate-600 absolute left-6 animate-pulse" style="font-size: 32px;">qr_code_scanner</span>
                                <input id="barcode-scanner" autofocus class="w-full h-full bg-transparent border-none focus:ring-0 text-3xl font-mono text-center placeholder:text-slate-300 dark:placeholder:text-slate-600 text-text-main dark:text-white tracking-widest outline-none font-bold uppercase pl-12" placeholder="Waiting for scan..." type="text" />
                                <div class="absolute right-6 size-3 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)] animate-pulse"></div>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                                <div class="h-full w-full bg-primary origin-left animate-pulse"></div>
                            </div>
                        </div>
                    </div>

                    <div id="scan-result" class="mt-8 hidden">
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-border-light dark:border-border-dark shadow-lg">
                            <div class="flex items-center justify-center gap-3 mb-4">
                                <div class="size-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-2xl">check_circle</span>
                                </div>
                            </div>
                            <h3 class="text-xl font-semibold text-text-main dark:text-white mb-2" id="result-message"></h3>
                            <p class="text-text-secondary" id="result-details"></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
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

        // Barcode scanner
        document.getElementById('barcode-scanner').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const barcode = e.target.value.trim();
                if (barcode) {
                    fetch('../api/check-status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                barcode: barcode
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            const action = data.can_clock_out ? 'clock_out' : 'clock_in';
                            fetch('../api/clock.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        barcode: barcode,
                                        action: action
                                    })
                                })
                                .then(response => response.json())
                                .then(result => {
                                    const resultDiv = document.getElementById('scan-result');
                                    const messageEl = document.getElementById('result-message');
                                    const detailsEl = document.getElementById('result-details');

                                    if (result.success) {
                                        messageEl.textContent = result.message;
                                        detailsEl.textContent = 'Time: ' + new Date().toLocaleTimeString();
                                        resultDiv.classList.remove('hidden');

                                        setTimeout(() => {
                                            resultDiv.classList.add('hidden');
                                        }, 3000);
                                    } else {
                                        alert('Error: ' + result.error);
                                    }
                                    e.target.value = '';
                                });
                        });
                }
            }
        });
    </script>
</body>

</html>