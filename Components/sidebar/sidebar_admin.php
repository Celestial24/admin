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
        
        /* Scrollable submenus */
        .submenu-container {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(156, 163, 175, 0.6) rgba(55, 65, 81, 0.3);
        }
        
        /* Custom scrollbar styling */
        .submenu-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .submenu-container::-webkit-scrollbar-track {
            background: rgba(31, 41, 55, 0.5);
            border-radius: 4px;
        }
        
        .submenu-container::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.7);
            border-radius: 4px;
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
        
        .submenu-container::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.9);
        }
        
        .submenu-container::-webkit-scrollbar-thumb:active {
            background: rgba(209, 213, 219, 1);
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
            <!-- Topics -->
            <div id="facilities-submenu" class="ml-9 space-y-1 hidden submenu-container">
                <a href="/admin/module-table/facilities.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facilities.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Facilities Overview
                </a>
                <a href="/admin/module-table/Reservation.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Reservation.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Room & Facility Reservation
                </a>
                <a href="/admin/module-table/Maintenance.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Maintenance.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Maintenance Requests
                </a>
                <a href="/admin/module-table/room-management.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'room-management.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Room Management
                </a>
                <a href="/admin/module-table/equipment.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'equipment.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Equipment Inventory
                </a>
                <a href="/admin/module-table/booking-calendar.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'booking-calendar.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Booking Calendar
                </a>
                <a href="/admin/module-table/facility-scheduling.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facility-scheduling.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Facility Scheduling
                </a>
                <a href="/admin/module-table/room-availability.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'room-availability.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Room Availability
                </a>
                <a href="/admin/module-table/facility-reports.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'facility-reports.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Facility Reports
                </a>
                <a href="/admin/module-table/cleaning-schedule.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'cleaning-schedule.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Cleaning Schedule
                </a>
                <a href="/admin/module-table/utility-management.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'utility-management.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Utility Management
                </a>
            </div>

            <!-- Visitor Management (Toggle Group) -->
            <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-700 group" data-submenu-toggle="visitor-submenu">
                <span class="flex items-center gap-3">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span class="sidebar-text">Visitor Management</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform submenu-chevron"></i>
            </button>
            <!-- Topics -->
            <div id="visitor-submenu" class="ml-9 space-y-1 hidden submenu-container">
                <a href="/admin/Main/Visitors.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Visitors.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Visitor Logs
                </a>
                <a href="/admin/module-table/visitor-registration.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'visitor-registration.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Visitor Registration
                </a>
                <a href="/admin/module-table/visitor-checkin.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'visitor-checkin.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Check-in/Check-out
                </a>
                <a href="/admin/module-table/visitor-reports.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'visitor-reports.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Visitor Reports
                </a>
            </div>

      

            <!-- Legal & Contract Management (Toggle Group) -->
            <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-700 group" data-submenu-toggle="legal-submenu">
                <span class="flex items-center gap-3">
                    <i data-lucide="gavel" class="w-5 h-5"></i>
                    <span class="sidebar-text">Legal Management</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform submenu-chevron"></i>
            </button>
            <!-- Topics -->
            <div id="legal-submenu" class="ml-9 space-y-1 hidden submenu-container">
                <a href="/admin/Main/legalmanagement.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'legalmanagement.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Legal Management
                </a>
                <a href="/admin/module-table/Contract.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'Contract.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Contract Management
                </a>
                <a href="/admin/module-table/legal-documents.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'legal-documents.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Legal Documents
                </a>
                <a href="/admin/module-table/compliance.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'compliance.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Compliance Tracking
                </a>
                <a href="/admin/module-table/legal-approvals.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'legal-approvals.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Legal Approvals
                </a>
                <a href="/admin/module-table/legal-reports.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'legal-reports.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Legal Reports
                </a>
            </div>

            <!-- Document Archiver (Toggle Group) -->
            <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-700 group" data-submenu-toggle="archiver-submenu">
                <span class="flex items-center gap-3">
                    <i data-lucide="folder-kanban" class="w-5 h-5"></i>
                    <span class="sidebar-text">Document Archiver</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform submenu-chevron"></i>
            </button>
            <div id="archiver-submenu" class="ml-9 space-y-1 hidden submenu-container">
                <a href="/admin/module-table/document.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Document Submission
                </a>
                <a href="/admin/module-table/document-archive.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document-archive.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Document Archive
                </a>
                <a href="/admin/module-table/document-categories.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document-categories.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> Categories
                </a>
                <a href="/admin/module-table/document-search.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document-search.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span>  Search
                </a>
                <a href="/admin/module-table/document-approval.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document-approval.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span>  Approval
                </a>
                <a href="/admin/module-table/document-reports.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'document-reports.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span>  Reports
                </a>
            </div>

            <!-- User Management (Toggle Group) -->
            <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-700 group" data-submenu-toggle="user-submenu">
                <span class="flex items-center gap-3">
                    <i data-lucide="user-cog" class="w-5 h-5"></i>
                    <span class="sidebar-text">User Management</span>
                </span>
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform submenu-chevron"></i>
            </button>
            
            <!-- Topics -->
            <div id="user-submenu" class="ml-9 space-y-1 hidden submenu-container">
                <a href="/admin/module-table/users.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'users.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> User Accounts
                </a>
                <a href="/admin/module-table/user-roles.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'user-roles.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> User Roles
                </a>
                <a href="/admin/module-table/user-permissions.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'user-permissions.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> User Permissions
                </a>
                <a href="/admin/module-table/user-activity.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'user-activity.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> User Activity Logs
                </a>
                <a href="/admin/module-table/user-registration.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'user-registration.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> User Registration
                </a>
                <a href="/admin/module-table/user-settings.php" class="block px-3 py-2 rounded-md hover:bg-gray-700 <?= $currentPage === 'user-settings.php' ? 'bg-gray-700' : '' ?>">
                    <span class="text-sm text-gray-300">•</span> User Settings
                </a>
            </div>

        </nav>
    </div>

    <!-- Main Content Area placeholder disabled when included in other pages -->
    <div class="hidden flex-1 min-h-screen overflow-auto"></div>

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
