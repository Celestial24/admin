<?php
session_start();

// ======= DATABASE CONFIGURATION =======
$host = "localhost";
$dbname = "admin_admin";
$username = "admin_admin";
$password = "123";

// ✅ Establish database connection
$conn = new mysqli($host, $username, $password, $dbname);

// ✅ Check for connection errors
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo "🚫 Sorry, we're having technical difficulties.";
    exit;
}

// ✅ Set charset
if (!$conn->set_charset("utf8")) {
    die("❌ Error setting charset: " . $conn->error);
}

// ======= FETCH RESERVATIONS =======
$reservations = [];
$resSql = "SELECT guest, roomType, checkIn, checkOut, rate FROM reservations ORDER BY checkIn DESC";

if ($result = $conn->query($resSql)) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $result->free();
}

// Close connection
$conn->close();

// ======= CALCULATE TOTAL REVENUE & OCCUPANCY =======
$totalRevenue = 0;
$totalRooms = 20;
$reservationCount = count($reservations);

foreach ($reservations as $res) {
    try {
        $checkIn = new DateTime($res['checkIn']);
        $checkOut = new DateTime($res['checkOut']);
        $nights = max(1, (int)$checkOut->diff($checkIn)->format('%a'));
    } catch (Exception $e) {
        $nights = 1;
    }

    // Fallback rate based on room type
    $rate = (isset($res['rate']) && is_numeric($res['rate']) && $res['rate'] > 0)
        ? (float)$res['rate']
        : match ($res['roomType']) {
            'Suite' => 8000.0,
            'Double' => 4000.0,
            default => 2000.0,
        };

    $totalRevenue += $rate * $nights;
}

$occupancyPercent = $totalRooms > 0
    ? min(100, round(($reservationCount / $totalRooms) * 100))
    : 0;

// Dummy values for demo
$department_h1 = "Admin";
$total_users = 123;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex">

<!-- Sidebar -->
<aside class="w-64 bg-gray-800 text-white p-6 min-h-screen flex flex-col">
    <?php
    $sidebar_path = '../Components/sidebar/sidebar_admin.php';
    if (file_exists($sidebar_path)) {
        include $sidebar_path;
    } else {
        echo "<p class='text-red-400'>Sidebar not found.</p>";
    }
    ?>
</aside>

<!-- Main content -->
<div class="flex-1 flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="flex items-center justify-between border-b bg-white px-6 py-4 sticky top-0 z-10">
        <h2 class="text-xl font-semibold text-gray-800">
            <?= htmlspecialchars($department_h1) ?> Dashboard
            <span class="ml-4 text-base text-gray-500 font-normal">(Total Users: <?= (int)$total_users ?>)</span>
        </h2>
        <?php
        $profile_path = __DIR__ . '/../profile.php';
        if (file_exists($profile_path)) {
            include $profile_path;
        }
        ?>
    </header>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto p-6">
        <div class="bg-white shadow rounded p-6">
            <h2 class="text-xl font-semibold mb-4">All Bookings</h2>

            <?php if (empty($reservations)): ?>
                <p class="text-gray-500">No bookings found.</p>
            <?php else: ?>
                <table class="w-full table-auto border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border px-4 py-2 text-left">Guest</th>
                            <th class="border px-4 py-2 text-left">Room Type</th>
                            <th class="border px-4 py-2 text-left">Check-in</th>
                            <th class="border px-4 py-2 text-left">Check-out</th>
                            <th class="border px-4 py-2 text-left">Rate (PHP)</th>
                            <th class="border px-4 py-2 text-left">Nights</th>
                            <th class="border px-4 py-2 text-left">Total (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                            <?php
                            try {
                                $checkIn = new DateTime($res['checkIn']);
                                $checkOut = new DateTime($res['checkOut']);
                                $nights = max(1, (int)$checkOut->diff($checkIn)->format('%a'));
                            } catch (Exception $e) {
                                $nights = 1;
                            }

                            $rate = (isset($res['rate']) && is_numeric($res['rate']) && $res['rate'] > 0)
                                ? (float)$res['rate']
                                : match ($res['roomType']) {
                                    'Suite' => 8000.0,
                                    'Double' => 4000.0,
                                    default => 2000.0,
                                };

                            $total = $rate * $nights;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['guest']) ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['roomType']) ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['checkIn']) ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['checkOut']) ?></td>
                                <td class="border px-4 py-2">₱<?= number_format($rate, 2) ?></td>
                                <td class="border px-4 py-2"><?= $nights ?></td>
                                <td class="border px-4 py-2">₱<?= number_format($total, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-6 text-sm text-gray-700 space-y-1">
                    <p>💰 <strong>Total Revenue:</strong> ₱<?= number_format($totalRevenue, 2) ?></p>
                    <p>📈 <strong>Occupancy (<?= $totalRooms ?> rooms):</strong> <?= $occupancyPercent ?>%</p>
                    <p>🏨 <strong>Total Bookings:</strong> <?= $reservationCount ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</div>

</body>
</html>
