-- =====================================================
-- ENHANCED VISITOR MANAGEMENT SYSTEM - DATABASE UPDATE
-- =====================================================
-- This script updates the database for Time-in/Time-out system with visitor types and reporting

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `admin_visitors` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_general_ci;

-- Use the database
USE `admin_visitors`;

-- =====================================================
-- 1. CREATE VISITOR TYPES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color_code` varchar(7) DEFAULT '#3B82F6',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default visitor types
INSERT IGNORE INTO `visitor_types` (`type_name`, `description`, `color_code`) VALUES
('Business', 'Business meetings, conferences, corporate visits', '#3B82F6'),
('Personal', 'Personal visits, family, friends', '#10B981'),
('Delivery', 'Package delivery, courier services', '#F59E0B'),
('Maintenance', 'Repair services, maintenance work', '#EF4444'),
('Interview', 'Job interviews, recruitment', '#8B5CF6'),
('Inspection', 'Safety inspections, audits', '#06B6D4'),
('Other', 'Other types of visits', '#6B7280');

-- =====================================================
-- 2. UPDATE GUEST_SUBMISSIONS TABLE
-- =====================================================
-- Add new columns for enhanced visitor management
ALTER TABLE `guest_submissions` 
ADD COLUMN IF NOT EXISTS `visitor_type_id` int(11) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `purpose` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `company` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `host_employee` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `expected_duration` int(11) DEFAULT NULL COMMENT 'Expected duration in minutes',
ADD COLUMN IF NOT EXISTS `time_in` timestamp NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `time_out` timestamp NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `actual_duration` int(11) DEFAULT NULL COMMENT 'Actual duration in minutes',
ADD COLUMN IF NOT EXISTS `status` enum('Scheduled','Time In','Time Out','Completed','Cancelled') DEFAULT 'Time In',
ADD COLUMN IF NOT EXISTS `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
ADD COLUMN IF NOT EXISTS `security_level` enum('Public','Restricted','Confidential') DEFAULT 'Public',
ADD COLUMN IF NOT EXISTS `badge_number` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `photo_path` varchar(500) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `id_verified` tinyint(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `temperature` decimal(4,1) DEFAULT NULL COMMENT 'Body temperature in Celsius',
ADD COLUMN IF NOT EXISTS `health_declaration` text DEFAULT NULL;

-- Add indexes for new columns
ALTER TABLE `guest_submissions` 
ADD INDEX `idx_visitor_type` (`visitor_type_id`),
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_priority` (`priority`),
ADD INDEX `idx_time_in` (`time_in`),
ADD INDEX `idx_time_out` (`time_out`),
ADD INDEX `idx_company` (`company`),
ADD INDEX `idx_host_employee` (`host_employee`);

-- Add foreign key constraint
ALTER TABLE `guest_submissions` 
ADD CONSTRAINT `fk_visitor_type` 
FOREIGN KEY (`visitor_type_id`) REFERENCES `visitor_types`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 3. CREATE VISITOR REPORTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` enum('Daily','Weekly','Monthly','Custom','Visitor_Type','Employee','Security') NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_data` longtext NOT NULL COMMENT 'JSON data',
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `generated_by` varchar(255) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_date_range` (`date_from`, `date_to`),
  KEY `idx_generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. CREATE VISITOR ANALYTICS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `visitor_type_id` int(11) DEFAULT NULL,
  `total_visitors` int(11) DEFAULT 0,
  `time_in_count` int(11) DEFAULT 0,
  `time_out_count` int(11) DEFAULT 0,
  `avg_duration` decimal(8,2) DEFAULT 0.00 COMMENT 'Average duration in minutes',
  `peak_hour` int(11) DEFAULT NULL COMMENT 'Hour with most visitors (0-23)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date_type` (`date`, `visitor_type_id`),
  KEY `idx_date` (`date`),
  KEY `idx_visitor_type` (`visitor_type_id`),
  FOREIGN KEY (`visitor_type_id`) REFERENCES `visitor_types`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. CREATE VISITOR MONITORING TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `visitor_monitoring` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) NOT NULL,
  `monitor_type` enum('Entry','Exit','Location_Change','Security_Alert','Duration_Warning') NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `alert_level` enum('Info','Warning','Critical') DEFAULT 'Info',
  `message` text NOT NULL,
  `monitored_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visitor_id` (`visitor_id`),
  KEY `idx_monitor_type` (`monitor_type`),
  KEY `idx_alert_level` (`alert_level`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`visitor_id`) REFERENCES `guest_submissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 6. CREATE EMPLOYEES TABLE (for host tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_id` (`employee_id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_department` (`department`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample employees
INSERT IGNORE INTO `employees` (`employee_id`, `full_name`, `email`, `department`, `position`) VALUES
('EMP001', 'John Smith', 'john.smith@company.com', 'IT', 'IT Manager'),
('EMP002', 'Sarah Johnson', 'sarah.johnson@company.com', 'HR', 'HR Manager'),
('EMP003', 'Mike Wilson', 'mike.wilson@company.com', 'Finance', 'Finance Director'),
('EMP004', 'Lisa Brown', 'lisa.brown@company.com', 'Operations', 'Operations Manager');

-- =====================================================
-- 7. CREATE VIEWS FOR REPORTING
-- =====================================================
-- Daily visitor summary view
CREATE OR REPLACE VIEW `daily_visitor_summary` AS
SELECT 
    DATE(time_in) as visit_date,
    vt.type_name as visitor_type,
    COUNT(*) as total_visitors,
    COUNT(CASE WHEN status = 'Time In' THEN 1 END) as active_visitors,
    COUNT(CASE WHEN status = 'Time Out' THEN 1 END) as completed_visits,
    AVG(actual_duration) as avg_duration_minutes,
    MIN(time_in) as first_time_in,
    MAX(time_out) as last_time_out
FROM guest_submissions gs
LEFT JOIN visitor_types vt ON gs.visitor_type_id = vt.id
WHERE time_in IS NOT NULL
GROUP BY DATE(time_in), vt.type_name;

-- Visitor type statistics view
CREATE OR REPLACE VIEW `visitor_type_stats` AS
SELECT 
    vt.type_name,
    vt.color_code,
    COUNT(*) as total_visits,
    COUNT(CASE WHEN gs.status = 'Time In' THEN 1 END) as active_visits,
    AVG(gs.actual_duration) as avg_duration,
    MAX(gs.time_in) as last_visit
FROM visitor_types vt
LEFT JOIN guest_submissions gs ON vt.id = gs.visitor_type_id
GROUP BY vt.id, vt.type_name, vt.color_code;

-- Employee visitor statistics view
CREATE OR REPLACE VIEW `employee_visitor_stats` AS
SELECT 
    host_employee,
    COUNT(*) as total_visitors,
    COUNT(CASE WHEN status = 'Time In' THEN 1 END) as active_visitors,
    AVG(actual_duration) as avg_duration_minutes
FROM guest_submissions
WHERE host_employee IS NOT NULL
GROUP BY host_employee;

-- =====================================================
-- 8. CREATE STORED PROCEDURES FOR REPORTING
-- =====================================================
DELIMITER //

-- Procedure to generate daily report
CREATE PROCEDURE IF NOT EXISTS `GenerateDailyReport`(IN report_date DATE, IN generated_by VARCHAR(255))
BEGIN
    DECLARE report_data JSON;
    
    SELECT JSON_OBJECT(
        'date', report_date,
        'total_visitors', COUNT(*),
        'active_visitors', COUNT(CASE WHEN status = 'Time In' THEN 1 END),
        'completed_visits', COUNT(CASE WHEN status = 'Time Out' THEN 1 END),
        'visitor_types', JSON_ARRAYAGG(
            JSON_OBJECT(
                'type', vt.type_name,
                'count', COUNT(*),
                'color', vt.color_code
            )
        ),
        'avg_duration', AVG(actual_duration),
        'peak_hour', HOUR(time_in) as peak_hour
    ) INTO report_data
    FROM guest_submissions gs
    LEFT JOIN visitor_types vt ON gs.visitor_type_id = vt.id
    WHERE DATE(time_in) = report_date;
    
    INSERT INTO visitor_reports (report_type, report_name, report_data, date_from, date_to, generated_by)
    VALUES ('Daily', CONCAT('Daily Report - ', report_date), report_data, report_date, report_date, generated_by);
    
    SELECT report_data as report_result;
END //

-- Procedure to get visitor analytics
CREATE PROCEDURE IF NOT EXISTS `GetVisitorAnalytics`(IN start_date DATE, IN end_date DATE)
BEGIN
    SELECT 
        DATE(time_in) as date,
        vt.type_name as visitor_type,
        vt.color_code,
        COUNT(*) as total_visitors,
        COUNT(CASE WHEN status = 'Time In' THEN 1 END) as active_visitors,
        AVG(actual_duration) as avg_duration_minutes,
        MIN(time_in) as first_time_in,
        MAX(time_out) as last_time_out
    FROM guest_submissions gs
    LEFT JOIN visitor_types vt ON gs.visitor_type_id = vt.id
    WHERE DATE(time_in) BETWEEN start_date AND end_date
    GROUP BY DATE(time_in), vt.type_name, vt.color_code
    ORDER BY date DESC, total_visitors DESC;
END //

DELIMITER ;

-- =====================================================
-- 9. UPDATE EXISTING DATA (if any)
-- =====================================================
-- Update existing records to use new terminology
UPDATE guest_submissions 
SET 
    time_in = submitted_at,
    time_out = checked_out_at,
    status = CASE 
        WHEN checked_out = 1 THEN 'Time Out'
        ELSE 'Time In'
    END,
    visitor_type_id = 1
WHERE time_in IS NULL;

-- =====================================================
-- 10. VERIFICATION QUERIES
-- =====================================================
SELECT 'Enhanced Database Setup Completed!' as Status;
SELECT 'Tables Created:' as Info;
SHOW TABLES;

SELECT 'Visitor Types Available:' as Info;
SELECT type_name, description, color_code FROM visitor_types;

SELECT 'Sample Data Verification:' as Info;
SELECT COUNT(*) as total_visitors FROM guest_submissions;
SELECT COUNT(*) as visitor_types FROM visitor_types;
SELECT COUNT(*) as employees FROM employees;
