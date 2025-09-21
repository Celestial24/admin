<?php
// ================= DATABASE CONNECTION =================
require_once '../backend/sql/db.php';

// Check if facilities table exists
$table_check = $conn->query("SHOW TABLES LIKE 'facilities'");
if ($table_check->num_rows == 0) {
    // Create facilities table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS facilities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_name VARCHAR(255) NOT NULL,
        facility_type VARCHAR(100) NOT NULL,
        capacity INT NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Active',
        location VARCHAR(255) NOT NULL,
        description TEXT,
        amenities TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table) === FALSE) {
        die("Error creating facilities table: " . $conn->error);
    }
}

// ================= DELETE HANDLING =================
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'delete_facility' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM facilities WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Facility deleted successfully!";
            $messageType = "success";
            
        } elseif ($action === 'delete_reservation' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Reservation deleted successfully!";
            $messageType = "success";
            
        } elseif ($action === 'delete_maintenance' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM maintenance WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Maintenance record deleted successfully!";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Error deleting record: " . $e->getMessage();
        $messageType = "error";
    }
}

// ================= FETCH DATA =================
$facilitiesResult = $conn->query("SELECT * FROM facilities ORDER BY id ASC") 
    or die("Facilities query failed: " . $conn->error);

$resResult = $conn->query("SELECT r.*, f.facility_name AS facility_name 
                           FROM reservations r 
                           JOIN facilities f ON r.facility_id = f.id 
                           ORDER BY r.start_time DESC") 
    or die("Reservations query failed: " . $conn->error);

$mainResult = $conn->query("SELECT m.*, f.facility_name AS facility_name 
                            FROM maintenance m 
                            JOIN facilities f ON m.facility_id = f.id 
                            ORDER BY m.created_at DESC") 
    or die("Maintenance query failed: " . $conn->error);

// ================= HELPER: STATUS BADGE =================
function getStatusBadge($status) {
    $statusLower = strtolower($status);
    $color = 'gray';

    if ($statusLower === 'high') $color = 'red';
    if ($statusLower === 'medium') $color = 'yellow';
    if ($statusLower === 'low') $color = 'green';

    if (in_array($statusLower, ['available','completed','active','confirmed'])) $color = 'green';
    if (in_array($statusLower, ['under maintenance','in progress','pending'])) $color = 'yellow';
    if (in_array($statusLower, ['unavailable','cancelled'])) $color = 'red';

    return "<span class='px-2 py-1 text-xs font-medium text-{$color}-800 bg-{$color}-100 rounded-full'>"
         . htmlspecialchars($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Facilities - Admin</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
  <style>
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #555; }
  </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

  <!-- SIDEBAR -->
  <aside id="sidebar-desktop" class="h-full hidden lg:block">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <aside id="sidebar-mobile" class="h-full fixed inset-0 flex z-40 lg:hidden hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" id="sidebar-overlay"></div>
    <div class="relative flex-1 flex flex-col max-w-xs w-full">
      <?php include '../Components/sidebar/sidebar_user.php'; ?>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="flex-1 flex flex-col w-full">
    <header class="flex items-center justify-between border-b px-4 lg:px-6 py-3 bg-white shadow-sm">
      <button id="mobile-menu-button" class="lg:hidden text-gray-600 hover:text-gray-900">
        <i data-lucide="menu" class="w-6 h-6"></i>
      </button>
      <h2 class="text-xl font-semibold text-gray-800">Facilities Management</h2>
      <?php include __DIR__ . '/../profile.php'; ?>
    </header>

    <div class="flex-1 p-4 lg:p-6 overflow-y-auto">
      <!-- Message Display -->
      <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-md <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
          <div class="flex">
            <div class="flex-shrink-0">
              <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium"><?= htmlspecialchars($message) ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
      
      <!-- Quick Actions -->
      <div class="mb-6 bg-white rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <a href="../Views/modules/facility.php" 
             class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-colors">
            <div class="p-2 bg-blue-100 rounded-lg">
              <i data-lucide="building" class="w-6 h-6 text-blue-600"></i>
            </div>
            <div>
              <h4 class="font-medium text-gray-900">Facility Management</h4>
              <p class="text-sm text-gray-500">Add, edit, and manage facilities</p>
            </div>
          </a>
          
          <a href="../Views/modules/reservation.php" 
             class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:bg-green-50 hover:border-green-300 transition-colors">
            <div class="p-2 bg-green-100 rounded-lg">
              <i data-lucide="calendar" class="w-6 h-6 text-green-600"></i>
            </div>
            <div>
              <h4 class="font-medium text-gray-900">Reservation System</h4>
              <p class="text-sm text-gray-500">Create and manage reservations</p>
            </div>
          </a>
          
          <a href="../Views/modules/maintenance.php" 
             class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg hover:bg-orange-50 hover:border-orange-300 transition-colors">
            <div class="p-2 bg-orange-100 rounded-lg">
              <i data-lucide="wrench" class="w-6 h-6 text-orange-600"></i>
            </div>
            <div>
              <h4 class="font-medium text-gray-900">Maintenance Reports</h4>
              <p class="text-sm text-gray-500">Report and track maintenance issues</p>
            </div>
          </a>
        </div>
      </div>
      
      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex -mb-px space-x-6" aria-label="Tabs">
          <button type="button" class="tab-link py-3 px-1 border-b-2 font-medium text-sm border-blue-600 text-blue-600" data-tab="facilities">Facilities</button>
          <button type="button" class="tab-link py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500" data-tab="reservations">Reservations</button>
          <button type="button" class="tab-link py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500" data-tab="maintenance">Maintenance</button>
        </nav>
      </div>

      <!-- Facilities Tab -->
      <div id="facilities" class="tab-content">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
          <table class="min-w-full text-sm text-center">
            <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
              <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Name</th>
                <th class="px-6 py-3">Type</th>
                <th class="px-6 py-3">Capacity</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($facilitiesResult->num_rows > 0): ?>
                <?php while ($row = $facilitiesResult->fetch_assoc()): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4"><?= $row['id'] ?></td>
                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($row['facility_type']) ?></td>
                    <td class="px-6 py-4"><?= $row['capacity'] ?></td>
                    <td class="px-6 py-4"><?= getStatusBadge($row['status']) ?></td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                      <a href="../Views/modules/facility.php?edit=<?= $row['id'] ?>" 
                         class="text-blue-600 hover:text-blue-900" title="Edit Facility">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                      </a>
                      <button onclick="confirmDelete('facility', <?= $row['id'] ?>, '<?= htmlspecialchars($row['facility_name']) ?>')" 
                              class="text-red-600 hover:text-red-900" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="py-6 text-gray-500">No facilities found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reservations Tab -->
      <div id="reservations" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
          <table class="min-w-full text-sm text-center">
            <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
              <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Facility</th>
                <th class="px-6 py-3">Reserved By</th>
                <th class="px-6 py-3">Start Time</th>
                <th class="px-6 py-3">End Time</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($resResult->num_rows > 0): ?>
                <?php while ($row = $resResult->fetch_assoc()): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4"><?= $row['id'] ?></td>
                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($row['reserved_by']) ?></td>
                    <td class="px-6 py-4"><?= date("M d, Y, g:i A", strtotime($row['start_time'])) ?></td>
                    <td class="px-6 py-4"><?= date("M d, Y, g:i A", strtotime($row['end_time'])) ?></td>
                    <td class="px-6 py-4"><?= getStatusBadge($row['status']) ?></td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                      <a href="../Views/modules/reservation.php?edit=<?= $row['id'] ?>" 
                         class="text-blue-600 hover:text-blue-900" title="Edit Reservation">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                      </a>
                      <button onclick="confirmDelete('reservation', <?= $row['id'] ?>, '<?= htmlspecialchars($row['facility_name']) ?>')" 
                              class="text-red-600 hover:text-red-900" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="7" class="py-6 text-gray-500">No reservations found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Maintenance Tab -->
      <div id="maintenance" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow overflow-x-auto">
          <table class="min-w-full text-sm text-center">
            <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
              <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Facility</th>
                <th class="px-6 py-3">Description</th>
                <th class="px-6 py-3">Priority</th>
                <th class="px-6 py-3">Reported By</th>
                <th class="px-6 py-3">Reported On</th>
                <th class="px-6 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($mainResult->num_rows > 0): ?>
                <?php while ($row = $mainResult->fetch_assoc()): ?>
                  <tr class="border-b hover:bg-gray-50">
                    <td class="px-6 py-4"><?= $row['id'] ?></td>
                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($row['description']) ?></td>
                    <td class="px-6 py-4"><?= getStatusBadge($row['priority']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($row['reported_by']) ?></td>
                    <td class="px-6 py-4"><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                    <td class="px-6 py-4 flex justify-center gap-2">
                      <a href="../Views/modules/maintenance.php?edit=<?= $row['id'] ?>" 
                         class="text-blue-600 hover:text-blue-900" title="Edit Maintenance">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                      </a>
                      <button onclick="confirmDelete('maintenance', <?= $row['id'] ?>, '<?= htmlspecialchars($row['facility_name']) ?>')" 
                              class="text-red-600 hover:text-red-900" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="7" class="py-6 text-gray-500">No maintenance records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
          <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mt-4">Confirm Delete</h3>
        <div class="mt-2 px-7 py-3">
          <p class="text-sm text-gray-500" id="deleteMessage">
            Are you sure you want to delete this item? This action cannot be undone.
          </p>
        </div>
        <div class="items-center px-4 py-3">
          <form id="deleteForm" method="POST" class="inline">
            <input type="hidden" name="action" id="deleteAction">
            <input type="hidden" name="id" id="deleteId">
            <button type="button" id="cancelDelete" 
                    class="bg-gray-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-600 mr-2">
              Cancel
            </button>
            <button type="submit" 
                    class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">
              Delete
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const tabLinks = document.querySelectorAll(".tab-link");
      const tabContents = document.querySelectorAll(".tab-content");

      tabLinks.forEach(link => {
        link.addEventListener("click", function () {
          // reset tabs
          tabLinks.forEach(l => {
            l.classList.remove("border-blue-600", "text-blue-600");
            l.classList.add("border-transparent", "text-gray-500");
          });
          tabContents.forEach(c => c.classList.add("hidden"));

          // activate selected tab
          this.classList.add("border-blue-600", "text-blue-600");
          this.classList.remove("border-transparent", "text-gray-500");

          const tabId = this.getAttribute("data-tab");
          document.getElementById(tabId).classList.remove("hidden");
        });
      });

      // Mobile menu toggle
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const sidebarMobile = document.getElementById('sidebar-mobile');
      const sidebarOverlay = document.getElementById('sidebar-overlay');

      if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
          sidebarMobile.classList.remove('hidden');
        });
      }

      if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
          sidebarMobile.classList.add('hidden');
        });
      }
    });

    // Delete confirmation function
    function confirmDelete(type, id, name) {
      const modal = document.getElementById('deleteModal');
      const message = document.getElementById('deleteMessage');
      const actionInput = document.getElementById('deleteAction');
      const idInput = document.getElementById('deleteId');
      
      // Set the appropriate message and action based on type
      let actionName = '';
      switch(type) {
        case 'facility':
          actionName = 'facility';
          message.textContent = `Are you sure you want to delete the facility "${name}"? This action cannot be undone.`;
          break;
        case 'reservation':
          actionName = 'reservation';
          message.textContent = `Are you sure you want to delete the reservation for "${name}"? This action cannot be undone.`;
          break;
        case 'maintenance':
          actionName = 'maintenance';
          message.textContent = `Are you sure you want to delete the maintenance record for "${name}"? This action cannot be undone.`;
          break;
      }
      
      actionInput.value = `delete_${actionName}`;
      idInput.value = id;
      
      modal.classList.remove('hidden');
    }

    // Cancel delete
    document.getElementById('cancelDelete').addEventListener('click', () => {
      document.getElementById('deleteModal').classList.add('hidden');
    });

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', (e) => {
      if (e.target === document.getElementById('deleteModal')) {
        document.getElementById('deleteModal').classList.add('hidden');
      }
    });
  </script>

</body>
</html>
<?php $conn->close(); ?>
