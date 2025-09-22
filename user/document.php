<?php
session_start();

// Normalize session like user dashboard
$hasLegacySession = isset($_SESSION['user_id']);
$hasStructuredSession = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
if (!$hasStructuredSession && $hasLegacySession) {
    $_SESSION['user'] = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
    $hasStructuredSession = true;
}
if (!$hasStructuredSession) {
    header("Location: ../auth/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'admin_Document';
$pass = '123';
$db = 'admin_Document';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

// Include document functions and initialize database (suppress output)
include __DIR__ . '/../backend/sql/document.php';
ob_start();
initializeDocumentDatabase($conn);
ob_end_clean();

// Create documents table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS user_documents (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    title varchar(255) NOT NULL,
    description text DEFAULT NULL,
    file_name varchar(255) NOT NULL,
    file_path varchar(500) NOT NULL,
    file_size int(11) DEFAULT NULL,
    file_type varchar(100) DEFAULT NULL,
    category varchar(100) DEFAULT 'General',
    tags text DEFAULT NULL,
    is_public tinyint(1) DEFAULT 0,
    download_count int(11) DEFAULT 0,
    uploaded_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_category (category),
    KEY idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($createTable);

// Handle file upload
$uploadMessage = '';
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $tags = trim($_POST['tags'] ?? '');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    if (empty($title)) {
        $uploadError = "Document title is required.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = "Please select a file to upload.";
    } else {
        $file = $_FILES['document'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $fileTmpName = $file['tmp_name'];
        
        // Validate file size (max 10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            $uploadError = "File size must be less than 10MB.";
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/documents/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $uniqueFileName;
            
            if (move_uploaded_file($fileTmpName, $filePath)) {
                // Save to database
                $stmt = $conn->prepare("INSERT INTO user_documents (user_id, title, description, file_name, file_path, file_size, file_type, category, tags, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
                    $stmt->bind_param("issssisssi", $userId, $title, $description, $fileName, $filePath, $fileSize, $fileType, $category, $tags, $isPublic);
                    
                    if ($stmt->execute()) {
                        $uploadMessage = "Document uploaded successfully!";
                    } else {
                        $uploadError = "Error saving document information: " . $stmt->error;
                        // Delete uploaded file if database save failed
                        unlink($filePath);
                    }
                    $stmt->close();
                } else {
                    $uploadError = "Database error: " . $conn->error;
                    unlink($filePath);
                }
            } else {
                $uploadError = "Failed to upload file.";
            }
        }
    }
}

// Handle file download
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $docId = (int)$_GET['id'];
    $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT file_name, file_path FROM user_documents WHERE id = ? AND (user_id = ? OR is_public = 1)");
    $stmt->bind_param("ii", $docId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filePath = $row['file_path'];
        $fileName = $row['file_name'];
        
        if (file_exists($filePath)) {
            // Update download count
            $updateStmt = $conn->prepare("UPDATE user_documents SET download_count = download_count + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $docId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit();
        }
    }
    $stmt->close();
}

// Handle file deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $docId = (int)$_GET['id'];
    $userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $docId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filePath = $row['file_path'];
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM user_documents WHERE id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $docId, $userId);
        
        if ($deleteStmt->execute()) {
            // Delete physical file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $uploadMessage = "Document deleted successfully!";
        } else {
            $uploadError = "Error deleting document.";
        }
        $deleteStmt->close();
    }
    $stmt->close();
}

// Fetch user's documents
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$categoryFilter = $_GET['category'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$whereClause = "WHERE user_id = ?";
$params = [$userId];
$paramTypes = "i";

if (!empty($categoryFilter)) {
    $whereClause .= " AND category = ?";
    $params[] = $categoryFilter;
    $paramTypes .= "s";
}

if (!empty($searchQuery)) {
    $whereClause .= " AND (title LIKE ? OR description LIKE ? OR tags LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $paramTypes .= "sss";
}

$sql = "SELECT * FROM user_documents $whereClause ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $documents = [];
}

