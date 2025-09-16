<?php
ob_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Sidebar</title>
    <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        #sidebar { transition: width 300ms cubic-bezier(0.4,0,0.2,1); }
        #sidebar-toggle i { transition: transform 300ms ease-in-out; }
        .rotate-180 { transform: rotate(180deg); }
    </style>
</head>
<body class="min-h-screen flex bg-gray-50">

    <div id="sidebar" class="bg-gray-800 text-white w-64 min-h-screen flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
            <a href="../Main/Dashboard.php" class="flex items-center gap-2">
                <img src="../assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
                <img src="../assets/image/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
            </a>
            <button id="sidebar-toggle" class="text-white focus:outline-none">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
        </div>

        <nav class="flex-1 px-2 py-4 space-y-2">
            <a href="../module-table/facilities.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='facilities.php'?'bg-gray-700':'' ?>">
                <i data-lucide="building" class="w-5 h-5"></i><span class="sidebar-text">Facility List</span>
            </a>
            <a href="../module-table/hotel-booking.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='hotel-booking.php'?'bg-gray-700':'' ?>">
                <i data-lucide="blocks" class="w-5 h-5"></i><span class="sidebar-text">Hotel Booking</span>
            </a>
            <a href="../module-table/visitors.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='visitors.php'?'bg-gray-700':'' ?>">
                <i data-lucide="users" class="w-5 h-5"></i><span class="sidebar-text">Visitor Logs</span>
            </a>
            <a href="../Main/contract.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='contract.php'?'bg-gray-700':'' ?>">
                <i data-lucide="file-text" class="w-5 h-5"></i><span class="sidebar-text">Contract Weka</span>
            </a>
            <a href="../Main/legalmanagement.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='legalmanagement.php'?'bg-gray-700':'' ?>">
                <i data-lucide="gavel" class="w-5 h-5"></i><span class="sidebar-text">Legal Management</span>
            </a>
            <a href="../Main/weka_dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='weka_dashboard.php'?'bg-gray-700':'' ?>">
                <i data-lucide="brain" class="w-5 h-5"></i><span class="sidebar-text">Weka Dashboard</span>
            </a>
            <a href="../Main/document.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='document.php'?'bg-gray-700':'' ?>">
                <i data-lucide="folder-kanban" class="w-5 h-5"></i><span class="sidebar-text">Document Archiver</span>
            </a>
            <a href="../Main/Accout-table.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage==='Accout-table.php'?'bg-gray-700':'' ?>">
                <i data-lucide="blocks" class="w-5 h-5"></i><span class="sidebar-text">Account User</span>
            </a>
        </nav>
    </div>

    <div class="flex-1 min-h-screen overflow-auto"></div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toggleBtn = document.getElementById("sidebar-toggle");
            const sidebar = document.getElementById("sidebar");
            const logoExpanded = document.querySelector(".sidebar-logo-expanded");
            const logoCollapsed = document.querySelector(".sidebar-logo-collapsed");
            const sidebarTextElements = document.querySelectorAll(".sidebar-text");
            const icon = toggleBtn.querySelector("i");

            toggleBtn.addEventListener("click", () => {
                sidebar.classList.toggle("w-64");
                sidebar.classList.toggle("w-20");
                logoExpanded.classList.toggle("hidden");
                logoCollapsed.classList.toggle("hidden");
                sidebarTextElements.forEach(el => el.classList.toggle("hidden"));
                icon.classList.toggle("rotate-180");
            });

            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        });
    </script>

</body>
</html>
