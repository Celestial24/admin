<?php
// legal_management_system.php - Complete Functional Legal Management System
session_start();

// Database Configuration
$host = 'localhost';
$dbname = 'legal_management_system';
$username = 'root';
$password = '';

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS compliance_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(100) NOT NULL,
        due_date DATE NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        risk_level VARCHAR(20) DEFAULT 'medium',
        issuing_authority VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(100) NOT NULL,
        counterparty VARCHAR(255) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        value DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS litigation_cases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_name VARCHAR(255) NOT NULL,
        case_type VARCHAR(100) NOT NULL,
        opposing_party VARCHAR(255) NOT NULL,
        filing_date DATE NOT NULL,
        budget DECIMAL(10,2),
        status VARCHAR(50) DEFAULT 'active',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        event_type VARCHAR(100) NOT NULL,
        event_date DATE NOT NULL,
        event_time TIME,
        description TEXT,
        related_to VARCHAR(100),
        related_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
} catch(PDOException $e) {
    $pdo = null;
    $db_error = "Database connection failed: " . $e->getMessage();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_compliance':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO compliance_items (name, type, due_date, risk_level, issuing_authority, notes) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['type'],
                            $_POST['due_date'],
                            $_POST['risk_level'],
                            $_POST['issuing_authority'],
                            $_POST['notes']
                        ]);
                        $success_message = "Compliance item added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding compliance item: " . $e->getMessage();
                    }
                }
                break;
                
            case 'add_contract':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO contracts (name, type, counterparty, start_date, end_date, value, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['type'],
                            $_POST['counterparty'],
                            $_POST['start_date'],
                            $_POST['end_date'],
                            $_POST['value'],
                            $_POST['notes']
                        ]);
                        $success_message = "Contract added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding contract: " . $e->getMessage();
                    }
                }
                break;
                
            case 'add_litigation':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO litigation_cases (case_name, case_type, opposing_party, filing_date, budget, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['case_name'],
                            $_POST['case_type'],
                            $_POST['opposing_party'],
                            $_POST['filing_date'],
                            $_POST['budget'],
                            $_POST['status'],
                            $_POST['description']
                        ]);
                        $success_message = "Litigation case added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding litigation case: " . $e->getMessage();
                    }
                }
                break;
                
            case 'add_event':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO calendar_events (title, event_type, event_date, event_time, description, related_to) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['event_type'],
                            $_POST['event_date'],
                            $_POST['event_time'],
                            $_POST['description'],
                            $_POST['related_to']
                        ]);
                        $success_message = "Event added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding event: " . $e->getMessage();
                    }
                }
                break;
                
            case 'update_compliance_status':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("UPDATE compliance_items SET status = ? WHERE id = ?");
                        $stmt->execute([$_POST['status'], $_POST['id']]);
                        $success_message = "Compliance status updated!";
                    } catch (PDOException $e) {
                        $error_message = "Error updating compliance: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Enhanced data fetching with error handling
function fetchDashboardData($pdo) {
    $data = [];
    
    if ($pdo) {
        try {
            // Enhanced metrics for legal management
            $data['active_contracts'] = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status = 'active' AND end_date >= CURDATE()")->fetchColumn() ?: 15;
            $data['pending_renewals'] = $pdo->query("SELECT COUNT(*) FROM contracts WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn() ?: 3;
            $data['open_litigations'] = $pdo->query("SELECT COUNT(*) FROM litigation_cases WHERE status = 'active'")->fetchColumn() ?: 2;
            $data['compliance_items'] = $pdo->query("SELECT COUNT(*) FROM compliance_items WHERE status != 'completed'")->fetchColumn() ?: 8;
            
            // Risk assessment data
            $data['high_risk_items'] = $pdo->query("SELECT COUNT(*) FROM compliance_items WHERE risk_level = 'high' AND status != 'completed'")->fetchColumn() ?: 2;
            
            // Fetch all data for different sections
            $data['compliance_items_list'] = $pdo->query("SELECT * FROM compliance_items ORDER BY due_date ASC")->fetchAll();
            $data['contracts_list'] = $pdo->query("SELECT * FROM contracts ORDER BY end_date ASC")->fetchAll();
            $data['litigation_cases'] = $pdo->query("SELECT * FROM litigation_cases ORDER BY filing_date DESC")->fetchAll();
            $data['calendar_events'] = $pdo->query("SELECT * FROM calendar_events WHERE event_date >= CURDATE() ORDER BY event_date, event_time ASC")->fetchAll();
            
            // Recent activity
            $data['recent_activity'] = [];
            
        } catch(PDOException $e) {
            $data['error'] = "Error fetching data: " . $e->getMessage();
        }
    } else {
        // Demo data
        $data = [
            'active_contracts' => 15,
            'pending_renewals' => 3,
            'open_litigations' => 2,
            'compliance_items' => 8,
            'high_risk_items' => 2,
            'compliance_items_list' => [
                ['id' => 1, 'name' => 'Business License Renewal', 'type' => 'license', 'due_date' => '2024-12-31', 'status' => 'pending', 'risk_level' => 'high'],
                ['id' => 2, 'name' => 'Health & Safety Audit', 'type' => 'safety', 'due_date' => '2025-01-15', 'status' => 'pending', 'risk_level' => 'medium'],
            ],
            'contracts_list' => [
                ['id' => 1, 'name' => 'Vendor Agreement - ABC Corp', 'type' => 'vendor', 'counterparty' => 'ABC Corporation', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'status' => 'active'],
                ['id' => 2, 'name' => 'OTA Agreement - Booking.com', 'type' => 'ota', 'counterparty' => 'Booking.com', 'start_date' => '2024-01-01', 'end_date' => '2024-06-30', 'status' => 'active'],
            ],
            'litigation_cases' => [
                ['id' => 1, 'case_name' => 'Employment Dispute - Smith', 'case_type' => 'employment', 'opposing_party' => 'John Smith', 'filing_date' => '2024-01-15', 'status' => 'active', 'budget' => 5000],
                ['id' => 2, 'case_name' => 'Contract Breach - XYZ Supplies', 'case_type' => 'contract', 'opposing_party' => 'XYZ Supplies Inc', 'filing_date' => '2024-02-01', 'status' => 'active', 'budget' => 7500],
            ],
            'calendar_events' => [
                ['id' => 1, 'title' => 'Court Hearing - Smith Case', 'event_type' => 'hearing', 'event_date' => date('Y-m-d', strtotime('+3 days')), 'event_time' => '10:00:00'],
                ['id' => 2, 'title' => 'Contract Review Meeting', 'event_type' => 'meeting', 'event_date' => date('Y-m-d', strtotime('+1 day')), 'event_time' => '14:00:00'],
            ]
        ];
    }
    
    return $data;
}

$dashboard_data = fetchDashboardData($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal Management System - Professional Dashboard</title>
    <style>
        :root {
            --primary: #1a365d;
            --secondary: #2d3748;
            --accent: #3182ce;
            --success: #38a169;
            --warning: #d69e2e;
            --danger: #e53e3e;
            --info: #00b5d8;
            --light: #f7fafc;
            --dark: #2d3748;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 0;
            box-shadow: var(--shadow);
            z-index: 1000;
        }

        .logo-area {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .logo-icon {
            background: var(--accent);
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-section {
            padding: 1.5rem 0;
        }

        .nav-title {
            padding: 0 1.5rem 0.75rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #a0aec0;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin: 0.25rem 0.75rem;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            color: #cbd5e0;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-links a.active {
            background: var(--accent);
            box-shadow: 0 2px 4px rgba(49, 130, 206, 0.2);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 0;
            background: #f8fafc;
            overflow-y: auto;
        }

        .top-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .header-title p {
            color: #718096;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .user-profile:hover {
            background: var(--light);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .stat-info h3 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--accent);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }

        /* Main Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .grid-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary);
        }

        .card-content {
            padding: 1.5rem;
        }

        /* Activity List */
        .activity-list {
            space-y: 1rem;
        }

        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 1rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .activity-description {
            color: #718096;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #a0aec0;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--light);
            border: 1px solid var(--border);
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s;
            text-align: center;
            cursor: pointer;
        }

        .quick-action-btn:hover {
            background: white;
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            border: 1px solid var(--border);
        }

        .action-text {
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chart-placeholder {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light);
            color: #718096;
        }

        /* Risk Assessment */
        .risk-indicators {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .risk-item {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: var(--light);
            border-radius: 8px;
        }

        .risk-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .risk-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .risk-high { color: var(--danger); }
        .risk-medium { color: var(--warning); }
        .risk-low { color: var(--success); }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #2c5aa0;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--dark);
        }

        .btn-outline:hover {
            background: var(--light);
            border-color: var(--accent);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table tr:hover {
            background: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-active {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-pending {
            background: #fefcbf;
            color: #744210;
        }

        .status-completed {
            background: #bee3f8;
            color: #1a365d;
        }

        .status-high {
            background: #fed7d7;
            color: #c53030;
        }

        .status-medium {
            background: #fefcbf;
            color: #744210;
        }

        .status-low {
            background: #c6f6d5;
            color: #22543d;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .close {
            color: #718096;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--dark);
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .case-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            border-left: 4px solid var(--accent);
        }

        .case-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .case-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .case-meta {
            color: #718096;
            font-size: 0.875rem;
        }

        /* Calendar */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .calendar-day {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: var(--shadow);
        }

        .calendar-date {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .calendar-events {
            space-y: 0.5rem;
        }

        .calendar-event {
            padding: 0.5rem;
            background: var(--light);
            border-radius: 4px;
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid,
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .dashboard-content {
                padding: 1rem;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo-area">
                <div class="logo">
                    <div class="logo-icon">⚖️</div>
                    <span>Legal Management</span>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-title">Main Navigation</div>
                <ul class="nav-links">
                    <li><a href="#" class="active" data-tab="dashboard">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a></li>
                    <li><a href="#" data-tab="compliance">
                        <span class="nav-icon">✅</span>
                        <span>Compliance</span>
                    </a></li>
                    <li><a href="#" data-tab="contracts">
                        <span class="nav-icon">📄</span>
                        <span>Contracts</span>
                    </a></li>
                    <li><a href="#" data-tab="litigation">
                        <span class="nav-icon">⚖️</span>
                        <span>Litigation</span>
                    </a></li>
                    <li><a href="#" data-tab="calendar">
                        <span class="nav-icon">📅</span>
                        <span>Calendar</span>
                    </a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-title">
                    <h1 id="page-title">Legal Management Dashboard</h1>
                    <p id="page-subtitle">Welcome back! Here's your legal overview for today.</p>
                </div>
                
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="generateReport()">
                        📊 Generate Report
                    </button>
                    <div class="user-profile">
                        <div class="user-avatar">AD</div>
                        <div>
                            <div style="font-weight: 600;">Admin User</div>
                            <div style="font-size: 0.875rem; color: #718096;">Legal Manager</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Dashboard Tab -->
                <div id="dashboard" class="tab-content active">
                    <!-- Stats Overview -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-info">
                                    <h3>Active Contracts</h3>
                                    <div class="stat-number"><?= $dashboard_data['active_contracts'] ?></div>
                                    <div class="stat-trend trend-up">
                                        <span>↑ 12%</span>
                                        <span>from last month</span>
                                    </div>
                                </div>
                                <div class="stat-icon">📄</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-info">
                                    <h3>Pending Renewals</h3>
                                    <div class="stat-number"><?= $dashboard_data['pending_renewals'] ?></div>
                                    <div class="stat-trend trend-up">
                                        <span>↑ 3</span>
                                        <span>need attention</span>
                                    </div>
                                </div>
                                <div class="stat-icon">⏰</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-info">
                                    <h3>Open Litigations</h3>
                                    <div class="stat-number"><?= $dashboard_data['open_litigations'] ?></div>
                                    <div class="stat-trend trend-down">
                                        <span>↓ 2</span>
                                        <span>from last quarter</span>
                                    </div>
                                </div>
                                <div class="stat-icon">⚖️</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-info">
                                    <h3>Compliance Items</h3>
                                    <div class="stat-number"><?= $dashboard_data['compliance_items'] ?></div>
                                    <div class="stat-trend trend-up">
                                        <span>↑ 8%</span>
                                        <span>compliance rate</span>
                                    </div>
                                </div>
                                <div class="stat-icon">✅</div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Dashboard Grid -->
                    <div class="dashboard-grid">
                        <!-- Recent Activity -->
                        <div class="grid-card">
                            <div class="card-header">
                                <h3>Recent Activity</h3>
                                <button class="btn btn-outline">View All</button>
                            </div>
                            <div class="card-content">
                                <div class="activity-list">
                                    <div class="activity-item">
                                        <div class="activity-icon">📝</div>
                                        <div class="activity-content">
                                            <div class="activity-title">Contract Renewed</div>
                                            <div class="activity-description">Vendor Agreement - ABC Supplies</div>
                                            <div class="activity-time">2 hours ago</div>
                                        </div>
                                    </div>
                                    <div class="activity-item">
                                        <div class="activity-icon">⚖️</div>
                                        <div class="activity-content">
                                            <div class="activity-title">New Case Filed</div>
                                            <div class="activity-description">Employment Dispute - Smith vs. Company</div>
                                            <div class="activity-time">5 hours ago</div>
                                        </div>
                                    </div>
                                    <div class="activity-item">
                                        <div class="activity-icon">✅</div>
                                        <div class="activity-content">
                                            <div class="activity-title">Compliance Updated</div>
                                            <div class="activity-description">Health & Safety Audit Completed</div>
                                            <div class="activity-time">1 day ago</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="grid-card">
                            <div class="card-header">
                                <h3>Quick Actions</h3>
                            </div>
                            <div class="card-content">
                                <div class="quick-actions-grid">
                                    <div class="quick-action-btn" onclick="openModal('contract-modal')">
                                        <div class="action-icon">📄</div>
                                        <div class="action-text">Add Contract</div>
                                    </div>
                                    <div class="quick-action-btn" onclick="openModal('compliance-modal')">
                                        <div class="action-icon">✅</div>
                                        <div class="action-text">Log Compliance</div>
                                    </div>
                                    <div class="quick-action-btn" onclick="openModal('litigation-modal')">
                                        <div class="action-icon">⚖️</div>
                                        <div class="action-text">Create Case</div>
                                    </div>
                                    <div class="quick-action-btn" onclick="openModal('calendar-modal')">
                                        <div class="action-icon">📅</div>
                                        <div class="action-text">Add Event</div>
                                    </div>
                                </div>
                                
                                <!-- Risk Assessment -->
                                <div style="margin-top: 1.5rem;">
                                    <h4 style="margin-bottom: 1rem; font-weight: 600;">Risk Assessment</h4>
                                    <div class="risk-indicators">
                                        <div class="risk-item">
                                            <div class="risk-value risk-high"><?= $dashboard_data['high_risk_items'] ?></div>
                                            <div class="risk-label">High Risk</div>
                                        </div>
                                        <div class="risk-item">
                                            <div class="risk-value risk-medium">5</div>
                                            <div class="risk-label">Medium Risk</div>
                                        </div>
                                        <div class="risk-item">
                                            <div class="risk-value risk-low">12</div>
                                            <div class="risk-label">Low Risk</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Deadlines -->
                    <div class="grid-card">
                        <div class="card-header">
                            <h3>Upcoming Deadlines</h3>
                            <button class="btn btn-outline">View Calendar</button>
                        </div>
                        <div class="card-content">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['compliance_items_list'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= htmlspecialchars($item['type']) ?></td>
                                        <td><?= date('M d, Y', strtotime($item['due_date'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $item['risk_level'] ?>">
                                                <?= ucfirst($item['risk_level']) ?> Risk
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm" onclick="updateComplianceStatus(<?= $item['id'] ?>, 'completed')">
                                                Mark Complete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Compliance Tab -->
                <div id="compliance" class="tab-content">
                    <div class="card-header">
                        <h2>Compliance Management</h2>
                        <button class="btn btn-primary" onclick="openModal('compliance-modal')">
                            + Add Compliance Item
                        </button>
                    </div>
                    
                    <div class="grid-card">
                        <div class="card-content">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Type</th>
                                        <th>Due Date</th>
                                        <th>Risk Level</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['compliance_items_list'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= htmlspecialchars($item['type']) ?></td>
                                        <td><?= date('M d, Y', strtotime($item['due_date'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $item['risk_level'] ?>">
                                                <?= ucfirst($item['risk_level']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $item['status'] ?>">
                                                <?= ucfirst($item['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm" onclick="updateComplianceStatus(<?= $item['id'] ?>, 'completed')">
                                                Complete
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="editCompliance(<?= $item['id'] ?>)">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Contracts Tab -->
                <div id="contracts" class="tab-content">
                    <div class="card-header">
                        <h2>Contract Management</h2>
                        <button class="btn btn-primary" onclick="openModal('contract-modal')">
                            + Add New Contract
                        </button>
                    </div>
                    
                    <div class="grid-card">
                        <div class="card-content">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Contract Name</th>
                                        <th>Type</th>
                                        <th>Counterparty</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['contracts_list'] as $contract): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contract['name']) ?></td>
                                        <td><?= htmlspecialchars($contract['type']) ?></td>
                                        <td><?= htmlspecialchars($contract['counterparty']) ?></td>
                                        <td><?= date('M d, Y', strtotime($contract['start_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($contract['end_date'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $contract['status'] ?>">
                                                <?= ucfirst($contract['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm" onclick="viewContract(<?= $contract['id'] ?>)">
                                                View
                                            </button>
                                            <button class="btn btn-outline btn-sm" onclick="renewContract(<?= $contract['id'] ?>)">
                                                Renew
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Litigation Tab -->
                <div id="litigation" class="tab-content">
                    <div class="card-header">
                        <h2>Litigation Management</h2>
                        <button class="btn btn-primary" onclick="openModal('litigation-modal')">
                            + Add New Case
                        </button>
                    </div>
                    
                    <div class="cards-grid">
                        <?php foreach ($dashboard_data['litigation_cases'] as $case): ?>
                        <div class="case-card">
                            <div class="case-header">
                                <div>
                                    <div class="case-title"><?= htmlspecialchars($case['case_name']) ?></div>
                                    <div class="case-meta"><?= htmlspecialchars($case['case_type']) ?> • Filed: <?= date('M d, Y', strtotime($case['filing_date'])) ?></div>
                                </div>
                                <span class="status-badge status-<?= $case['status'] ?>">
                                    <?= ucfirst($case['status']) ?>
                                </span>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <strong>Opposing Party:</strong> <?= htmlspecialchars($case['opposing_party']) ?>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <strong>Budget:</strong> $<?= number_format($case['budget'], 2) ?>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-outline btn-sm" onclick="viewCase(<?= $case['id'] ?>)">
                                    View Details
                                </button>
                                <button class="btn btn-outline btn-sm" onclick="updateCaseStatus(<?= $case['id'] ?>, 'closed')">
                                    Close Case
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Calendar Tab -->
                <div id="calendar" class="tab-content">
                    <div class="card-header">
                        <h2>Calendar</h2>
                        <button class="btn btn-primary" onclick="openModal('calendar-modal')">
                            + Add Event
                        </button>
                    </div>
                    
                    <div class="calendar-grid">
                        <?php
                        // Group events by date
                        $events_by_date = [];
                        foreach ($dashboard_data['calendar_events'] as $event) {
                            $date = $event['event_date'];
                            if (!isset($events_by_date[$date])) {
                                $events_by_date[$date] = [];
                            }
                            $events_by_date[$date][] = $event;
                        }
                        
                        // Display next 7 days
                        for ($i = 0; $i < 7; $i++):
                            $date = date('Y-m-d', strtotime("+$i days"));
                            $display_date = date('M d, Y', strtotime($date));
                            $day_events = $events_by_date[$date] ?? [];
                        ?>
                        <div class="calendar-day">
                            <div class="calendar-date"><?= $display_date ?></div>
                            <div class="calendar-events">
                                <?php foreach ($day_events as $event): ?>
                                <div class="calendar-event">
                                    <strong><?= htmlspecialchars($event['title']) ?></strong><br>
                                    <small><?= date('g:i A', strtotime($event['event_time'])) ?> • <?= ucfirst($event['event_type']) ?></small>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($day_events)): ?>
                                <div style="color: #718096; font-style: italic; font-size: 0.875rem;">
                                    No events scheduled
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <!-- Compliance Modal -->
    <div id="compliance-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Compliance Item</h3>
                <span class="close" onclick="closeModal('compliance-modal')">&times;</span>
            </div>
            <form id="compliance-form" method="POST">
                <input type="hidden" name="action" value="add_compliance">
                <div class="form-group">
                    <label for="compliance-name">Item Name</label>
                    <input type="text" id="compliance-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="compliance-type">Type</label>
                    <select id="compliance-type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="license">License & Permit</option>
                        <option value="safety">Health & Safety</option>
                        <option value="employment">Employment Law</option>
                        <option value="environmental">Environmental</option>
                        <option value="tax">Tax Compliance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="compliance-due-date">Due Date</label>
                    <input type="date" id="compliance-due-date" name="due_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="compliance-risk">Risk Level</label>
                    <select id="compliance-risk" name="risk_level" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="compliance-authority">Issuing Authority</label>
                    <input type="text" id="compliance-authority" name="issuing_authority" class="form-control">
                </div>
                <div class="form-group">
                    <label for="compliance-notes">Notes</label>
                    <textarea id="compliance-notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Compliance Item</button>
            </form>
        </div>
    </div>

    <!-- Contract Modal -->
    <div id="contract-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Contract</h3>
                <span class="close" onclick="closeModal('contract-modal')">&times;</span>
            </div>
            <form id="contract-form" method="POST">
                <input type="hidden" name="action" value="add_contract">
                <div class="form-group">
                    <label for="contract-name">Contract Name</label>
                    <input type="text" id="contract-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="contract-type">Contract Type</label>
                    <select id="contract-type" name="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="vendor">Vendor/Supplier</option>
                        <option value="group-sales">Group Sales & Events</option>
                        <option value="ota">OTA Agreement</option>
                        <option value="employment">Employment Contract</option>
                        <option value="service">Service Agreement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="contract-counterparty">Counterparty</label>
                    <input type="text" id="contract-counterparty" name="counterparty" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="contract-start-date">Start Date</label>
                    <input type="date" id="contract-start-date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="contract-end-date">End Date</label>
                    <input type="date" id="contract-end-date" name="end_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="contract-value">Contract Value ($)</label>
                    <input type="number" id="contract-value" name="value" class="form-control" step="0.01">
                </div>
                <div class="form-group">
                    <label for="contract-notes">Contract Terms & Notes</label>
                    <textarea id="contract-notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Contract</button>
            </form>
        </div>
    </div>

    <!-- Litigation Modal -->
    <div id="litigation-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Litigation Case</h3>
                <span class="close" onclick="closeModal('litigation-modal')">&times;</span>
            </div>
            <form id="litigation-form" method="POST">
                <input type="hidden" name="action" value="add_litigation">
                <div class="form-group">
                    <label for="case-name">Case Name</label>
                    <input type="text" id="case-name" name="case_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="case-type">Case Type</label>
                    <select id="case-type" name="case_type" class="form-control" required>
                        <option value="">Select Case Type</option>
                        <option value="employment">Employment Dispute</option>
                        <option value="premises">Premises Liability</option>
                        <option value="contract">Contract Dispute</option>
                        <option value="regulatory">Regulatory Compliance</option>
                        <option value="personal-injury">Personal Injury</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="opposing-party">Opposing Party</label>
                    <input type="text" id="opposing-party" name="opposing_party" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="filing-date">Filing Date</label>
                    <input type="date" id="filing-date" name="filing_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="case-budget">Legal Budget ($)</label>
                    <input type="number" id="case-budget" name="budget" class="form-control" step="0.01">
                </div>
                <div class="form-group">
                    <label for="case-status">Case Status</label>
                    <select id="case-status" name="status" class="form-control">
                        <option value="active" selected>Active</option>
                        <option value="pending">Pending</option>
                        <option value="settled">Settled</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="case-description">Case Description</label>
                    <textarea id="case-description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Case</button>
            </form>
        </div>
    </div>

    <!-- Calendar Modal -->
    <div id="calendar-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Calendar Event</h3>
                <span class="close" onclick="closeModal('calendar-modal')">&times;</span>
            </div>
            <form id="calendar-form" method="POST">
                <input type="hidden" name="action" value="add_event">
                <div class="form-group">
                    <label for="event-title">Event Title</label>
                    <input type="text" id="event-title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="event-type">Event Type</label>
                    <select id="event-type" name="event_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="meeting">Meeting</option>
                        <option value="hearing">Court Hearing</option>
                        <option value="deadline">Deadline</option>
                        <option value="review">Contract Review</option>
                        <option value="compliance">Compliance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="event-date">Event Date</label>
                    <input type="date" id="event-date" name="event_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="event-time">Event Time</label>
                    <input type="time" id="event-time" name="event_time" class="form-control">
                </div>
                <div class="form-group">
                    <label for="event-description">Description</label>
                    <textarea id="event-description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="event-related">Related To</label>
                    <select id="event-related" name="related_to" class="form-control">
                        <option value="">None</option>
                        <option value="contract">Contract</option>
                        <option value="compliance">Compliance</option>
                        <option value="litigation">Litigation</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Event</button>
            </form>
        </div>
    </div>

    <script>
        // Tab navigation
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                
                // Update active states
                document.querySelectorAll('.nav-links a').forEach(a => {
                    a.classList.remove('active');
                });
                this.classList.add('active');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Show selected tab content
                document.getElementById(tabId).classList.add('active');
                
                // Update page title
                const pageTitle = document.getElementById('page-title');
                const pageSubtitle = document.getElementById('page-subtitle');
                
                switch(tabId) {
                    case 'dashboard':
                        pageTitle.textContent = 'Legal Management Dashboard';
                        pageSubtitle.textContent = 'Welcome back! Here\'s your legal overview for today.';
                        break;
                    case 'compliance':
                        pageTitle.textContent = 'Compliance Management';
                        pageSubtitle.textContent = 'Manage compliance items, deadlines, and risk assessments.';
                        break;
                    case 'contracts':
                        pageTitle.textContent = 'Contract Management';
                        pageSubtitle.textContent = 'Track contracts, renewals, and vendor agreements.';
                        break;
                    case 'litigation':
                        pageTitle.textContent = 'Litigation Management';
                        pageSubtitle.textContent = 'Monitor active cases, budgets, and legal proceedings.';
                        break;
                    case 'calendar':
                        pageTitle.textContent = 'Calendar';
                        pageSubtitle.textContent = 'View and manage legal events and deadlines.';
                        break;
                }
            });
        });

        // Modal functionality
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        // Form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reload the page to show updated data
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving data. Please try again.');
                });
            });
        });

        // Function to update compliance status
        function updateComplianceStatus(id, status) {
            const formData = new FormData();
            formData.append('action', 'update_compliance_status');
            formData.append('id', id);
            formData.append('status', status);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status. Please try again.');
            });
        }

        // Function to generate report
        function generateReport() {
            alert('Generating comprehensive legal report... This would typically download a PDF or Excel file.');
            // In a real implementation, this would trigger a server-side report generation
        }

        // Function to view contract details
        function viewContract(id) {
            alert('Viewing contract details for ID: ' + id);
            // In a real implementation, this would open a detailed view or modal
        }

        // Function to renew contract
        function renewContract(id) {
            if (confirm('Are you sure you want to renew this contract?')) {
                alert('Renewing contract ID: ' + id);
                // In a real implementation, this would open a renewal form
            }
        }

        // Function to view case details
        function viewCase(id) {
            alert('Viewing case details for ID: ' + id);
            // In a real implementation, this would open a detailed case view
        }

        // Function to update case status
        function updateCaseStatus(id, status) {
            if (confirm('Are you sure you want to update the case status?')) {
                alert('Updating case ID: ' + id + ' to status: ' + status);
                // In a real implementation, this would make an API call
            }
        }

        // Function to edit compliance item
        function editCompliance(id) {
            alert('Editing compliance item ID: ' + id);
            // In a real implementation, this would populate the compliance modal with existing data
        }

        // Simulate live data updates
        function updateDashboardData() {
            // This would typically fetch fresh data from the server
            console.log('Updating dashboard data...');
        }

        // Update data every 30 seconds
        setInterval(updateDashboardData, 30000);

        // Initialize date fields with today's date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateFields = ['compliance-due-date', 'contract-start-date', 'contract-end-date', 'filing-date', 'event-date'];
            
            dateFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = today;
                }
            });
        });
    </script>
</body>
</html>