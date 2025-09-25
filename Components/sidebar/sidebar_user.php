<?php
// Get the current page filename to highlight the active link
$currentPage = basename($_SERVER['PHP_SELF']);
?>

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
    .facility-modules {
        display: none;
    }
    .facility-modules.show {
        display: block;
    }
</style>

<!-- Sidebar Container -->
<div id="sidebar" class="bg-gray-800 text-white w-64 min-h-screen flex flex-col overflow-hidden">

        <!-- Logo and Toggle Button -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
            <a href="/admin/Main/Dashboard.php" class="flex items-center gap-2" aria-label="Logo">
                <img src="../../assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
                
            </a>
            <button id="sidebar-toggle" class="text-white focus:outline-none">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-2 py-4 space-y-2">
            <!-- Dashboard -->
            <a href="/admin/Main/Dashboard.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Dashboard.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="home" class="w-5 h-5"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>

  

            <!-- Facilities Toggle Button -->
            <button id="facilityToggle" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 w-full text-left">
                <i data-lucide="building" class="w-5 h-5"></i>
                <span class="sidebar-text">Facility Dashboard</span>
                <i data-lucide="chevron-down" class="w-4 h-4 ml-auto facility-arrow"></i>
            </button>

            <!-- Facility Modules (Hidden by default) -->
            <div id="facilityModules" class="facility-modules ml-4 space-y-1">
                <!-- Facility List (Employees only) -->
                <a href="/admin/Views/modules/facility.php" 
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facility.php' ? 'bg-gray-700' : '' ?>">
                    <i data-lucide="list" class="w-4 h-4"></i>
                    <span class="sidebar-text text-sm">Facilities Employee</span>
                </a>

                <!-- Reservation (Employees only) -->
                <a href="/admin/Views/modules/reservation.php" 
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'reservation.php' ? 'bg-gray-700' : '' ?>">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span class="sidebar-text text-sm">Facilities Reservations</span>
                </a>

                <!-- Maintenance -->
                <a href="/admin/Views/modules/maintenance.php" 
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'maintenance.php' ? 'bg-gray-700' : '' ?>">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                    <span class="sidebar-text text-sm">Maintenance </span>
                </a>
            </div>

            <!-- Legal Management Toggle Button -->
            <button id="legalToggle" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 w-full text-left">
                <i data-lucide="gavel" class="w-5 h-5"></i>
                <span class="sidebar-text">Legal Management</span>
                <i data-lucide="chevron-down" class="w-4 h-4 ml-auto legal-arrow"></i>
            </button>

            <!-- Legal Management Modules (Hidden by default) -->
            <div id="legalModules" class="facility-modules ml-4 space-y-1">
                <a href="/admin/Main/legalmanagement.php" 
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'legalmanagement.php' ? 'bg-gray-700' : '' ?>">
                    <i data-lucide="layout" class="w-4 h-4"></i>
                    <span class="sidebar-text text-sm">Legal Documents</span>
                </a>
                <a href="/admin/Main/contract.php" 
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'contract.php' ? 'bg-gray-700' : '' ?>">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span class="sidebar-text text-sm">Contract & Weka</span>
                </a>
            </div>

            <!-- Visitor Logs -->
            <a href="/admin/Main/Visitors.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Visitors.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="sidebar-text">Visitor Logs</span>
            </a>

            

            <!-- Document Archiver -->
            <a href="/admin/Main/document.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="folder-kanban" class="w-5 h-5"></i>
                <span class="sidebar-text">Document Archiver</span>
            </a>

            

            <!-- Logout -->
            <a href="../../auth/logout.php" 
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-red-600 text-red-300 hover:text-white">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </nav>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const toggleBtn = document.getElementById("sidebar-toggle");
        const sidebar = document.getElementById("sidebar");
        const logoExpanded = document.querySelector(".sidebar-logo-expanded");
        const logoCollapsed = document.querySelector(".sidebar-logo-collapsed");
        const sidebarTextElements = document.querySelectorAll(".sidebar-text");
        const icon = toggleBtn.querySelector("i");
        
        // Facility toggle functionality
        const facilityToggle = document.getElementById("facilityToggle");
        const facilityModules = document.getElementById("facilityModules");
        const facilityArrow = document.querySelector(".facility-arrow");
        
        // Legal toggle functionality
        const legalToggle = document.getElementById("legalToggle");
        const legalModules = document.getElementById("legalModules");
        const legalArrow = document.querySelector(".legal-arrow");

        // Sidebar toggle functionality
        if (toggleBtn) {
            toggleBtn.addEventListener("click", () => {
                // Toggle sidebar width
                sidebar.classList.toggle("w-64");
                sidebar.classList.toggle("w-20");

                // Toggle visibility of logos and text
                if (logoExpanded) logoExpanded.classList.toggle("hidden");
                if (logoCollapsed) logoCollapsed.classList.toggle("hidden");
                sidebarTextElements.forEach(el => el.classList.toggle("hidden"));
                
                // Rotate the toggle icon
                if (icon) icon.classList.toggle("rotate-180");
            });
        }

        // Facility modules toggle functionality
        if (facilityToggle && facilityModules && facilityArrow) {
            facilityToggle.addEventListener("click", () => {
                facilityModules.classList.toggle("show");
                facilityArrow.classList.toggle("rotate-180");
            });
        }

        // Legal modules toggle functionality
        if (legalToggle && legalModules && legalArrow) {
            legalToggle.addEventListener("click", () => {
                legalModules.classList.toggle("show");
                legalArrow.classList.toggle("rotate-180");
            });
        }

        // Initialize Lucide icons
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    });
</script>