<?php
// ======= CONFIGURATION & SETUP =======
// Define a constant for the total number of rooms. Easier to update later.
define('TOTAL_ROOMS', 20);

// Set error reporting for development (optional but recommended)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// Start a session if your included files need it (good practice)
// session_start(); 

// ======= DATABASE CONNECTION =======
// Require the database connection file. Script will stop if it's not found.
require_once '../backend/sql/db.php';


// ======= DATA FETCHING & PROCESSING =======
$reservations = [];
$totalRevenue = 0;
$resSql = "SELECT guest, roomType, checkIn, checkOut, rate FROM reservations ORDER BY checkIn DESC";

if ($result = $conn->query($resSql)) {
    while ($row = $result->fetch_assoc()) {
        // Calculate rate and nights here to avoid repeating logic later (DRY Principle)
        $checkIn  = new DateTime($row['checkIn']);
        $checkOut = new DateTime($row['checkOut']);
        $nights   = max(1, $checkOut->diff($checkIn)->days); // Ensure at least 1 night is charged

        // Use the rate from DB if available, otherwise calculate it based on room type
        $computedRate = $row['rate'] ?: match ($row['roomType']) {
            'Suite'  => 8000,
            'Double' => 4000,
            default  => 2000,
        };
        
        // Add the calculated values to the row
        $row['nights'] = $nights;
        $row['computedRate'] = $computedRate;

        // Add to the total revenue
        $totalRevenue += $computedRate * $nights;
        
        // Add the fully processed row to our reservations array
        $reservations[] = $row;
    }
    $result->free(); // Free the result set
} else {
    // Handle potential SQL query errors
    die("Error fetching reservations: " . $conn->error);
}


// ======= BUSINESS LOGIC & CALCULATIONS =======
// Calculate occupancy percentage based on the number of fetched reservations
$activeBookings = count($reservations);
$occupancyPercent = min(100, round(($activeBookings / TOTAL_ROOMS) * 100));

// Dummy dashboard values (can be replaced with real data)
$department_h1 = "Admin";
$total_users   = 123;
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