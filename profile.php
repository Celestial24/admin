<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/backend/sql/db.php';

// Set display name based on user type
$username = 'User'; // default for regular users

if (!empty($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
    
    if ($user_type === 'admin') {
        $username = 'Admin';
    } elseif ($user_type === 'user') {
        $username = 'User';
    }
}
?>

<!-- User Dropdown -->
<div class="relative inline-block text-left">
  <button id="userDropdownToggle" class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
    <img src="/admin/assets/image/logo2.png" alt="Profile" class="w-6 h-6 rounded-full object-cover" />
    <span class="capitalize"><?= htmlspecialchars($username) ?></span>
    <svg id="dropdownArrow" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
  </button>

  <!-- Dropdown Menu -->
  <div id="userDropdown" class="hidden absolute right-0 mt-2 w-44 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 z-50">
    <a href="/admin/Views/Account.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
    <a href="/admin/Main/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
    <a href="#" id="logoutBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
  </div>

  <!-- Logout Modal -->
  <div id="logoutModal" class="hidden absolute right-0 mt-2 w-64 bg-white p-4 rounded-md shadow-lg z-50">
    <h3 class="text-sm font-semibold mb-4">Are you sure you want to logout?</h3>
    <div class="flex justify-end gap-2">
      <button id="cancelBtn" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
      <a href="/admin/auth/login.php" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">Logout</a>
    </div>
  </div>
</div>

<script>
  const toggleBtn = document.getElementById('userDropdownToggle');
  const dropdown = document.getElementById('userDropdown');
  const arrow = document.getElementById('dropdownArrow');
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutModal = document.getElementById('logoutModal');
  const cancelBtn = document.getElementById('cancelBtn');

  let isDropdownOpen = false;

  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    isDropdownOpen = !isDropdownOpen;
    dropdown.classList.toggle('hidden', !isDropdownOpen);
    arrow.classList.toggle('rotate-180', isDropdownOpen);
    logoutModal.classList.add('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!toggleBtn.contains(e.target) && !dropdown.contains(e.target) && !logoutModal.contains(e.target)) {
      isDropdownOpen = false;
      dropdown.classList.add('hidden');
      arrow.classList.remove('rotate-180');
      logoutModal.classList.add('hidden');
    }
  });

  logoutBtn.addEventListener('click', (e) => {
    e.preventDefault();
    logoutModal.classList.remove('hidden');
  });

  cancelBtn.addEventListener('click', () => {
    logoutModal.classList.add('hidden');
  });
</script>
