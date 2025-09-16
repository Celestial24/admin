<?php
session_start();
// Database configuration
// Make sure this path is correct and the file defines $servername, $username, $password, $database
include_once __DIR__ . '/../backend/sql/db.php';

// --- FIX: Create the connection object FIRST ---
// Create connection using the variables from config.php
$conn = new mysqli($servername, $username, $password, $database);


// Check connection
// Now $conn exists and can be checked
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
// Now $conn exists and the method can be called
if (!$conn->set_charset("utf8")) {
    die("Error setting charset: " . $conn->error); // Use $conn->error for specific error
}

// --- Your database queries should go here ---
// Example (replace with your actual table/column names if different):
$reservations = [];
$sql = "SELECT guest, roomType, checkIn, checkOut, rate FROM reservations ORDER BY checkIn DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}
// Add similar blocks for other data like visitors if needed

// Close the connection when you're done fetching data
$conn->close();
// --- End Database Queries ---

// --- Your existing calculations ---
$totalRevenue = 0;
foreach ($reservations as $res) {
    try {
        $checkIn = new DateTime($res['checkIn']);
        $checkOut = new DateTime($res['checkOut']);
        $interval = $checkOut->diff($checkIn);
        $nights = max(1, (int)$interval->format('%a')); // Ensure at least 1 night
    } catch (Exception $e) {
        $nights = 1; // Default if date parsing fails
    }

    // Determine rate
    if (isset($res['rate']) && is_numeric($res['rate']) && $res['rate'] > 0) {
        $rate = (float)$res['rate'];
    } else {
        // Default rates based on room type
        switch ($res['roomType']) {
            case 'Suite':
                $rate = 8000.0;
                break;
            case 'Double':
                $rate = 4000.0;
                break;
            case 'Single':
            default:
                $rate = 2000.0;
                break;
        }
    }
    $totalRevenue += $rate * $nights;
}

// Assuming max 20 rooms for occupancy calculation (consider making this dynamic or configurable)
$totalRooms = 20;
$reservationCount = count($reservations);
$occupancyPercent = $totalRooms > 0 ? min(100, round(($reservationCount / $totalRooms) * 100)) : 0;

// Demo values (replace with actual logic if needed)
$department_h1 = "Hotel"; // Example
$total_users = 50;       // Example
?>
<!doctype html>
<html lang="en" class="h-full bg-gray-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex">

  <aside id="sidebar" class="w-64 bg-gray-800 text-white p-6 min-h-screen">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <div class="flex-1 flex flex-col overflow-hidden">

    <header class="flex items-center justify-between border-b bg-white px-6 py-4 sticky top-0 z-10">
      <h2 class="text-xl font-semibold text-gray-800">
        <?= htmlspecialchars($department_h1) ?> Dashboard
        <span class="ml-4 text-base text-gray-500 font-normal">(Total Users: <?= number_format($total_users) ?>)</span>
      </h2>
      <?php include __DIR__ . '/../profile.php'; ?>
    </header>

    <main class="flex-1 overflow-y-auto p-6">
      <div class="bg-white shadow rounded p-6">
        <h2 class="text-xl font-semibold mb-4">All Bookings (<?= $activeBookings ?>)</h2>

        <div class="overflow-x-auto">
          <table class="w-full table-auto border border-gray-300">
            <thead class="bg-gray-200">
              <tr>
                <th class="border px-4 py-2 text-left">Guest</th>
                <th class="border px-4 py-2 text-left">Room Type</th>
                <th class="border px-4 py-2 text-left">Check-in</th>
                <th class="border px-4 py-2 text-left">Check-out</th>
                <th class="border px-4 py-2 text-left">Nights</th>
                <th class="border px-4 py-2 text-left">Rate</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($reservations)): ?>
                <tr>
                  <td colspan="6" class="border px-4 py-2 text-center text-gray-500">No reservations found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($reservations as $res): ?>
                <tr>
                  <td class="border px-4 py-2"><?= htmlspecialchars($res['guest']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars($res['roomType']) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars(date("M d, Y", strtotime($res['checkIn']))) ?></td>
                  <td class="border px-4 py-2"><?= htmlspecialchars(date("M d, Y", strtotime($res['checkOut']))) ?></td>
                  <td class="border px-4 py-2 text-center"><?= $res['nights'] ?></td>
                  <td class="border px-4 py-2">₱<?= number_format($res['computedRate']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-6 text-sm text-gray-700 space-y-1">
          <p>💰 **Total Revenue:** ₱<?= number_format($totalRevenue) ?></p>
          <p>📈 **Occupancy (Based on <?= TOTAL_ROOMS ?> rooms):** <?= $occupancyPercent ?>%</p>
        </div>
      </div>
    </main>

  </div>

</body>
</html>
<?php
// ======= CLOSE DATABASE CONNECTION =======
// This is the correct place to close the connection, at the very end of the script.
$conn->close();
?>