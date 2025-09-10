<?php
ob_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sidebar</title>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />

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
      <a href="/admin/Main/Dashboard.php" class="flex items-center gap-2">
        <img src="../assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
        <img src="../assets/image/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
      </a>
      <button id="sidebar-toggle" class="text-white focus:outline-none">
        <i data-lucide="chevron-left" class="w-5 h-5 transition-transform duration-300"></i>
      </button>
    </div>

    <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto text-gray-300">
      
      <!-- Dashboard -->
      <a href="/admin/Main/Dashboard.php"
         class="flex items-center gap-3 px-3 py-2 rounded  <?= $currentPage === 'Dashboard.php' ? 'bg-gray-700 text-white' : '' ?>">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span class="sidebar-text">Dashboard</span>
      </a>
      
      
<!-- Core dropdown with label side-by-side -->
      <div class="flex items-center px-3 py-2 space-x-3">
       
        <select
          id="coreSelect"
          class="flex-1 bg-gray-700 text-white px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          onchange="if(this.value) window.location.href=this.value;"
        >
        
          <option value="/admin/module-table/core1.php">Core 1</option>
          <option value="/admin/module-table/core2.php">Core 2</option>
        </select>
      </div>

      <!-- HR dropdown with label side-by-side -->
      <div class="flex items-center px-3 py-2 space-x-3">
       
        <select
          id="hrSelect"
          class="flex-1 bg-gray-700 text-white px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          onchange="if(this.value) window.location.href=this.value;"
        >
        
          <option value="/admin/module-table/hr1.php">HR 1</option>
          <option value="/admin/module-table/hr2.php">HR 2</option>
          <option value="/admin/module-table/hr3.php">HR 3</option>
          <option value="/admin/module-table/hr4.php">HR 4</option>
        </select>
      </div>

      <!-- Logistic dropdown with label side-by-side -->
      <div class="flex items-center px-3 py-2 space-x-3">
        <select
          id="logisticSelect"
          class="flex-1 bg-gray-700 text-white px-3 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
          onchange="if(this.value) window.location.href=this.value;"
        >
        
          <option value="/admin/module-table/logistic1.php">Logistic 1</option>
          <option value="/admin/module-table/logistic2.php">Logistic 2</option>
        </select>
      </div>


</nav>
      
    </nav>
  </div>

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
