<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

// DB connection (adjust path if needed)
include '../backend/sql/db.php';

// Get user ID from session
$userId = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
  echo "User not found.";
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - user</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
</head>

<body class="flex h-screen bg-gray-100">

  <!-- Sidebar -->
  <aside class="fixed left-0 top-0 text-white">
    <?php include '../Components/sidebar/sidebar_user.php'; ?>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 ml-64 flex flex-col overflow-hidden">

    <!-- Header -->
    <header class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
      <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
    </header>

    <!-- Content -->
    <section class="flex-1 overflow-y-auto p-6 bg-gray-50">
      <div class="bg-white p-6 rounded shadow text-gray-800">
        <h2 class="text-2xl font-semibold mb-4">Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Joined:</strong> <?= htmlspecialchars(date("F j, Y, g:i a", strtotime($user['created_at']))) ?></p>
      </div>
    </section>

    <!-- Footer -->
    <?php include '../Components/Footer/footer_admin.php'; ?>

  </main>

  <!-- User Dropdown Script -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const userDropdownToggle = document.getElementById("userDropdownToggle");
      const userDropdown = document.getElementById("userDropdown");

      userDropdownToggle?.addEventListener("click", function () {
        userDropdown.classList.toggle("hidden");
      });

      document.addEventListener("click", function (event) {
        if (!userDropdown?.contains(event.target) && !userDropdownToggle?.contains(event.target)) {
          userDropdown.classList.add("hidden");
        }
      });
    });
  </script>

</body>

</html>
