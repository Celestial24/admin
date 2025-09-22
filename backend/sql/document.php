<?php
/**
 * Document Management System - Database Schema and Queries
 * This file contains all SQL operations for the document management system
 */

// Database connection - only create if not already exists
if (!isset($conn)) {
    $host = 'localhost';
    $user = 'admin_Document';
    $pass = '123';
    $db = 'admin_Document';

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// =====================================================
// DOCUMENT MANAGEMENT SYSTEM - DATABASE SCHEMA
// =====================================================

/**
 * Create user_documents table
 * Stores all document information including metadata
 */
$createUserDocumentsTable = "
CREATE TABLE IF NOT EXISTS user_documents (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    title varchar(255) NOT NULL,
    description text DEFAULT NULL,
    file_name varchar(255) NOT NULL,
    file_path varchar(500) NOT NULL,
    file_size int(11) DEFAULT NULL,
    file_type varchar(100) DEFAULT NULL,
    file_extension varchar(10) DEFAULT NULL,
    category varchar(100) DEFAULT 'General',
    tags text DEFAULT NULL,
    is_public tinyint(1) DEFAULT 0,
    is_archived tinyint(1) DEFAULT 0,
    download_count int(11) DEFAULT 0,
    view_count int(11) DEFAULT 0,
    last_accessed timestamp NULL DEFAULT NULL,
    uploaded_at timestamp NOT NULL DEFAULT current_timestamp(),
    updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_category (category),
    KEY idx_is_public (is_public),
    KEY idx_is_archived (is_archived),
    KEY idx_uploaded_at (uploaded_at),
    KEY idx_file_type (file_type),
    KEY idx_download_count (download_count),
    FULLTEXT KEY idx_search (title, description, tags)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

/**
 * Create document_categories table
 * Predefined categories for better organization
 */
$createDocumentCategoriesTable = "
CREATE TABLE IF NOT EXISTS document_categories (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    description text DEFAULT NULL,
    color_code varchar(7) DEFAULT '#3B82F6',
    icon varchar(50) DEFAULT 'file',
    is_active tinyint(1) DEFAULT 1,
    sort_order int(11) DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY unique_name (name),
    KEY idx_is_active (is_active),
    KEY idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

/**
 * Create document_access_logs table
 * Track who accessed what documents when
 */
$createDocumentAccessLogsTable = "
CREATE TABLE IF NOT EXISTS document_access_logs (
    id int(11) NOT NULL AUTO_INCREMENT,
    document_id int(11) NOT NULL,
    user_id int(11) DEFAULT NULL,
    access_type enum('view','download','edit','delete') NOT NULL,
    ip_address varchar(45) DEFAULT NULL,
    user_agent text DEFAULT NULL,
    accessed_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_document_id (document_id),
    KEY idx_user_id (user_id),
    KEY idx_access_type (access_type),
    KEY idx_accessed_at (accessed_at),
    FOREIGN KEY (document_id) REFERENCES user_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

/**
 * Create document_sharing table
 * For sharing documents with specific users
 */
$createDocumentSharingTable = "
CREATE TABLE IF NOT EXISTS document_sharing (
    id int(11) NOT NULL AUTO_INCREMENT,
    document_id int(11) NOT NULL,
    shared_with_user_id int(11) NOT NULL,
    shared_by_user_id int(11) NOT NULL,
    permission_level enum('view','download','edit') DEFAULT 'view',
    expires_at timestamp NULL DEFAULT NULL,
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY unique_share (document_id, shared_with_user_id),
    KEY idx_document_id (document_id),
    KEY idx_shared_with (shared_with_user_id),
    KEY idx_shared_by (shared_by_user_id),
    KEY idx_expires_at (expires_at),
    FOREIGN KEY (document_id) REFERENCES user_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

/**
 * Create document_versions table
 * Track document version history
 */
$createDocumentVersionsTable = "
CREATE TABLE IF NOT EXISTS document_versions (
    id int(11) NOT NULL AUTO_INCREMENT,
    document_id int(11) NOT NULL,
    version_number int(11) NOT NULL,
    file_name varchar(255) NOT NULL,
    file_path varchar(500) NOT NULL,
    file_size int(11) DEFAULT NULL,
    change_description text DEFAULT NULL,
    uploaded_by int(11) NOT NULL,
    uploaded_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY idx_document_id (document_id),
    KEY idx_version_number (version_number),
    KEY idx_uploaded_by (uploaded_by),
    FOREIGN KEY (document_id) REFERENCES user_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// =====================================================
// EXECUTE TABLE CREATION
// =====================================================

$tables = [
    'user_documents' => $createUserDocumentsTable,
    'document_categories' => $createDocumentCategoriesTable,
    'document_access_logs' => $createDocumentAccessLogsTable,
    'document_sharing' => $createDocumentSharingTable,
    'document_versions' => $createDocumentVersionsTable
];

foreach ($tables as $tableName => $sql) {
    if ($conn->query($sql)) {
        echo "✅ Table '$tableName' created/verified successfully\n";
    } else {
        echo "❌ Error creating table '$tableName': " . $conn->error . "\n";
    }
}

// =====================================================
// INSERT DEFAULT DATA
// =====================================================

/**
 * Insert default document categories
 */
$insertDefaultCategories = "
INSERT IGNORE INTO document_categories (name, description, color_code, icon, sort_order) VALUES
('General', 'General purpose documents', '#6B7280', 'file', 1),
('Contracts', 'Legal contracts and agreements', '#EF4444', 'file-text', 2),
('Reports', 'Business reports and analytics', '#3B82F6', 'bar-chart', 3),
('Forms', 'Application forms and templates', '#10B981', 'edit', 4),
('Images', 'Photos, graphics, and visual content', '#8B5CF6', 'image', 5),
('Archives', 'Compressed files and archives', '#F59E0B', 'archive', 6),
('Presentations', 'Slides and presentation files', '#EC4899', 'presentation', 7),
('Spreadsheets', 'Excel files and data sheets', '#059669', 'trending-up', 8),
('PDFs', 'PDF documents and manuals', '#DC2626', 'file-text', 9),
('Other', 'Other file types', '#6B7280', 'folder', 10)";

if ($conn->query($insertDefaultCategories)) {
    echo "✅ Default categories inserted successfully\n";
} else {
    echo "❌ Error inserting default categories: " . $conn->error . "\n";
}

// =====================================================
// USEFUL QUERIES AND FUNCTIONS
// =====================================================

/**
 * Get document statistics
 */
function getDocumentStats($conn) {
    $stats = [];
    
    // Total documents
    $result = $conn->query("SELECT COUNT(*) as count FROM user_documents");
    $stats['total_documents'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Total size
    $result = $conn->query("SELECT SUM(file_size) as total FROM user_documents");
    $stats['total_size'] = $result ? ($result->fetch_assoc()['total'] ?: 0) : 0;
    
    // Total downloads
    $result = $conn->query("SELECT SUM(download_count) as total FROM user_documents");
    $stats['total_downloads'] = $result ? ($result->fetch_assoc()['total'] ?: 0) : 0;
    
    // Public documents
    $result = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 1");
    $stats['public_documents'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Private documents
    $result = $conn->query("SELECT COUNT(*) as count FROM user_documents WHERE is_public = 0");
    $stats['private_documents'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Categories count
    $result = $conn->query("SELECT COUNT(DISTINCT category) as count FROM user_documents");
    $stats['categories_count'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Most downloaded documents
    $result = $conn->query("SELECT title, download_count FROM user_documents ORDER BY download_count DESC LIMIT 5");
    $stats['top_downloaded'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Recent uploads
    $result = $conn->query("SELECT title, uploaded_at FROM user_documents ORDER BY uploaded_at DESC LIMIT 5");
    $stats['recent_uploads'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    return $stats;
}

/**
 * Get documents with filters
 */
function getDocuments($conn, $filters = []) {
    $whereClause = "1=1";
    $params = [];
    $paramTypes = "";
    
    // User filter
    if (isset($filters['user_id'])) {
        $whereClause .= " AND user_id = ?";
        $params[] = $filters['user_id'];
        $paramTypes .= "i";
    }
    
    // Category filter
    if (isset($filters['category']) && !empty($filters['category'])) {
        $whereClause .= " AND category = ?";
        $params[] = $filters['category'];
        $paramTypes .= "s";
    }
    
    // Public/Private filter
    if (isset($filters['is_public'])) {
        $whereClause .= " AND is_public = ?";
        $params[] = $filters['is_public'];
        $paramTypes .= "i";
    }
    
    // Search filter
    if (isset($filters['search']) && !empty($filters['search'])) {
        $whereClause .= " AND (title LIKE ? OR description LIKE ? OR tags LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $paramTypes .= "sss";
    }
    
    // File type filter
    if (isset($filters['file_type']) && !empty($filters['file_type'])) {
        $whereClause .= " AND file_type = ?";
        $params[] = $filters['file_type'];
        $paramTypes .= "s";
    }
    
    // Date range filter
    if (isset($filters['date_from'])) {
        $whereClause .= " AND uploaded_at >= ?";
        $params[] = $filters['date_from'];
        $paramTypes .= "s";
    }
    
    if (isset($filters['date_to'])) {
        $whereClause .= " AND uploaded_at <= ?";
        $params[] = $filters['date_to'];
        $paramTypes .= "s";
    }
    
    // Sort options
    $sortBy = $filters['sort_by'] ?? 'uploaded_at';
    $sortOrder = $filters['sort_order'] ?? 'DESC';
    $allowedSorts = ['title', 'file_name', 'file_size', 'category', 'uploaded_at', 'download_count', 'view_count'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'uploaded_at';
    }
    
    // Limit
    $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
    $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
    
    $sql = "SELECT ud.*, u.name as user_name, u.email as user_email 
            FROM user_documents ud 
            LEFT JOIN users u ON ud.user_id = u.id 
            WHERE $whereClause 
            ORDER BY $sortBy $sortOrder 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($paramTypes, ...$params);
    }
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $documents = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $documents;
    }
    
    return [];
}

/**
 * Log document access
 */
function logDocumentAccess($conn, $documentId, $userId, $accessType, $ipAddress = null, $userAgent = null) {
    $sql = "INSERT INTO document_access_logs (document_id, user_id, access_type, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iisss", $documentId, $userId, $accessType, $ipAddress, $userAgent);
        $result = $stmt->execute();
        $stmt->close();
        
        // Update document view count
        if ($accessType === 'view') {
            $updateSql = "UPDATE user_documents SET view_count = view_count + 1, last_accessed = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param("i", $documentId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
        
        return $result;
    }
    
    return false;
}

/**
 * Get file type icon
 */
function getFileTypeIcon($fileExtension) {
    $icons = [
        'pdf' => '📕',
        'doc' => '📘',
        'docx' => '📘',
        'txt' => '📄',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'zip' => '📦',
        'rar' => '📦',
        'xls' => '📈',
        'xlsx' => '📈',
        'ppt' => '📽️',
        'pptx' => '📽️',
        'mp4' => '🎥',
        'avi' => '🎥',
        'mp3' => '🎵',
        'wav' => '🎵'
    ];
    
    return $icons[strtolower($fileExtension)] ?? '📄';
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Get document categories
 */
function getDocumentCategories($conn) {
    $sql = "SELECT * FROM document_categories WHERE is_active = 1 ORDER BY sort_order, name";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Clean up old access logs (older than 1 year)
 */
function cleanupOldAccessLogs($conn) {
    $sql = "DELETE FROM document_access_logs WHERE accessed_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    $result = $conn->query($sql);
    return $result ? $conn->affected_rows : 0;
}

// =====================================================
// SAMPLE QUERIES FOR TESTING
// =====================================================

if (isset($_GET['test'])) {
    echo "\n=== TESTING DOCUMENT SYSTEM ===\n";
    
    // Test statistics
    $stats = getDocumentStats($conn);
    echo "📊 Statistics:\n";
    foreach ($stats as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . count($value) . " items\n";
        } else {
            echo "  $key: $value\n";
        }
    }
    
    // Test categories
    $categories = getDocumentCategories($conn);
    echo "\n📁 Categories:\n";
    foreach ($categories as $category) {
        echo "  {$category['name']} - {$category['description']}\n";
    }
    
    // Test documents query
    $documents = getDocuments($conn, ['limit' => 5]);
    echo "\n📄 Recent Documents:\n";
    foreach ($documents as $doc) {
        echo "  {$doc['title']} ({$doc['category']}) - " . formatFileSize($doc['file_size']) . "\n";
    }
    
    echo "\n✅ All tests completed!\n";
}

// Close connection
$conn->close();

echo "\n🎉 Document Management System database setup completed!\n";
?>
