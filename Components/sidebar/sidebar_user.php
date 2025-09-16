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
    <title>Admin Sidebar</title>
    <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />

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
            <a href="/user/dashboard.php" class="flex items-center gap-2">
                <img src="../assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
                <img src="../assets/image/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
            </a>
            <button id="sidebar-toggle" class="text-white focus:outline-none">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-2 py-4 space-y-2">
            <!-- Facility List -->
           <!-- Facility Reservation -->
            <a href="../Views/modules/facility.php" 
            class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facilities.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="building" class="w-5 h-5"></i>
                <span class="sidebar-text">Facility Reservation</span>
            </a>

            <!-- Hotel Booking (updated to correct file if needed) -->
            <a href="../Main/booking.php" 
            class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'hotel.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="building" class="w-5 h-5"></i>
                <span class="sidebar-text">Hotel Booking</span>
            </a>

            <!-- Visitor Logs -->
            <a href=../user/Visitors.php" 
            class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'visitors.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="sidebar-text">Visitor Logs</span>
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