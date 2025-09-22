<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$employeeId = $_SESSION['employee_id'] ?? null;
$roles = $_SESSION['roles'] ?? 'Employee';

// DB Connection
include __DIR__ . '/../backend/sql/db.php';
$conn = $empConn;

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_stats') {
    header('Content-Type: application/json');
    
    $stats = [];
    $stats['total_documents'] = $conn->query("SELECT COUNT(*) as count FROM user_documents")->fetch_assoc()['count'];
    $stats['total_size'] = $conn->query("SELECT SUM(file_size) as total FROM user_documents")->fetch_assoc()['total'] ?: 0;
    $stats['total_downloads'] = $conn->query("SELECT SUM(download_count) as total FROM user_documents")->fetch_assoc()['total'] ?: 0;
    $stats['public_documents'] = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 1")->fetch_assoc()['count'];
    $stats['private_documents'] = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 0")->fetch_assoc()['count'];
    $stats['categories_count'] = $conn->query("SELECT COUNT(DISTINCT category) as count FROM user_documents")->fetch_assoc()['count'];
    
    echo json_encode($stats);
    exit();
}

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

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedDocs = $_POST['selected_docs'] ?? [];
    
    if (!empty($selectedDocs)) {
        $placeholders = str_repeat('?,', count($selectedDocs) - 1) . '?';
        
        switch ($action) {
            case 'delete':
                // Get file paths first
                $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($selectedDocs)), ...$selectedDocs);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $filePaths = [];
                while ($row = $result->fetch_assoc()) {
                    $filePaths[] = $row['file_path'];
                }
                $stmt->close();
                
                // Delete from database
                $deleteStmt = $conn->prepare("DELETE FROM user_documents WHERE id IN ($placeholders)");
                $deleteStmt->bind_param(str_repeat('i', count($selectedDocs)), ...$selectedDocs);
                
                if ($deleteStmt->execute()) {
                    // Delete physical files
                    foreach ($filePaths as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    $successMessage = "Selected documents deleted successfully!";
                } else {
                    $errorMessage = "Error deleting documents.";
                }
                $deleteStmt->close();
                break;
                
            case 'make_public':
                $updateStmt = $conn->prepare("UPDATE user_documents SET is_public = 1 WHERE id IN ($placeholders)");
                $updateStmt->bind_param(str_repeat('i', count($selectedDocs)), ...$selectedDocs);
                if ($updateStmt->execute()) {
                    $successMessage = "Selected documents made public!";
                } else {
                    $errorMessage = "Error updating documents.";
                }
                $updateStmt->close();
                break;
                
            case 'make_private':
                $updateStmt = $conn->prepare("UPDATE user_documents SET is_public = 0 WHERE id IN ($placeholders)");
                $updateStmt->bind_param(str_repeat('i', count($selectedDocs)), ...$selectedDocs);
                if ($updateStmt->execute()) {
                    $successMessage = "Selected documents made private!";
                } else {
                    $errorMessage = "Error updating documents.";
                }
                $updateStmt->close();
                break;
        }
    }
}

// Handle individual document actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $docId = (int)$_GET['id'];
    
    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE id = ?");
            $stmt->bind_param("i", $docId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $filePath = $row['file_path'];
                
                $deleteStmt = $conn->prepare("DELETE FROM user_documents WHERE id = ?");
                $deleteStmt->bind_param("i", $docId);
                
                if ($deleteStmt->execute()) {
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $successMessage = "Document deleted successfully!";
                } else {
                    $errorMessage = "Error deleting document.";
                }
                $deleteStmt->close();
            }
            $stmt->close();
            break;
            
        case 'toggle_public':
            $stmt = $conn->prepare("UPDATE user_documents SET is_public = NOT is_public WHERE id = ?");
            $stmt->bind_param("i", $docId);
            if ($stmt->execute()) {
                $successMessage = "Document visibility updated!";
            } else {
                $errorMessage = "Error updating document.";
            }
            $stmt->close();
            break;
            
        case 'download':
            $stmt = $conn->prepare("SELECT file_name, file_path FROM user_documents WHERE id = ?");
            $stmt->bind_param("i", $docId);
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
            break;
    }
}

