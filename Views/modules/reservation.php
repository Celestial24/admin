<?php
session_start();

// Check if user is logged in and is an employee
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
            if ($action === 'create_reservation') {
                $stmt = $conn->prepare("INSERT INTO reservations (facility_id, reserved_by, purpose, start_time, end_time, status, employee_id, created_at) VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())");
                $stmt->bind_param("issssi", 
                    $_POST['facility_id'], 
                    $_POST['reserved_by'], 
                    $_POST['purpose'], 
                    $_POST['start_time'], 
                    $_POST['end_time'],
                    $_SESSION['user_id']
                );
                $stmt->execute();
                $success_message = "Reservation request submitted successfully!";
                
            } elseif ($action === 'update_reservation') {
                $stmt = $conn->prepare("UPDATE reservations SET facility_id=?, reserved_by=?, purpose=?, start_time=?, end_time=? WHERE id=? AND employee_id=?");
                $stmt->bind_param("issssi", 
                    $_POST['facility_id'], 
                    $_POST['reserved_by'], 
                    $_POST['purpose'], 
                    $_POST['start_time'], 
                    $_POST['end_time'],
                    $_POST['reservation_id'],
                    $_SESSION['user_id']
                );
                $stmt->execute();
                $success_message = "Reservation updated successfully!";
                
            } elseif ($action === 'cancel_reservation') {
                $stmt = $conn->prepare("UPDATE reservations SET status='Cancelled' WHERE id=? AND employee_id=?");
                $stmt->bind_param("ii", $_POST['reservation_id'], $_SESSION['user_id']);
                $stmt->execute();
                $success_message = "Reservation cancelled successfully!";
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
    $facilities_result = $conn->query("SELECT id, facility_name, facility_type, capacity FROM facilities WHERE status = 'Active' ORDER BY facility_name");
} catch (Exception $e) {
    error_log("Facilities query failed: " . $e->getMessage());
}

// Fetch user's reservations with error handling
$user_id = $_SESSION['user_id'];
$reservations_result = false;
try {
    $reservations_result = $conn->query("
        SELECT r.*, f.facility_name, f.facility_type 
        FROM reservations r 
        JOIN facilities f ON r.facility_id = f.id 
        WHERE r.employee_id = $user_id 
        ORDER BY r.start_time DESC
    ");
} catch (Exception $e) {
    error_log("Reservations query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Module (Employees only)</title>
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
                <h2 class="text-xl font-semibold text-gray-800">Reservation Module (Employees only)</h2>
                <p class="text-sm text-gray-600">Manage your facility reservations</p>
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

            <!-- Employee Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i data-lucide="info" class="w-5 h-5 text-blue-600 mr-2"></i>
                    <div>
                        <h3 class="text-sm font-medium text-blue-800">Employee Access Only</h3>
                        <p class="text-sm text-blue-700">This module is restricted to employees only. All reservations require approval.</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">My Reservations</h3>
                <button onclick="openReservationModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    New Reservation
                </button>
            </div>

            <!-- Reservations Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($reservations_result && $reservations_result !== false && $reservations_result->num_rows > 0): ?>
                                <?php while ($reservation = $reservations_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($reservation['facility_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($reservation['facility_type']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($reservation['purpose']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div><?= date('M j, Y', strtotime($reservation['start_time'])) ?></div>
                                            <div class="text-gray-500"><?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $reservation['status'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                                   ($reservation['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($reservation['status'] === 'Cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) ?>">
                                                <?= htmlspecialchars($reservation['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($reservation['status'] === 'Pending'): ?>
                                                <button onclick="editReservation(<?= htmlspecialchars(json_encode($reservation)) ?>)" 
                                                        class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                                <button onclick="cancelReservation(<?= $reservation['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900">Cancel</button>
                                            <?php else: ?>
                                                <span class="text-gray-400">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No reservations found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Available Facilities -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Available Facilities</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php 
                    if ($facilities_result && $facilities_result !== false) {
                        $facilities_result->data_seek(0); // Reset result pointer
                        while ($facility = $facilities_result->fetch_assoc()): 
                    ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-gray-900"><?= htmlspecialchars($facility['facility_name']) ?></h4>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($facility['facility_type']) ?></p>
                            <p class="text-sm text-gray-500">Capacity: <?= htmlspecialchars($facility['capacity']) ?> people</p>
                        </div>
                    <?php 
                        endwhile; 
                    } else {
                        echo '<div class="col-span-full text-center text-gray-500 py-8">No facilities available</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Modal -->
    <div id="reservationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">New Reservation</h3>
                <form id="reservationForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create_reservation">
                    <input type="hidden" name="reservation_id" id="reservationId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Facility</label>
                            <select name="facility_id" id="facilityId" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Facility</option>
                                <?php 
                                if ($facilities_result && $facilities_result !== false) {
                                    $facilities_result->data_seek(0); // Reset result pointer
                                    while ($facility = $facilities_result->fetch_assoc()): 
                                ?>
                                    <option value="<?= $facility['id'] ?>">
                                        <?= htmlspecialchars($facility['facility_name']) ?> (<?= htmlspecialchars($facility['facility_type']) ?>)
                                    </option>
                                <?php 
                                    endwhile; 
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reserved By</label>
                            <input type="text" name="reserved_by" id="reservedBy" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Purpose</label>
                            <textarea name="purpose" id="purpose" rows="3" required 
                                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date & Time</label>
                            <input type="datetime-local" name="start_time" id="startTime" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date & Time</label>
                            <input type="datetime-local" name="end_time" id="endTime" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Submit Request
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

        function openReservationModal() {
            document.getElementById('modalTitle').textContent = 'New Reservation';
            document.getElementById('formAction').value = 'create_reservation';
            document.getElementById('reservationForm').reset();
            document.getElementById('reservationId').value = '';
            document.getElementById('reservationModal').classList.remove('hidden');
        }

        function editReservation(reservation) {
            document.getElementById('modalTitle').textContent = 'Edit Reservation';
            document.getElementById('formAction').value = 'update_reservation';
            document.getElementById('reservationId').value = reservation.id;
            document.getElementById('facilityId').value = reservation.facility_id;
            document.getElementById('reservedBy').value = reservation.reserved_by;
            document.getElementById('purpose').value = reservation.purpose;
            document.getElementById('startTime').value = reservation.start_time.replace(' ', 'T');
            document.getElementById('endTime').value = reservation.end_time.replace(' ', 'T');
            document.getElementById('reservationModal').classList.remove('hidden');
        }

        function cancelReservation(id) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel_reservation">
                    <input type="hidden" name="reservation_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('reservationModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('reservationModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Set minimum date to today
        const today = new Date().toISOString().slice(0, 16);
        document.getElementById('startTime').min = today;
        document.getElementById('endTime').min = today;
    </script>
</body>
</html>
