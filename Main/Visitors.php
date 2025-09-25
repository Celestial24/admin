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
    header("Location: Visitors.php?msg=Visitor added successfully");
    exit;
}

// ================== DELETE VISITOR ==================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM visitors WHERE id=$id");
    header("Location: Visitors.php?msg=Visitor deleted successfully&type=deleted");
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
    header("Location: Visitors.php?msg=Visitor updated successfully&type=updated");
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
    
    /* Alert messages */
    .alert {
      padding: 10px 15px;
      border-left: 5px solid;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    .alert-success {
      background: #d4edda;
      color: #155724;
      border-color: #28a745;
    }
    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border-color: #dc3545;
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
  <?php include '../Components/sidebar/sidebar_admin.php'; ?>

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
      <?php 
        if (isset($_GET['msg'])) {
          $type = isset($_GET['type']) ? $_GET['type'] : '';
          $cls = $type === 'deleted' ? 'alert alert-danger' : 'alert alert-success';
          echo "<p class='".$cls."'>".htmlspecialchars($_GET['msg'])."</p>";
        }
      ?>

      <!-- Add Visitor (Modal Trigger + Modal) -->
      <?php if (!isset($_GET['edit'])): ?>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900">Add New Visitor</h2>
        <button id="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">Add Visitor</button>
      </div>

      <div id="createModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
          <div class="bg-white rounded-lg shadow-lg w-full max-w-lg">
            <div class="px-6 py-4 border-b flex items-center justify-between">
              <h3 class="text-lg font-semibold">New Visitor</h3>
              <button id="closeCreateModal" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            <div class="p-6">
              <form id="createVisitorForm" name="visitorForm" method="POST" onsubmit="return validateForm()" class="space-y-4">
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
                <div class="flex justify-end space-x-3 pt-2">
                  <button type="button" id="cancelCreate" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                  <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Create</button>
                </div>
              </form>
            </div>
          </div>
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
              <a href="Visitors.php" 
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
          <div class="flex items-center gap-3">
            <div class="relative">
              <input id="visitorSearch" type="text" placeholder="Search visitors..." class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" />
              <span class="absolute left-3 top-2.5 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
              </span>
            </div>
            <div class="text-sm text-gray-500">
              <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
              All Visitors
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full text-center">
              <thead>
                <tr>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">ID</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Full Name</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Email</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Phone</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Address</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Purpose</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php
                  $result = $conn->query("SELECT * FROM visitors ORDER BY created_at DESC");
                  if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                          echo "<tr class='hover:bg-gray-50'>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center'>{$row['id']}</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center'>".htmlspecialchars($row['fullname'])."</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center'>".htmlspecialchars($row['email'])."</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center'>".htmlspecialchars($row['phone'])."</td>
                                  <td class='px-6 py-4 text-sm text-gray-900 text-center'>".htmlspecialchars($row['address'])."</td>
                                  <td class='px-6 py-4 text-sm text-gray-900 text-center'>".htmlspecialchars($row['purpose'])."</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center'>{$row['created_at']}</td>
                                  <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-center'>
                                      <a href='Visitors.php?edit={$row['id']}' class='text-blue-600 hover:text-blue-900 mr-3'>Edit</a>
                                      <button type='button' data-id='{$row['id']}' class='openDeleteModal text-red-600 hover:text-red-900'>Delete</button>
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

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
        <div class="px-6 py-4 border-b">
          <h3 class="text-lg font-semibold text-gray-900">Delete Visitor</h3>
        </div>
        <div class="p-6">
          <p class="text-gray-700">Are you sure you want to delete this visitor? This action cannot be undone.</p>
        </div>
        <div class="px-6 py-4 border-t flex justify-end space-x-3">
          <button id="cancelDelete" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
          <a id="confirmDelete" href="#" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Initialize Lucide icons
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
    // Create modal controls
    document.addEventListener('DOMContentLoaded', function() {
      const createModal = document.getElementById('createModal');
      const openCreateModal = document.getElementById('openCreateModal');
      const closeCreateModal = document.getElementById('closeCreateModal');
      const cancelCreate = document.getElementById('cancelCreate');
      if (openCreateModal) openCreateModal.addEventListener('click', () => createModal.classList.remove('hidden'));
      if (closeCreateModal) closeCreateModal.addEventListener('click', () => createModal.classList.add('hidden'));
      if (cancelCreate) cancelCreate.addEventListener('click', () => createModal.classList.add('hidden'));
      if (createModal) createModal.addEventListener('click', (e) => { if (e.target === createModal) createModal.classList.add('hidden'); });

      // Delete modal controls
      const deleteModal = document.getElementById('deleteModal');
      const confirmDelete = document.getElementById('confirmDelete');
      const cancelDelete = document.getElementById('cancelDelete');
      document.querySelectorAll('.openDeleteModal').forEach(btn => {
        btn.addEventListener('click', function() {
          const id = this.getAttribute('data-id');
          confirmDelete.setAttribute('href', `Visitors.php?delete=${id}`);
          deleteModal.classList.remove('hidden');
        });
      });
      if (cancelDelete) cancelDelete.addEventListener('click', () => deleteModal.classList.add('hidden'));
      if (deleteModal) deleteModal.addEventListener('click', (e) => { if (e.target === deleteModal) deleteModal.classList.add('hidden'); });

      // Client-side search filter
      const searchInput = document.getElementById('visitorSearch');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const query = this.value.toLowerCase();
          document.querySelectorAll('tbody tr').forEach(tr => {
            const text = tr.textContent.toLowerCase();
            tr.style.display = text.includes(query) ? '' : 'none';
          });
        });
      }
    });
  </script>
</body>
</html>
