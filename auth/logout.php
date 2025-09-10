<?php
session_start();
require_once '../backend/sql/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = 'admin';

if ($user_id) {
    $stmt = $conn->prepare("SELECT username FROM admin_user WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $username = $user['username'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-100">

<div class="relative inline-block text-left">
  <button id="userDropdownToggle" class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none cursor-pointer select-text">
    <img src="../assets/image/logo2.png" alt="profile picture" class="w-6 h-6 rounded-full object-cover" />
    <span id="usernameDisplay" class="capitalize"><?= htmlspecialchars($username) ?></span>
    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
  </button>

  <div id="userDropdown" class="absolute right-0 mt-2 w-44 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 z-50">
    <a href="../Views/Account.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
    <a href="../Main/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
    <a href="../auth/logout.php" id="logoutBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
  </div>
  
  <div id="logoutModal" class="absolute right-0 mt-2 w-44 bg-white p-4 rounded-md shadow-lg hidden z-50">
    <h3 class="text-sm font-semibold mb-4">Are you sure you want to logout?</h3>
    <div class="flex justify-between">
      <button id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
      <a href="O" id="confirmBtn" class="px-4 py-2 bg-red-500 text-white rounded-md">Logout</a>
    </div>
  </div>
</div>

<script>
  const toggleBtn = document.getElementById('userDropdownToggle');
  const dropdown = document.getElementById('userDropdown');
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutModal = document.getElementById('logoutModal');
  const cancelBtn = document.getElementById('cancelBtn');

  toggleBtn.addEventListener('click', () => {
    dropdown.classList.toggle('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!toggleBtn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });

  logoutBtn.addEventListener('click', (e) => {
    e.preventDefault();
    logoutModal.classList.remove('hidden');
  });

  cancelBtn.addEventListener('click', () => {
    logoutModal.classList.add('hidden');
  });

  window.addEventListener('click', (e) => {
    if (e.target === logoutModal) {
      logoutModal.classList.add('hidden');
    }
  });
</script>

</body>
</html>