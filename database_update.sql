-- =====================================================
-- VISITOR MANAGEMENT SYSTEM - DATABASE UPDATE SCRIPT
-- =====================================================
-- This script updates the database structure for the visitor management system
-- Run this script to ensure all tables are properly configured

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `admin_visitors` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

-- Use the database
USE `admin_visitors`;

-- =====================================================
-- 1. CREATE/UPDATE GUEST_SUBMISSIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `guest_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `agreement` tinyint(1) NOT NULL DEFAULT 0,
  `checked_out` tinyint(1) DEFAULT 0,
  `checked_out_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `idx_checked_out` (`checked_out`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. CREATE VISITOR STATISTICS TABLE (Optional)
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `total_checkins` int(11) DEFAULT 0,
  `total_checkouts` int(11) DEFAULT 0,
  `active_visitors` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. CREATE VISITOR LOGS TABLE (For detailed tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) NOT NULL,
  `action` enum('checkin','checkout','update') NOT NULL,
  `action_details` text DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visitor_id` (`visitor_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`visitor_id`) REFERENCES `guest_submissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. CREATE ADMIN USERS TABLE (For employee management)
-- =====================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employee','supervisor') DEFAULT 'employee',
  `full_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. INSERT DEFAULT ADMIN USER (Optional)
-- =====================================================
-- Password: admin123 (hashed)
INSERT IGNORE INTO `admin_users` (`username`, `email`, `password_hash`, `role`, `full_name`) 
VALUES ('admin', 'admin@visitor.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator');

-- =====================================================
-- 6. CREATE INDEXES FOR PERFORMANCE
-- =====================================================
-- Add additional indexes for better performance
ALTER TABLE `guest_submissions` 
ADD INDEX `idx_full_name` (`full_name`),
ADD INDEX `idx_phone` (`phone`),
ADD INDEX `idx_agreement` (`agreement`);

-- =====================================================
-- 7. CREATE VIEWS FOR COMMON QUERIES
-- =====================================================
-- Active visitors view
CREATE OR REPLACE VIEW `active_visitors` AS
SELECT 
    id,
    full_name,
    email,
    phone,
    notes,
    submitted_at,
    TIMESTAMPDIFF(MINUTE, submitted_at, NOW()) as duration_minutes,
    TIMESTAMPDIFF(HOUR, submitted_at, NOW()) as duration_hours
FROM `guest_submissions` 
WHERE `checked_out` = 0;

-- Daily statistics view
CREATE OR REPLACE VIEW `daily_stats` AS
SELECT 
    DATE(submitted_at) as date,
    COUNT(*) as total_checkins,
    COUNT(CASE WHEN checked_out = 1 THEN 1 END) as total_checkouts,
    COUNT(CASE WHEN checked_out = 0 THEN 1 END) as active_visitors
FROM `guest_submissions` 
GROUP BY DATE(submitted_at);

-- =====================================================
-- 8. CREATE STORED PROCEDURES
-- =====================================================
DELIMITER //

-- Procedure to check out a visitor
CREATE PROCEDURE IF NOT EXISTS `CheckOutVisitor`(IN visitor_id INT, IN performed_by VARCHAR(255))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Update visitor status
    UPDATE `guest_submissions` 
    SET `checked_out` = 1, `checked_out_at` = NOW() 
    WHERE `id` = visitor_id AND `checked_out` = 0;
    
    -- Log the action
    INSERT INTO `visitor_logs` (`visitor_id`, `action`, `action_details`, `performed_by`)
    VALUES (visitor_id, 'checkout', 'Visitor checked out', performed_by);
    
    COMMIT;
END //

-- Procedure to get visitor statistics
CREATE PROCEDURE IF NOT EXISTS `GetVisitorStats`()
BEGIN
    SELECT 
        COUNT(*) as total_visitors,
        COUNT(CASE WHEN checked_out = 0 THEN 1 END) as active_visitors,
        COUNT(CASE WHEN DATE(submitted_at) = CURDATE() THEN 1 END) as checked_in_today,
        COUNT(CASE WHEN DATE(checked_out_at) = CURDATE() THEN 1 END) as checked_out_today
    FROM `guest_submissions`;
END //

DELIMITER ;

-- =====================================================
-- 9. CREATE TRIGGERS FOR AUTOMATIC LOGGING
-- =====================================================
DELIMITER //

-- Trigger to log check-ins
CREATE TRIGGER IF NOT EXISTS `log_checkin` 
AFTER INSERT ON `guest_submissions`
FOR EACH ROW
BEGIN
    INSERT INTO `visitor_logs` (`visitor_id`, `action`, `action_details`)
    VALUES (NEW.id, 'checkin', CONCAT('Visitor ', NEW.full_name, ' checked in'));
END //

-- Trigger to log check-outs
CREATE TRIGGER IF NOT EXISTS `log_checkout` 
AFTER UPDATE ON `guest_submissions`
FOR EACH ROW
BEGIN
    IF OLD.checked_out = 0 AND NEW.checked_out = 1 THEN
        INSERT INTO `visitor_logs` (`visitor_id`, `action`, `action_details`)
        VALUES (NEW.id, 'checkout', CONCAT('Visitor ', NEW.full_name, ' checked out'));
    END IF;
END //

DELIMITER ;

-- =====================================================
-- 10. GRANT PERMISSIONS (Adjust as needed)
-- =====================================================
-- Create user for the application
CREATE USER IF NOT EXISTS 'admin_visitors'@'localhost' IDENTIFIED BY '123';
GRANT SELECT, INSERT, UPDATE, DELETE ON `admin_visitors`.* TO 'admin_visitors'@'localhost';
FLUSH PRIVILEGES;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
-- Check if tables were created successfully
SELECT 'Tables Created:' as Status;
SHOW TABLES;

-- Check table structures
SELECT 'guest_submissions structure:' as Status;
DESCRIBE guest_submissions;

SELECT 'visitor_logs structure:' as Status;
DESCRIBE visitor_logs;

SELECT 'admin_users structure:' as Status;
DESCRIBE admin_users;

-- =====================================================
-- SCRIPT COMPLETION MESSAGE
-- =====================================================
SELECT 'Database update completed successfully!' as Status;
