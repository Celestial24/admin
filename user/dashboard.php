<?php
session_start();
include '../backend/sql/db.php';

// Redirect if not authenticated
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'] ?? $_SESSION['id'];

// Fetch user info
$user = null;
if ($stmt = $conn->prepare("SELECT id, name, email, department, role, created_at FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$userName = $user['name'] ?? 'User';
$department = $user['department'] ?? 'Unknown';

// Fetch counts securely
function fetchCount(mysqli $conn, string $table, string $idCol, string $nameCol, int $userId, string $userName): int {
    $allowedTables = ['weka_contracts', 'visitors'];
    if (!in_array($table, $allowedTables, true)) return 0;

    $tableEscaped = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$tableEscaped'");
    if (!$result || $result->num_rows === 0) return 0;

    $count = 0;
    $sql = "SELECT COUNT(*) AS count FROM `$tableEscaped` WHERE `$idCol` = ? OR `$nameCol` = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $userId, $userName);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $count = (int)($res['count'] ?? 0);
        $stmt->close();
    }
    return $count;
}

$contractCount = fetchCount($conn, 'weka_contracts', 'employee_id', 'employee_name', $userId, $userName);
$visitorCount = fetchCount($conn, 'visitors', 'user_id', 'checked_in_by', $userId, $userName);
$recentActivity = $contractCount;

// Count users in the same department
$deptUsers = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE department = ?")) {
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $deptUsers = (int)($res['count'] ?? 0);
    $stmt->close();
}

// Fetch recent contracts
$recentContracts = [];
if ($conn->query("SHOW TABLES LIKE 'weka_contracts'")->num_rows > 0) {
    $sql = "SELECT id, employee_name, created_at FROM weka_contracts WHERE employee_id = ? OR employee_name = ? ORDER BY created_at DESC LIMIT 5";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $userId, $userName);
        $stmt->execute();
        $recentContracts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png">
  <style>
    html, body { height: 100%; overflow-x: hidden; }
    .card-hover { transition: all 0.3s ease; cursor: default; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); cursor: pointer; }
    .pulse-animation { animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
  </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden">
  <div class="flex h-full">
    <aside class="w-64 bg-white shadow-lg flex-shrink-0">
      <?php include '../Components/sidebar/sidebar_user.php'; ?>
    </aside>
    <main class="flex-1 flex flex-col overflow-hidden">
      <section class="flex-1 overflow-y-auto w-full py-4 px-6 space-y-6">
        <header class="flex items-center justify-between border-b pb-4">
          <h2 class="text-xl font-semibold text-gray-800">User Dashboard</h2>
          <?php include '../profile.php'; ?>
        </header>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow-md p-6 card-hover">
            <p class="text-sm font-medium text-gray-600">My Contracts</p>
            <p class="text-2xl font-bold text-gray-900"><?= (int)$contractCount ?></p>
          </div>
          <div class="bg-white rounded-lg shadow-md p-6 card-hover">
            <p class="text-sm font-medium text-gray-600">Visitor Logs</p>
            <p class="text-2xl font-bold text-gray-900"><?= (int)$visitorCount ?></p>
          </div>
          <div class="bg-white rounded-lg shadow-md p-6 card-hover">
            <p class="text-sm font-medium text-gray-600">Recent Activity</p>
            <p class="text-2xl font-bold text-gray-900"><?= (int)$recentActivity ?></p>
          </div>
          <div class="bg-white rounded-lg shadow-md p-6 card-hover">
            <p class="text-sm font-medium text-gray-600">Department</p>
            <p class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($department) ?></p>
          </div>
        </div>

        <!-- Date & Time -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 flex justify-between items-center">
          <p class="text-gray-600 font-medium">Current Date:</p>
          <p id="currentDate" class="text-gray-900 font-bold"></p>
          <p class="text-gray-600 font-medium">Current Time:</p>
          <p id="currentTime" class="text-gray-900 font-bold"></p>
        </div>

        <!-- Recent Contracts -->
        <?php if (!empty($recentContracts)): ?>
          <section>
            <h3 class="text-lg font-semibold mb-3">Recent Contracts</h3>
            <ul class="space-y-2">
              <?php foreach ($recentContracts as $contract): ?>
                <li class="bg-white p-4 rounded shadow-md" tabindex="0">
                  <p><strong>Contract ID:</strong> <?= htmlspecialchars($contract['id']) ?></p>
                  <p><strong>Employee:</strong> <?= htmlspecialchars($contract['employee_name']) ?></p>
                  <p><strong>Created:</strong> <?= htmlspecialchars($contract['created_at']) ?></p>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>
      </section>
    </main>
  </div>

<!-- Chatbot -->
<div class="fixed bottom-6 right-6 z-50 flex flex-col-reverse items-end space-y-2 space-y-reverse">
  <button id="chatbotToggle" class="bg-gray-800 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg" aria-label="Toggle chatbot">
    <img src="../assets/image/logo2.png" alt="Assistant" class="w-10 h-10">
  </button>
  <div id="chatbotBox" class="hidden w-80 bg-white border border-gray-200 rounded-xl shadow-xl flex flex-col overflow-hidden" role="region" aria-live="polite" aria-atomic="true">
    <div class="bg-blue-600 text-white px-4 py-2 rounded-t-xl font-semibold">Admin Assistant</div>
    <div id="chatContent" class="p-4 flex-grow overflow-y-auto text-sm bg-gray-50 flex flex-col space-y-3" style="max-height: 300px;"></div>
    <div class="p-3 border-t border-gray-200 bg-white flex gap-2 flex-shrink-0">
      <input id="userInput" type="text" placeholder="Ask me anything..." class="flex-1 rounded-lg px-3 py-2 border focus:outline-none focus:ring-2 focus:ring-blue-600">
      <button id="sendBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Send</button>
    </div>
  </div>
</div>

<script>
  // Chatbot
  document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('chatbotToggle');
    const chatBox = document.getElementById('chatbotBox');
    const sendBtn = document.getElementById('sendBtn');
    const userInput = document.getElementById('userInput');
    const chatContent = document.getElementById('chatContent');

    toggleBtn.addEventListener('click', () => {
      chatBox.classList.toggle('hidden');
      if (!chatBox.classList.contains('hidden')) userInput.focus();
    });

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', e => { if(e.key==='Enter') sendMessage(); });

    function addMessage(message, isUser=false) {
      const msgDiv = document.createElement('div');
      msgDiv.className = `p-3 rounded-lg shadow max-w-xs ${isUser?'bg-blue-600 text-white self-end':'bg-white text-gray-900 self-start'}`;
      if(typeof message==='object') message = JSON.stringify(message,null,2);
      msgDiv.textContent = message;
      chatContent.appendChild(msgDiv);
      chatContent.scrollTop = chatContent.scrollHeight;
    }

    function sendMessage() {
      const msg = userInput.value.trim();
      if(!msg) return;
      addMessage(`You: ${msg}`, true);
      userInput.value = '';
      setTimeout(()=>{ addMessage("I'm your assistant. How can I help?"); }, 500);
    }
  });

  // Date & Time
  function updateDateTime() {
    const now = new Date();
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true });
  }
  setInterval(updateDateTime, 1000);
  updateDateTime();
</script>
</body>
</html>
