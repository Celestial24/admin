-- Schema for Facilities, Reservations, and Maintenance modules
-- Compatible with MariaDB/MySQL 10.x

SET SQL_MODE = "";
SET time_zone = "+00:00";

-- Ensure target database exists (matches backend/sql/db.php)
CREATE DATABASE IF NOT EXISTS `admin_admin` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `admin_admin`;

START TRANSACTION;

-- ------------------------------------------
-- Table: facilities
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_name` varchar(255) NOT NULL,
  `facility_type` varchar(100) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `status` enum('Available','Unavailable','Under Maintenance') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `facility_type` (`facility_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------
-- Table: reservations
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) NOT NULL,
  `reserved_by` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  KEY `start_time` (`start_time`),
  CONSTRAINT `reservations_ibfk_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------
-- Table: maintenance
-- ------------------------------------------
CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Low',
  `reported_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  KEY `priority` (`priority`),
  CONSTRAINT `maintenance_ibfk_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------
-- Optional sample data
-- ------------------------------------------
INSERT INTO `facilities` (`facility_name`, `facility_type`, `capacity`, `status`) VALUES
('Main Conference Room', 'Conference Room', 20, 'Available'),
('Gymnasium', 'Recreation', 50, 'Under Maintenance'),
('Auditorium', 'Event Hall', 200, 'Available')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP();

INSERT INTO `reservations` (`facility_id`, `reserved_by`, `start_time`, `end_time`, `status`) VALUES
(1, 'John Doe', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY + INTERVAL 2 HOUR), 'Confirmed'),
(3, 'Jane Smith', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY + INTERVAL 3 HOUR), 'Pending');

INSERT INTO `maintenance` (`facility_id`, `description`, `priority`, `reported_by`) VALUES
(2, 'Treadmill malfunction and routine equipment check', 'High', 'Maintenance Bot'),
(1, 'Projector bulb replacement', 'Medium', 'Alice Admin');

COMMIT;


