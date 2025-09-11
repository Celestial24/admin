<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../login.php");
    exit();
}

include '../backend/sql/db.php';

$userId = $_SESSION['user']['id'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<div style='color:red; padding:2em;'>User not found. Please <a href='../login.php'>login again</a>.</div>";
    exit();
}

// Set department name (or any label you want)
$department_h1 = "User";

// Get total users
$total_users = 0;
$res = $conn->query("SELECT COUNT(*) as cnt FROM users");
if ($res) {
    $row = $res->fetch_assoc();
    $total_users = $row['cnt'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Dashboard</title>
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
    <div class="flex items-center justify-between border-b pb-4">
      <h2 class="text-xl font-semibold text-gray-800">
        <?= htmlspecialchars($department_h1) ?> Dashboard
        <span class="ml-4 text-base text-gray-500 font-normal">(Total Users: <?= $total_users ?>)</span>
      </h2>
      <?php include __DIR__ . '/../profile.php'; ?>
    </div>

    <!-- Content -->
    <section class="flex-1 overflow-y-auto p-6 bg-gray-50">
      <div class="bg-white p-6 rounded shadow text-gray-800">
        <h2 class="text-2xl font-semibold mb-4">
          Welcome, <?= htmlspecialchars($user['name'] ?? 'User') ?>!
        </h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
        <p><strong>Joined:</strong>
          <?= isset($user['created_at']) && $user['created_at'] !== null && $user['created_at'] !== '0000-00-00 00:00:00'
            ? htmlspecialchars(date("F j, Y, g:i a", strtotime($user['created_at'])))
            : '-' ?>
        </p>
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
