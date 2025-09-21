<?php
// Use output buffering to prevent header errors
ob_start();
// Get the current page filename to highlight the active link
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Sidebar</title>
    <link rel="icon" type="image/png" href="../assets/image/logo2.png" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Custom styles for smoother transitions */
        #sidebar {
            transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        #sidebar-toggle i {
            transition: transform 300ms ease-in-out;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="min-h-screen flex bg-gray-50">

    <!-- Sidebar Container -->
    <div id="sidebar" class="bg-gray-800 text-white w-64 min-h-screen flex flex-col overflow-hidden">

        <!-- Logo and Toggle Button -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
            <a href="../../user/dashboard.php" class="flex items-center gap-2">
                <img src="../../assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
                
            </a>
            <button id="sidebar-toggle" class="text-white focus:outline-none">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-2 py-4 space-y-2">
            <!-- Dashboard -->
            <a href="../../user/dashboard.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'dashboard.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="home" class="w-5 h-5"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>

            <!-- Profile -->
            <a href="../../user/profile.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'profile.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span class="sidebar-text">Profile</span>
            </a>

            <!-- Facility List (Employees only) -->
            <a href="../../Views/modules/facility.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facility.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="building" class="w-5 h-5"></i>
                <span class="sidebar-text">Facility List (Employees only)</span>
            </a>

            <!-- Facilities Management -->
            <a href="../../Views/modules/facility_management.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facility_management.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span class="sidebar-text">Facilities Management - Details</span>
            </a>

            <!-- Reservation (Employees only) -->
            <a href="../../Views/modules/reservation_module.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'reservation_module.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="calendar" class="w-5 h-5"></i>
                <span class="sidebar-text">Reservation (Employees only) - Module</span>
            </a>

            <!-- Maintenance -->
            <a href="../../Views/modules/maintenance.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'maintenance.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="wrench" class="w-5 h-5"></i>
                <span class="sidebar-text">Maintenance - Who Reported</span>
            </a>

            <!-- Visitor Logs -->
            <a href="../../user/visitors.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'visitors.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="sidebar-text">Visitor Logs</span>
            </a>

            <!-- My Reservations -->
            <a href="../../user/my_reservations.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'my_reservations.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="calendar" class="w-5 h-5"></i>
                <span class="sidebar-text">My Reservations</span>
            </a>

            <!-- Settings -->
            <a href="../../user/settings.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'settings.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span class="sidebar-text">Settings</span>
            </a>

            <!-- Logout -->
            <a href="../../auth/logout.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-red-600 text-red-300 hover:text-white">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content Area (for layout purposes) -->
    <div class="flex-1 min-h-screen overflow-auto">
        <!-- Your main content goes here -->
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toggleBtn = document.getElementById("sidebar-toggle");
            const sidebar = document.getElementById("sidebar");
            const logoExpanded = document.querySelector(".sidebar-logo-expanded");
            const logoCollapsed = document.querySelector(".sidebar-logo-collapsed");
            const sidebarTextElements = document.querySelectorAll(".sidebar-text");
            const icon = toggleBtn.querySelector("i");

            toggleBtn.addEventListener("click", () => {
                // Toggle sidebar width
                sidebar.classList.toggle("w-64");
                sidebar.classList.toggle("w-20");

                // Toggle visibility of logos and text
                logoExpanded.classList.toggle("hidden");
                logoCollapsed.classList.toggle("hidden");
                sidebarTextElements.forEach(el => el.classList.toggle("hidden"));
                
                // Rotate the toggle icon
                icon.classList.toggle("rotate-180");
            });

            // Initialize Lucide icons
            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        });
    </script>

</body>
</html>