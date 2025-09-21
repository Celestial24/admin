-- ATIERA Hotel & Restaurant - Facilities Management Database
-- Database: admin_facilities
-- User: admin_admin_admin
-- Password: 123

-- Create database
CREATE DATABASE IF NOT EXISTS admin_facilities;
USE admin_facilities;

-- Create facilities table
CREATE TABLE IF NOT EXISTS facilities (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(255) NOT NULL,
    facility_type VARCHAR(100) NOT NULL,
    capacity INT(11) NOT NULL,
    status ENUM('Active', 'Inactive', 'Under Maintenance') DEFAULT 'Active',
    location VARCHAR(255),
    description TEXT,
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facility_id INT(11) NOT NULL,
    reserved_by VARCHAR(255) NOT NULL,
    purpose TEXT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    employee_id INT(11),
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

-- Create maintenance_reports table
CREATE TABLE IF NOT EXISTS maintenance_reports (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    facility_id INT(11) NOT NULL,
    reported_by VARCHAR(255) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

-- Create user_activity table
CREATE TABLE IF NOT EXISTS user_activity (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create contracts table
CREATE TABLE IF NOT EXISTS contracts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    contract_name VARCHAR(255) NOT NULL,
    contract_type VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Terminated') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create visitor_logs table
CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    visitor_name VARCHAR(255) NOT NULL,
    visitor_email VARCHAR(255),
    visitor_phone VARCHAR(20),
    purpose TEXT,
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out_time TIMESTAMP NULL,
    status ENUM('Checked In', 'Checked Out') DEFAULT 'Checked In'
);

-- Insert sample facilities data
INSERT INTO facilities (facility_name, facility_type, capacity, status, location, description, amenities) VALUES
('Conference Room A', 'Conference Room', 20, 'Active', '2nd Floor', 'Large conference room with projector and whiteboard', 'Projector, Whiteboard, Air Conditioning, WiFi'),
('Conference Room B', 'Conference Room', 15, 'Active', '2nd Floor', 'Medium conference room for smaller meetings', 'Whiteboard, Air Conditioning, WiFi'),
('Training Room A', 'Training Room', 30, 'Active', '3rd Floor', 'Large training room with presentation equipment', 'Projector, Screen, Air Conditioning, WiFi, Sound System'),
('Training Room B', 'Training Room', 25, 'Active', '3rd Floor', 'Medium training room for workshops', 'Whiteboard, Air Conditioning, WiFi'),
('Meeting Room 1', 'Meeting Room', 8, 'Active', '1st Floor', 'Small meeting room for team discussions', 'Whiteboard, Air Conditioning, WiFi'),
('Meeting Room 2', 'Meeting Room', 6, 'Active', '1st Floor', 'Intimate meeting space for small groups', 'Air Conditioning, WiFi'),
('Auditorium', 'Auditorium', 100, 'Active', 'Ground Floor', 'Large auditorium for presentations and events', 'Stage, Sound System, Projector, Air Conditioning, WiFi'),
('Recreation Area', 'Recreation Area', 50, 'Active', 'Ground Floor', 'Open recreation space for events and activities', 'Air Conditioning, WiFi, Flexible Seating');

-- Insert sample reservations data
INSERT INTO reservations (facility_id, reserved_by, purpose, start_time, end_time, status, employee_id, created_by) VALUES
(1, 'John Doe', 'Team meeting for project discussion', '2025-01-20 10:00:00', '2025-01-20 12:00:00', 'Approved', 1, 1),
(2, 'Jane Smith', 'Client presentation', '2025-01-21 14:00:00', '2025-01-21 16:00:00', 'Pending', 2, 2),
(3, 'Mike Johnson', 'Training session for new employees', '2025-01-22 09:00:00', '2025-01-22 17:00:00', 'Approved', 3, 3);

-- Insert sample maintenance reports
INSERT INTO maintenance_reports (facility_id, reported_by, issue_type, description, priority, status) VALUES
(1, 'John Doe', 'Electrical', 'Light flickering in the room', 'Medium', 'Open'),
(2, 'Jane Smith', 'Cleaning', 'Room needs deep cleaning', 'Low', 'Resolved'),
(3, 'Mike Johnson', 'Equipment', 'Projector not working properly', 'High', 'In Progress');

-- Insert sample user activity
INSERT INTO user_activity (user_id, activity_type, description) VALUES
(1, 'Login', 'User logged into the system'),
(1, 'Reservation', 'Created a new facility reservation'),
(2, 'Login', 'User logged into the system'),
(2, 'Maintenance', 'Reported a maintenance issue'),
(3, 'Login', 'User logged into the system'),
(3, 'Reservation', 'Updated facility reservation');

-- Insert sample contracts
INSERT INTO contracts (user_id, contract_name, contract_type, start_date, end_date, status) VALUES
(1, 'Employment Contract', 'Employment', '2024-01-01', '2025-12-31', 'Active'),
(2, 'Service Agreement', 'Service', '2024-06-01', '2025-05-31', 'Active'),
(3, 'Consulting Contract', 'Consulting', '2024-03-01', '2025-02-28', 'Active');

-- Insert sample visitor logs
INSERT INTO visitor_logs (user_id, visitor_name, visitor_email, visitor_phone, purpose, check_in_time, check_out_time, status) VALUES
(1, 'Alice Johnson', 'alice@example.com', '123-456-7890', 'Business meeting', '2025-01-20 09:00:00', NULL, 'Checked In'),
(2, 'Bob Wilson', 'bob@example.com', '987-654-3210', 'Interview', '2025-01-20 10:30:00', '2025-01-20 12:00:00', 'Checked Out'),
(3, 'Carol Davis', 'carol@example.com', '555-123-4567', 'Training session', '2025-01-21 08:00:00', NULL, 'Checked In');
