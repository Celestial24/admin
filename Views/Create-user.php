<?php
require_once '../backend/sql/db.php'; // Make sure this file sets up $conn as mysqli connection

// Initialize variables
$employees = [];
$departments = [
    'Administration',
    'Logistic 1',
    'Logistic 2',
    'Core1',
    'Core2',
    'Human Resources 1',
    'Human Resources 2',
    'Human Resources 3',
    'Human Resources 4',
    'Financial Management'
];

// Fetch employees
$sql = "SELECT employee_id, name, email, contact_number, department, password FROM employees";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
    mysqli_free_result($result);
} else {
    die("Error fetching employees: " . mysqli_error($conn));
}

// Get next AUTO_INCREMENT value
$sqlNextId = "
    SELECT AUTO_INCREMENT 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'employees'
";

$resultNextId = mysqli_query($conn, $sqlNextId);
if ($resultNextId) {
    $row = mysqli_fetch_assoc($resultNextId);
    $nextId = $row['AUTO_INCREMENT'] ?? 1;
    mysqli_free_result($resultNextId);

    $formattedNextId = str_pad($nextId, 3, '0', STR_PAD_LEFT);
} else {
    die("Error fetching next AUTO_INCREMENT: " . mysqli_error($conn));
}
?>


<head>
  <title>Create User</title>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      min-height: 100svh;
      margin: 0;
      background:
        radial-gradient(80% 60% at 8% 10%, rgba(255,255,255,.18) 0, transparent 60%),
        radial-gradient(80% 40% at 100% 0%, rgba(212,175,55,.08) 0, transparent 40%),
        linear-gradient(140deg, rgba(15,28,73,1) 50%, rgba(255,255,255,1) 50%);
    }
  </style>
</head>

<body class="bg-gray-100 p-10">
  <a href="../Main/index.php"><img src="../assets/image/logo.png" alt="" style="width: 100px;"></a>

  <!-- Employee Table Container -->
  <div class="max-w-7xl mx-auto bg-white p-6 rounded shadow-md">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Employee list</h1>
      <button id="openModalBtn"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Employee
      </button>
    </div>

    <table class="min-w-full table-auto border-collapse">
      <thead class="bg-gray-200 text-gray-700 uppercase text-sm">
        <tr>
          <th class="px-4 py-3 text-left">Employee ID</th>
          <th class="px-4 py-3 text-left">Name</th>
          <th class="px-4 py-3 text-left">Email</th>
          <th class="px-4 py-3 text-left">Contact</th>
          <th class="px-4 py-3 text-left">Department</th>
          <th class="px-4 py-3 text-left">Password</th>
          <th class="px-4 py-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($employees)) : ?>
          <?php foreach ($employees as $emp): ?>
            <tr class="hover:bg-gray-50 border-b">
              <td class="px-4 py-2"><?= htmlspecialchars($emp['employee_id']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($emp['name']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($emp['email']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($emp['contact_number']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($emp['department'] ?? 'None') ?></td>
              <td class="px-4 py-2">
                <div class="relative inline-block max-w-xs">
                  <input 
                    type="password" 
                    value="<?= htmlspecialchars($emp['password']) ?>" 
                    readonly
                    class="password-field border px-2 py-1 rounded text-sm w-full"
                    style="max-width: 140px;"
                  />
                  <button type="button" 
                          class="toggle-password absolute right-1 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-900"
                          aria-label="Toggle password visibility"
                          title="Show/Hide password">
                    👁️
                  </button>
                </div>
              </td>
              <td class="px-4 py-2 text-center space-x-2">
                <a href="view-employee.php?id=<?= urlencode($emp['employee_id']) ?>" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">View</a>
                <a href="#" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">Edit</a>
                <a href="#" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="px-4 py-4 text-center text-gray-500">No employees found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal -->
  <div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white p-6 rounded shadow-md max-w-md w-full relative">
      <button id="closeModalBtn" class="absolute top-2 right-2 text-gray-600 hover:text-red-600 text-xl font-bold">&times;</button>
      <h2 class="text-xl font-semibold mb-4">Add New Employee</h2>

      <form action="../backend/modules/Create-employee.php" method="POST" class="space-y-4">
        <div>
          <label for="display_id" class="block text-gray-700">Employee ID</label>
          <input type="text" id="display_id" value="<?= htmlspecialchars($formattedNextId) ?>" disabled
                 class="w-full px-3 py-2 bg-gray-100 border rounded text-gray-600"/>
        </div>

        <div>
          <label for="name" class="block text-gray-700">Name</label>
          <input type="text" name="name" id="name" required
                 class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"/>
        </div>

        <div>
          <label for="email" class="block text-gray-700">Email</label>
          <input type="email" name="email" id="email" required
                 class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"/>
        </div>

        <div>
          <label for="contact_number" class="block text-gray-700">Contact Number</label>
          <input type="text" name="contact_number" id="contact_number" required
                 class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"/>
        </div>

        <div>
          <label for="department" class="block text-gray-700">Department</label>
          <select name="department" id="department"
                  class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">None</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="password" class="block text-gray-700">Password</label>
          <input type="password" name="password" id="password" required
                 class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"/>
        </div>

        <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">
          Save
        </button>
      </form>
    </div>
  </div>

  <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
      button.addEventListener('click', () => {
        const input = button.previousElementSibling;
        if (input.type === 'password') {
          input.type = 'text';
          button.textContent = '🙈'; // change icon to closed eye
        } else {
          input.type = 'password';
          button.textContent = '👁️'; // open eye icon
        }
      });
    });

    // Modal open/close
    const modal = document.getElementById('modal');
    document.getElementById('openModalBtn').addEventListener('click', () => {
      modal.classList.remove('hidden');
    });
    document.getElementById('closeModalBtn').addEventListener('click', () => {
      modal.classList.add('hidden');
    });
    window.addEventListener('keydown', (e) => {
      if (e.key === "Escape") {
        modal.classList.add('hidden');
      }
    });
  </script>
</body>
