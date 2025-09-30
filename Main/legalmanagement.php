<?php
// legal_management_system.php - Complete Legal Management System
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
} catch(PDOException $e) {
    $pdo = null;
    $db_error = "Database connection failed: " . $e->getMessage();
}

// Initialize session variables
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Guest';
    $_SESSION['role'] = 'guest';
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_case':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO cases (case_number, title, description, case_type, status, open_date) VALUES (?, ?, ?, ?, 'open', CURDATE())");
                        $stmt->execute([
                            $_POST['case_number'],
                            $_POST['title'],
                            $_POST['description'],
                            $_POST['case_type']
                        ]);
                        $success_message = "Case added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding case: " . $e->getMessage();
                    }
                }
                break;
                
            case 'add_time_entry':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO time_entries (case_id, description, hours, date_worked, billing_rate) VALUES (?, ?, ?, ?, 250.00)");
                        $stmt->execute([
                            $_POST['case_id'],
                            $_POST['description'],
                            $_POST['hours'],
                            $_POST['date_worked']
                        ]);
                        $success_message = "Time entry recorded successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error recording time: " . $e->getMessage();
                    }
                }
                break;
                
            case 'add_event':
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO calendar_events (title, description, event_date, event_type) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['description'],
                            $_POST['event_date'],
                            $_POST['event_type']
                        ]);
                        $success_message = "Event added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding event: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get data for dashboard
if ($pdo) {
    try {
        // Case statistics
        $total_cases = $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();
        $active_cases = $pdo->query("SELECT COUNT(*) FROM cases WHERE status = 'active'")->fetchColumn();
        
        // Financial statistics
        $unbilled_amount = $pdo->query("SELECT SUM(hours * 250) FROM time_entries WHERE billed = 0")->fetchColumn();
        $unbilled_amount = $unbilled_amount ?: 0;
        
        // Recent cases
        $recent_cases = $pdo->query("SELECT * FROM cases ORDER BY open_date DESC LIMIT 5")->fetchAll();
        
        // Recent time entries
        $recent_time = $pdo->query("SELECT te.*, c.case_number FROM time_entries te LEFT JOIN cases c ON te.case_id = c.id ORDER BY te.date_worked DESC LIMIT 5")->fetchAll();
        
        // Upcoming events
        $upcoming_events = $pdo->query("SELECT * FROM calendar_events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5")->fetchAll();
        
        // All cases for dropdowns
        $all_cases = $pdo->query("SELECT * FROM cases ORDER BY case_number")->fetchAll();
        
        // Calendar events
        $calendar_events = $pdo->query("SELECT * FROM calendar_events WHERE MONTH(event_date) = MONTH(CURDATE()) AND YEAR(event_date) = YEAR(CURDATE())")->fetchAll();
        
    } catch (PDOException $e) {
        $db_error = "Error fetching data: " . $e->getMessage();
    }
} else {
    // Demo data for when database is not available
    $total_cases = 12;
    $active_cases = 8;
    $unbilled_amount = 12500.00;
    
    $recent_cases = [
        ['case_number' => 'LC-2024-001', 'title' => 'Smith vs. Johnson Contract Dispute', 'status' => 'active'],
        ['case_number' => 'LC-2024-002', 'title' => 'ABC Corp Merger Agreement', 'status' => 'active'],
        ['case_number' => 'LC-2024-003', 'title' => 'Estate Planning - Wilson Family', 'status' => 'pending']
    ];
    
    $recent_time = [
        ['case_number' => 'LC-2024-001', 'description' => 'Client meeting and case review', 'hours' => 2.5],
        ['case_number' => 'LC-2024-002', 'description' => 'Document drafting and review', 'hours' => 4.0]
    ];
    
    $upcoming_events = [
        ['title' => 'Court Hearing - Smith Case', 'event_date' => date('Y-m-d', strtotime('+3 days')), 'event_type' => 'hearing'],
        ['title' => 'Client Meeting - ABC Corp', 'event_date' => date('Y-m-d', strtotime('+1 day')), 'event_type' => 'meeting']
    ];
}

