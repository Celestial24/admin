<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$employeeId = $_SESSION['employee_id'] ?? null;
$roles = $_SESSION['roles'] ?? 'Employee';
$userName = $_SESSION['user_name'] ?? 'User';

// DB Connection
$host = 'localhost';
$user = 'admin_Document';
$pass = '123';
$db = 'admin_Document';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create required tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    roles VARCHAR(255) DEFAULT 'Employee',
    employee_id VARCHAR(50) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS user_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT NULL,
    file_type VARCHAR(100) DEFAULT NULL,
    category VARCHAR(100) DEFAULT 'General',
    tags TEXT DEFAULT NULL,
    is_public TINYINT(1) DEFAULT 0,
    download_count INT DEFAULT 0,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_user_id (user_id),
    KEY idx_category (category),
    KEY idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS user_pins (
    user_id INT PRIMARY KEY,
    pin_code VARCHAR(255) NOT NULL,
    attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// PIN Code Security Layer
$user_id = $_SESSION['user_id'];
// Allow admin to reset PIN back to default (0000) via header action
if (isset($_GET['reset_pin']) && $_GET['reset_pin'] === '1') {
    $default_pin = password_hash("0000", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO user_pins (user_id, pin_code, attempts, locked_until) VALUES (?, ?, 0, NULL)
        ON DUPLICATE KEY UPDATE pin_code = VALUES(pin_code), attempts = 0, locked_until = NULL");
    $stmt->bind_param("is", $user_id, $default_pin);
    if ($stmt->execute()) {
        unset($_SESSION['pin_verified'], $_SESSION['pin_verified_time']);
        $successMessage = "PIN has been reset to default (0000).";
    }
    $stmt->close();
}
if (!isset($_SESSION['pin_verified']) || !$_SESSION['pin_verified']) {
    // Check if user has a PIN record
    $stmt = $conn->prepare("SELECT pin_code, attempts, locked_until FROM user_pins WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pin_data = $result->fetch_assoc();
    $stmt->close();

    // If no PIN record exists, create one with default PIN "0000"
    if (!$pin_data) {
        $default_pin = password_hash("0000", PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO user_pins (user_id, pin_code) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $default_pin);
        $stmt->execute();
        $stmt->close();
        $pin_data = ['pin_code' => $default_pin, 'attempts' => 0, 'locked_until' => null];
    }

    // Check if account is locked
    if ($pin_data['locked_until'] && strtotime($pin_data['locked_until']) > time()) {
        $remaining = strtotime($pin_data['locked_until']) - time();
        $pin_error = "Account locked. Try again in " . ceil($remaining / 60) . " minutes.";
        showPinForm($pin_error);
        exit();
    }

    // Handle PIN submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin_code'])) {
        if (password_verify($_POST['pin_code'], $pin_data['pin_code'])) {
            $_SESSION['pin_verified'] = true;
            $_SESSION['pin_verified_time'] = time();
            // Reset attempts
            $stmt = $conn->prepare("UPDATE user_pins SET attempts = 0, locked_until = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $new_attempts = $pin_data['attempts'] + 1;
            $locked_until = null;
            if ($new_attempts >= 3) {
                $locked_until = date('Y-m-d H:i:s', time() + 300); // 5 minutes
                $pin_error = "Too many failed attempts. Account locked for 5 minutes.";
            } else {
                $pin_error = "Invalid PIN. " . (3 - $new_attempts) . " attempts remaining.";
            }
            // Update attempts in database
            $stmt = $conn->prepare("UPDATE user_pins SET attempts = ?, locked_until = ? WHERE user_id = ?");
            $stmt->bind_param("isi", $new_attempts, $locked_until, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    showPinForm($pin_error ?? '');
    exit();
}

// Auto-expire PIN verification after 30 minutes
if (isset($_SESSION['pin_verified_time']) && (time() - $_SESSION['pin_verified_time']) > 1800) {
    unset($_SESSION['pin_verified'], $_SESSION['pin_verified_time']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $tags = trim($_POST['tags']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['document_file']['name']);
    $filePath = $uploadDir . $fileName;
    $fileSize = $_FILES['document_file']['size'];
    $fileType = $_FILES['document_file']['type'];
    
    if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
        $stmt = $conn->prepare("INSERT INTO user_documents (user_id, title, description, file_name, file_path, file_size, file_type, category, tags, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssisssi", $user_id, $title, $description, $_FILES['document_file']['name'], $filePath, $fileSize, $fileType, $category, $tags, $is_public);
        
        if ($stmt->execute()) {
            $successMessage = "Document uploaded successfully!";
        } else {
            $errorMessage = "Error uploading document to database.";
        }
        $stmt->close();
    } else {
        $errorMessage = "Error uploading file.";
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedDocs = $_POST['selected_docs'] ?? [];
    if (!empty($selectedDocs)) {
        $placeholders = str_repeat('?,', count($selectedDocs) - 1) . '?';
        switch ($action) {
            case 'delete':
                $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($selectedDocs)), ...$selectedDocs);
                $stmt->execute();
                $result = $stmt->get_result();
                $filePaths = [];
                while ($row = $result->fetch_assoc()) {
                    $filePaths[] = $row['file_path'];
                }
                $stmt->close();
                $deleteStmt = $conn->prepare("DELETE FROM user_documents WHERE id IN ($placeholders)");
                $deleteStmt->bind_param(str_repeat('i', count($selectedDocs)), ...$selectedDocs);
                if ($deleteStmt->execute()) {
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
                    $updateStmt = $conn->prepare("UPDATE user_documents SET download_count = download_count + 1 WHERE id = ?");
                    $updateStmt->bind_param("i", $docId);
                    $updateStmt->execute();
                    $updateStmt->close();
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

// Get statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM user_documents");
$stats['total_documents'] = $result ? $result->fetch_assoc()['count'] : 0;
$result = $conn->query("SELECT SUM(file_size) as total FROM user_documents");
$stats['total_size'] = $result ? ($result->fetch_assoc()['total'] ?: 0) : 0;
$result = $conn->query("SELECT SUM(download_count) as total FROM user_documents");
$stats['total_downloads'] = $result ? ($result->fetch_assoc()['total'] ?: 0) : 0;
$result = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 1");
$stats['public_documents'] = $result ? $result->fetch_assoc()['count'] : 0;
$result = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 0");
$stats['private_documents'] = $result ? $result->fetch_assoc()['count'] : 0;
$result = $conn->query("SELECT COUNT(DISTINCT category) as count FROM user_documents");
$stats['categories_count'] = $result ? $result->fetch_assoc()['count'] : 0;

$categoriesResult = $conn->query("SELECT DISTINCT category FROM user_documents ORDER BY category");
$categories = [];
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$usersResult = $conn->query("SELECT DISTINCT ud.user_id, u.name, u.email FROM user_documents ud LEFT JOIN users u ON ud.user_id = u.id ORDER BY u.name");
$users = [];
if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

if (!function_exists('formatFileSize')) {
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
}

function showPinForm($error = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PIN Required - Document Archiver</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="icon" type="image/png" href="assets/image/logo2.png">
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-6">
                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">PIN Required</h2>
                <p class="text-gray-600 mt-2">Please enter your PIN code to access the document management system.</p>
            </div>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label for="pin_code" class="block text-sm font-medium text-gray-700 mb-2">PIN Code</label>
                    <input type="password" 
                           id="pin_code" 
                           name="pin_code" 
                           maxlength="4"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-center text-2xl tracking-widest"
                           autocomplete="off"
                           autofocus
                           required>
                </div>
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-300">
                    Verify PIN
                </button>
            </form>
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Default PIN: 0000</p>
                <p>For security reasons, access to this page requires additional verification.</p>
            </div>
        </div>
        <script>
            document.getElementById('pin_code')?.addEventListener('input', function(e) {
                if (this.value.length === 4) {
                    this.form.submit();
                }
            });
            document.getElementById('pin_code')?.addEventListener('keypress', function(e) {
                if (e.key < '0' || e.key > '9') {
                    e.preventDefault();
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Archiver</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/image/logo2.png">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#1e40af'
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen flex bg-gray-50">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>

    <div class="flex-1 min-h-screen overflow-auto">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-file-alt text-2xl text-blue-600 mr-3"></i>
                    <h1 class="text-2xl font-bold text-gray-900">Document Archiver</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?= htmlspecialchars($userName) ?></span>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"><?= htmlspecialchars($roles) ?></span>
                    <a href="?reset_pin=1" class="text-yellow-600 hover:text-yellow-800" onclick="return confirm('Reset PIN to default (0000)?')">
                        <i class="fas fa-key"></i> Reset PIN
                    </a>
                    <a href="../auth/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-file text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Documents</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_documents'] ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-download text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Downloads</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['total_downloads'] ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-folder text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Categories</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['categories_count'] ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                        <i class="fas fa-database text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Storage Used</p>
                        <p class="text-2xl font-bold text-gray-900"><?= formatFileSize($stats['total_size']) ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-eye text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Public Docs</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['public_documents'] ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-eye-slash text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Private Docs</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $stats['private_documents'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">Upload New Document</h2>
            </div>
            <div class="p-6">
                <?php if (isset($successMessage)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($successMessage) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($errorMessage)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Document Title</label>
                            <input type="text" id="title" name="title" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select id="category" name="category" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="General">General</option>
                                <option value="HR">HR Documents</option>
                                <option value="Finance">Finance</option>
                                <option value="Technical">Technical</option>
                                <option value="Legal">Legal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags (comma separated)</label>
                            <input type="text" id="tags" name="tags"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="document, report, 2024">
                        </div>
                        <div>
                            <label for="document_file" class="block text-sm font-medium text-gray-700 mb-2">Document File</label>
                            <input type="file" id="document_file" name="document_file" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="is_public" name="is_public" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_public" class="ml-2 block text-sm text-gray-900">Make this document public (visible to all users)</label>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit"
                                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                            <i class="fas fa-upload mr-2"></i>Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Document Library</h2>
                <div class="flex space-x-4">
                    <!-- Search -->
                    <form method="GET" class="flex space-x-2">
                        <input type="text" name="search" placeholder="Search documents..." 
                               value="<?= htmlspecialchars($searchQuery) ?>"
                               class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- Filters -->
                    <form method="GET" class="flex space-x-2">
                        <select name="category" onchange="this.form.submit()"
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" id="bulkForm" class="px-6 py-4 border-b bg-gray-50">
                <div class="flex items-center space-x-4">
                    <select name="bulk_action" class="px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete Selected</option>
                        <option value="make_public">Make Public</option>
                        <option value="make_private">Make Private</option>
                    </select>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Apply
                    </button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" class="rounded">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=title&order=<?= $sortBy === 'title' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>">
                                    Title <?= $sortBy === 'title' ? ($sortOrder === 'ASC' ? '↑' : '↓') : '' ?>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=file_size&order=<?= $sortBy === 'file_size' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>">
                                    Size <?= $sortBy === 'file_size' ? ($sortOrder === 'ASC' ? '↑' : '↓') : '' ?>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Visibility
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=uploaded_at&order=<?= $sortBy === 'uploaded_at' && $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>">
                                    Uploaded <?= $sortBy === 'uploaded_at' ? ($sortOrder === 'ASC' ? '↑' : '↓') : '' ?>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($documents as $doc): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" name="selected_docs[]" value="<?= $doc['id'] ?>" class="docCheckbox rounded">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-file text-gray-400 mr-3"></i>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($doc['title']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($doc['file_name']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($doc['category']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= formatFileSize($doc['file_size']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $doc['is_public'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $doc['is_public'] ? 'Public' : 'Private' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M j, Y g:i A', strtotime($doc['uploaded_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="?action=download&id=<?= $doc['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-900" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="?action=toggle_public&id=<?= $doc['id'] ?>" 
                                       class="text-green-600 hover:text-green-900" title="<?= $doc['is_public'] ? 'Make Private' : 'Make Public' ?>">
                                        <i class="fas fa-eye<?= $doc['is_public'] ? '' : '-slash' ?>"></i>
                                    </a>
                                    <a href="?action=delete&id=<?= $doc['id'] ?>" 
                                       class="text-red-600 hover:text-red-900" 
                                       onclick="return confirm('Are you sure you want to delete this document?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                No documents found. Upload your first document to get started!
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.docCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk form submission
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.docCheckbox:checked');
            if (selected.length === 0) {
                e.preventDefault();
                alert('Please select at least one document.');
                return false;
            }
            
            const action = this.bulk_action.value;
            if (!action) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return false;
            }
            
            if (action === 'delete' && !confirm('Are you sure you want to delete the selected documents?')) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</div>
</body>
</html>