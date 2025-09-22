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

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_stats':
            $stats = getDocumentStats($conn);
            echo json_encode($stats);
            break;
            
        case 'get_documents':
            $filters = [
                'limit' => (int)($_GET['limit'] ?? 50),
                'offset' => (int)($_GET['offset'] ?? 0),
                'search' => $_GET['search'] ?? '',
                'category' => $_GET['category'] ?? '',
                'file_type' => $_GET['file_type'] ?? '',
                'is_public' => isset($_GET['is_public']) ? (int)$_GET['is_public'] : null,
                'sort_by' => $_GET['sort_by'] ?? 'uploaded_at',
                'sort_order' => $_GET['sort_order'] ?? 'DESC'
            ];
            
            $documents = getDocuments($conn, $filters);
            echo json_encode($documents);
            break;
            
        case 'delete_document':
            $documentId = (int)$_POST['document_id'];
            $sql = "DELETE FROM user_documents WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $documentId);
            $result = $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => $result]);
            break;
            
        case 'bulk_action':
            $action = $_POST['action'];
            $documentIds = $_POST['document_ids'];
            
            if ($action === 'delete') {
                $placeholders = str_repeat('?,', count($documentIds) - 1) . '?';
                $sql = "DELETE FROM user_documents WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(str_repeat('i', count($documentIds)), ...$documentIds);
                $result = $stmt->execute();
                $stmt->close();
                
                echo json_encode(['success' => $result, 'affected' => $conn->affected_rows]);
            }
            break;
    }
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_document':
                $documentId = (int)$_POST['document_id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $category = $_POST['category'];
                $isPublic = isset($_POST['is_public']) ? 1 : 0;
                
                $sql = "UPDATE user_documents SET title = ?, description = ?, category = ?, is_public = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $title, $description, $category, $isPublic, $documentId);
                $stmt->execute();
                $stmt->close();
                
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
                break;
        }
    }
}

// Get initial data
$stats = getDocumentStats($conn);
$categories = getDocumentCategories($conn);
$documents = getDocuments($conn, ['limit' => 50]);

