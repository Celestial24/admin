<?php
// Start session
session_start();

// Database connection
$host = 'localhost';
$user = 'admin_visitors';
$pass = '123';
$db   = 'admin_visitors';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ================== ADD VISITOR ==================
if (isset($_POST['action']) && $_POST['action'] === "add") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $purpose = $_POST['purpose'];

    $stmt = $conn->prepare("INSERT INTO visitors (fullname, email, phone, address, purpose) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullname, $email, $phone, $address, $purpose);
    $stmt->execute();
    header("Location: visitors.php?msg=Visitor added successfully");
    exit;
}

// ================== DELETE VISITOR ==================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM visitors WHERE id=$id");
    header("Location: visitors.php?msg=Visitor deleted successfully");
    exit;
}

// ================== UPDATE VISITOR ==================
if (isset($_POST['action']) && $_POST['action'] === "update") {
    $id = intval($_POST['id']);
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $purpose = $_POST['purpose'];

    $stmt = $conn->prepare("UPDATE visitors SET fullname=?, email=?, phone=?, address=?, purpose=? WHERE id=?");
    $stmt->bind_param("sssssi", $fullname, $email, $phone, $address, $purpose, $id);
    $stmt->execute();
    header("Location: visitors.php?msg=Visitor updated successfully");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hotel & Restaurant Visitors Log</title>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>
  
  <style>
    /* Custom styles for smoother transitions */
    #sidebar {
      transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }
    #sidebar-toggle i {
      transition: transform 300ms ease-in-out;
    }
    .rotate-180 {
      transform: rotate(180deg);
    }
    .facility-modules {
      display: none;
    }
    .facility-modules.show {
      display: block;
    }
    
    /* Custom form styles */
    .form-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 60vh;
    }
    
    .form-wrapper {
      text-align: center;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 500px;
    }
    
    .form-wrapper h2 {
      margin-bottom: 20px;
      font-size: 1.5rem;
      color: #2c3e50;
      border-left: 4px solid #013c63ff;
      padding-left: 10px;
      display: inline-block;
    }
    
    /* Success message */
    .success {
      background: #d4edda;
      color: #155724;
      padding: 10px 15px;
      border-left: 5px solid #28a745;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    
    /* Table styles */
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    
    table thead {
      background: #013c63ff;
      color: white;
    }
    
    table th, table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }
    
    table tbody tr:hover {
      background: #f9f9f9;
    }
    
    table td a {
      color: #013c63ff;
      text-decoration: none;
      font-weight: 500;
      margin: 0 5px;
    }
    
    table td a:hover {
      text-decoration: underline;
    }
  </style>

  <script>
    function validateForm() {
      const phone = document.forms["visitorForm"]["phone"].value;
      if (!/^[0-9]{10,15}$/.test(phone)) {
        alert("Enter a valid phone number (10-15 digits).");
        return false;
      }
      return true;
    }
  </script>
</head>
<body class="min-h-screen flex bg-gray-50">
  <!-- Include Sidebar -->
  <?php include '../Components/sidebar/sidebar_user.php'; ?>

  <!-- Main Content Area -->
  <div class="flex-1 min-h-screen overflow-auto">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Visitor Management</h1>
          <p class="text-sm text-gray-600">Manage hotel and restaurant visitors</p>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-sm text-gray-500">
            <i data-lucide="users" class="w-4 h-4 inline mr-1"></i>
            Visitor Logs
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="p-6">
      <?php if (isset($_GET['msg'])) echo "<p class='success'>".$_GET['msg']."</p>"; ?>

      <!-- Add Visitor Form -->
      <?php if (!isset($_GET['edit'])): ?>
      <div class="form-container">
        <div class="form-wrapper">
          <h2>Add New Visitor</h2>
          <?php if (isset($_GET['msg'])) echo "<p class='success'>".$_GET['msg']."</p>"; ?>
          
          <form name="visitorForm" method="POST" onsubmit="return validateForm()" class="space-y-4">
            <input type="hidden" name="action" value="add">
            <input type="text" name="fullname" placeholder="Full Name" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="email" name="email" placeholder="Email" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" name="phone" placeholder="Phone Number" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" name="address" placeholder="Address" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <textarea name="purpose" placeholder="Purpose of Visit" required 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent h-24 resize-none"></textarea>
            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
              Add Visitor
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <!-- Edit Visitor Form -->
      <?php
      if (isset($_GET['edit'])) {
          $id = intval($_GET['edit']);
          $result = $conn->query("SELECT * FROM visitors WHERE id=$id");
          if ($result && $row = $result->fetch_assoc()) {
      ?>
      <div class="form-container">
        <div class="form-wrapper">
          <h2>Edit Visitor</h2>
          <form method="POST" onsubmit="return validateForm()" class="space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($row['fullname']); ?>" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" name="phone" value="<?php echo htmlspecialchars($row['phone']); ?>" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <input type="text" name="address" value="<?php echo htmlspecialchars($row['address']); ?>" required 
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <textarea name="purpose" required 
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent h-24 resize-none"><?php echo htmlspecialchars($row['purpose']); ?></textarea>
            <div class="flex space-x-3">
              <button type="submit" 
                      class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                Update Visitor
              </button>
              <a href="../user/Visitors.php" 
                 class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 text-center">
                Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
      <?php 
          }
      } 
      ?>

      <!-- Visitors Dashboard -->
      <div class="mt-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900">Visitors Dashboard</h2>
          <div class="text-sm text-gray-500">
            <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
            All Visitors
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Full Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Phone</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Address</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Purpose</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php
                  $result = $conn->query("SELECT * FROM visitors ORDER BY created_at DESC");
                  if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                          echo "<tr class='hover:bg-gray-50'>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>{$row['id']}</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>".htmlspecialchars($row['fullname'])."</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>".htmlspecialchars($row['email'])."</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>".htmlspecialchars($row['phone'])."</td>
                                  <td class='px-6 py-4 text-sm text-gray-900'>".htmlspecialchars($row['address'])."</td>
                                  <td class='px-6 py-4 text-sm text-gray-900'>".htmlspecialchars($row['purpose'])."</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>{$row['created_at']}</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>
                                      <a href='../user/Visitors.php?edit={$row['id']}' class='text-blue-600 hover:text-blue-900 mr-3'>Edit</a>
                                      <a href='../user/Visitors.php?delete={$row['id']}' onclick='return confirm(\"Delete this visitor?\")' class='text-red-600 hover:text-red-900'>Delete</a>
                                  </td>
                                </tr>";
                      }
                  } else {
                      echo "<tr><td colspan='8' class='px-6 py-4 text-center text-gray-500'>No visitors yet.</td></tr>";
                  }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Initialize Lucide icons
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  </script>
</body>
</html>
