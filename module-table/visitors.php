<?php
session_start();

// -- DATABASE CONNECTION --
$host = "localhost";
$user = "admin_visitors";
$pass = "123";
$dbname = "admin_visitors";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -- EMPLOYEE MONITORING STATISTICS --
$stats = [];
$stats['total_visitors'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions")->fetch_assoc()['count'];
$stats['active_visitors'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE checked_out = 0")->fetch_assoc()['count'];
$stats['checked_out_today'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(checked_out_at) = CURDATE()")->fetch_assoc()['count'];
$stats['checked_in_today'] = $conn->query("SELECT COUNT(*) as count FROM guest_submissions WHERE DATE(submitted_at) = CURDATE()")->fetch_assoc()['count'];

// -- HANDLE CHECK-IN FORM SUBMISSION --
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_checkin'])) {
    if (!empty($_POST['fullName']) && !empty($_POST['email']) && isset($_POST['agreement'])) {
        
        $fullName = $_POST['fullName'];
        $email = $_POST['email'];
        $phone = $_POST['phone'] ?? null;
        $notes = $_POST['notes'] ?? null;
        $agreement = 1;

        $stmt = $conn->prepare(
            "INSERT INTO guest_submissions (full_name, email, phone, notes, agreement, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("ssssi", $fullName, $email, $phone, $notes, $agreement);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// -- HANDLE CHECK-OUT ACTION --
if (isset($_GET['action']) && $_GET['action'] == 'checkout' && isset($_GET['id'])) {
    $visitorId = (int)$_GET['id'];
    
    $stmt = $conn->prepare("UPDATE guest_submissions SET checked_out = 1, checked_out_at = NOW() WHERE id = ? AND checked_out = 0");
    $stmt->bind_param("i", $visitorId);
    $stmt->execute();
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// -- FETCH VISITOR LOGS FOR DISPLAY WITH FILTERING --
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereClause = "1=1";
if ($filter === 'active') {
    $whereClause = "checked_out = 0";
} elseif ($filter === 'checked_out') {
    $whereClause = "checked_out = 1";
} elseif ($filter === 'today') {
    $whereClause = "DATE(submitted_at) = CURDATE()";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereClause .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

$visitorLogsResult = $conn->query("SELECT * FROM guest_submissions WHERE $whereClause ORDER BY submitted_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Visitor Monitoring Dashboard - Employee</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen">

  <!-- Sidebar -->
  <aside class="w-64 fixed left-0 top-0 h-full bg-white shadow-md z-10">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 flex-1 flex flex-col">
    <header class="px-6 py-4 bg-white border-b shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Visitor Monitoring Dashboard</h1>
          <p class="text-sm text-gray-500 mt-1">Real-time Visitor Tracking & Management</p>
        </div>
        <div class="flex items-center gap-4">
            <button id="openCheckinModalBtn" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center gap-2">
                ➕ Manual Check-in
            </button>
            <button id="refreshDataBtn" class="bg-green-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 flex items-center gap-2">
                🔄 Refresh
            </button>
            <?php include '../profile.php'; ?>
        </div>
      </div>
    </header>

    <div class="flex-1 p-6 overflow-y-auto">
      <!-- Monitoring Statistics Dashboard -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Total Visitors</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['total_visitors'] ?></p>
            </div>
            <div class="text-blue-500 text-3xl">👥</div>
          </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Active Visitors</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['active_visitors'] ?></p>
            </div>
            <div class="text-green-500 text-3xl">✅</div>
          </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Checked In Today</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['checked_in_today'] ?></p>
            </div>
            <div class="text-yellow-500 text-3xl">📥</div>
          </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-600">Checked Out Today</p>
              <p class="text-2xl font-bold text-gray-900"><?= $stats['checked_out_today'] ?></p>
            </div>
            <div class="text-red-500 text-3xl">📤</div>
          </div>
        </div>
      </div>

      <!-- Filter and Search Controls -->
      <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
          <div class="flex flex-col md:flex-row gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
              <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-2">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Visitors</option>
                <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="checked_out" <?= $filter === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today Only</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search Visitors</label>
              <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." 
                     value="<?= htmlspecialchars($search) ?>"
                     class="border border-gray-300 rounded-md px-3 py-2 w-64">
            </div>
          </div>
          
          <div class="flex gap-2">
            <button id="applyFiltersBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
              🔍 Apply Filters
            </button>
            <button id="clearFiltersBtn" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
              🗑️ Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Visitor Logs -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Visitor Logs</h2>
            <div class="flex items-center gap-4">
              <span class="text-sm text-gray-500">
                Showing <?= $visitorLogsResult ? $visitorLogsResult->num_rows : 0 ?> records
              </span>
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span class="text-xs text-gray-600">Active</span>
                <div class="w-3 h-3 bg-gray-400 rounded-full ml-2"></div>
                <span class="text-xs text-gray-600">Checked Out</span>
              </div>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
              <tr>
                <th class="px-6 py-3">ID</th>
                <th class="px-6 py-3">Visitor Info</th>
                <th class="px-6 py-3">Contact</th>
                <th class="px-6 py-3">Check-in Time</th>
                <th class="px-6 py-3">Duration</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Notes</th>
                <th class="px-6 py-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($visitorLogsResult && $visitorLogsResult->num_rows > 0) : ?>
                <?php while ($row = $visitorLogsResult->fetch_assoc()) : ?>
                  <?php
                  $checkinTime = strtotime($row['submitted_at']);
                  $checkoutTime = $row['checked_out_at'] ? strtotime($row['checked_out_at']) : time();
                  $duration = $checkoutTime - $checkinTime;
                  $hours = floor($duration / 3600);
                  $minutes = floor(($duration % 3600) / 60);
                  $durationText = $row['checked_out'] ? sprintf('%dh %dm', $hours, $minutes) : 'Ongoing';
                  ?>
                  <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900"><?= sprintf('%03d', $row['id']) ?></td>
                    <td class="px-6 py-4">
                      <div>
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></div>
                        <div class="text-sm text-gray-500">ID: <?= sprintf('%03d', $row['id']) ?></div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div>
                        <div class="text-gray-900"><?= htmlspecialchars($row['email']) ?></div>
                        <?php if ($row['phone']): ?>
                          <div class="text-sm text-gray-500"><?= htmlspecialchars($row['phone']) ?></div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-green-600 font-medium">
                      <?= date('Y-m-d H:i', strtotime($row['submitted_at'])) ?>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm <?= $row['checked_out'] ? 'text-gray-600' : 'text-green-600 font-medium' ?>">
                        <?= $durationText ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <?php if (!$row['checked_out']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                          Active
                        </span>
                      <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          <div class="w-2 h-2 bg-gray-500 rounded-full mr-1"></div>
                          Checked Out
                        </span>
                      <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                      <div class="max-w-xs truncate">
                        <?= htmlspecialchars($row['notes'] ?: 'No notes') ?>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <?php if (!$row['checked_out']): ?>
                        <a href="?action=checkout&id=<?= $row['id'] ?>" 
                           class="font-medium text-red-600 hover:text-red-800 hover:underline">
                          Check Out
                        </a>
                      <?php else: ?>
                        <span class="font-medium text-gray-400">Completed</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else : ?>
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
    </div>
  </main>

<!-- Check-in Modal -->
<div id="checkinModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 hidden z-50">
    <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-lg">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Visitor Check-in Form</h2>
            <button id="closeCheckinModalBtn" class="text-gray-400 hover:text-gray-600 text-3xl">&times;</button>
        </div>
        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="submit_checkin" value="1">
            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input type="text" name="fullName" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address *</label>
                <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Special Notes</label>
                <textarea name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
            </div>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <input id="agreement" name="agreement" type="checkbox" required class="h-4 w-4 text-blue-600 border-gray-300 rounded mt-1">
                    <label for="agreement" class="ml-3 text-sm text-gray-700">
                        I agree to the Terms & Conditions <span class="text-red-500">*</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-4 pt-4">
                <button type="button" id="clearFormBtn" class="bg-gray-200 px-4 py-2 rounded-lg">Clear</button>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">→ Check In</button>
            </div>
        </form>
    </div>
</div>
<!-- ✅ Chatbot: Toggle Button + Chat Window -->
<div class="fixed bottom-6 right-6 z-50">
    <!-- ✅ Toggle Button -->
    <button id="chatbotToggle"
        class="bg-gray-800 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg transition-all duration-300 group relative">
        <img src="/admin/assets/image/logo2.png" alt="Admin Assistant" class="w-12 h-12 object-contain">
        <span class="absolute bottom-full mb-2 right-1/2 transform translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg">
            Admin Assistant
        </span>
    </button>

    <!-- ✅ Chatbot Window -->
    <div id="chatbotBox"
        class="fixed bottom-24 right-6 w-[420px] bg-white border border-gray-200 rounded-xl shadow-xl opacity-0 scale-95 pointer-events-none transition-all duration-300 overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-blue-600 text-white">
            <h3 class="font-semibold">Admin Assistant</h3>
            <button id="chatbotClose" class="text-white hover:text-gray-200 text-xl leading-none">×</button>
        </div>

        <!-- Chat Content -->
        <div id="chatContent" class="p-4 h-64 overflow-y-auto text-sm bg-gray-50 space-y-4">
            <!-- Messages will be added here -->
        </div>

        <!-- Quick Action Buttons -->
        <div class="p-3 border-t border-gray-200 bg-white">
            <div class="flex flex-wrap gap-2 mb-3">
                <button class="quickBtn bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-lg text-xs" data-action="facilities">View Facilities</button>
                <button class="quickBtn bg-green-100 hover:bg-green-200 text-green-800 px-3 py-1 rounded-lg text-xs" data-action="reservations">Check Reservations</button>
                <button class="quickBtn bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded-lg text-xs" data-action="maintenance">Maintenance Log</button>
            </div>
        </div>

        <!-- Input Field -->
        <div class="p-3 border-t border-gray-200 bg-white flex gap-2">
            <input id="userInput" type="text" placeholder="Ask me anything..."
                class="flex-1 rounded-lg px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button id="sendBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Send</button>
        </div>
    </div>
</div>

<!-- ✅ Chatbot Script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    class VisitorChatbot {
        constructor() {
            this.elements = {
                toggleBtn: document.getElementById('chatbotToggle'),
                closeBtn: document.getElementById('chatbotClose'),
                chatBox: document.getElementById('chatbotBox'),
                chatContent: document.getElementById('chatContent'),
                userInput: document.getElementById('userInput'),
                sendBtn: document.getElementById('sendBtn'),
            };
            this.state = { isOpen: false };
            this.responses = [
                "👋 Welcome! You can check-in by filling up the visitor form.",
                "📋 Don’t forget to provide your full name and purpose of visit.",
                "🕒 Visiting hours are from 8 AM to 6 PM.",
                "✅ Your visitor log will be saved in the system. Would you like me to guide you?"
            ];
            this.init();
        }

        init() {
            this.bindEvents();
            this.addMessage("Hello! I'm your visitor assistant. How can I help you today?");
        }

        bindEvents() {
            this.elements.toggleBtn.addEventListener('click', () => this.toggle());
            this.elements.closeBtn.addEventListener('click', () => this.toggle(false));
            this.elements.sendBtn.addEventListener('click', () => this.sendMessage());
            this.elements.userInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') this.sendMessage();
            });

            document.querySelectorAll('.quickBtn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const action = e.target.dataset.action;
                    this.addMessage(`User clicked: ${action}`, true);
                    this.addMessage(this.getAIResponse(action));
                });
            });
        }

        toggle(force = null) {
            this.state.isOpen = force !== null ? force : !this.state.isOpen;
            const box = this.elements.chatBox;
            if (this.state.isOpen) {
                box.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                box.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
                this.elements.userInput.focus();
            } else {
                box.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
                box.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            }
        }

        addMessage(message, isUser = false) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex items-start gap-2.5 ${isUser ? 'flex-row-reverse' : ''}`;
            const avatar = `
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold ${isUser ? 'bg-gray-600' : 'bg-blue-600'}">
                    ${isUser ? 'U' : 'VA'}
                </div>`;
            const content = `
                <div class="p-3 rounded-lg shadow-sm max-w-xs ${isUser ? 'bg-blue-600 text-white' : 'bg-white text-gray-800'}">
                    <p class="text-sm">${message}</p>
                </div>`;
            wrapper.innerHTML = avatar + content;
            this.elements.chatContent.appendChild(wrapper);
            this.elements.chatContent.scrollTop = this.elements.chatContent.scrollHeight;
        }

        sendMessage() {
            const msg = this.elements.userInput.value.trim();
            if (!msg) return;
            this.addMessage(msg, true);
            this.elements.userInput.value = '';
            setTimeout(() => {
                this.addMessage(this.getAIResponse(msg));
            }, 800);
        }

        getAIResponse(msg) {
            const lower = msg.toLowerCase();
            if (lower.includes('check in') || lower.includes('log')) return "📝 You can check-in at the front desk or online visitor form.";
            if (lower.includes('hours') || lower.includes('time')) return "⏰ Visiting hours are from 8 AM to 6 PM.";
            if (lower.includes('id') || lower.includes('requirement')) return "🪪 A valid ID is required for all visitors.";
            if (lower.includes('facilities')) return "🏢 You can view the list of facilities in the Facilities section.";
            if (lower.includes('reservations')) return "📅 You can view and manage reservations in the Reservation tab.";
            if (lower.includes('maintenance')) return "🛠️ View logs and schedule maintenance for facilities.";
            return this.responses[Math.floor(Math.random() * this.responses.length)];
        }
    }

    new VisitorChatbot();

    // Visitor Monitoring Features
    const refreshDataBtn = document.getElementById('refreshDataBtn');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const openCheckinModalBtn = document.getElementById('openCheckinModalBtn');
    const closeCheckinModalBtn = document.getElementById('closeCheckinModalBtn');
    const checkinModal = document.getElementById('checkinModal');
    const clearFormBtn = document.getElementById('clearFormBtn');

    // Modal functionality
    openCheckinModalBtn?.addEventListener('click', function() {
        checkinModal.classList.remove('hidden');
    });

    closeCheckinModalBtn?.addEventListener('click', function() {
        checkinModal.classList.add('hidden');
    });

    clearFormBtn?.addEventListener('click', function() {
        const form = checkinModal.querySelector('form');
        form.reset();
    });

    // Close modal when clicking outside
    checkinModal?.addEventListener('click', function(e) {
        if (e.target === checkinModal) {
            checkinModal.classList.add('hidden');
        }
    });

    // Refresh data functionality
    refreshDataBtn?.addEventListener('click', function() {
        this.innerHTML = '<i class="mr-2">🔄</i>Refreshing...';
        
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    });

    // Apply filters functionality
    applyFiltersBtn?.addEventListener('click', function() {
        const filter = statusFilter.value;
        const search = searchInput.value.trim();
        
        let url = window.location.pathname;
        const params = new URLSearchParams();
        
        if (filter !== 'all') {
            params.append('filter', filter);
        }
        
        if (search) {
            params.append('search', search);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        window.location.href = url;
    });

    // Clear filters functionality
    clearFiltersBtn?.addEventListener('click', function() {
        statusFilter.value = 'all';
        searchInput.value = '';
        window.location.href = window.location.pathname;
    });

    // Auto-apply filters on Enter key in search
    searchInput?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyFiltersBtn.click();
        }
    });

    // Auto-refresh data every 30 seconds for real-time monitoring
    setInterval(() => {
        // Only auto-refresh if no user interaction in the last 10 seconds
        if (document.hidden === false) {
            const lastInteraction = localStorage.getItem('lastUserInteraction');
            const now = Date.now();
            
            if (!lastInteraction || (now - parseInt(lastInteraction)) > 10000) {
                // Silently refresh the page to update data
                window.location.reload();
            }
        }
    }, 30000);

    // Track user interactions to prevent auto-refresh during active use
    ['click', 'keypress', 'scroll'].forEach(event => {
        document.addEventListener(event, () => {
            localStorage.setItem('lastUserInteraction', Date.now().toString());
        });
    });

    // Add monitoring alerts for high visitor counts
    const activeVisitors = <?= $stats['active_visitors'] ?>;
    if (activeVisitors > 10) {
        // Create a toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 bg-blue-100 text-blue-700 border border-blue-300';
        toast.innerHTML = `
          <div class="flex items-center">
            <span class="mr-2">⚠️</span>
            High visitor activity: ${activeVisitors} active visitors
          </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
});
</script>

