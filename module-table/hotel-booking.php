<?php 
// ======= DATABASE CONFIGURATION =======
$host = "localhost";
$user = "admin_admin";
$pass = "123";
$db   = "admin_admin";

// ======= CONNECT TO MYSQL =======
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");

// Fetch Reservations
$reservations = [];
$resSql = "SELECT guest, roomType, checkIn, checkOut, rate FROM reservations ORDER BY checkIn DESC";
if ($result = $conn->query($resSql)) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $result->free();
}

// Fetch Visitors (not used here, but fetched if needed)
$visitors = [];
$visSql = "SELECT name, idNo, purpose, checkIn, checkedOut FROM visitors ORDER BY checkIn DESC";
if ($result = $conn->query($visSql)) {
    while ($row = $result->fetch_assoc()) {
        $visitors[] = $row;
    }
    $result->free();
}

$conn->close();

// Calculate total revenue and occupancy
$totalRevenue = 0;
foreach ($reservations as $res) {
    $checkIn = new DateTime($res['checkIn']);
    $checkOut = new DateTime($res['checkOut']);
    $nights = max(1, $checkOut->diff($checkIn)->days);
    $rate = $res['rate'] ?: ($res['roomType'] === 'Suite' ? 8000 : ($res['roomType'] === 'Double' ? 4000 : 2000));
    $totalRevenue += $rate * $nights;
}

// Assuming max 20 rooms for occupancy calculation
$occupancyPercent = min(100, round((count($reservations) / 20) * 100));

// Just for demo, add department_h1 and total_users values:
$department_h1 = "Admin"; 
$total_users = 123;
?>

<!doctype html>
<html lang="en" class="h-full bg-gray-100">
<head>
  <meta charset="utf-8" />
  <title>Booking Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex">

  <!-- Sidebar -->
  <aside id="sidebar" class="w-64 bg-gray-800 text-white p-6 min-h-screen">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <!-- Main content wrapper -->
  <div class="flex-1 flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="flex items-center justify-between border-b bg-white px-6 py-4 sticky top-0 z-10">
      <h2 class="text-xl font-semibold text-gray-800">
        <?= htmlspecialchars($department_h1) ?> Dashboard
        <span class="ml-4 text-base text-gray-500 font-normal">(Total Users: <?= $total_users ?>)</span>
      </h2>

      <!-- User Profile -->
      <?php include __DIR__ . '/../profile.php'; ?>
    </header>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto p-6">
      <div class="bg-white shadow rounded p-6">

        <h2 class="text-xl font-semibold mb-4">All Bookings</h2>

        <table class="w-full table-auto border border-gray-300">
          <thead class="bg-gray-200">
            <tr>
              <th class="border px-4 py-2 text-left">Guest</th>
              <th class="border px-4 py-2 text-left">Room Type</th>
              <th class="border px-4 py-2 text-left">Check-in</th>
              <th class="border px-4 py-2 text-left">Check-out</th>
              <th class="border px-4 py-2 text-left">Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $res): 
              $checkIn = new DateTime($res['checkIn']);
              $checkOut = new DateTime($res['checkOut']);
              $rate = $res['rate'] ?: ($res['roomType'] === 'Suite' ? 8000 : ($res['roomType'] === 'Double' ? 4000 : 2000));
            ?>
            <tr>
              <td class="border px-4 py-2"><?= htmlspecialchars($res['guest']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($res['roomType']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($res['checkIn']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($res['checkOut']) ?></td>
              <td class="border px-4 py-2">₱<?= number_format($rate) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="mt-6 text-sm text-gray-700">
          <p>💰 Total Revenue: ₱<?= number_format($totalRevenue) ?></p>
          <p>📈 Occupancy (approx): <?= $occupancyPercent ?>%</p>
        </div>

      </div>
    </main>

  </div>

</body>
</html>
