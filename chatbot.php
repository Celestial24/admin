<?php
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$employeeId = $_SESSION['employee_id'] ?? null;
$roles = $_SESSION['roles'] ?? 'Employee';

// DB Connection (adjust path if needed)
include __DIR__ . '/backend/sql/db.php';
$empConn = $conn;

// Fetch all employees
$employees = [];
$empResult = $empConn->query("SELECT employee_id, first_name, last_name, gender FROM employees");
if ($empResult) {
    $employees = $empResult->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching employees: " . $empConn->error);
}

// Determine logged-in employee's gender (default to 'Both')
$employeeGender = 'Both';
foreach ($employees as $emp) {
    if ($emp['employee_id'] == $employeeId) {
        $employeeGender = $emp['gender'] ?? 'Both';
        break;
    }
}

// Fetch leave types
$leaveTypes = [];
$leaveResult = $empConn->query("SELECT leave_type_id, leave_name, gender FROM leave_types ORDER BY leave_name");
if ($leaveResult) {
    $leaveTypes = $leaveResult->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching leave types: " . $empConn->error);
}
?>

<script>
  // Pass PHP variable to JS securely
  const loggedInUserGender = "<?php echo htmlspecialchars($employeeGender, ENT_QUOTES, 'UTF-8'); ?>";
</script>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Chatbot</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- Chatbot Widget -->
<div class="fixed bottom-6 right-6 z-[9999]">
  <button id="chatbotToggle"
          class="bg-gray-800 hover:bg-gray-700 text-white rounded-full p-2 shadow-lg transition-all duration-300 group relative">
    <img src="/admin/assets/image/logo2.png" alt="Admin Assistant" class="w-12 h-12 object-contain" />
    <span
      class="absolute bottom-full mb-2 right-1/2 transform translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg">
      Admin Assistant
    </span>
  </button>

  <div id="chatbotBox"
       class="fixed bottom-24 right-6 w-80 bg-white border border-gray-200 rounded-xl shadow-xl transition-all duration-300 transform scale-95 opacity-0 pointer-events-none overflow-hidden z-[9999]">
    <div class="p-4 border-b border-gray-200 bg-blue-600 text-white">
      <h3 class="font-semibold">Admin Assistant</h3>
    </div>
    <div id="chatContent" class="p-4 h-64 overflow-y-auto text-sm bg-gray-50 space-y-4"></div>
    <div class="p-3 border-t border-gray-200 bg-white flex gap-2">
      <input id="userInput" type="text" placeholder="Ask me anything..."
             class="flex-1 rounded-lg px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      <button id="sendBtn"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
        Send
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.getElementById('chatbotToggle');
  const chatBox = document.getElementById('chatbotBox');
  const chatContent = document.getElementById('chatContent');
  const userInput = document.getElementById('userInput');
  const sendBtn = document.getElementById('sendBtn');

  // Toggle chatbot open/close
  toggleBtn.addEventListener('click', () => {
    const isOpen = chatBox.classList.contains('opacity-100');
    if (isOpen) {
      chatBox.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
      chatBox.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
    } else {
      chatBox.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
      chatBox.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
    }
  });

  // Save chat messages in localStorage
  function saveChatMessage(text) {
    const history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
    history.push(text);
    localStorage.setItem('chatHistory', JSON.stringify(history));
  }

  // Load chat history from localStorage
  function loadChatHistory() {
    const history = JSON.parse(localStorage.getItem('chatHistory') || '[]');
    chatContent.innerHTML = '';
    history.forEach(msg => {
      const p = document.createElement("p");
      p.textContent = msg;
      chatContent.appendChild(p);
    });
    chatContent.scrollTop = chatContent.scrollHeight;
  }

  // Send message to chatbot API
  async function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    const userMsg = "You: " + message;
    const userP = document.createElement("p");
    userP.textContent = userMsg;
    chatContent.appendChild(userP);
    chatContent.scrollTop = chatContent.scrollHeight;
    saveChatMessage(userMsg);
    userInput.value = "";

    try {
      // TODO: Adjust fetch URL based on your actual file location
      const res = await fetch("Main/chatbot_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message })
      });

      const data = await res.json();

      let botReply = "No response";

      if (typeof data.answer === "object" && data.answer !== null) {
        if (typeof data.answer.text === "string") {
          botReply = data.answer.text;
        } else {
          botReply = JSON.stringify(data.answer);
        }
      } else {
        botReply = data.answer || "No response";
      }

      const botMsg = "Bot: " + botReply;
      const botP = document.createElement("p");
      botP.textContent = botMsg;
      chatContent.appendChild(botP);
      chatContent.scrollTop = chatContent.scrollHeight;
      saveChatMessage(botMsg);
    } catch (err) {
      const botP = document.createElement("p");
      botP.textContent = "Bot: Error occurred.";
      chatContent.appendChild(botP);
      chatContent.scrollTop = chatContent.scrollHeight;
      console.error("Chatbot error:", err);
    }
  }

  sendBtn.addEventListener("click", sendMessage);
  userInput.addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
  });

  loadChatHistory();
});
</script>

</body>
</html>
