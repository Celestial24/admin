<?php
// ================= DATABASE CONNECTION =================
$host   = "localhost";
$user   = "admin_admin_admin";
$pass   = "123";
$dbname = "admin_facilities";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// ================= FORM HANDLING PLACEHOLDER =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type'])) {
    // TODO: Add form handling for facility, reservation, maintenance
}

// ================= FETCH DATA =================
$facilitiesResult = $conn->query("SELECT * FROM facilities ORDER BY id ASC") or die("Facilities query failed: " . $conn->error);

$resResult = $conn->query("SELECT r.*, f.name AS facility_name 
                           FROM reservations r 
                           JOIN facilities f ON r.facility_id = f.id 
                           ORDER BY r.start_time DESC") or die("Reservations query failed: " . $conn->error);

$mainResult = $conn->query("SELECT m.*, f.name AS facility_name 
                            FROM maintenance m 
                            JOIN facilities f ON m.facility_id = f.id 
                            ORDER BY m.created_at DESC") or die("Maintenance query failed: " . $conn->error);

// ================= COUNTS =================
$totalFacilities   = $facilitiesResult->num_rows;
$totalReservations = $resResult->num_rows;
$totalMaintenance  = $mainResult->num_rows;

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

  <aside id="sidebar-mobile" class="h-full fixed inset-0 flex z-40 lg:hidden" style="display: none;">
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
      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex -mb-px space-x-6" aria-label="Tabs">
          <button class="tab-link py-3 px-1 border-b-2 font-medium text-sm border-blue-600 text-blue-600" data-tab="facilities">Facilities</button>
          <button class="tab-link py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500" data-tab="reservations">Reservations</button>
          <button class="tab-link py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500" data-tab="maintenance">Maintenance</button>
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
              <?php if ($facilitiesResult->num_rows > 0): while ($row = $facilitiesResult->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4"><?= $row['id'] ?></td>
                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['name']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($row['type']) ?></td>
                <td class="px-6 py-4"><?= $row['capacity'] ?></td>
                <td class="px-6 py-4"><?= getStatusBadge($row['status']) ?></td>
                <td class="px-6 py-4 flex justify-center gap-2">
                  <button class="text-blue-600 hover:text-blue-900"><i data-lucide="edit" class="w-4 h-4"></i></button>
                  <button class="text-red-600 hover:text-red-900"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="6" class="text-center py-6 text-gray-500">No facilities found.</td></tr>
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
              <?php if ($resResult->num_rows > 0): while ($row = $resResult->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4"><?= $row['id'] ?></td>
                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($row['reserved_by']) ?></td>
                <td class="px-6 py-4"><?= date("M d, Y, g:i A", strtotime($row['start_time'])) ?></td>
                <td class="px-6 py-4"><?= date("M d, Y, g:i A", strtotime($row['end_time'])) ?></td>
                <td class="px-6 py-4"><?= getStatusBadge($row['status']) ?></td>
                <td class="px-6 py-4 flex justify-center gap-2">
                  <button class="text-blue-600 hover:text-blue-900"><i data-lucide="edit" class="w-4 h-4"></i></button>
                  <button class="text-red-600 hover:text-red-900"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="7" class="text-center py-6 text-gray-500">No reservations found.</td></tr>
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
              <?php if ($mainResult->num_rows > 0): while ($row = $mainResult->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4"><?= $row['id'] ?></td>
                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['facility_name']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($row['description']) ?></td>
                <td class="px-6 py-4"><?= getStatusBadge($row['priority']) ?></td>
                <td class="px-6 py-4"><?= htmlspecialchars($row['reported_by']) ?></td>
                <td class="px-6 py-4"><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                <td class="px-6 py-4 flex justify-center gap-2">
                  <button class="text-blue-600 hover:text-blue-900"><i data-lucide="edit" class="w-4 h-4"></i></button>
                  <button class="text-red-600 hover:text-red-900"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
              </tr>
              <?php endwhile; else: ?>
              <tr><td colspan="7" class="text-center py-6 text-gray-500">No maintenance records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
<?php $conn->close(); ?>
