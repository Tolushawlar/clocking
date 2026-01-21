<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['super_admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['submission_id'];
    $status = $db->real_escape_string($_POST['status']);
    $db->query("UPDATE contact_submissions SET status = '$status' WHERE id = $id");
    header('Location: contact_submissions.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM contact_submissions WHERE id = $id");
    header('Location: contact_submissions.php');
    exit;
}

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';

// Build query
$where_clauses = [];
if ($status_filter !== 'all') {
    $where_clauses[] = "status = '$status_filter'";
}
if ($search) {
    $where_clauses[] = "(first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' OR company LIKE '%$search%' OR message LIKE '%$search%')";
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get statistics
$total_submissions = $db->query("SELECT COUNT(*) as count FROM contact_submissions")->fetch_assoc()['count'];
$new_submissions = $db->query("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'new'")->fetch_assoc()['count'];
$read_submissions = $db->query("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'read'")->fetch_assoc()['count'];
$replied_submissions = $db->query("SELECT COUNT(*) as count FROM contact_submissions WHERE status = 'replied'")->fetch_assoc()['count'];

// Get submissions
$submissions_query = "SELECT * FROM contact_submissions $where_sql ORDER BY created_at DESC";
$submissions = $db->query($submissions_query);
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Contact Submissions - Super Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3c83f6",
                        "primary-hover": "#2563eb",
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
        $current_page = 'contact_submissions.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto px-6 py-8">
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-gray-900">Contact Submissions</h1>
                        <p class="text-gray-600 mt-1">View and manage contact form submissions</p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <a href="?status=all" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow <?php echo $status_filter === 'all' ? 'ring-2 ring-primary' : ''; ?>">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Total Submissions</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $total_submissions; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-blue-600 text-2xl">mail</span>
                                </div>
                            </div>
                        </a>

                        <a href="?status=new" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow <?php echo $status_filter === 'new' ? 'ring-2 ring-primary' : ''; ?>">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">New</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $new_submissions; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-orange-600 text-2xl">mark_email_unread</span>
                                </div>
                            </div>
                        </a>

                        <a href="?status=read" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow <?php echo $status_filter === 'read' ? 'ring-2 ring-primary' : ''; ?>">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Read</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $read_submissions; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-blue-600 text-2xl">drafts</span>
                                </div>
                            </div>
                        </a>

                        <a href="?status=replied" class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-lg transition-shadow <?php echo $status_filter === 'replied' ? 'ring-2 ring-primary' : ''; ?>">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Replied</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $replied_submissions; ?></p>
                                </div>
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600 text-2xl">mark_email_read</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Search Bar -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
                        <form method="GET" class="flex gap-4">
                            <div class="flex-1">
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, company, or message..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none" />
                                </div>
                            </div>
                            <?php if ($status_filter !== 'all'): ?>
                                <input type="hidden" name="status" value="<?php echo $status_filter; ?>" />
                            <?php endif; ?>
                            <button type="submit" class="px-6 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg font-medium transition-colors">
                                Search
                            </button>
                            <?php if ($search): ?>
                                <a href="?<?php echo $status_filter !== 'all' ? 'status=' . $status_filter : ''; ?>" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition-colors">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Submissions List -->
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">
                                <?php
                                if ($status_filter === 'all') {
                                    echo 'All Submissions';
                                } else {
                                    echo ucfirst($status_filter) . ' Submissions';
                                }
                                ?>
                            </h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if ($submissions->num_rows > 0): ?>
                                <?php while ($submission = $submissions->fetch_assoc()): ?>
                                    <div class="p-6 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                                        <?php echo strtoupper(substr($submission['first_name'], 0, 1) . substr($submission['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-semibold text-gray-900">
                                                            <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?>
                                                        </h3>
                                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($submission['email']); ?></p>
                                                    </div>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                                        <?php
                                                        if ($submission['status'] === 'new') echo 'bg-orange-100 text-orange-800';
                                                        elseif ($submission['status'] === 'read') echo 'bg-blue-100 text-blue-800';
                                                        else echo 'bg-green-100 text-green-800';
                                                        ?>">
                                                        <?php echo ucfirst($submission['status']); ?>
                                                    </span>
                                                </div>

                                                <div class="grid md:grid-cols-2 gap-4 mb-3 text-sm">
                                                    <?php if ($submission['phone']): ?>
                                                        <div class="flex items-center gap-2 text-gray-600">
                                                            <span class="material-symbols-outlined text-base">call</span>
                                                            <span><?php echo htmlspecialchars($submission['phone']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($submission['company']): ?>
                                                        <div class="flex items-center gap-2 text-gray-600">
                                                            <span class="material-symbols-outlined text-base">business</span>
                                                            <span><?php echo htmlspecialchars($submission['company']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex items-center gap-2 text-gray-600">
                                                        <span class="material-symbols-outlined text-base">label</span>
                                                        <span><?php echo ucfirst(str_replace('_', ' ', $submission['subject'])); ?></span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-gray-600">
                                                        <span class="material-symbols-outlined text-base">schedule</span>
                                                        <span><?php echo date('M d, Y h:i A', strtotime($submission['created_at'])); ?></span>
                                                    </div>
                                                </div>

                                                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                                    <p class="text-sm font-semibold text-gray-700 mb-2">Message:</p>
                                                    <p class="text-sm text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($submission['message'])); ?></p>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>" />
                                                        <select name="status" onchange="this.form.submit()" class="text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                                                            <option value="new" <?php echo $submission['status'] === 'new' ? 'selected' : ''; ?>>Mark as New</option>
                                                            <option value="read" <?php echo $submission['status'] === 'read' ? 'selected' : ''; ?>>Mark as Read</option>
                                                            <option value="replied" <?php echo $submission['status'] === 'replied' ? 'selected' : ''; ?>>Mark as Replied</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1" />
                                                    </form>

                                                    <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>?subject=Re: <?php echo urlencode($submission['subject']); ?>" class="inline-flex items-center gap-1 px-4 py-1.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-lg transition-colors">
                                                        <span class="material-symbols-outlined text-base">reply</span>
                                                        Reply
                                                    </a>

                                                    <a href="?delete=<?php echo $submission['id']; ?>" onclick="return confirm('Are you sure you want to delete this submission?')" class="inline-flex items-center gap-1 px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                        <span class="material-symbols-outlined text-base">delete</span>
                                                        Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-12 text-center">
                                    <span class="material-symbols-outlined text-gray-300 text-6xl mb-4">inbox</span>
                                    <p class="text-gray-500 text-lg">No submissions found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>