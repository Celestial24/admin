<?php
// ===== DATABASE CONNECTION =====
include '../backend/sql/db.php';

// ===== HANDLE DELETE ACTION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_email'])) {
    $delete_email = $_POST['delete_email'];
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $delete_email);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to avoid form resubmission
    header("Location: accounttable.php");
    exit;
}

// ===== FETCH USERS =====
$sql = "SELECT name, email, department FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Example dynamic values for header
$department_h1 = "HR1";
$total_users = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HR Admin - User Accounts</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>

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

<body class="bg-gray-100 min-h-screen flex">

  <!-- Sidebar -->
  <aside id="sidebar" class="w-64 bg-white shadow-md">
    <?php include '../Components/sidebar/sidebar_admin.php'; ?>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- Header -->
    <header class="flex items-center justify-between border-b bg-white px-6 py-4 shadow-sm">
      <h2 class="text-xl font-semibold text-gray-800">
        <?= htmlspecialchars($department_h1) ?> Dashboard
        <span class="ml-2 text-base text-gray-500 font-normal">(Total Users: <?= $total_users ?>)</span>
      </h2>
      <!-- User Profile -->
      <?php include __DIR__ . '/../profile.php'; ?>
    </header>

    <!-- Main Dashboard -->
    <main class="flex-1 overflow-y-auto w-full py-6 px-6">
      <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6 text-center">Created Accounts</h1>

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
              <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['department'] ?? 'N/A') ?></td>
                    <td class="py-2 px-4 border-b">
                      <form method="POST" onsubmit="return confirm('Are you sure you want to delete this account?');" class="inline">
                        <input type="hidden" name="delete_email" value="<?= htmlspecialchars($row['email']) ?>">
                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="py-2 px-4 border-b text-center text-gray-500">No users found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

</body>
</html>
