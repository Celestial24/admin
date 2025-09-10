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
  <title>Facilities - User</title>
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
    <?php include '../../Components/sidebar/sidebar_user.php'; ?>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6 overflow-y-auto">

    <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

    <!-- Tabs -->
    <div class="flex space-x-6 border-b mb-6">
      <button class="tab-link border-b-2 border-blue-600 text-blue-600 font-semibold pb-2" data-tab="facilities">Facilities</button>
      <button class="tab-link text-gray-600 hover:text-blue-600 pb-2" data-tab="reservations">Reservations</button>
      <button class="tab-link text-gray-600 hover:text-blue-600 pb-2" data-tab="maintenance">Maintenance</button>
    </div>

    <!-- Facilities Form -->
    <div id="facilities" class="tab-content">
      <form method="POST" class="bg-white p-6 rounded shadow space-y-4" autocomplete="off">
        <input type="hidden" name="form_type" value="facility" />
        <input type="text" name="name" placeholder="Name" required class="w-full border rounded px-3 py-2" />
        <input type="text" name="type" placeholder="Type" required class="w-full border rounded px-3 py-2" />
        <input type="number" name="capacity" placeholder="Capacity" required min="1" class="w-full border rounded px-3 py-2" />
        <select name="status" class="w-full border rounded px-3 py-2" required>
          <option value="Available">Available</option>
          <option value="Unavailable">Unavailable</option>
        </select>
        <textarea name="notes" placeholder="Notes" class="w-full border rounded px-3 py-2"></textarea>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Add Facility</button>
      </form>
    </div>

    <!-- Reservations Form -->
    <div id="reservations" class="tab-content hidden">
      <form method="POST" class="bg-white p-6 rounded shadow space-y-4" autocomplete="off">
        <input type="hidden" name="form_type" value="reservation" />
        <select name="facility_id" required class="w-full border rounded px-3 py-2">
          <option value="" disabled selected>Select Facility</option>
          <?= $facilityOptions ?>
        </select>
        <input type="text" name="reserved_by" placeholder="Reserved By" required class="w-full border rounded px-3 py-2" />
        <input type="text" name="purpose" placeholder="Purpose" required class="w-full border rounded px-3 py-2" />
        <select name="status" class="w-full border rounded px-3 py-2" required>
          <option value="Confirmed">Confirmed</option>
          <option value="Pending">Pending</option>
          <option value="Cancelled">Cancelled</option>
        </select>
        <label class="block">
          Start Time:
          <input type="datetime-local" name="start_time" required class="w-full border rounded px-3 py-2" />
        </label>
        <label class="block">
          End Time:
          <input type="datetime-local" name="end_time" required class="w-full border rounded px-3 py-2" />
        </label>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Add Reservation</button>
      </form>
    </div>

    <!-- Maintenance Form -->
    <div id="maintenance" class="tab-content hidden">
      <form method="POST" class="bg-white p-6 rounded shadow space-y-4" autocomplete="off">
        <input type="hidden" name="form_type" value="maintenance" />
        <select name="facility_id" required class="w-full border rounded px-3 py-2">
          <option value="" disabled selected>Select Facility</option>
          <?= $facilityOptions ?>
        </select>
        <input type="text" name="reported_by" placeholder="Reported By" required class="w-full border rounded px-3 py-2" />
        <textarea name="description" placeholder="Description" required class="w-full border rounded px-3 py-2"></textarea>
        <select name="priority" class="w-full border rounded px-3 py-2" required>
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Add Maintenance</button>
      </form>
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