// Fetch documents with filters
$categoryFilter = $_GET['category'] ?? '';
$userFilter = $_GET['user'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$sortBy = $_GET['sort'] ?? 'uploaded_at';
$sortOrder = $_GET['order'] ?? 'DESC';

$whereClause = "1=1";
$params = [];
$paramTypes = "";

if (!empty($categoryFilter)) {
    $whereClause .= " AND category = ?";
    $params[] = $categoryFilter;
    $paramTypes .= "s";
}

if (!empty($userFilter)) {
    $whereClause .= " AND user_id = ?";
    $params[] = $userFilter;
    $paramTypes .= "i";
}

if (!empty($searchQuery)) {
    $whereClause .= " AND (title LIKE ? OR description LIKE ? OR file_name LIKE ? OR tags LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $paramTypes .= "ssss";
}

// Validate sort column
$allowedSorts = ['title', 'file_name', 'file_size', 'category', 'uploaded_at', 'download_count'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'uploaded_at';
}

$sql = "SELECT ud.*, u.name as user_name, u.email as user_email 
        FROM user_documents ud 
        LEFT JOIN users u ON ud.user_id = u.id 
        WHERE $whereClause 
        ORDER BY $sortBy $sortOrder";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $documents = [];
}

// Get statistics with better error handling
$stats = [];
$stats['total_documents'] = 0;
$stats['total_size'] = 0;
$stats['total_downloads'] = 0;
$stats['public_documents'] = 0;
$stats['private_documents'] = 0;
$stats['categories_count'] = 0;

// Get total documents
$result = $conn->query("SELECT COUNT(*) as count FROM user_documents");
if ($result) {
    $stats['total_documents'] = $result->fetch_assoc()['count'];
}

// Get total size
$result = $conn->query("SELECT SUM(file_size) as total FROM user_documents");
if ($result) {
    $stats['total_size'] = $result->fetch_assoc()['total'] ?: 0;
}

// Get total downloads
$result = $conn->query("SELECT SUM(download_count) as total FROM user_documents");
if ($result) {
    $stats['total_downloads'] = $result->fetch_assoc()['total'] ?: 0;
}

// Get public documents
$result = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 1");
if ($result) {
    $stats['public_documents'] = $result->fetch_assoc()['count'];
}

// Get private documents
$result = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 0");
if ($result) {
    $stats['private_documents'] = $result->fetch_assoc()['count'];
}

// Get categories count
$result = $conn->query("SELECT COUNT(DISTINCT category) as count FROM user_documents");
if ($result) {
    $stats['categories_count'] = $result->fetch_assoc()['count'];
}

