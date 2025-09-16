<?php
// db connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "facilities";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Facilities</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="container mx-auto">
        <h1 class="text-2xl font-semibold mb-4">Facilities List</h1>

        <!-- Show Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="mb-4 px-4 py-2 rounded 
                <?php echo $_GET['type'] === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Facilities Table -->
        <table class="min-w-full bg-white shadow-md rounded border">
            <thead>
                <tr class="bg-gray-200 text-left text-sm uppercase">
                    <th class="py-3 px-4">ID</th>
                    <th class="py-3 px-4">Name</th>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Capacity</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM facilities";
                $result = $conn->query($sql);

                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                <tr class="border-t">
                    <td class="py-2 px-4"><?php echo $row['id']; ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['type']); ?></td>
                    <td class="py-2 px-4"><?php echo $row['capacity']; ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['status']); ?></td>
                    <td class="py-2 px-4">
                        <form action="delete_facility.php" method="POST" onsubmit="return confirm('Delete this facility?');">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="delete_type" value="facility">
                            <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-500">No facilities found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
