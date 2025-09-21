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
            if ($action === 'add_facility') {
                $stmt = $conn->prepare("INSERT INTO facilities (facility_name, facility_type, capacity, status, location, description, amenities) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssissss", 
                    $_POST['facility_name'], 
                    $_POST['facility_type'], 
                    $_POST['capacity'], 
                    $_POST['status'], 
                    $_POST['location'], 
                    $_POST['description'], 
                    $_POST['amenities']
                );
                $stmt->execute();
                $success_message = "Facility added successfully!";
                
            } elseif ($action === 'update_facility') {
                $stmt = $conn->prepare("UPDATE facilities SET facility_name=?, facility_type=?, capacity=?, status=?, location=?, description=?, amenities=? WHERE id=?");
                $stmt->bind_param("ssissssi", 
                    $_POST['facility_name'], 
                    $_POST['facility_type'], 
                    $_POST['capacity'], 
                    $_POST['status'], 
                    $_POST['location'], 
                    $_POST['description'], 
                    $_POST['amenities'],
                    $_POST['facility_id']
                );
                $stmt->execute();
                $success_message = "Facility updated successfully!";
                
            } elseif ($action === 'delete_facility') {
                $stmt = $conn->prepare("DELETE FROM facilities WHERE id=?");
                $stmt->bind_param("i", $_POST['facility_id']);
                $stmt->execute();
                $success_message = "Facility deleted successfully!";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch facilities with error handling
$facilities_result = false;
try {
    $facilities_result = $conn->query("SELECT * FROM facilities ORDER BY facility_name");
} catch (Exception $e) {
    error_log("Facilities query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities Management - Details</title>
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
            <div class="flex items-center gap-4">
                <a href="../../module-table/facilities.php" 
                   class="flex items-center gap-2 text-blue-600 hover:text-blue-800 transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    <span>Back to Overview</span>
                </a>
                <h2 class="text-xl font-semibold text-gray-800">Facilities Management - Details</h2>
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

            <!-- Add New Facility Button -->
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Facility Details</h3>
                <button onclick="openAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Add New Facility
                </button>
            </div>

            <!-- Facilities Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($facilities_result && $facilities_result !== false && $facilities_result->num_rows > 0): ?>
                                <?php while ($facility = $facilities_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($facility['facility_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($facility['description']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($facility['facility_type']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($facility['capacity']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $facility['status'] === 'Active' ? 'bg-green-100 text-green-800' : 
                                                   ($facility['status'] === 'Under Maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                <?= htmlspecialchars($facility['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($facility['location']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewDetails(<?= htmlspecialchars(json_encode($facility)) ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                            <button onclick="editFacility(<?= htmlspecialchars(json_encode($facility)) ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                            <button onclick="deleteFacility(<?= $facility['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No facilities found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Facility Modal -->
    <div id="facilityModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">Add New Facility</h3>
                <form id="facilityForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add_facility">
                    <input type="hidden" name="facility_id" id="facilityId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Facility Name</label>
                            <input type="text" name="facility_name" id="facilityName" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="facility_type" id="facilityType" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Type</option>
                                <option value="Conference Room">Conference Room</option>
                                <option value="Meeting Room">Meeting Room</option>
                                <option value="Auditorium">Auditorium</option>
                                <option value="Training Room">Training Room</option>
                                <option value="Recreation Area">Recreation Area</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Capacity</label>
                            <input type="number" name="capacity" id="capacity" required min="1"
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" required 
                                    class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Under Maintenance">Under Maintenance</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input type="text" name="location" id="location" required 
                                   class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amenities</label>
                            <textarea name="amenities" id="amenities" rows="2"
                                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Save
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Facility Details</h3>
                <div id="facilityDetails" class="space-y-3">
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

    <script>
        // Initialize Lucide icons
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Facility';
            document.getElementById('formAction').value = 'add_facility';
            document.getElementById('facilityForm').reset();
            document.getElementById('facilityId').value = '';
            document.getElementById('facilityModal').classList.remove('hidden');
        }

        function editFacility(facility) {
            document.getElementById('modalTitle').textContent = 'Edit Facility';
            document.getElementById('formAction').value = 'update_facility';
            document.getElementById('facilityId').value = facility.id;
            document.getElementById('facilityName').value = facility.facility_name;
            document.getElementById('facilityType').value = facility.facility_type;
            document.getElementById('capacity').value = facility.capacity;
            document.getElementById('status').value = facility.status;
            document.getElementById('location').value = facility.location;
            document.getElementById('description').value = facility.description || '';
            document.getElementById('amenities').value = facility.amenities || '';
            document.getElementById('facilityModal').classList.remove('hidden');
        }

        function viewDetails(facility) {
            const details = `
                <div class="space-y-2">
                    <div><strong>Name:</strong> ${facility.facility_name}</div>
                    <div><strong>Type:</strong> ${facility.facility_type}</div>
                    <div><strong>Capacity:</strong> ${facility.capacity}</div>
                    <div><strong>Status:</strong> ${facility.status}</div>
                    <div><strong>Location:</strong> ${facility.location}</div>
                    <div><strong>Description:</strong> ${facility.description || 'N/A'}</div>
                    <div><strong>Amenities:</strong> ${facility.amenities || 'N/A'}</div>
                </div>
            `;
            document.getElementById('facilityDetails').innerHTML = details;
            document.getElementById('detailsModal').classList.remove('hidden');
        }

        function deleteFacility(id) {
            if (confirm('Are you sure you want to delete this facility?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_facility">
                    <input type="hidden" name="facility_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('facilityModal').classList.add('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const facilityModal = document.getElementById('facilityModal');
            const detailsModal = document.getElementById('detailsModal');
            
            if (event.target === facilityModal) {
                closeModal();
            }
            if (event.target === detailsModal) {
                closeDetailsModal();
            }
        }
    </script>
</body>
</html>
