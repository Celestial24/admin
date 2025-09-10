<?php
// Prevent output before PHP headers are sent
ob_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />

  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
  

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen flex bg-gray-50">


  <div id="sidebar" class="bg-gray-800 text-white w-64 transition-all duration-300 min-h-screen flex flex-col">


    <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
      <a href="../../Main/index.php">
        <img src="../../assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
        <img src="../../assets/image/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
      </a>
      <button id="sidebar-toggle" class="text-white focus:outline-none">
        <i data-lucide="chevron-left" class="w-5 h-5 transition-transform"></i>
      </button>
    </div>

    <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto">
      <a href="../../Main/index.php"
         class="flex items-center gap-3 px-3 py-2 rounded hover:bg-gray-700 <?= $currentPage === 'index.php' ? 'bg-gray-700 text-white' : '' ?>">
        <i data-lucide="home" class="w-5 h-5"></i>
        <span class="sidebar-text">Dashboard</span>
      </a>

    




  </div>


  <div class="flex-1 min-h-screen overflow-auto">

  </div>


  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const toggleBtn = document.getElementById("sidebar-toggle");
      const sidebar = document.getElementById("sidebar");
      const logoExpanded = document.querySelector(".sidebar-logo-expanded");
      const logoCollapsed = document.querySelector(".sidebar-logo-collapsed");
      const sidebarText = document.querySelectorAll(".sidebar-text");

      toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("w-64");
        sidebar.classList.toggle("w-20");
        sidebar.classList.toggle("overflow-hidden");

        logoExpanded.classList.toggle("hidden");
        logoCollapsed.classList.toggle("hidden");

        sidebarText.forEach(el => el.classList.toggle("hidden"));

        const icon = toggleBtn.querySelector("i");
        if (icon) icon.classList.toggle("rotate-180");
      });

      if (typeof lucide !== "undefined" && lucide.createIcons) {
        lucide.createIcons();
      }
    });
  </script>

</body>
</html>
