<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'admin_Document';
$pass = '123';
$db = 'admin_Document';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include document functions
include '../backend/sql/document.php';

// Initialize database tables and data (suppress output)
ob_start();
initializeDocumentDatabase($conn);
ob_end_clean();

// Get initial data
$stats = getDocumentStats($conn);
$categories = getDocumentCategories($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .loading { opacity: 0.6; pointer-events: none; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="bg-gray-50 flex h-screen">
    <!-- Sidebar -->
    <aside id="sidebar">
        <?php include '../Components/sidebar/sidebar_user.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4 mb-6">
            <h2 class="text-xl font-semibold text-gray-800">
                Document Management
                <span class="ml-4 text-base text-gray-500 font-normal">
                    (Total Documents: <?= $stats['total_documents'] ?>)
                </span>
            </h2>
            
            <div class="flex items-center space-x-4">
                <button onclick="refreshStats()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
                <a href="module-table/document.php" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-table mr-2"></i>View Table
                </a>
                
                <!-- User Profile -->
                <?php include __DIR__ . '/../profile.php'; ?>
            </div>
        </div>

        <main class="flex-1 overflow-y-auto w-full py-4 px-6 space-y-6">
        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Documents</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-docs"><?= $stats['total_documents'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-globe text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Public Documents</p>
                        <p class="text-2xl font-bold text-gray-900" id="public-docs"><?= $stats['public_documents'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-download text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Downloads</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-downloads"><?= $stats['total_downloads'] ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-hdd text-orange-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Storage Used</p>
                        <p class="text-2xl font-bold text-gray-900" id="storage-used"><?= formatFileSize($stats['total_size']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="module-table/document.php" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg border border-blue-200 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-table text-blue-600 text-2xl mr-3"></i>
                            <div>
                                <h3 class="font-medium text-gray-900">View All Documents</h3>
                                <p class="text-sm text-gray-600">Browse and manage all documents</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="user/document.php" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg border border-green-200 transition-colors">
                        <div class="flex items-center">
                            <i class="fas fa-upload text-green-600 text-2xl mr-3"></i>
                            <div>
                                <h3 class="font-medium text-gray-900">Upload Documents</h3>
                                <p class="text-sm text-gray-600">Upload new documents to the system</p>
                            </div>
                        </div>
                    </a>
                    
                    <div class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg border border-purple-200 transition-colors cursor-pointer" onclick="refreshStats()">
                        <div class="flex items-center">
                            <i class="fas fa-sync-alt text-purple-600 text-2xl mr-3"></i>
                            <div>
                                <h3 class="font-medium text-gray-900">Refresh Statistics</h3>
                                <p class="text-sm text-gray-600">Update current statistics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Overview -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Document Categories</h2>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <?php foreach ($categories as $category): ?>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-2xl mb-2">
                            <?php
                            $iconMap = [
                                'file' => 'fas fa-file',
                                'file-text' => 'fas fa-file-alt',
                                'bar-chart' => 'fas fa-chart-bar',
                                'edit' => 'fas fa-edit',
                                'image' => 'fas fa-image',
                                'archive' => 'fas fa-archive',
                                'presentation' => 'fas fa-presentation',
                                'trending-up' => 'fas fa-chart-line',
                                'folder' => 'fas fa-folder'
                            ];
                            $icon = $iconMap[$category['icon']] ?? 'fas fa-file';
                            ?>
                            <i class="<?= $icon ?> text-<?= str_replace('#', '', $category['color_code']) ?>-600"></i>
                        </div>
                        <h3 class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($category['name']) ?></h3>
                        <p class="text-xs text-gray-600"><?= htmlspecialchars($category['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">System Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Database Status</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Database:</span>
                                <span class="font-medium text-green-600">Connected</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tables:</span>
                                <span class="font-medium text-green-600">5 Tables Created</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Categories:</span>
                                <span class="font-medium text-blue-600"><?= count($categories) ?> Available</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Document Statistics</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Private Documents:</span>
                                <span class="font-medium text-gray-900"><?= $stats['private_documents'] ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Categories Used:</span>
                                <span class="font-medium text-blue-600"><?= $stats['categories_count'] ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Views:</span>
                                <span class="font-medium text-purple-600"><?= $stats['total_downloads'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>
    </div>

    <script>
        // Refresh statistics
        function refreshStats() {
            const refreshBtn = event.target.closest('button');
            refreshBtn.classList.add('loading');
            
            fetch('?ajax=get_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-docs').textContent = data.total_documents;
                    document.getElementById('public-docs').textContent = data.public_documents;
                    document.getElementById('total-downloads').textContent = data.total_downloads;
                    document.getElementById('storage-used').textContent = formatFileSize(data.total_size);
                    
                    refreshBtn.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error refreshing stats:', error);
                    refreshBtn.classList.remove('loading');
                });
        }

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Auto-refresh stats every 30 seconds
        setInterval(refreshStats, 30000);
    </script>
</body>
</html>