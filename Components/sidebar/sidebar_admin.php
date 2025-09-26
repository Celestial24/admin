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
            <a href="/admin/Main/Dashboard.php" class="flex items-center gap-2">
                <img src="/admin/assets/image/logo.png" alt="Logo" class="h-14 sidebar-logo-expanded" />
                <img src="/admin/assets/image/logo2.png" alt="Logo" class="h-14 sidebar-logo-collapsed hidden" />
            </a>
            <button id="sidebar-toggle" class="text-white focus:outline-none">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-2 py-4 space-y-2">
            
            <!-- Facilities (Toggle Group) -->
            <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-700 group" data-submenu-toggle="facilities-submenu">
                <span class="flex items-center gap-3">
                    <i data-lucide="building" class="w-5 h-5"></i>
                    <span class="sidebar-text">Facilities Management</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform submenu-chevron"></i>
            </button>
            <div id="facilities-submenu" class="ml-9 space-y-1 hidden">
                <a href="/admin/module-table/facilities.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facilities.php' ? 'bg-gray-700' : '' ?>">
                🏠 Facilities Overview

                </a>
                <a href="/admin/module-table/Reservation.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'reservation.php' ? 'bg-gray-700' : '' ?>">
                📅 Room & Facility Reservation
                </a>
                <a href="/admin/module-table/Maintenance.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'maintenance.php' ? 'bg-gray-700' : '' ?>">
                🛠️ Maintenance Requests
                </a>
            </div>

            <!-- Visitor Logs -->
            <a href="/admin/Main/Visitors.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Visitors.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="sidebar-text">Visitor Logs</span>
            </a>

        <!-- 

    Legal & Contract Management Section
    -----------------------------------
    This toggle group provides navigation links related to legal and contract management.
    - "Contract Result & Risk Analysis": Displays outcomes and risk evaluations for contracts.
    - "Submission History": Shows the history of contract submissions for review and tracking.
    The submenu is collapsible, improving sidebar organization and user experience.
-->

            <!-- Legal Management (Toggle Group) -->
            <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-700 group" data-submenu-toggle="legal-submenu">
                <span class="flex items-center gap-3">
                    <i data-lucide="gavel" class="w-5 h-5"></i>
                    <span class="sidebar-text"> Legal & Contract</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform submenu-chevron"></i>
            </button>
            <div id="legal-submenu" class="ml-9 space-y-1 hidden">
                <a href="/admin/Main/legalmanagement.php" class="block px-3 py-1 rounded-md hover:bg-gray-700 <?= $currentPage === 'legalmanagement.php' ? 'bg-gray-700' : '' ?>">
                    Contract Result & Risk Analysis
                </a>
                <a href="/admin/module-table/Contract.php" class="block px-3 py-1 rounded-md hover:bg-gray-700 <?= $currentPage === 'contract.php' ? 'bg-gray-700' : '' ?>">
                    Submission History
                </a>
            </div>

            <!-- Document Archiver -->
            <a href="/admin/module-table/document.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="folder-kanban" class="w-5 h-5"></i>
                <span class="sidebar-text">Document Archiver</span>
            </a>

            <!-- Navigation -->
            <a href="/admin/Main/Accout-table.php" class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'integration.php' ? 'bg-gray-700' : '' ?>">
                <i data-lucide="blocks" class="w-5 h-5"></i>
                <span class="sidebar-text">User Management</span>
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

            // Submenu toggles
            document.querySelectorAll('[data-submenu-toggle]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const targetId = btn.getAttribute('data-submenu-toggle');
                    const submenu = document.getElementById(targetId);
                    const chevron = btn.querySelector('.submenu-chevron');
                    if (submenu) {
                        submenu.classList.toggle('hidden');
                        chevron && chevron.classList.toggle('rotate-180');
                    }
                });
            });

            // Initialize Lucide icons
            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        });
    </script>

</body>
</html>