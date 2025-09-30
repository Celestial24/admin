<?php
// ==========================
// Session & Authentication
// ==========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header("Location: ../auth/login.php");
    exit;
}

// ==========================
// Error Reporting (Development Only)
// ==========================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================
// Database Connection
// ==========================
require_once '../backend/sql/db.php';

// ==========================
// Fetch Latest Department (reuse for header context)
// ==========================
$department_h1 = 'Super Admin';
$dept_result = $conn->query("SELECT department FROM users ORDER BY id DESC LIMIT 1");
if ($dept_result && $dept_row = $dept_result->fetch_assoc()) {
    $department_h1 = trim($dept_row['department']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Super Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" href="../assets/image/logo2.png" type="image/png">
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
    <aside id="sidebar">
      <?php include '../Components/sidebar/sidebar_user'; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <main class="flex-1 overflow-y-auto w-full py-4 px-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4">
          <h2 class="text-xl font-semibold text-gray-800">
            Super Admin Dashboard
          </h2>

          <!-- User Profile -->
          <?php include __DIR__ . '/../profile.php'; ?>
        </div>

        <!-- Content placeholder: mirror structure for widgets/tables -->
        <div class="grid grid-cols-1 gap-6">
          <div class="bg-white border border-gray-200 rounded-md p-4">
            <h3 class="text-lg font-semibold mb-2">Welcome</h3>
            <p>Hello, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Super Admin'); ?>. Build Super Admin widgets here.</p>
          </div>
        </div>

      </main>

      <!-- Footer -->
      <?php include '../Components/Footer/footer_admin.php'; ?>
    </div>
  </div>

  <!-- Chatbot -->
  <?php include __DIR__ . '/../chatbot.php'; ?>

  <?php
    if (isset($conn)) { $conn->close(); }
  ?>
</body>
</html>


