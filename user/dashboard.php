<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

// Database connection
require_once '../backend/sql/db.php';

// Get user information
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'User';

// Get statistics (you can customize these queries based on your database structure)
$stats = [
    'my_contracts' => 0,
    'visitor_logs' => 0,
    'recent_activity' => 0,
    'department' => 'HR1'
];

// Try to get actual statistics from database
try {
    // My Contracts count
    $contracts_result = $conn->query("SELECT COUNT(*) as count FROM contracts WHERE user_id = $user_id");
    if ($contracts_result) {
        $contracts_data = $contracts_result->fetch_assoc();
        $stats['my_contracts'] = $contracts_data['count'];
    }
    
    // Visitor Logs count
    $visitors_result = $conn->query("SELECT COUNT(*) as count FROM visitor_logs WHERE user_id = $user_id");
    if ($visitors_result) {
        $visitors_data = $visitors_result->fetch_assoc();
        $stats['visitor_logs'] = $visitors_data['count'];
    }
    
    // Recent Activity count (last 7 days)
    $activity_result = $conn->query("SELECT COUNT(*) as count FROM user_activity WHERE user_id = $user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($activity_result) {
        $activity_data = $activity_result->fetch_assoc();
        $stats['recent_activity'] = $activity_data['count'];
    }
    
    // Get user department
    $dept_result = $conn->query("SELECT department FROM users WHERE id = $user_id");
    if ($dept_result && $dept_result->num_rows > 0) {
        $dept_data = $dept_result->fetch_assoc();
        $stats['department'] = $dept_data['department'] ?? 'HR1';
    }
} catch (Exception $e) {
    // Use default values if database queries fail
}

// Get current date and time
$current_date = date('l, F j, Y');
$current_time = date('H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/image/logo2.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="shadow-lg h-screen fixed top-0 left-0">
        <?php include '../Components/sidebar/sidebar_user.php'; ?>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">User Dashboard</h1>
                <p class="text-sm text-gray-600">Welcome back, <?= htmlspecialchars($user_name) ?>!</p>
            </div>
            <?php include '../profile.php'; ?>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            
            <!-- Welcome Message -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Welcome to ATIERA Management System</h2>
                        <p class="text-blue-100">Manage your facilities, reservations, and activities all in one place.</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-200">Current Date</div>
                        <div class="text-lg font-semibold"><?= $current_date ?></div>
                        <div class="text-sm text-blue-200">Current Time</div>
                        <div class="text-lg font-semibold" id="currentTime"><?= $current_time ?></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- My Contracts -->
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <i data-lucide="file-text" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">My Contracts</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['my_contracts'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Visitor Logs -->
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <i data-lucide="users" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Visitor Logs</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['visitor_logs'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 rounded-lg">
                            <i data-lucide="activity" class="w-6 h-6 text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Recent Activity</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['recent_activity'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Department -->
                <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <i data-lucide="building-2" class="w-6 h-6 text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Department</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($stats['department']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="../Views/modules/facility.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i data-lucide="building" class="w-5 h-5 text-blue-600 mr-3"></i>
                        <span class="text-sm font-medium">Facility List</span>
                    </a>
                    <a href="../Views/modules/reservation_module.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i data-lucide="calendar" class="w-5 h-5 text-green-600 mr-3"></i>
                        <span class="text-sm font-medium">Make Reservation</span>
                    </a>
                    <a href="../user/visitors.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i data-lucide="users" class="w-5 h-5 text-purple-600 mr-3"></i>
                        <span class="text-sm font-medium">Visitor Logs</span>
                    </a>
                    <a href="../Views/modules/maintenance.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <i data-lucide="wrench" class="w-5 h-5 text-red-600 mr-3"></i>
                        <span class="text-sm font-medium">Report Issue</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Reservations -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Reservations</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i data-lucide="calendar" class="w-4 h-4 text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium">Conference Room A</p>
                                    <p class="text-xs text-gray-500">Today, 2:00 PM - 4:00 PM</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Approved</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i data-lucide="calendar" class="w-4 h-4 text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium">Training Room B</p>
                                    <p class="text-xs text-gray-500">Tomorrow, 10:00 AM - 12:00 PM</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Pending</span>
                        </div>
                    </div>
                    <a href="../Views/modules/reservation_module.php" class="block text-center text-blue-600 text-sm mt-4 hover:underline">
                        View All Reservations
                    </a>
                </div>

                <!-- System Notifications -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">System Notifications</h3>
                    <div class="space-y-3">
                        <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                            <i data-lucide="info" class="w-4 h-4 text-blue-600 mr-3 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-blue-800">System Update</p>
                                <p class="text-xs text-blue-600">New features have been added to the facility management system.</p>
                            </div>
                        </div>
                        <div class="flex items-start p-3 bg-green-50 rounded-lg">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 mr-3 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-green-800">Reservation Approved</p>
                                <p class="text-xs text-green-600">Your reservation for Conference Room A has been approved.</p>
                            </div>
                        </div>
                        <div class="flex items-start p-3 bg-yellow-50 rounded-lg">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-yellow-600 mr-3 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-yellow-800">Maintenance Alert</p>
                                <p class="text-xs text-yellow-600">Scheduled maintenance for Training Room B tomorrow.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-gray-500 text-sm py-4">
                <p>&copy; 2025 ATIERA Hotel & Restaurant Management System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        // Update time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.bg-white');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