// Determine current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal Management System</title>
    <style>
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d3748;
            --accent-color: #3182ce;
            --success-color: #38a169;
            --warning-color: #d69e2e;
            --danger-color: #e53e3e;
            --light-bg: #f7fafc;
            --border-color: #e2e8f0;
            --text-dark: #2d3748;
            --text-light: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }

        .nav-logo h2 {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(45deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-link.active {
            background-color: rgba(255,255,255,0.2);
        }

        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .nav-user {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .login-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            min-height: 70vh;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background-color: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), #2c5aa0);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* Tables */
        .table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem;
        }

        .table-body {
            padding: 0;
        }

        .table-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background-color: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-open { background-color: #bee3f8; color: #2c5282; }
        .status-active { background-color: #c6f6d5; color: #276749; }
        .status-pending { background-color: #fefcbf; color: #744210; }
        .status-closed { background-color: #fed7d7; color: #c53030; }

        /* Calendar */
        .calendar-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: var(--border-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .calendar-day {
            background: white;
            padding: 0.75rem;
            min-height: 120px;
            position: relative;
        }

        .calendar-day-header {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            text-align: center;
        }

        .calendar-day.today {
            background-color: #ebf8ff;
        }

        .calendar-event {
            background: var(--accent-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Modals */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 1.5rem;
        }

        .tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab.active {
            border-bottom-color: var(--accent-color);
            color: var(--accent-color);
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Footer */
        .footer {
            background: var(--secondary-color);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        .footer-content p {
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .table-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .calendar-grid {
                grid-template-columns: 1fr;
            }
            
            .calendar-day {
                min-height: 80px;
            }
        }

        /* Page-specific styles */
        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .module-section {
            margin-bottom: 3rem;
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2>LegalManage Pro</h2>
            </div>
            <div class="nav-menu">
                <a href="?page=dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="?page=cases" class="nav-link <?php echo $current_page == 'cases' ? 'active' : ''; ?>">Cases</a>
                <a href="?page=clients" class="nav-link <?php echo $current_page == 'clients' ? 'active' : ''; ?>">Clients</a>
                <a href="?page=financial" class="nav-link <?php echo $current_page == 'financial' ? 'active' : ''; ?>">Financial</a>
                <a href="?page=calendar" class="nav-link <?php echo $current_page == 'calendar' ? 'active' : ''; ?>">Calendar</a>
                <a href="?page=reports" class="nav-link <?php echo $current_page == 'reports' ? 'active' : ''; ?>">Reports</a>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                <button class="login-btn" onclick="openModal('loginModal')">Login</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Alerts -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($db_error)): ?>
            <div class="alert alert-error">
                <strong>Database Error:</strong> <?php echo $db_error; ?>
                <br><small>Showing demo data. System will work fully when database is connected.</small>
            </div>
        <?php endif; ?>

        <!-- Page Content -->
        <?php if ($current_page == 'dashboard'): ?>
            <!-- Dashboard Page -->
            <div class="dashboard-header">
                <h1>Legal Management Dashboard</h1>
                <p>Comprehensive overview of your legal practice</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_cases; ?></span>
                    <span class="stat-label">Total Cases</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $active_cases; ?></span>
                    <span class="stat-label">Active Cases</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$<?php echo number_format($unbilled_amount, 2); ?></span>
                    <span class="stat-label">Unbilled Amount</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo count($upcoming_events); ?></span>
                    <span class="stat-label">Upcoming Events</span>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Cases -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Cases</h3>
                        <a href="?page=cases" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table">
                        <div class="table-body">
                            <?php foreach ($recent_cases as $case): ?>
                            <div class="table-row">
                                <div>
                                    <strong><?php echo htmlspecialchars($case['case_number']); ?></strong>
                                    <div><?php echo htmlspecialchars($case['title']); ?></div>
                                </div>
                                <div>
                                    <span class="status-badge status-<?php echo strtolower($case['status']); ?>">
                                        <?php echo $case['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upcoming Events</h3>
                        <a href="?page=calendar" class="btn btn-primary btn-sm">View Calendar</a>
                    </div>
                    <div class="table">
                        <div class="table-body">
                            <?php foreach ($upcoming_events as $event): ?>
                            <div class="table-row">
                                <div>
                                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                    <div><?php echo date('M j, Y', strtotime($event['event_date'])); ?></div>
                                </div>
                                <div>
                                    <span class="status-badge"><?php echo $event['event_type']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Time Entries -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Time Entries</h3>
                        <a href="?page=financial" class="btn btn-primary btn-sm">Track Time</a>
                    </div>
                    <div class="table">
                        <div class="table-body">
                            <?php foreach ($recent_time as $time): ?>
                            <div class="table-row">
                                <div>
                                    <strong><?php echo htmlspecialchars($time['case_number']); ?></strong>
                                    <div><?php echo htmlspecialchars($time['description']); ?></div>
                                </div>
                                <div>
                                    <?php echo $time['hours']; ?> hrs
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <button class="btn btn-primary" onclick="openModal('addCaseModal')">Add New Case</button>
                        <button class="btn btn-primary" onclick="openModal('addTimeModal')">Record Time</button>
                        <button class="btn btn-primary" onclick="openModal('addEventModal')">Add Calendar Event</button>
                        <a href="?page=reports" class="btn btn-success">Generate Reports</a>
                    </div>
                </div>
            </div>

        <?php elseif ($current_page == 'cases'): ?>
            <!-- Cases Management Page -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Case Management</h2>
                    <button class="btn btn-primary" onclick="openModal('addCaseModal')">Add New Case</button>
                </div>
                
                <div class="table">
                    <div class="table-header">
                        <div class="table-row">
                            <div>Case Number</div>
                            <div>Title</div>
                            <div>Type</div>
                            <div>Status</div>
                            <div>Open Date</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <div class="table-body">
                        <?php foreach ($recent_cases as $case): ?>
                        <div class="table-row">
                            <div><?php echo htmlspecialchars($case['case_number']); ?></div>
                            <div><?php echo htmlspecialchars($case['title']); ?></div>
                            <div><?php echo isset($case['case_type']) ? $case['case_type'] : 'General'; ?></div>
                            <div>
                                <span class="status-badge status-<?php echo strtolower($case['status']); ?>">
                                    <?php echo $case['status']; ?>
                                </span>
                            </div>
                            <div><?php echo isset($case['open_date']) ? date('M j, Y', strtotime($case['open_date'])) : 'N/A'; ?></div>
                            <div>
                                <button class="btn btn-primary btn-sm">View</button>
                                <button class="btn btn-success btn-sm">Edit</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($current_page == 'financial'): ?>
            <!-- Financial Management Page -->
            <div class="module-section">
                <h2 class="section-title">Financial Management</h2>
                
                <div class="dashboard-grid">
                    <!-- Time Tracking -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Time Tracking</h3>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_time_entry">
                            <div class="form-group">
                                <label class="form-label">Case</label>
                                <select class="form-control" name="case_id" required>
                                    <option value="">Select Case</option>
                                    <?php foreach ($all_cases as $case): ?>
                                    <option value="<?php echo $case['id']; ?>">
                                        <?php echo htmlspecialchars($case['case_number'] . ' - ' . $case['title']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" required placeholder="Describe work performed..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hours</label>
                                <input type="number" class="form-control" name="hours" step="0.25" min="0.25" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date Worked</label>
                                <input type="date" class="form-control" name="date_worked" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Record Time</button>
                        </form>
                    </div>

                    <!-- Recent Time Entries -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Time Entries</h3>
                        </div>
                        <div class="table">
                            <div class="table-body">
                                <?php foreach ($recent_time as $time): ?>
                                <div class="table-row">
                                    <div>
                                        <strong><?php echo htmlspecialchars($time['case_number']); ?></strong>
                                        <div><?php echo htmlspecialchars($time['description']); ?></div>
                                    </div>
                                    <div>
                                        <?php echo $time['hours']; ?> hrs
                                    </div>
                                    <div>
                                        <?php echo isset($time['date_worked']) ? date('M j, Y', strtotime($time['date_worked'])) : 'N/A'; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Section -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Billing & Invoices</h3>
                    <button class="btn btn-primary">Generate Invoice</button>
                </div>
                <div class="table">
                    <div class="table-header">
                        <div class="table-row">
                            <div>Invoice #</div>
                            <div>Case</div>
                            <div>Amount</div>
                            <div>Status</div>
                            <div>Due Date</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <div class="table-body">
                        <div class="table-row">
                            <div>INV-2024-001</div>
                            <div>LC-2024-001 - Smith Case</div>
                            <div>$5,250.00</div>
                            <div><span class="status-badge">Sent</span></div>
                            <div><?php echo date('M j, Y', strtotime('+30 days')); ?></div>
                            <div><button class="btn btn-primary btn-sm">View</button></div>
                        </div>
                        <div class="table-row">
                            <div>INV-2024-002</div>
                            <div>LC-2024-002 - ABC Corp</div>
                            <div>$8,750.00</div>
                            <div><span class="status-badge">Paid</span></div>
                            <div><?php echo date('M j, Y', strtotime('-5 days')); ?></div>
                            <div><button class="btn btn-primary btn-sm">View</button></div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($current_page == 'calendar'): ?>
            <!-- Calendar Page -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Legal Calendar</h2>
                    <button class="btn btn-primary" onclick="openModal('addEventModal')">Add Event</button>
                </div>
                
                <div class="calendar-container">
                    <div class="calendar-header">
                        <h3><?php echo date('F Y'); ?></h3>
                        <div>
                            <button class="btn btn-sm">Today</button>
                            <button class="btn btn-sm">Prev</button>
                            <button class="btn btn-sm">Next</button>
                        </div>
                    </div>
                    
                    <div class="calendar-grid">
                        <?php
                        // Day headers
                        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        foreach ($dayNames as $dayName) {
                            echo '<div class="calendar-day-header">' . $dayName . '</div>';
                        }
                        
                        // Calendar days
                        $firstDay = date('N', strtotime(date('Y-m-01'))) % 7;
                        $daysInMonth = date('t');
                        $today = date('j');
                        
                        // Empty cells for days before the first day of month
                        for ($i = 0; $i < $firstDay; $i++) {
                            echo '<div class="calendar-day"></div>';
                        }
                        
                        // Days of the month
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $currentDate = date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $isToday = ($day == $today) ? 'today' : '';
                            
                            echo '<div class="calendar-day ' . $isToday . '">';
                            echo '<div class="calendar-day-header">' . $day . '</div>';
                            
                            // Show events for this day
                            if (isset($calendar_events)) {
                                foreach ($calendar_events as $event) {
                                    if ($event['event_date'] == $currentDate) {
                                        echo '<div class="calendar-event" title="' . htmlspecialchars($event['title']) . '">';
                                        echo htmlspecialchars($event['title']);
                                        echo '</div>';
                                    }
                                }
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

        <?php elseif ($current_page == 'reports'): ?>
            <!-- Reports Page -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Legal Management Reports</h2>
                </div>
                
                <div class="tabs">
                    <div class="tab active" onclick="switchTab('financial-reports')">Financial Reports</div>
                    <div class="tab" onclick="switchTab('case-reports')">Case Reports</div>
                    <div class="tab" onclick="switchTab('time-reports')">Time Reports</div>
                </div>
                
                <div id="financial-reports" class="tab-content active">
                    <h3>Financial Overview</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number">$<?php echo number_format($unbilled_amount, 2); ?></span>
                            <span class="stat-label">Total Unbilled</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">$15,250.00</span>
                            <span class="stat-label">Monthly Revenue</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">$182,500.00</span>
                            <span class="stat-label">Yearly Revenue</span>
                        </div>
                    </div>
                </div>
                
                <div id="case-reports" class="tab-content">
                    <h3>Case Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $total_cases; ?></span>
                            <span class="stat-label">Total Cases</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $active_cases; ?></span>
                            <span class="stat-label">Active Cases</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">3</span>
                            <span class="stat-label">Closed This Month</span>
                        </div>
                    </div>
                </div>
                
                <div id="time-reports" class="tab-content">
                    <h3>Time Tracking Reports</h3>
                    <div class="table">
                        <div class="table-header">
                            <div class="table-row">
                                <div>Case</div>
                                <div>Total Hours</div>
                                <div>Billable Amount</div>
                                <div>Status</div>
                            </div>
                        </div>
                        <div class="table-body">
                            <div class="table-row">
                                <div>LC-2024-001 - Smith Case</div>
                                <div>21.5 hrs</div>
                                <div>$5,375.00</div>
                                <div><span class="status-badge">Billed</span></div>
                            </div>
                            <div class="table-row">
                                <div>LC-2024-002 - ABC Corp</div>
                                <div>35.0 hrs</div>
                                <div>$8,750.00</div>
                                <div><span class="status-badge">Unbilled</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($current_page == 'clients'): ?>
            <!-- Clients Page -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Client Management</h2>
                    <button class="btn btn-primary">Add New Client</button>
                </div>
                
                <div class="table">
                    <div class="table-header">
                        <div class="table-row">
                            <div>Client Name</div>
                            <div>Email</div>
                            <div>Phone</div>
                            <div>Active Cases</div>
                            <div>Total Billed</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <div class="table-body">
                        <div class="table-row">
                            <div>John Smith</div>
                            <div>john.smith@email.com</div>
                            <div>(555) 123-4567</div>
                            <div>2</div>
                            <div>$15,250.00</div>
                            <div>
                                <button class="btn btn-primary btn-sm">View</button>
                                <button class="btn btn-success btn-sm">Edit</button>
                            </div>
                        </div>
                        <div class="table-row">
                            <div>ABC Corporation</div>
                            <div>legal@abccorp.com</div>
                            <div>(555) 987-6543</div>
                            <div>1</div>
                            <div>$8,750.00</div>
                            <div>
                                <button class="btn btn-primary btn-sm">View</button>
                                <button class="btn btn-success btn-sm">Edit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Modals -->
        <!-- Add Case Modal -->
        <div id="addCaseModal" class="modal">
            <div class="modal-content">
                <h3>Add New Case</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_case">
                    <div class="form-group">
                        <label class="form-label">Case Number</label>
                        <input type="text" class="form-control" name="case_number" required placeholder="e.g., LC-2024-001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Case Title</label>
                        <input type="text" class="form-control" name="title" required placeholder="Enter case title">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Case description"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Case Type</label>
                        <select class="form-control" name="case_type">
                            <option value="Litigation">Litigation</option>
                            <option value="Corporate">Corporate</option>
                            <option value="Real Estate">Real Estate</option>
                            <option value="Family Law">Family Law</option>
                            <option value="Criminal">Criminal</option>
                        </select>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeModal('addCaseModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Case</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Time Entry Modal -->
        <div id="addTimeModal" class="modal">
            <div class="modal-content">
                <h3>Record Time Entry</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_time_entry">
                    <div class="form-group">
                        <label class="form-label">Case</label>
                        <select class="form-control" name="case_id" required>
                            <option value="">Select Case</option>
                            <?php foreach ($all_cases as $case): ?>
                            <option value="<?php echo $case['id']; ?>">
                                <?php echo htmlspecialchars($case['case_number'] . ' - ' . $case['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required placeholder="Describe work performed..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hours</label>
                        <input type="number" class="form-control" name="hours" step="0.25" min="0.25" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Worked</label>
                        <input type="date" class="form-control" name="date_worked" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeModal('addTimeModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Time</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Event Modal -->
        <div id="addEventModal" class="modal">
            <div class="modal-content">
                <h3>Add Calendar Event</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_event">
                    <div class="form-group">
                        <label class="form-label">Event Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event Date</label>
                        <input type="date" class="form-control" name="event_date" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event Type</label>
                        <select class="form-control" name="event_type">
                            <option value="hearing">Court Hearing</option>
                            <option value="meeting">Client Meeting</option>
                            <option value="deadline">Filing Deadline</option>
                            <option value="appointment">Appointment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeModal('addEventModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Login Modal -->
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <h3>Login to LegalManage Pro</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeModal('loginModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2024 Legal Management System. All rights reserved.</p>
            <p>Comprehensive Legal Practice Management Solution</p>
        </div>
    </footer>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Tab switching function
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Activate selected tab
            event.target.classList.add('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>