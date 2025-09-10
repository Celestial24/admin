<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "facilities_management";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle forms
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type'])) {
    $form_type = $_POST['form_type'];

    if ($form_type == "facility") {
        $stmt = $conn->prepare("INSERT INTO facilities (name, type, capacity, status, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $_POST['name'], $_POST['type'], $_POST['capacity'], $_POST['status'], $_POST['notes']);
        if ($stmt->execute()) {
            echo "<script>alert('Facility added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding facility: " . $stmt->error . "');</script>";
        }
        $stmt->close();

    } elseif ($form_type == "reservation") {
        // Validate that end_time > start_time
        if (strtotime($_POST['end_time']) <= strtotime($_POST['start_time'])) {
            echo "<script>alert('End time must be after start time.');</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO reservations (facility_id, reserved_by, purpose, status, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "isssss",
                $_POST['facility_id'],
                $_POST['reserved_by'],
                $_POST['purpose'],
                $_POST['status'],
                $_POST['start_time'],
                $_POST['end_time']
            );
            if ($stmt->execute()) {
                echo "<script>alert('Reservation added successfully!');</script>";
            } else {
                echo "<script>alert('Error adding reservation: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        }

    } elseif ($form_type == "maintenance") {
        $stmt = $conn->prepare("INSERT INTO maintenance (facility_id, reported_by, description, priority) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_POST['facility_id'], $_POST['reported_by'], $_POST['description'], $_POST['priority']);
        if ($stmt->execute()) {
            echo "<script>alert('Maintenance request added successfully!');</script>";
        } else {
            echo "<script>alert('Error adding maintenance request: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}

// Fetch facilities for dropdown
$facilityOptions = "";
$result = $conn->query("SELECT id, name FROM facilities");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facilityOptions .= "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
    }
} else {
    $facilityOptions = "<option value='' disabled>No facilities available</option>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Facilities - Admin</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html, body {
      overflow-y: hidden; 
      height: 100%;     
      margin: 0;         
      padding: 0;         
    }
    html::-webkit-scrollbar, body::-webkit-scrollbar {
      display: none;
    }
  </style>
</head>
<body class="bg-gray-100">

<!-- Page Layout Wrapper -->
<div class="flex h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-gray-800 text-white h-full">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6 overflow-y-auto">

    <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

    <?php
// Open connection ONCE here
$conn = new mysqli("localhost", "root", "", "facilities_management");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Facilities data
$facilitiesResult = $conn->query("SELECT * FROM facilities");

// Reservations data
$resResult = $conn->query("
    SELECT r.*, f.name AS facility_name
    FROM reservations r
    JOIN facilities f ON r.facility_id = f.id
    ORDER BY r.start_time DESC
");

// Maintenance data
$mainResult = $conn->query("
    SELECT m.*, f.name AS facility_name
    FROM maintenance m
    JOIN facilities f ON m.facility_id = f.id
    ORDER BY m.created_at DESC
");
?>

    <!-- Tabs -->
    <div class="flex space-x-6 border-b mb-6">
      <button class="tab-link border-b-2 border-blue-600 text-blue-600 font-semibold pb-2" data-tab="facilities">Facilities</button>
      <button class="tab-link text-gray-600 hover:text-blue-600 pb-2" data-tab="reservations">Reservations</button>
      <button class="tab-link text-gray-600 hover:text-blue-600 pb-2" data-tab="maintenance">Maintenance</button>
    </div>

    <!-- Facilities Form -->
    <div id="facilities" class="tab-content">
      <h2 class="text-lg font-semibold mb-2 text-green-400 ">Facilities List</h2>
    <table class="min-w-full text-sm text-left bg-white border rounded shadow">
        <thead class="bg-gray-100 text-center">
            <tr>
                <th class="px-4 py-2 border">ID</th>
                <th class="px-4 py-2 border">Name</th>
                <th class="px-4 py-2 border">Type</th>
                <th class="px-4 py-2 border">Capacity</th>
                <th class="px-4 py-2 border">Status</th>
                <th class="px-4 py-2 border">Notes</th>
            </tr>
        </thead>
      <tbody class="text-center">
            <?php while ($row = $facilitiesResult->fetch_assoc()) : ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?= $row['id'] ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['type']) ?></td>
                    <td class="px-4 py-2 border"><?= $row['capacity'] ?></td>
                    <td class="px-4 py-2 border"><?= $row['status'] ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['notes']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
      
    </div>

    <!-- Reservations Form -->
    <div id="reservations" class="tab-content hidden">
      <h2 class="text-lg font-semibold mb-2">Reservations List</h2>
    <table class="min-w-full text-sm text-left bg-white border rounded shadow">
        <thead class="bg-gray-100 text-center">
            <tr class="text-center">
                <th class="px-4 py-2 border">ID</th>
                <th class="px-4 py-2 border">Facility</th>
                <th class="px-4 py-2 border">Reserved By</th>
                <th class="px-4 py-2 border">Purpose</th>
                <th class="px-4 py-2 border">Status</th>
                <th class="px-4 py-2 border">Start Time</th>
                <th class="px-4 py-2 border">End Time</th>
            </tr>
        </thead>
       <tbody class="text-center">
            <?php while ($row = $resResult->fetch_assoc()) : ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?= $row['id'] ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['facility_name']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['reserved_by']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['purpose']) ?></td>
                    <td class="px-4 py-2 border"><?= $row['status'] ?></td>
                    <td class="px-4 py-2 border"><?= $row['start_time'] ?></td>
                    <td class="px-4 py-2 border"><?= $row['end_time'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>

    <!-- Maintenance Form -->
    <div id="maintenance" class="tab-content hidden">
      
    <h2 class="text-lg font-semibold mb-2">Maintenance Records</h2>
    <table class="min-w-full text-sm text-left bg-white border rounded shadow">
        <thead class="bg-gray-100 text-center">
            <tr>
                <th class="px-4 py-2 border">ID</th>
                <th class="px-4 py-2 border">Facility</th>
                <th class="px-4 py-2 border">Reported By</th>
                <th class="px-4 py-2 border">Description</th>
                <th class="px-4 py-2 border">Priority</th>
                <th class="px-4 py-2 border">Reported At</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php while ($row = $mainResult->fetch_assoc()) : ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?= $row['id'] ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['facility_name']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['reported_by']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['description']) ?></td>
                    <td class="px-4 py-2 border"><?= $row['priority'] ?></td>
                    <td class="px-4 py-2 border"><?= $row['created_at'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>

  </main>
</div>

<!-- JS for Tabs -->
<script>
  const tabs = document.querySelectorAll('.tab-link');
  const contents = document.querySelectorAll('.tab-content');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('border-blue-600','text-blue-600','font-semibold'));
      contents.forEach(c => c.classList.add('hidden'));
      tab.classList.add('border-blue-600','text-blue-600','font-semibold');
      document.getElementById(tab.dataset.tab).classList.remove('hidden');
    });
  });
</script>

</body>
</html>
