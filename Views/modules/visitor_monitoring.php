<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'admin_visitors';
$pass = '123';
$db = 'admin_visitors';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Handle time-out action
if (isset($_GET['action']) && $_GET['action'] == 'timeout' && isset($_GET['id'])) {
    $visitorId = (int)$_GET['id'];
    
    $stmt = $conn->prepare("UPDATE guest_submissions SET time_out = NOW(), status = 'Time Out', actual_duration = TIMESTAMPDIFF(MINUTE, time_in, NOW()) WHERE id = ? AND status = 'Time In'");
    $stmt->bind_param("i", $visitorId);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get date range for filtering
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Fetch visitor statistics
$stats = [];
$stats['total_visitors'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(time_in) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
$stats['active_visitors'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE status = 'Time In'")->fetch_assoc()['count'];
$stats['time_in_today'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(time_in) = CURDATE()")->fetch_assoc()['count'];
$stats['time_out_today'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(time_out) = CURDATE()")->fetch_assoc()['count'];

// Fetch visitor logs with filtering
$filter = $_GET['filter'] ?? 'all';
$visitor_type_filter = $_GET['visitor_type'] ?? 'all';

$whereClause = "DATE(gs.time_in) BETWEEN '$date_from' AND '$date_to'";
if ($filter === 'active') {
    $whereClause .= " AND gs.status = 'Time In'";
} elseif ($filter === 'completed') {
    $whereClause .= " AND gs.status = 'Time Out'";
} elseif ($filter === 'today') {
    $whereClause .= " AND DATE(gs.time_in) = CURDATE()";
}

if ($visitor_type_filter !== 'all') {
    $whereClause .= " AND gs.visitor_type_id = " . (int)$visitor_type_filter;
}

$visitorLogsResult = $conn->query("
    SELECT gs.*, vt.type_name, vt.color_code 
    FROM guest_submissions gs 
    LEFT JOIN visitor_types vt ON gs.visitor_type_id = vt.id 
    WHERE $whereClause 
    ORDER BY gs.time_in DESC
");

// Fetch visitor types for filter
$visitorTypesResult = $conn->query("SELECT * FROM visitor_types WHERE is_active = 1 ORDER BY type_name");

// Generate report data
$reportData = [];
if (isset($_GET['generate_report'])) {
    $reportType = $_GET['report_type'] ?? 'daily';
    
    if ($reportType === 'daily') {
        $reportQuery = $conn->query("
            SELECT 
                DATE(time_in) as date,
                vt.type_name,
                vt.color_code,
                COUNT(*) as total_visitors,
                COUNT(CASE WHEN status = 'Time In' THEN 1 END) as active_visitors,
                AVG(actual_duration) as avg_duration
            FROM guest_submissions gs
            LEFT JOIN visitor_types vt ON gs.visitor_type_id = vt.id
            WHERE DATE(time_in) BETWEEN '$date_from' AND '$date_to'
            GROUP BY DATE(time_in), vt.type_name, vt.color_code
            ORDER BY date DESC
        ");
        
        while ($row = $reportQuery->fetch_assoc()) {
            $reportData[] = $row;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Monitoring & Reports</title>
    <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Visitor Monitoring & Reports</h1>
                        <p class="text-gray-600 mt-1">Real-time visitor tracking and analytics</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button id="refreshBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                            🔄 Refresh Data
                        </button>
                        <a href="../user/Visitors.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300">
                            ➕ New Time-In
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Statistics Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Visitors</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['total_visitors'] ?></p>
                            <p class="text-xs text-gray-500"><?= $date_from ?> to <?= $date_to ?></p>
                        </div>
                        <div class="text-blue-500 text-3xl">👥</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Active Visitors</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['active_visitors'] ?></p>
                            <p class="text-xs text-gray-500">Currently on-site</p>
                        </div>
                        <div class="text-green-500 text-3xl">✅</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Time-In Today</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['time_in_today'] ?></p>
                            <p class="text-xs text-gray-500"><?= date('M d, Y') ?></p>
                        </div>
                        <div class="text-yellow-500 text-3xl">📥</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Time-Out Today</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['time_out_today'] ?></p>
                            <p class="text-xs text-gray-500"><?= date('M d, Y') ?></p>
                        </div>
                        <div class="text-red-500 text-3xl">📤</div>
                    </div>
                </div>
            </div>

            <!-- Filters and Controls -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                        <input type="date" name="date_from" value="<?= $date_from ?>" class="border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                        <input type="date" name="date_to" value="<?= $date_to ?>" class="border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                        <select name="filter" class="border border-gray-300 rounded-md px-3 py-2">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Visitors</option>
                            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="completed" <?= $filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today Only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Visitor Type</label>
                        <select name="visitor_type" class="border border-gray-300 rounded-md px-3 py-2">
                            <option value="all">All Types</option>
                            <?php if ($visitorTypesResult && $visitorTypesResult->num_rows > 0): ?>
                                <?php while ($type = $visitorTypesResult->fetch_assoc()): ?>
                                    <option value="<?= $type['id'] ?>" <?= $visitor_type_filter == $type['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['type_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                            🔍 Apply Filters
                        </button>
                        <button type="button" onclick="generateReport()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300">
                            📊 Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Visitor Logs Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Visitor Logs</h2>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-xs text-gray-600">Time In</span>
                            <div class="w-3 h-3 bg-gray-400 rounded-full ml-2"></div>
                            <span class="text-xs text-gray-600">Time Out</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Visitor Info</th>
                                <th class="px-6 py-3">Type & Purpose</th>
                                <th class="px-6 py-3">Time In</th>
                                <th class="px-6 py-3">Duration</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Host</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($visitorLogsResult && $visitorLogsResult->num_rows > 0): ?>
                                <?php while ($row = $visitorLogsResult->fetch_assoc()): ?>
                                    <?php
                                    $duration = $row['actual_duration'] ?: 
                                        ($row['time_in'] ? floor((time() - strtotime($row['time_in'])) / 60) : 0);
                                    $durationText = $duration > 0 ? sprintf('%dh %dm', floor($duration / 60), $duration % 60) : 'N/A';
                                    ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900"><?= sprintf('%03d', $row['id']) ?></td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($row['email']) ?></div>
                                                <?php if ($row['company']): ?>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($row['company']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                                      style="background-color: <?= $row['color_code'] ?>20; color: <?= $row['color_code'] ?>">
                                                    <?= htmlspecialchars($row['type_name']) ?>
                                                </span>
                                                <div class="text-sm text-gray-500 mt-1 max-w-xs truncate">
                                                    <?= htmlspecialchars($row['purpose'] ?: 'No purpose specified') ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-green-600 font-medium">
                                            <?= $row['time_in'] ? date('Y-m-d H:i', strtotime($row['time_in'])) : 'N/A' ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm <?= $row['status'] === 'Time In' ? 'text-green-600 font-medium' : 'text-gray-600' ?>">
                                                <?= $durationText ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($row['status'] === 'Time In'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                                                    Time In
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <div class="w-2 h-2 bg-gray-500 rounded-full mr-1"></div>
                                                    Time Out
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($row['host_employee'] ?: 'No host assigned') ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($row['status'] === 'Time In'): ?>
                                                <a href="?action=timeout&id=<?= $row['id'] ?>" 
                                                   class="font-medium text-red-600 hover:text-red-800 hover:underline">
                                                    Time Out
                                                </a>
                                            <?php else: ?>
                                                <span class="font-medium text-gray-400">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <div class="text-4xl mb-2">📋</div>
                                            <div>No visitor logs found.</div>
                                            <div class="text-sm text-gray-400 mt-1">Try adjusting your filters or check back later.</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report Generation Section -->
            <?php if (!empty($reportData)): ?>
            <div class="bg-white p-6 rounded-lg shadow mt-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Generated Report</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Visitor Type</th>
                                <th class="px-6 py-3">Total Visitors</th>
                                <th class="px-6 py-3">Active Visitors</th>
                                <th class="px-6 py-3">Avg Duration (min)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $report): ?>
                                <tr class="bg-white border-b">
                                    <td class="px-6 py-4"><?= $report['date'] ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                              style="background-color: <?= $report['color_code'] ?>20; color: <?= $report['color_code'] ?>">
                                            <?= htmlspecialchars($report['type_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?= $report['total_visitors'] ?></td>
                                    <td class="px-6 py-4"><?= $report['active_visitors'] ?></td>
                                    <td class="px-6 py-4"><?= round($report['avg_duration'], 1) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-refresh functionality
        setInterval(() => {
            if (!document.hidden) {
                window.location.reload();
            }
        }, 30000);

        // Refresh button functionality
        document.getElementById('refreshBtn').addEventListener('click', function() {
            this.innerHTML = '🔄 Refreshing...';
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });

        function generateReport() {
            const form = document.querySelector('form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'generate_report';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
    </script>
</body>
</html>