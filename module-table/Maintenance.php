<?php
// ================= DATABASE CONNECTION =================
require_once '../backend/sql/facilities.php';

// ================= ACTION HANDLING =================
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'delete_maintenance' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM maintenance WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Maintenance record deleted successfully!";
            $messageType = "success";
        } elseif ($action === 'create_maintenance') {
            $facilityId = isset($_POST['facility_id']) && $_POST['facility_id'] !== '' ? (int)$_POST['facility_id'] : null;
            $description = trim($_POST['description'] ?? '');
            $priority = trim($_POST['priority'] ?? 'Low');
            $reportedBy = trim($_POST['reported_by'] ?? '');
            if ($description !== '') {
                if ($facilityId === null) {
                    $stmt = $conn->prepare("INSERT INTO maintenance (facility_id, description, priority, reported_by) VALUES (NULL, ?, ?, ?)");
                    $stmt->bind_param("sss", $description, $priority, $reportedBy);
                } else {
                    $stmt = $conn->prepare("INSERT INTO maintenance (facility_id, description, priority, reported_by) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $facilityId, $description, $priority, $reportedBy);
                }
                $stmt->execute();
                $message = "Maintenance record created successfully!";
                $messageType = "success";
            } else {
                $message = "Description is required.";
                $messageType = "error";
            }
        }
    } catch (Exception $e) {
        $message = "Error deleting record: " . $e->getMessage();
        $messageType = "error";
    }
}

// ================= FETCH DATA =================
$mainResult = false;
try {
    $mainResult = $conn->query("SELECT m.*, f.facility_name AS facility_name 
                                FROM maintenance m 
                                JOIN facilities f ON m.facility_id = f.id 
                                ORDER BY m.created_at DESC");
} catch (Exception $e) {
    try {
        $mainResult = $conn->query("SELECT * FROM maintenance ORDER BY created_at DESC");
    } catch (Exception $e2) {
        error_log("Maintenance query failed: " . $e2->getMessage());
    }
}

function getStatusBadge($status) {
    $statusLower = strtolower($status);
    $color = 'gray';
    if ($statusLower === 'high') $color = 'red';
    if ($statusLower === 'medium') $color = 'yellow';
    if ($statusLower === 'low') $color = 'green';
    if (in_array($statusLower, ['available','completed','active','confirmed'])) $color = 'green';
    if (in_array($statusLower, ['under maintenance','in progress','pending'])) $color = 'yellow';
    if (in_array($statusLower, ['unavailable','cancelled'])) $color = 'red';
    return "<span class='px-2 py-1 text-xs font-medium text-{$color}-800 bg-{$color}-100 rounded-full'>" . htmlspecialchars($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Maintenance - Admin</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
  <style>
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #555; }
  </style>
  <script>
    function confirmDelete(id, name) {
      const modal = document.getElementById('deleteModal');
      const message = document.getElementById('deleteMessage');
      const actionInput = document.getElementById('deleteAction');
      const idInput = document.getElementById('deleteId');
      message.textContent = `Are you sure you want to delete the maintenance record for "${name}"? This action cannot be undone.`;
      actionInput.value = 'delete_maintenance';
      idInput.value = id;
      modal.classList.remove('hidden');
    }
  </script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

  <aside id="sidebar-desktop" class="h-full hidden lg:block">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <aside id="sidebar-mobile" class="h-full fixed inset-0 flex z-40 lg:hidden hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" id="sidebar-overlay"></div>
    <div class="relative flex-1 flex flex-col max-w-xs w-full">
      <?php include '../Components/sidebar/sidebar_user.php'; ?>
    </div>
  </aside>

  <main class="flex-1 flex flex-col w-full">
    <header class="flex items-center justify-between border-b px-4 lg:px-6 py-3 bg-white shadow-sm">
      <button id="mobile-menu-button" class="lg:hidden text-gray-600 hover:text-gray-900">
        <i data-lucide="menu" class="w-6 h-6"></i>
      </button>
      <h2 class="text-xl font-semibold text-gray-800">Maintenance</h2>
      <div class="flex items-center gap-2">
        <button id="openCreateModal" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-3 py-2 rounded-md">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Add Maintenance
        </button>
        <?php include __DIR__ . '/../profile.php'; ?>
      </div>
    </header>

    <div class="flex-1 p-4 lg:p-6 overflow-y-auto">
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
            <?php if ($mainResult && $mainResult !== false && $mainResult->num_rows > 0): ?>
              <?php while ($row = $mainResult->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="px-6 py-4"><?= $row['id'] ?></td>
                  <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name'] ?? 'Unknown Facility') ?></td>
                  <td class="px-6 py-4"><?= htmlspecialchars($row['description']) ?></td>
                  <td class="px-6 py-4"><?= getStatusBadge($row['priority']) ?></td>
                  <td class="px-6 py-4"><?= htmlspecialchars($row['reported_by']) ?></td>
                  <td class="px-6 py-4"><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                  <td class="px-6 py-4 flex justify-center gap-2">
                    <a href="../Views/modules/maintenance.php?edit=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit Maintenance">
                      <i data-lucide="edit" class="w-4 h-4"></i>
                    </a>
                    <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['facility_name']) ?>')" class="text-red-600 hover:text-red-900" title="Delete">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
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
  </main>

  <!-- Create Maintenance Modal -->
  <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium text-gray-900">Add Maintenance</h3>
        <button id="closeCreateModal" class="text-gray-500 hover:text-gray-700">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="action" value="create_maintenance" />
        <div>
          <label class="block text-sm text-gray-700 mb-1">Facility (optional)</label>
          <select name="facility_id" class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— None —</option>
            <?php
              $facilities = $conn->query("SELECT id, facility_name FROM facilities ORDER BY facility_name ASC");
              if ($facilities) {
                while ($f = $facilities->fetch_assoc()) {
                  echo '<option value="'.(int)$f['id'].'">'.htmlspecialchars($f['facility_name']).'</option>';
                }
              }
            ?>
          </select>
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="3" required class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Priority</label>
            <select name="priority" class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option>Low</option>
              <option>Medium</option>
              <option>High</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Reported By</label>
            <input type="text" name="reported_by" class="w-full border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
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
          <p class="text-sm text-gray-500" id="deleteMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
        </div>
        <div class="items-center px-4 py-3">
          <form method="POST" class="inline">
            <input type="hidden" name="action" id="deleteAction">
            <input type="hidden" name="id" id="deleteId">
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden');" class="bg-gray-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-600 mr-2">Cancel</button>
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const sidebarMobile = document.getElementById('sidebar-mobile');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => { sidebarMobile.classList.remove('hidden'); });
      }
      if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => { sidebarMobile.classList.add('hidden'); });
      }

      const createModal = document.getElementById('createModal');
      const openCreateModal = document.getElementById('openCreateModal');
      const closeCreateModal = document.getElementById('closeCreateModal');
      const cancelCreate = document.getElementById('cancelCreate');
      if (openCreateModal) openCreateModal.addEventListener('click', () => createModal.classList.remove('hidden'));
      if (closeCreateModal) closeCreateModal.addEventListener('click', () => createModal.classList.add('hidden'));
      if (cancelCreate) cancelCreate.addEventListener('click', () => createModal.classList.add('hidden'));
      if (createModal) createModal.addEventListener('click', (e) => { if (e.target === createModal) createModal.classList.add('hidden'); });
    });
  </script>

</body>
</html>
<?php $conn->close(); ?>


