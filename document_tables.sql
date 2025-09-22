-- Document Management System Database Tables
-- Database: admin_Document
-- User: admin_Document
-- Password: 123

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS admin_Document;
USE admin_Document;

-- =====================================================
-- 1. USER_DOCUMENTS TABLE
-- Main table for storing document information
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. DOCUMENT_CATEGORIES TABLE
-- Predefined categories for better organization
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. DOCUMENT_ACCESS_LOGS TABLE
-- Track who accessed what documents when
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. DOCUMENT_SHARING TABLE
-- For sharing documents with specific users
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. DOCUMENT_VERSIONS TABLE
-- Track document version history
-- =====================================================
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- INSERT DEFAULT CATEGORIES
-- =====================================================
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
('Other', 'Other file types', '#6B7280', 'folder', 10);

-- =====================================================
-- CREATE USERS TABLE (if not exists)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    password varchar(255) NOT NULL,
    role varchar(50) DEFAULT 'User',
    created_at timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT 'Document Management System tables created successfully!' as message;
