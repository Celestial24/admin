<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once '../../backend/sql/db.php';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            if ($action === 'report_maintenance') {
                $stmt = $conn->prepare("INSERT INTO maintenance_reports (facility_id, reported_by, issue_type, description, priority, status, reported_at) VALUES (?, ?, ?, ?, ?, 'Open', NOW())");
                $stmt->bind_param("issss", 
                    $_POST['facility_id'], 
                    $_POST['reported_by'], 
                    $_POST['issue_type'], 
                    $_POST['description'], 
                    $_POST['priority']
                );
                $stmt->execute();
                $success_message = "Maintenance issue reported successfully!";
                
            } elseif ($action === 'update_status') {
                $stmt = $conn->prepare("UPDATE maintenance_reports SET status=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("si", $_POST['new_status'], $_POST['report_id']);
                $stmt->execute();
                $success_message = "Maintenance status updated successfully!";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch facilities for dropdown with error handling
$facilities_result = false;
try {
    $facilities_result = $conn->query("SELECT id, facility_name, facility_type FROM facilities ORDER BY facility_name");
} catch (Exception $e) {
    error_log("Facilities query failed: " . $e->getMessage());
}

// Fetch maintenance reports with reporter information with error handling
$maintenance_result = false;
try {
    $maintenance_result = $conn->query("
        SELECT mr.*, f.facility_name, f.facility_type 
        FROM maintenance_reports mr 
        JOIN facilities f ON mr.facility_id = f.id 
        ORDER BY mr.reported_at DESC
    ");
} catch (Exception $e) {
    error_log("Maintenance reports query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Who Reported</title>
    <link rel="icon" type="image/png" href="../../assets/image/logo2.png">
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
        <?php include '../../Components/sidebar/sidebar_user.php'; ?>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <a href="../../module-table/facilities.php" 
                       class="flex items-center gap-2 text-blue-600 hover:text-blue-800 transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        <span>Back to Overview</span>
                    </a>
                </div>
                <h2 class="text-xl font-semibold text-gray-800">Maintenance - Who Reported</h2>
                <p class="text-sm text-gray-600">Track maintenance issues and who reported them</p>
            </div>
            <?php include '../../profile.php'; ?>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Maintenance Reports</h3>
                <button onclick="openReportModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    Report Issue
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php
                // Get statistics with error handling
                $stats = [
                    'total' => 0,
                    'open_count' => 0,
                    'in_progress_count' => 0,
                    'resolved_count' => 0
                ];
                
                try {
                    $stats_result = $conn->query("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_count,
                            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
                            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved_count
                        FROM maintenance_reports
                    ");
                    
                    if ($stats_result && $stats_result !== false) {
                        $stats_data = $stats_result->fetch_assoc();
                        if ($stats_data) {
                            $stats = $stats_data;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Maintenance stats query failed: " . $e->getMessage());
                }
                ?>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i data-lucide="clipboard-list" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Reports</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Open Issues</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['open_count'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">In Progress</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['in_progress_count'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Resolved</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['resolved_count'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Information Card -->
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-lucide="info" class="w-6 h-6 text-orange-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-orange-800">Maintenance Report Input Only</h3>
                        <div class="mt-2 text-sm text-orange-700">
                            <p>This module is for reporting maintenance issues only. To view, manage, or track existing maintenance reports, please use the <a href="../../module-table/facilities.php" class="font-medium underline hover:text-orange-800">Facilities Overview</a> page.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Issue Modal -->
    <div id="reportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Report Maintenance Issue</h3>
                <form id="reportForm" method="POST">
                    <input type="hidden" name="action" value="report_maintenance">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Facility</label>
                            <select name="facility_id" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Facility</option>
                                <?php 
                                if ($facilities_result && $facilities_result !== false) {
                                    $facilities_result->data_seek(0); // Reset result pointer
                                    while ($facility = $facilities_result->fetch_assoc()): 
                                ?>
                                    <option value="<?= $facility['id'] ?>">
                                        <?= htmlspecialchars($facility['facility_name']) ?>
                                    </option>
                                <?php 
                                    endwhile; 
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reported By</label>
                            <input type="text" name="reported_by" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Issue Type</label>
                            <select name="issue_type" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Issue Type</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Plumbing">Plumbing</option>
                                <option value="HVAC">HVAC</option>
                                <option value="Furniture">Furniture</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Cleaning">Cleaning</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <select name="priority" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="4" required 
                                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Report Issue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Maintenance Report Details</h3>
                <div id="reportDetails" class="space-y-3">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="flex justify-end mt-6">
                    <button onclick="closeDetailsModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Status</h3>
                <form id="statusForm" method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="report_id" id="statusReportId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">New Status</label>
                            <select name="new_status" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="Open">Open</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeStatusModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        function openReportModal() {
            document.getElementById('reportForm').reset();
            document.getElementById('reportModal').classList.remove('hidden');
        }

        function viewDetails(report) {
            const details = `
                <div class="space-y-2">
                    <div><strong>Facility:</strong> ${report.facility_name}</div>
                    <div><strong>Issue Type:</strong> ${report.issue_type}</div>
                    <div><strong>Reported By:</strong> ${report.reported_by}</div>
                    <div><strong>Priority:</strong> ${report.priority}</div>
                    <div><strong>Status:</strong> ${report.status}</div>
                    <div><strong>Reported Date:</strong> ${new Date(report.reported_at).toLocaleString()}</div>
                    <div><strong>Description:</strong> ${report.description}</div>
                </div>
            `;
            document.getElementById('reportDetails').innerHTML = details;
            document.getElementById('detailsModal').classList.remove('hidden');
        }

        function updateStatus(id, currentStatus) {
            document.getElementById('statusReportId').value = id;
            document.querySelector('#statusForm select[name="new_status"]').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('reportModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const reportModal = document.getElementById('reportModal');
            
            if (event.target === reportModal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
