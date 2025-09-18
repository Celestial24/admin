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
$facilitiesResult = $conn->query("SELECT * FROM facilities ORDER BY id ASC");
if (!$facilitiesResult) die("Facilities query failed: " . $conn->error);

$resResult = $conn->query("SELECT r.*, f.name AS facility_name 
                           FROM reservations r 
                           JOIN facilities f ON r.facility_id = f.id 
                           ORDER BY r.start_time DESC");
if (!$resResult) die("Reservations query failed: " . $conn->error);

$mainResult = $conn->query("SELECT m.*, f.name AS facility_name 
                            FROM maintenance m 
                            JOIN facilities f ON m.facility_id = f.id 
                            ORDER BY m.created_at DESC");
if (!$mainResult) die("Maintenance query failed: " . $conn->error);

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
          <table class="min-w-full text-sm">
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
                <td class="px-6 py-4 flex gap-2">
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
          <table class="min-w-full text-sm">
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
                <td class="px-6 py-4 flex gap-2">
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
          <table class="min-w-full text-sm">
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
                <td class="px-6 py-4 flex gap-2">
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

  <!-- CHATBOT -->
  <div class="fixed bottom-6 right-6 z-50">
    <button id="chatbotToggle" class="bg-gray-800 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg transition-all duration-300 group">
      <img src="/admin/assets/image/logo2.png" alt="Admin Assistant" class="w-12 h-12 object-contain">
      <span class="absolute bottom-full mb-2 right-1/2 transform translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity shadow-lg">Admin Assistant</span>
    </button>

    <div id="chatbotBox" class="fixed bottom-24 right-6 w-80 bg-white border border-gray-200 rounded-xl shadow-xl opacity-0 scale-95 pointer-events-none transition-all duration-300 overflow-hidden">
      <div class="p-4 border-b bg-blue-600 text-white font-semibold">Admin Assistant</div>
      <div id="chatContent" class="p-4 h-64 overflow-y-auto text-sm bg-gray-50 space-y-4"></div>
      <div class="p-3 border-t flex gap-2">
        <input id="userInput" type="text" placeholder="Ask me anything..." class="flex-1 rounded-lg px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button id="sendBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Send</button>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
    lucide.createIcons();

    // Tabs
    const tabs = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('border-blue-600','text-blue-600'));
        contents.forEach(c => c.classList.add('hidden'));
        tab.classList.add('border-blue-600','text-blue-600');
        document.getElementById(tab.dataset.tab).classList.remove('hidden');
      });
    });

    // Mobile Sidebar
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileSidebar = document.getElementById('sidebar-mobile');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const toggleMobileSidebar = () => {
      mobileSidebar.style.display = mobileSidebar.style.display === 'flex' ? 'none' : 'flex';
    };
    if (mobileMenuButton) mobileMenuButton.addEventListener('click', toggleMobileSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMobileSidebar);

    // Chatbot
    class AdminChatbot {
      constructor() {
        this.toggleBtn = document.getElementById('chatbotToggle');
        this.chatBox = document.getElementById('chatbotBox');
        this.chatContent = document.getElementById('chatContent');
        this.userInput = document.getElementById('userInput');
        this.sendBtn = document.getElementById('sendBtn');
        this.isOpen = false;
        this.init();
      }
      init() {
        this.toggleBtn.addEventListener('click', () => this.toggle());
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.userInput.addEventListener('keypress', e => { if (e.key === 'Enter') this.sendMessage(); });
        this.addMessage("Hello! I'm your facilities assistant. How can I help you today?");
      }
      toggle() {
        this.isOpen = !this.isOpen;
        if (this.isOpen) {
          this.chatBox.classList.remove('opacity-0','scale-95','pointer-events-none');
          this.chatBox.classList.add('opacity-100','scale-100','pointer-events-auto');
          this.userInput.focus();
        } else {
          this.chatBox.classList.add('opacity-0','scale-95','pointer-events-none');
          this.chatBox.classList.remove('opacity-100','scale-100','pointer-events-auto');
        }
      }
      addMessage(message, isUser = false) {
        const wrap = document.createElement('div');
        wrap.className = `flex items-start gap-2.5 ${isUser ? 'flex-row-reverse' : ''}`;
        wrap.innerHTML = `
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold ${isUser?'bg-gray-600':'bg-blue-600'}">
            ${isUser?'U':'AI'}
          </div>
          <div class="p-3 rounded-lg shadow-sm max-w-xs ${isUser?'bg-blue-600 text-white':'bg-white text-gray-800'}">
            <p class="text-sm">${message}</p>
          </div>`;
        this.chatContent.appendChild(wrap);
        this.chatContent.scrollTop = this.chatContent.scrollHeight;
      }
      sendMessage() {
        const msg = this.userInput.value.trim();
        if (!msg) return;
        this.addMessage(msg,true);
        this.userInput.value = '';
        setTimeout(() => this.addMessage(this.getResponse(msg)),800);
      }
      getResponse(msg) {
        const q = msg.toLowerCase();
        if (q.includes('facility')) return `We have <?= $totalFacilities ?> facilities registered.`;
        if (q.includes('reservation')) return `There are <?= $totalReservations ?> reservations.`;
        if (q.includes('maintenance')) return `There are <?= $totalMaintenance ?> maintenance records.`;
        return "I can help with facilities, reservations, and maintenance. What would you like to check?";
      }
    }
    new AdminChatbot();
  });
  </script>
</body>
</html>
<?php $conn->close(); ?>
