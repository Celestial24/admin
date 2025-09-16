<?php
session_start(); // Add session_start if you need sessions (e.g., for login checks)

// ======= DATABASE CONFIGURATION =======
// It's generally better practice to store DB credentials in a separate config file
// and include it, rather than hardcoding them. But for this fix, we'll keep them here.
$host = "localhost";
$dbname = "admin_admin";
$username = "admin_admin";
$password = "123";

// ✅ Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// ✅ Check connection
if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);


if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo "🚫 Sorry, we're having technical difficulties.";
    exit;
}
// Set charset
if (!$conn->set_charset("utf8")) {
    die("❌ Error setting charset: " . $conn->error);
}

// Fetch Reservations
$reservations = [];
$resSql = "SELECT guest, roomType, checkIn, checkOut, rate FROM reservations ORDER BY checkIn DESC";
if ($result = $conn->query($resSql)) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $result->free();
} else {
    // Optional: Handle query error
    // echo "Error fetching reservations: " . $conn->error;
}

// --- Optional/Future Use: Fetch Visitors ---
// $visitors = [];
// $visSql = "SELECT name, idNo, purpose, checkIn, checkedOut FROM visitors ORDER BY checkIn DESC";
// if ($result = $conn->query($visSql)) {
//     while ($row = $result->fetch_assoc()) {
//         $visitors[] = $row;
//     }
//     $result->free();
// } else {
//     // Optional: Handle query error
//     // echo "Error fetching visitors: " . $conn->error;
// }

// Close the connection as it's no longer needed for fetching
$conn->close();

// Calculate total revenue and occupancy
$totalRevenue = 0;
foreach ($reservations as $res) {
    // Add error handling for date parsing
    try {
        $checkIn = new DateTime($res['checkIn']);
        $checkOut = new DateTime($res['checkOut']);
        $interval = $checkOut->diff($checkIn);
        // Ensure nights is at least 1
        $nights = max(1, (int)$interval->format('%a'));
    } catch (Exception $e) {
        // If date parsing fails, default to 1 night
        $nights = 1;
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

// Assuming max 20 rooms for occupancy calculation (consider making this dynamic)
$totalRooms = 20;
$reservationCount = count($reservations);
$occupancyPercent = $totalRooms > 0 ? min(100, round(($reservationCount / $totalRooms) * 100)) : 0;

// Just for demo, add department_h1 and total_users values:
$department_h1 = "Admin";
$total_users = 123;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Dashboard</title>
    <!-- FIX: Removed extra space at the end of the CDN URL -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<!-- FIX: Ensure html/body classes and structure are consistent -->
<body class="h-full flex">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-gray-800 text-white p-6 min-h-screen flex flex-col">
        <?php
        // Include sidebar, check if file exists
        $sidebar_path = '../Components/sidebar/sidebar_admin.php';
        if (file_exists($sidebar_path) && is_readable($sidebar_path)) {
            include $sidebar_path;
        } else {
            echo "<p class='text-red-400'>Sidebar not found.</p>";
        }
        ?>
    </aside>

    <!-- Main content wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <header class="flex items-center justify-between border-b bg-white px-6 py-4 sticky top-0 z-10">
            <h2 class="text-xl font-semibold text-gray-800">
                <?= htmlspecialchars($department_h1 ?? 'Department') ?> Dashboard
                <span class="ml-4 text-base text-gray-500 font-normal">(Total Users: <?= htmlspecialchars($total_users ?? 0) ?>)</span>
            </h2>

            <!-- User Profile -->
            <?php
            // Include profile, check if file exists
            $profile_path = __DIR__ . '/../profile.php';
            if (file_exists($profile_path) && is_readable($profile_path)) {
                include $profile_path;
            } else {
                // Optionally render a placeholder or nothing
                // echo "<div class='bg-gray-200 rounded-full w-8 h-8'></div>";
            }
            ?>
        </header>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto p-6">
            <div class="bg-white shadow rounded p-6">

                <h2 class="text-xl font-semibold mb-4">All Bookings</h2>

                <!-- Check if there are reservations -->
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
                            <?php foreach ($reservations as $res):
                                // Recalculate nights and rate for display
                                try {
                                    $checkIn = new DateTime($res['checkIn']);
                                    $checkOut = new DateTime($res['checkOut']);
                                    $interval = $checkOut->diff($checkIn);
                                    $nights = max(1, (int)$interval->format('%a'));
                                } catch (Exception $e) {
                                    $nights = 1;
                                }

                                if (isset($res['rate']) && is_numeric($res['rate']) && $res['rate'] > 0) {
                                    $rate = (float)$res['rate'];
                                } else {
                                    switch ($res['roomType']) {
                                        case 'Suite': $rate = 8000.0; break;
                                        case 'Double': $rate = 4000.0; break;
                                        case 'Single':
                                        default: $rate = 2000.0; break;
                                    }
                                }
                                $total = $rate * $nights;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['guest'] ?? 'N/A') ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['roomType'] ?? 'N/A') ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['checkIn'] ?? 'N/A') ?></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars($res['checkOut'] ?? 'N/A') ?></td>
                                <td class="border px-4 py-2">₱<?= number_format($rate, 2) ?></td>
                                <td class="border px-4 py-2"><?= $nights ?></td>
                                <td class="border px-4 py-2">₱<?= number_format($total, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-6 text-sm text-gray-700 space-y-1">
                        <p>💰 <strong>Total Revenue:</strong> ₱<?= number_format($totalRevenue, 2) ?></p>
                        <p>📈 <strong>Occupancy (approx, based on <?= $totalRooms ?> max rooms):</strong> <?= $occupancyPercent ?>%</p>
                        <p>🏨 <strong>Total Bookings:</strong> <?= $reservationCount ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </main>

    </div>

</body>
</html>