// Get file types for filter
$fileTypesResult = $conn->query("SELECT DISTINCT file_type FROM user_documents WHERE file_type IS NOT NULL ORDER BY file_type");
$fileTypes = $fileTypesResult ? $fileTypesResult->fetch_all(MYSQLI_ASSOC) : [];
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
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-600 p-2 rounded-lg">
                        <i class="fas fa-file-alt text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Document Management</h1>
                        <p class="text-gray-600">Admin Panel - Manage all documents</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="refreshStats()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                    <button onclick="exportData()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" id="search-input" placeholder="Search documents..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="category-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File Type</label>
                        <select id="file-type-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Types</option>
                            <?php foreach ($fileTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type['file_type']) ?>"><?= htmlspecialchars($type['file_type']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Visibility</label>
                        <select id="visibility-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Documents</option>
                            <option value="1">Public Only</option>
                            <option value="0">Private Only</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-4">
                    <div class="flex space-x-2">
                        <button onclick="applyFilters()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Apply Filters
                        </button>
                        <button onclick="clearFilters()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Select All</span>
                        </label>
                        <button onclick="bulkDelete()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors" id="bulk-delete-btn" disabled>
                            <i class="fas fa-trash mr-2"></i>Delete Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Documents</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="select-all-header" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Downloads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibility</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="documents-table-body" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($documents as $doc): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="document-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="<?= $doc['id'] ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-2xl mr-3">
                                            <?= getFileTypeIcon($doc['file_extension'] ?? '') ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($doc['title']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($doc['file_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($doc['user_name'] ?? 'Unknown') ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($doc['user_email'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($doc['category']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= formatFileSize($doc['file_size']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $doc['download_count'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($doc['is_public']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-globe mr-1"></i>Public
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-lock mr-1"></i>Private
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewDocument(<?= $doc['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="downloadDocument(<?= $doc['id'] ?>)" class="text-green-600 hover:text-green-900" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button onclick="editDocument(<?= $doc['id'] ?>)" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteDocument(<?= $doc['id'] ?>)" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Showing <span id="showing-start">1</span> to <span id="showing-end"><?= count($documents) ?></span> of <span id="total-count"><?= $stats['total_documents'] ?></span> results
            </div>
            <div class="flex space-x-2">
                <button onclick="previousPage()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50" id="prev-btn" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="nextPage()" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50" id="next-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Document Modal -->
    <div id="edit-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Edit Document</h3>
                </div>
                <form id="edit-form" method="POST">
                    <input type="hidden" name="action" value="update_document">
                    <input type="hidden" name="document_id" id="edit-document-id">
                    
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" id="edit-title" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="edit-description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" id="edit-category" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_public" id="edit-is-public" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Make this document public</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 0;
        const pageSize = 50;
        let selectedDocuments = new Set();

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            updateStats();
        });

        function setupEventListeners() {
            // Select all functionality
            document.getElementById('select-all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.document-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    if (this.checked) {
                        selectedDocuments.add(checkbox.value);
                    } else {
                        selectedDocuments.delete(checkbox.value);
                    }
                });
                updateBulkActions();
            });

            // Individual checkbox changes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('document-checkbox')) {
                    if (e.target.checked) {
                        selectedDocuments.add(e.target.value);
                    } else {
                        selectedDocuments.delete(e.target.value);
                    }
                    updateBulkActions();
                }
            });

            // Search input
            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        }

        function updateStats() {
            fetch('?ajax=get_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-docs').textContent = data.total_documents;
                    document.getElementById('public-docs').textContent = data.public_documents;
                    document.getElementById('total-downloads').textContent = data.total_downloads;
                    document.getElementById('storage-used').textContent = formatFileSize(data.total_size);
                })
                .catch(error => console.log('Stats update failed:', error));
        }

        function applyFilters() {
            const filters = {
                search: document.getElementById('search-input').value,
                category: document.getElementById('category-filter').value,
                file_type: document.getElementById('file-type-filter').value,
                is_public: document.getElementById('visibility-filter').value,
                limit: pageSize,
                offset: currentPage * pageSize
            };

            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) params.append(key, filters[key]);
            });

            fetch('?ajax=get_documents&' + params.toString())
                .then(response => response.json())
                .then(documents => {
                    updateDocumentsTable(documents);
                })
                .catch(error => console.log('Filter failed:', error));
        }

        function clearFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('category-filter').value = '';
            document.getElementById('file-type-filter').value = '';
            document.getElementById('visibility-filter').value = '';
            applyFilters();
        }

        function updateDocumentsTable(documents) {
            const tbody = document.getElementById('documents-table-body');
            tbody.innerHTML = '';

            documents.forEach(doc => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="document-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="${doc.id}">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="text-2xl mr-3">${getFileTypeIcon(doc.file_extension || '')}</div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(doc.title)}</div>
                                <div class="text-sm text-gray-500">${escapeHtml(doc.file_name)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${escapeHtml(doc.user_name || 'Unknown')}</div>
                        <div class="text-sm text-gray-500">${escapeHtml(doc.user_email || '')}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ${escapeHtml(doc.category)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${formatFileSize(doc.file_size)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${doc.download_count}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${doc.is_public ? 
                            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-globe mr-1"></i>Public</span>' :
                            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-lock mr-1"></i>Private</span>'
                        }
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${formatDate(doc.uploaded_at)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <button onclick="viewDocument(${doc.id})" class="text-blue-600 hover:text-blue-900" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="downloadDocument(${doc.id})" class="text-green-600 hover:text-green-900" title="Download">
                                <i class="fas fa-download"></i>
                            </button>
                            <button onclick="editDocument(${doc.id})" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteDocument(${doc.id})" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateBulkActions() {
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            bulkDeleteBtn.disabled = selectedDocuments.size === 0;
        }

        function editDocument(id) {
            // Get document data and populate modal
            fetch('?ajax=get_documents&limit=1&offset=0')
                .then(response => response.json())
                .then(documents => {
                    const doc = documents.find(d => d.id == id);
                    if (doc) {
                        document.getElementById('edit-document-id').value = doc.id;
                        document.getElementById('edit-title').value = doc.title;
                        document.getElementById('edit-description').value = doc.description || '';
                        document.getElementById('edit-category').value = doc.category;
                        document.getElementById('edit-is-public').checked = doc.is_public == 1;
                        document.getElementById('edit-modal').classList.remove('hidden');
                    }
                });
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.add('hidden');
        }

        function deleteDocument(id) {
            if (confirm('Are you sure you want to delete this document?')) {
                const formData = new FormData();
                formData.append('document_id', id);

                fetch('?ajax=delete_document', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete document');
                    }
                });
            }
        }

        function bulkDelete() {
            if (selectedDocuments.size === 0) return;
            
            if (confirm(`Are you sure you want to delete ${selectedDocuments.size} selected documents?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('document_ids', Array.from(selectedDocuments));

                fetch('?ajax=bulk_action', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete documents');
                    }
                });
            }
        }

        function viewDocument(id) {
            // Open document in new tab
            window.open(`../user/document.php?view=${id}`, '_blank');
        }

        function downloadDocument(id) {
            // Trigger download
            window.open(`../user/document.php?download=${id}`, '_blank');
        }

        function refreshStats() {
            updateStats();
        }

        function exportData() {
            // Export functionality
            window.open('?export=csv', '_blank');
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function getFileTypeIcon(extension) {
            const icons = {
                'pdf': '📕',
                'doc': '📘',
                'docx': '📘',
                'txt': '📄',
                'jpg': '🖼️',
                'jpeg': '🖼️',
                'png': '🖼️',
                'gif': '🖼️',
                'zip': '📦',
                'rar': '📦',
                'xls': '📈',
                'xlsx': '📈',
                'ppt': '📽️',
                'pptx': '📽️',
                'mp4': '🎥',
                'avi': '🎥',
                'mp3': '🎵',
                'wav': '🎵'
            };
            return icons[extension.toLowerCase()] || '📄';
        }
    </script>
</body>
</html>