// Get categories for filter
$categoriesResult = $conn->query("SELECT DISTINCT category FROM user_documents WHERE user_id = $userId ORDER BY category");
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Archiver</title>
    <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .file-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 24px;
        }
        .pdf-icon { background: #ff4444; color: white; }
        .doc-icon { background: #2b579a; color: white; }
        .docx-icon { background: #2b579a; color: white; }
        .txt-icon { background: #666; color: white; }
        .img-icon { background: #4caf50; color: white; }
        .zip-icon { background: #ff9800; color: white; }
        .default-icon { background: #9e9e9e; color: white; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="shadow-lg h-screen fixed top-0 left-0">
        <?php include '../Components/sidebar/sidebar_user.php'; ?>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Document Archiver</h1>
                <p class="text-sm text-gray-500 mt-1">Manage and organize your documents</p>
            </div>
            <div class="flex items-center gap-4">
                <button id="uploadBtn" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center gap-2">
                    <i data-lucide="upload"></i>
                    Upload Document
                </button>
                <?php include __DIR__ . '/../profile.php'; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($uploadMessage): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mx-6 mt-4 rounded">
                <?= htmlspecialchars($uploadMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($uploadError): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mx-6 mt-4 rounded">
                <?= htmlspecialchars($uploadError) ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Documents</p>
                            <p class="text-2xl font-bold text-gray-900"><?= count($documents) ?></p>
                        </div>
                        <div class="text-blue-500 text-3xl">📄</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Categories</p>
                            <p class="text-2xl font-bold text-gray-900"><?= count($categories) ?></p>
                        </div>
                        <div class="text-green-500 text-3xl">📁</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Downloads</p>
                            <p class="text-2xl font-bold text-gray-900"><?= array_sum(array_column($documents, 'download_count')) ?></p>
                        </div>
                        <div class="text-yellow-500 text-3xl">⬇️</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Storage Used</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format(array_sum(array_column($documents, 'file_size')) / 1024 / 1024, 1) ?> MB</p>
                        </div>
                        <div class="text-purple-500 text-3xl">💾</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select id="categoryFilter" class="border border-gray-300 rounded-md px-3 py-2">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="searchInput" placeholder="Search documents..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>"
                                   class="border border-gray-300 rounded-md px-3 py-2 w-64">
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button id="applyFiltersBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                            <i data-lucide="search"></i> Search
                        </button>
                        <button id="clearFiltersBtn" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
                            <i data-lucide="x"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Documents Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if (empty($documents)): ?>
                    <div class="col-span-full text-center py-12">
                        <div class="text-6xl mb-4">📄</div>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No documents found</h3>
                        <p class="text-gray-500 mb-4">Upload your first document to get started</p>
                        <button id="uploadFirstBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-300">
                            Upload Document
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-300">
                            <div class="p-6">
                                <!-- File Icon -->
                                <div class="flex items-center mb-4">
                                    <?php
                                    $extension = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                                    $iconClass = 'default-icon';
                                    $icon = '📄';
                                    
                                    switch ($extension) {
                                        case 'pdf':
                                            $iconClass = 'pdf-icon';
                                            $icon = '📕';
                                            break;
                                        case 'doc':
                                        case 'docx':
                                            $iconClass = 'doc-icon';
                                            $icon = '📘';
                                            break;
                                        case 'txt':
                                            $iconClass = 'txt-icon';
                                            $icon = '📄';
                                            break;
                                        case 'jpg':
                                        case 'jpeg':
                                        case 'png':
                                        case 'gif':
                                            $iconClass = 'img-icon';
                                            $icon = '🖼️';
                                            break;
                                        case 'zip':
                                        case 'rar':
                                            $iconClass = 'zip-icon';
                                            $icon = '📦';
                                            break;
                                    }
                                    ?>
                                    <div class="file-icon <?= $iconClass ?>">
                                        <?= $icon ?>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h3 class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($doc['title']) ?></h3>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($doc['file_name']) ?></p>
                                    </div>
                                </div>

                                <!-- Document Info -->
                                <div class="space-y-2 mb-4">
                                    <?php if ($doc['description']): ?>
                                        <p class="text-sm text-gray-600 line-clamp-2"><?= htmlspecialchars($doc['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?= htmlspecialchars($doc['category']) ?></span>
                                        <span><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between text-xs text-gray-500">
                                        <span><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></span>
                                        <span><?= $doc['download_count'] ?> downloads</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2">
                                    <a href="?action=download&id=<?= $doc['id'] ?>" 
                                       class="flex-1 bg-blue-600 text-white text-center px-3 py-2 rounded text-sm hover:bg-blue-700 transition duration-300">
                                        <i data-lucide="download"></i> Download
                                    </a>
                                    <button onclick="deleteDocument(<?= $doc['id'] ?>)" 
                                            class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition duration-300">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Upload Document</h2>
                <button id="closeUploadModal" class="text-gray-400 hover:text-gray-600 text-3xl">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="upload">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Title *</label>
                    <input type="text" name="title" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Brief description of the document..."></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="General">General</option>
                            <option value="Contracts">Contracts</option>
                            <option value="Reports">Reports</option>
                            <option value="Forms">Forms</option>
                            <option value="Images">Images</option>
                            <option value="Archives">Archives</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                        <input type="text" name="tags" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2"
                               placeholder="tag1, tag2, tag3">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select File *</label>
                    <input type="file" name="document" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar">
                    <p class="text-xs text-gray-500 mt-1">Maximum file size: 10MB</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <input id="isPublic" name="is_public" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded mt-1">
                        <label for="isPublic" class="ml-3 text-sm text-gray-700">
                            <strong>Make this document public</strong><br>
                            <span class="text-gray-600">Other users will be able to download this document</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" id="cancelUpload" class="bg-gray-200 px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg flex items-center gap-2">
                        <i data-lucide="upload"></i> Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            lucide.createIcons();
            
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadFirstBtn = document.getElementById('uploadFirstBtn');
            const uploadModal = document.getElementById('uploadModal');
            const closeUploadModal = document.getElementById('closeUploadModal');
            const cancelUpload = document.getElementById('cancelUpload');
            const categoryFilter = document.getElementById('categoryFilter');
            const searchInput = document.getElementById('searchInput');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');

            // Upload modal functions
            function openUploadModal() {
                uploadModal.classList.remove('hidden');
            }

            function closeUploadModalFunc() {
                uploadModal.classList.add('hidden');
            }

            uploadBtn?.addEventListener('click', openUploadModal);
            uploadFirstBtn?.addEventListener('click', openUploadModal);
            closeUploadModal?.addEventListener('click', closeUploadModalFunc);
            cancelUpload?.addEventListener('click', closeUploadModalFunc);

            // Close modal when clicking outside
            uploadModal?.addEventListener('click', function(e) {
                if (e.target === uploadModal) {
                    closeUploadModalFunc();
                }
            });

            // Filter functions
            applyFiltersBtn?.addEventListener('click', function() {
                const category = categoryFilter.value;
                const search = searchInput.value.trim();
                
                let url = window.location.pathname;
                const params = new URLSearchParams();
                
                if (category) {
                    params.append('category', category);
                }
                
                if (search) {
                    params.append('search', search);
                }
                
                if (params.toString()) {
                    url += '?' + params.toString();
                }
                
                window.location.href = url;
            });

            clearFiltersBtn?.addEventListener('click', function() {
                categoryFilter.value = '';
                searchInput.value = '';
                window.location.href = window.location.pathname;
            });

            // Search on Enter key
            searchInput?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyFiltersBtn.click();
                }
            });

            // Auto-hide messages
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(msg => {
                setTimeout(() => {
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                }, 5000);
            });
        });

        function deleteDocument(id) {
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                window.location.href = '?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>
