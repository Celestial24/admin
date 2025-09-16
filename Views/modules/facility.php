<?php
session_start();

// -- DATABASE CONNECTION & ALL PHP LOGIC AT THE TOP --
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "facilities_management";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -- FORM SUBMISSION HANDLING --
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type'])) {
    $form_type = $_POST['form_type'];
    $redirect_tab = $form_type; // Set the tab to redirect back to

    try {
        if ($form_type == "facility") {
            $stmt = $conn->prepare("INSERT INTO facilities (name, type, capacity, status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $_POST['name'], $_POST['type'], $_POST['capacity'], $_POST['status'], $_POST['notes']);
            $stmt->execute();
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Facility added successfully!'];
        } elseif ($form_type == "reservation") {
            if (strtotime($_POST['end_time']) <= strtotime($_POST['start_time'])) {
                throw new Exception('End time must be after start time.');
            }
            $stmt = $conn->prepare("INSERT INTO reservations (facility_id, reserved_by, purpose, status, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $_POST['facility_id'], $_POST['reserved_by'], $_POST['purpose'], $_POST['status'], $_POST['start_time'], $_POST['end_time']);
            $stmt->execute();
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Reservation created successfully!'];
        } elseif ($form_type == "maintenance") {
            $stmt = $conn->prepare("INSERT INTO maintenance (facility_id, reported_by, description, priority) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $_POST['facility_id'], $_POST['reported_by'], $_POST['description'], $_POST['priority']);
            $stmt->execute();
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Maintenance request submitted successfully!'];
        }
        $stmt->close();
    } catch (Exception $e) {
        // Set a user-friendly error message, do not expose raw database errors
        $_SESSION['feedback'] = ['type' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()];
    }

    // Redirect to the same page with a tab parameter to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=" . $redirect_tab);
    exit();
}

// Check for feedback messages from session
$feedback = null;
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}

// Fetch facilities for dropdown menus
$facilityOptions = "";
$result = $conn->query("SELECT id, name FROM facilities WHERE status = 'Available'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facilityOptions .= "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
    }
} else {
    $facilityOptions = "<option value='' disabled>No facilities available</option>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Facilities - User</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://unpkg.com/lucide@latest/dist/lucide.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen">

  <aside class="w-64 fixed left-0 top-0 h-full bg-white shadow-md z-10">
    <?php include '../../Components/sidebar/sidebar_user.php'; ?>
  </aside>

  <main class="ml-64 flex-1 flex flex-col">
    <header class="flex items-center justify-between border-b px-6 py-4 bg-white shadow-sm">
      <h2 class="text-xl font-semibold text-gray-800">Facilities Dashboard</h2>
      <?php include '../../profile.php'; ?>
    </header>

    <div class="flex-1 p-6 overflow-y-auto">
      <?php if ($feedback): ?>
          <div class="mb-4 p-4 rounded-lg <?= $feedback['type'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>" role="alert">
              <?= htmlspecialchars($feedback['message']) ?>
          </div>
      <?php endif; ?>

      <div class="flex space-x-6 border-b mb-6">
        <button class="tab-link pb-2" data-tab="facilities">Add Facility</button>
        <button class="tab-link pb-2" data-tab="reservations">Create Reservation</button>
        <button class="tab-link pb-2" data-tab="maintenance">Request Maintenance</button>
      </div>

      <div>
        <div id="facilities" class="tab-content">
          <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-4 max-w-lg" autocomplete="off">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">New Facility Details</h3>
            <input type="hidden" name="form_type" value="facility" />
            <input type="text" name="name" placeholder="Facility Name (e.g., Conference Room A)" required class="w-full border rounded-md px-3 py-2" />
            <input type="text" name="type" placeholder="Type (e.g., Room, Vehicle)" required class="w-full border rounded-md px-3 py-2" />
            <input type="number" name="capacity" placeholder="Capacity" required min="1" class="w-full border rounded-md px-3 py-2" />
            <select name="status" class="w-full border rounded-md px-3 py-2" required>
              <option value="Available">Available</option>
              <option value="Unavailable">Unavailable</option>
            </select>
            <textarea name="notes" placeholder="Notes" class="w-full border rounded-md px-3 py-2"></textarea>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700">submit</button>
          </form>
        </div>

        <div id="reservations" class="tab-content hidden">
          <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-4 max-w-lg" autocomplete="off">
             <h3 class="text-lg font-semibold border-b pb-2 mb-4">New Reservation Details</h3>
            <input type="hidden" name="form_type" value="reservation" />
            <select name="facility_id" required class="w-full border rounded-md px-3 py-2">
              <option value="" disabled selected>Select a Facility...</option>
              <?= $facilityOptions ?>
            </select>
            <input type="text" name="reserved_by" placeholder="Your Name" required class="w-full border rounded-md px-3 py-2" />
            <input type="text" name="purpose" placeholder="Purpose of Reservation" required class="w-full border rounded-md px-3 py-2" />
            <label class="block text-sm">Start Time:
              <input type="datetime-local" name="start_time" required class="w-full border rounded-md px-3 py-2 mt-1" />
            </label>
            <label class="block text-sm">End Time:
              <input type="datetime-local" name="end_time" required class="w-full border rounded-md px-3 py-2 mt-1" />
            </label>
             <input type="hidden" name="status" value="Pending" />
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700">Submit Reservation</button>
          </form>
        </div>

        <div id="maintenance" class="tab-content hidden">
          <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-4 max-w-lg" autocomplete="off">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">New Maintenance Request</h3>
            <input type="hidden" name="form_type" value="maintenance" />
            <select name="facility_id" required class="w-full border rounded-md px-3 py-2">
              <option value="" disabled selected>Select a Facility...</option>
              <?= $facilityOptions ?>
            </select>
            <input type="text" name="reported_by" placeholder="Your Name" required class="w-full border rounded-md px-3 py-2" />
            <textarea name="description" placeholder="Describe the issue" required class="w-full border rounded-md px-3 py-2"></textarea>
            <select name="priority" class="w-full border rounded-md px-3 py-2" required>
              <option value="Low">Low Priority</option>
              <option value="Medium">Medium Priority</option>
              <option value="High">High Priority</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-blue-700">Submit Request</button>
          </form>
        </div>
      </div>
    </div>
  </main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.querySelectorAll('.tab-link');
    const contents = document.querySelectorAll('.tab-content');
    const activeTabClass = 'border-b-2 border-blue-600 text-blue-600 font-semibold';
    const inactiveTabClass = 'text-gray-500 hover:text-blue-600';

    function setActiveTab(tabId) {
        tabs.forEach(tab => {
            tab.className = `tab-link pb-2 ${inactiveTabClass}`; // Reset all tabs
            if (tab.dataset.tab === tabId) {
                tab.classList.remove(...inactiveTabClass.split(' '));
                tab.classList.add(...activeTabClass.split(' '));
            }
        });

        contents.forEach(content => {
            content.classList.add('hidden');
            if (content.id === tabId) {
                content.classList.remove('hidden');
            }
        });
        // Save the active tab to localStorage to remember it
        localStorage.setItem('activeFormTab', tabId);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            setActiveTab(e.target.dataset.tab);
        });
    });

    // On page load, check for a tab in the URL or in localStorage
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    const savedTab = localStorage.getItem('activeFormTab');
    
    const initialTab = tabFromUrl || savedTab || 'facilities';
    setActiveTab(initialTab);
});
</script>

</body>
</html>
<?php
// Close the single database connection at the very end
$conn->close();
?>