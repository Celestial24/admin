<?php
include '../backend/sql/db.php';

// 1. Get latest department or default to HR1
$department_h1 = "HR1";
$dept_result = $conn->query("SELECT department FROM users ORDER BY id DESC LIMIT 1");
if ($dept_result && $dept_row = $dept_result->fetch_assoc()) {
    $department_h1 = trim($dept_row['department']);
}

// 2. Prepare statement to fetch users by department
$sql = "SELECT name, email, department FROM users WHERE department = ?";
$stmt = $conn->prepare($sql);

$users = [];
if ($stmt) {
    $stmt->bind_param("s", $department_h1);
    $stmt->execute();
    $result = $stmt->get_result();
    // Fetch all users into array to avoid pointer issues
    $users = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("SQL prepare failed: " . $conn->error);
}

// 3. Count total users in this department
$total_users = count($users);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>intergation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
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
<body class="flex h-screen bg-gray-50">
  <div class="flex flex-1 w-full">

    <!-- Sidebar -->
    <div id="sidebar">
      <?php include '../Components/sidebar/sidebar_system.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <main class="flex-1 overflow-y-auto w-full py-4 px-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4">
          <h2 class="text-xl font-semibold text-gray-800">
            <?= htmlspecialchars($department_h1) ?> Dashboard
            <span class="ml-4 text-base text-gray-500 font-normal">(Total Users: <?= $total_users ?>)</span>
          </h2>
          <?php include __DIR__ . '../../profile.php'; ?>
        </div>

        <!-- Accounts Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border border-gray-200 rounded-md">
            <thead>
              <tr class="bg-gray-100 text-left">
                <th class="py-3 px-4 border-b">Fullname</th>
                <th class="py-3 px-4 border-b">Admin Email</th>
                <th class="py-3 px-4 border-b">Department</th>
                <th class="py-3 px-4 border-b">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($users)): ?>
                <?php foreach ($users as $row): ?>
                  <tr>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['department'] ?? 'N/A') ?></td>
                    <td class="py-2 px-4 border-b">
                      <form method="POST" onsubmit="return confirm('Are you sure you want to delete this account?');" style="display:inline;">
                        <input type="hidden" name="delete_email" value="<?= htmlspecialchars($row['email']) ?>">
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="py-2 px-4 border-b text-center text-gray-500">No users found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </main>

      <!-- Footer -->
      <?php include '../Components/Footer/footer_admin.php'; ?>
    </div>
  </div>
</body>
</html>
