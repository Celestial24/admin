<?php
// ================= DATABASE CONNECTION =================
require_once '../backend/sql/facilities.php';

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
        } elseif ($action === 'create_facility') {
            $name = trim($_POST['facility_name'] ?? '');
            $type = trim($_POST['facility_type'] ?? '');
            $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0;
            $status = trim($_POST['status'] ?? 'Available');
            if ($name !== '') {
                $stmt = $conn->prepare("INSERT INTO facilities (facility_name, facility_type, capacity, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $name, $type, $capacity, $status);
                $stmt->execute();
                $message = "Facility added successfully!";
                $messageType = "success";
            } else {
                $message = "Facility name is required.";
                $messageType = "error";
            }
        }
    } catch (Exception $e) {
        $message = "Error deleting record: " . $e->getMessage();
        $messageType = "error";
    }
}

// ================= FETCH DATA =================
$facilitiesResult = $conn->query("SELECT * FROM facilities ORDER BY id ASC") 
    or die("Facilities query failed: " . $conn->error);

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
      <div class="flex items-center gap-3">
        <button id="mobile-menu-button" class="lg:hidden text-gray-600 hover:text-gray-900">
          <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
        <h2 class="text-xl font-semibold text-gray-800">Facilities</h2>
      </div>
      <div class="flex items-center gap-2">
        <button id="openCreateModal" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-3 py-2 rounded-md">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Add Facility
        </button>
        <?php include __DIR__ . '/../profile.php'; ?>
      </div>
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
      
      <!-- Toolbar: Search & Filters -->
      <div class="mb-4 bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="col-span-1 md:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <div class="relative">
              <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input id="searchInput" type="text" placeholder="Search by name or type..." class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select id="statusFilter" class="w-full py-2 px-3 border border-gray-200 rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">All</option>
              <option value="available">Available</option>
              <option value="under maintenance">Under Maintenance</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Facilities Table Only -->
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
                <tr class="border-b hover:bg-gray-50" 
                    data-name="<?= htmlspecialchars(strtolower($row['facility_name'])) ?>" 
                    data-type="<?= htmlspecialchars(strtolower($row['facility_type'])) ?>" 
                    data-status="<?= htmlspecialchars(strtolower($row['status'])) ?>">
                  <td class="px-6 py-4"><?= $row['id'] ?></td>
                  <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name']) ?></td>
                  <td class="px-6 py-4"><?= htmlspecialchars($row['facility_type']) ?></td>
                  <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                      <?= $row['capacity'] ?> pax
                    </span>
                  </td>
                  <td class="px-6 py-4"><?= getStatusBadge($row['status']) ?></td>
                  <td class="px-6 py-4 flex justify-center gap-2">
                    <a href="../Views/modules/facility.php?edit=<?= $row['id'] ?>" 
                       class="text-blue-600 hover:text-blue-900" title="Edit Facility">
                      <i data-lucide="edit" class="w-4 h-4"></i>
                    </a>
                    <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['facility_name']) ?>')" 
                            class="text-red-600 hover:text-red-900" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="py-6 text-gray-500">No facilities found.</td></tr>
            <?php endif; ?>
            <tr id="noResultsRow" class="hidden"><td colspan="6" class="py-6 text-gray-500">No matching results.</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </main>

  <!-- Create Facility Modal -->
  <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium text-gray-900">Add Facility</h3>
        <button id="closeCreateModal" class="text-gray-500 hover:text-gray-700">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="action" value="create_facility" />
        <div>
          <label class="block text-sm text-gray-700 mb-1">Name</label>
          <input name="facility_name" type="text" required class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Type</label>
          <input name="facility_type" type="text" class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Capacity</label>
            <input name="capacity" type="number" min="0" value="0" class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option>Available</option>
              <option>Under Maintenance</option>
              <option>Unavailable</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="cancelCreate" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Save</button>
        </div>
      </form>
    </div>
  </div>

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
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const sidebarMobile = document.getElementById('sidebar-mobile');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const searchInput = document.getElementById('searchInput');
      const statusFilter = document.getElementById('statusFilter');
      const tableBody = document.querySelector('table tbody');
      const noResultsRow = document.getElementById('noResultsRow');
      if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => { sidebarMobile.classList.remove('hidden'); });
      }
      if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => { sidebarMobile.classList.add('hidden'); });
      }

      function applyFilters() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const status = (statusFilter?.value || '').toLowerCase();
        let anyVisible = false;
        tableBody.querySelectorAll('tr').forEach(row => {
          if (row.id === 'noResultsRow') return;
          const name = row.getAttribute('data-name') || '';
          const type = row.getAttribute('data-type') || '';
          const rowStatus = row.getAttribute('data-status') || '';
          const matchesQuery = !q || name.includes(q) || type.includes(q);
          const matchesStatus = !status || rowStatus === status;
          const show = matchesQuery && matchesStatus;
          row.classList.toggle('hidden', !show);
          if (show) anyVisible = true;
        });
        if (noResultsRow) noResultsRow.classList.toggle('hidden', anyVisible);
      }

      if (searchInput) searchInput.addEventListener('input', applyFilters);
      if (statusFilter) statusFilter.addEventListener('change', applyFilters);
      applyFilters();

      // Create modal controls
      const createModal = document.getElementById('createModal');
      const openCreateModal = document.getElementById('openCreateModal');
      const closeCreateModal = document.getElementById('closeCreateModal');
      const cancelCreate = document.getElementById('cancelCreate');
      if (openCreateModal) openCreateModal.addEventListener('click', () => createModal.classList.remove('hidden'));
      if (closeCreateModal) closeCreateModal.addEventListener('click', () => createModal.classList.add('hidden'));
      if (cancelCreate) cancelCreate.addEventListener('click', () => createModal.classList.add('hidden'));
      if (createModal) createModal.addEventListener('click', (e) => { if (e.target === createModal) createModal.classList.add('hidden'); });
    });

    // Delete confirmation (facilities only)
    function confirmDelete(id, name) {
      const modal = document.getElementById('deleteModal');
      const message = document.getElementById('deleteMessage');
      const actionInput = document.getElementById('deleteAction');
      const idInput = document.getElementById('deleteId');
      message.textContent = `Are you sure you want to delete the facility "${name}"? This action cannot be undone.`;
      actionInput.value = 'delete_facility';
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
