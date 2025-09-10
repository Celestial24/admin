<?php
ob_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sidebar</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    /* Rotate icon smoothly */
    .rotate-180 {
      transform: rotate(180deg);
      transition: transform 0.3s ease;
    }
    #sidebar {
      transition-property: width;
      transition-duration: 300ms;
      transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    }
  </style>
</head>
<body class="min-h-screen flex bg-gray-50">

  <div id="sidebar" class="bg-gray-800 text-white w-64 min-h-screen flex flex-col transition-width duration-300 ease-in-out overflow-hidden">

    <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
      <a href="http://localhost/admin/super-admin/dashboard.php" class="flex items-center gap-2">
        <img src="/admin/assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
        <img src="/admin/assets/image/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
      </a>
      <button id="sidebar-toggle" class="text-white focus:outline-none">
        <i data-lucide="chevron-left" class="w-5 h-5 transition-transform duration-300"></i>
      </button>
    </div>

<nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto">

  <a href="http://localhost/admin/user/dashboard.php"
     class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'superadmin_dashboard.php' ? 'bg-gray-700 text-white' : '' ?>">
    <i data-lucide="home" class="w-5 h-5"></i>
    <span class="sidebar-text">Dashboard</span>
  </a>


  <a href="http://localhost/admin/user/modules/Facilities.php"
     class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'facilities_reservation.php' ? 'bg-gray-700 text-white' : '' ?>">
    <i data-lucide="building" class="w-5 h-5"></i>
    <span class="sidebar-text">Facilities Booking</span>
  </a>


  <a href="document_management.php"
     class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'document_management.php' ? 'bg-gray-700 text-white' : '' ?>">
    <i data-lucide="archive" class="w-5 h-5"></i>
    <span class="sidebar-text">Document Archive</span>
  </a>

  <a href="http://localhost/admin/user/modules/Vistors.php"
     class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'visitor_management.php' ? 'bg-gray-700 text-white' : '' ?>">
    <i data-lucide="user" class="w-5 h-5"></i>
    <span class="sidebar-text">Visitor Logs</span>
  </a>

  <a href="user_management.php"
     class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'user_management.php' ? 'bg-gray-700 text-white' : '' ?>">
    <i data-lucide="users" class="w-5 h-5"></i>
    <span class="sidebar-text">Users</span>
  </a>

  <a href="integration_management.php"
     class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'integration_management.php' ? 'bg-gray-700 text-white' : '' ?>">
    <i data-lucide="plug" class="w-5 h-5"></i>
    <span class="sidebar-text">Integrations</span>
  </a>

</nav>



  <div class="flex-1 min-h-screen overflow-auto">
    <!-- Main content -->
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const toggleBtn = document.getElementById("sidebar-toggle");
      const sidebar = document.getElementById("sidebar");
      const logoExpanded = document.querySelector(".sidebar-logo-expanded");
      const logoCollapsed = document.querySelector(".sidebar-logo-collapsed");
      const sidebarText = document.querySelectorAll(".sidebar-text");
      const icon = toggleBtn.querySelector("i");

      toggleBtn.addEventListener("click", () => {
        // Toggle width classes
        sidebar.classList.toggle("w-64");
        sidebar.classList.toggle("w-20");
        sidebar.classList.toggle("overflow-hidden");

        logoExpanded.classList.toggle("hidden");
        logoCollapsed.classList.toggle("hidden");

        sidebarText.forEach(el => el.classList.toggle("hidden"));

        if (icon) icon.classList.toggle("rotate-180");
      });

      if (typeof lucide !== "undefined" && lucide.createIcons) {
        lucide.createIcons();
      }
    });
  </script>

</body>
</html>