// Get categories
$categoriesResult = $conn->query("SELECT DISTINCT category FROM user_documents ORDER BY category");
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Get users
$usersResult = $conn->query("SELECT DISTINCT ud.user_id, u.name, u.email FROM user_documents ud LEFT JOIN users u ON ud.user_id = u.id ORDER BY u.name");
$users = [];
if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - Admin</title>
    <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .file-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 16px;
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
    <aside class="w-64 fixed left-0 top-0 h-full bg-white shadow-md z-10">
        <?php include '../Components/sidebar/sidebar_admin.php'; ?>
    </aside>

    <!-- Main Content -->
    <main class="ml-64 flex flex-col">
        <!-- Header -->
        <header class="px-6 py-4 bg-white border-b shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Document Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage all user documents and files</p>
                </div>
                <div class="flex items-center gap-4">
                    <button id="refreshBtn" class="bg-green-600 text-white font-semibold px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300 flex items-center gap-2">
                        <i data-lucide="refresh-cw"></i> Refresh
                    </button>
                    <?php include '../profile.php'; ?>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <?php if (isset($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mx-6 mt-4 rounded">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mx-6 mt-4 rounded">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="flex-1 p-6 overflow-y-auto">
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Documents</p>
                            <p class="text-2xl font-bold text-gray-900" id="total-docs"><?= $stats['total_documents'] ?></p>
                        </div>
                        <div class="text-blue-500 text-3xl">📄</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Public</p>
                            <p class="text-2xl font-bold text-gray-900" id="public-docs"><?= $stats['public_documents'] ?></p>
                        </div>
                        <div class="text-green-500 text-3xl">🌐</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-gray-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Private</p>
                            <p class="text-2xl font-bold text-gray-900" id="private-docs"><?= $stats['private_documents'] ?></p>
                        </div>
                        <div class="text-gray-500 text-3xl">🔒</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Downloads</p>
                            <p class="text-2xl font-bold text-gray-900" id="total-downloads"><?= $stats['total_downloads'] ?></p>
                        </div>
                        <div class="text-yellow-500 text-3xl">⬇️</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Storage</p>
                            <p class="text-2xl font-bold text-gray-900" id="storage-used"><?= number_format($stats['total_size'] / 1024 / 1024, 1) ?> MB</p>
                        </div>
                        <div class="text-purple-500 text-3xl">💾</div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-indigo-500">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Categories</p>
                            <p class="text-2xl font-bold text-gray-900" id="categories-count"><?= $stats['categories_count'] ?></p>
                        </div>
                        <div class="text-indigo-500 text-3xl">📁</div>
                    </div>
                </div>
            </div>

            <!-- Filters and Controls -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                            <select name="user" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['user_id'] ?>" <?= $userFilter == $user['user_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name'] ?: $user['email']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" placeholder="Search documents..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <div class="flex gap-2">
                                <select name="sort" class="flex-1 border border-gray-300 rounded-md px-3 py-2">
                                    <option value="uploaded_at" <?= $sortBy === 'uploaded_at' ? 'selected' : '' ?>>Upload Date</option>
                                    <option value="title" <?= $sortBy === 'title' ? 'selected' : '' ?>>Title</option>
                                    <option value="file_size" <?= $sortBy === 'file_size' ? 'selected' : '' ?>>File Size</option>
                                    <option value="download_count" <?= $sortBy === 'download_count' ? 'selected' : '' ?>>Downloads</option>
                                    <option value="category" <?= $sortBy === 'category' ? 'selected' : '' ?>>Category</option>
                                </select>
                                <select name="order" class="border border-gray-300 rounded-md px-3 py-2">
                                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>↓</option>
                                    <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>↑</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-300">
                            <i data-lucide="search"></i> Apply Filters
                        </button>
                        <a href="?" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition duration-300">
                            <i data-lucide="x"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form method="POST" id="bulkForm" class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="selectAll" class="rounded">
                        <label for="selectAll" class="text-sm font-medium text-gray-700">Select All</label>
                    </div>
                    
                    <select name="bulk_action" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete Selected</option>
                        <option value="make_public">Make Public</option>
                        <option value="make_private">Make Private</option>
                    </select>
                    
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-300">
                        Apply
                    </button>
                </form>
            </div>

            <!-- Documents Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Documents</h2>
                        <span class="text-sm text-gray-500">
                            Showing <?= count($documents) ?> documents
                        </span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs text-gray-700 uppercase">
                            <tr>
                                <th class="px-6 py-3">Select</th>
                                <th class="px-6 py-3">Document</th>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Size</th>
                                <th class="px-6 py-3">Downloads</th>
                                <th class="px-6 py-3">Visibility</th>
                                <th class="px-6 py-3">Uploaded</th>
                                <th class="px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <div class="text-4xl mb-2">📄</div>
                                            <div>No documents found.</div>
                                            <div class="text-sm text-gray-400 mt-1">Try adjusting your filters.</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
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
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" name="selected_docs[]" value="<?= $doc['id'] ?>" class="doc-checkbox rounded">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="file-icon <?= $iconClass ?> mr-3">
                                                    <?= $icon ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($doc['title']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($doc['file_name']) ?></div>
                                                    <?php if ($doc['description']): ?>
                                                        <div class="text-xs text-gray-400 mt-1 max-w-xs truncate">
                                                            <?= htmlspecialchars($doc['description']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($doc['user_name'] ?: 'Unknown User') ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($doc['user_email'] ?: 'No email') ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($doc['category']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= number_format($doc['file_size'] / 1024, 1) ?> KB
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= $doc['download_count'] ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($doc['is_public']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                                                    Public
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <div class="w-2 h-2 bg-gray-500 rounded-full mr-1"></div>
                                                    Private
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?= date('M d, Y H:i', strtotime($doc['uploaded_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="?action=download&id=<?= $doc['id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-800" title="Download">
                                                    <i data-lucide="download"></i>
                                                </a>
                                                <a href="?action=toggle_public&id=<?= $doc['id'] ?>" 
                                                   class="text-yellow-600 hover:text-yellow-800" title="Toggle Visibility">
                                                    <i data-lucide="eye"></i>
                                                </a>
                                                <a href="?action=delete&id=<?= $doc['id'] ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this document?')"
                                                   class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i data-lucide="trash-2"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            lucide.createIcons();
            
            // Select all functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            const docCheckboxes = document.querySelectorAll('.doc-checkbox');
            
            selectAllCheckbox?.addEventListener('change', function() {
                docCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
            
            // Update select all when individual checkboxes change
            docCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedCount = document.querySelectorAll('.doc-checkbox:checked').length;
                    selectAllCheckbox.checked = checkedCount === docCheckboxes.length;
                    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < docCheckboxes.length;
                });
            });
            
            // Refresh button with loading state
            document.getElementById('refreshBtn')?.addEventListener('click', function() {
                this.innerHTML = '<i data-lucide="loader-2" class="animate-spin"></i> Refreshing...';
                lucide.createIcons();
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
            
            // Auto-refresh statistics every 30 seconds
            setInterval(updateStats, 30000);
            
            // Auto-hide messages
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(msg => {
                setTimeout(() => {
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                }, 5000);
            });
            
            // Add confirmation for bulk actions
            document.getElementById('bulkForm')?.addEventListener('submit', function(e) {
                const action = this.querySelector('select[name="bulk_action"]').value;
                const selectedCount = document.querySelectorAll('.doc-checkbox:checked').length;
                
                if (selectedCount === 0) {
                    e.preventDefault();
                    alert('Please select at least one document.');
                    return;
                }
                
                let confirmMessage = '';
                switch (action) {
                    case 'delete':
                        confirmMessage = `Are you sure you want to delete ${selectedCount} document(s)? This action cannot be undone.`;
                        break;
                    case 'make_public':
                        confirmMessage = `Are you sure you want to make ${selectedCount} document(s) public?`;
                        break;
                    case 'make_private':
                        confirmMessage = `Are you sure you want to make ${selectedCount} document(s) private?`;
                        break;
                    default:
                        e.preventDefault();
                        alert('Please select an action.');
                        return;
                }
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                }
            });
            
            // Add tooltips for action buttons
            const actionButtons = document.querySelectorAll('a[title]');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'absolute bg-gray-900 text-white text-xs rounded py-1 px-2 z-50';
                    tooltip.textContent = this.getAttribute('title');
                    tooltip.style.top = (this.offsetTop - 30) + 'px';
                    tooltip.style.left = (this.offsetLeft + this.offsetWidth / 2) + 'px';
                    tooltip.style.transform = 'translateX(-50%)';
                    document.body.appendChild(tooltip);
                    this.tooltip = tooltip;
                });
                
                button.addEventListener('mouseleave', function() {
                    if (this.tooltip) {
                        this.tooltip.remove();
                        this.tooltip = null;
                    }
                });
            });
        });
        
        // Function to update statistics via AJAX
        function updateStats() {
            fetch('?ajax=get_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-docs').textContent = data.total_documents;
                    document.getElementById('public-docs').textContent = data.public_documents;
                    document.getElementById('private-docs').textContent = data.private_documents;
                    document.getElementById('total-downloads').textContent = data.total_downloads;
                    document.getElementById('storage-used').textContent = (data.total_size / 1024 / 1024).toFixed(1) + ' MB';
                    document.getElementById('categories-count').textContent = data.categories_count;
                })
                .catch(error => console.log('Stats update failed:', error));
        }
        
        // Function to show loading state
        function showLoading(element) {
            const originalContent = element.innerHTML;
            element.innerHTML = '<i data-lucide="loader-2" class="animate-spin"></i> Loading...';
            element.disabled = true;
            lucide.createIcons();
            
            return function hideLoading() {
                element.innerHTML = originalContent;
                element.disabled = false;
                lucide.createIcons();
            };
        }
        
        // Function to show success message
        function showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 mx-6 mt-4 rounded';
            successDiv.textContent = message;
            document.querySelector('.ml-64').insertBefore(successDiv, document.querySelector('.flex-1'));
            
            setTimeout(() => {
                successDiv.style.opacity = '0';
                setTimeout(() => successDiv.remove(), 300);
            }, 3000);
        }
        
        // Function to show error message
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 mx-6 mt-4 rounded';
            errorDiv.textContent = message;
            document.querySelector('.ml-64').insertBefore(errorDiv, document.querySelector('.flex-1'));
            
            setTimeout(() => {
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>
