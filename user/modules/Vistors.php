<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'visitor';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check agreement checkbox
    if (empty($_POST['agreement'])) {
        http_response_code(400);
        echo '<span style="color: red;">You must agree to the Terms and Conditions.</span>';
        exit;
    }

    $fullName = trim($_POST['fullName'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    // Basic validation
    if (!$fullName || !$email) {
        http_response_code(400);
        echo '<span style="color: red;">Full Name and Email are required.</span>';
        exit;
    }

    // Use prepared statement for security
    $stmt = $conn->prepare("INSERT INTO guest_submissions (full_name, email, phone, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $fullName, $email, $phone, $notes);

    if ($stmt->execute()) {
        echo '<span style="color: green;">Thank you for your submission!</span>';
    } else {
        http_response_code(500);
        echo '<span style="color: red;">Error: ' . htmlspecialchars($stmt->error) . '</span>';
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <title>Visitors - User</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-gray-100">
<div class="flex h-screen">
  <aside class="w-64 bg-gray-800 text-white h-full">
    <?php include '../../Components/sidebar/sidebar_user.php'; ?>
  </aside>

  <main class="flex-1 p-6 overflow-y-auto">
    <h1 class="text-2xl font-bold mb-6">Dashboard</h1>

    <!-- Terms Section -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
      <h2 class="text-xl font-bold mb-2">Terms & Conditions</h2>
      <p class="mb-4 font-semibold">Welcome to our Hotel & Restaurant!</p>
      <p class="mb-4">By checking in or dining with us, you agree to the following policies:</p>
      <ul class="list-disc list-inside space-y-2 text-sm text-gray-700">
        <li><strong>Check-In & Check-Out:</strong> Valid ID required. Standard check-in 2:00 PM, check-out 12:00 PM.</li>
        <li><strong>Room & Facility Use:</strong> Guests are responsible for property. Damages will be charged.</li>
        <li><strong>Restaurant Policies:</strong> Reservations held for 15 minutes. No outside food/drinks.</li>
        <li><strong>Safety & Security:</strong> No smoking in restricted areas. No illegal items.</li>
        <li><strong>Payments & Cancellations:</strong> All payments on check-out. Late cancellations may incur charges.</li>
        <li><strong>Conduct:</strong> Respect staff & guests. Misconduct may lead to eviction.</li>
      </ul>
    </div>

    <!-- Visitor Form -->
    <div class="bg-white p-6 rounded-lg shadow">
      <form id="visitorForm" class="space-y-4" method="POST" action="">
        <div>
          <label for="fullName" class="block text-sm font-medium text-gray-700">Full Name:</label>
          <input type="text" id="fullName" name="fullName" required class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email:</label>
          <input type="email" id="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
        </div>
        <div>
          <label for="phone" class="block text-sm font-medium text-gray-700">Phone:</label>
          <input type="tel" id="phone" name="phone" class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
        </div>
        <div>
          <label for="notes" class="block text-sm font-medium text-gray-700">Special Notes (e.g., allergies):</label>
          <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md p-2"></textarea>
        </div>
        <div class="flex items-center space-x-2">
          <input type="checkbox" id="agreement" name="agreement" required class="rounded" />
          <label for="agreement" class="text-sm text-gray-700">I agree to the Terms and Conditions</label>
        </div>
        <div class="pt-4">
          <button type="submit" class="w-full bg-blue-600 text-white font-medium py-2 px-4 rounded hover:bg-blue-700 transition">Submit</button>
        </div>
      </form>

      <div id="responseMessage" class="mt-4 text-center"></div>
    </div>
  </main>
</div>

<!-- Ajax Submit Script -->
<script>
document.getElementById('visitorForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const form = this;
  const formData = new FormData(form);
  const responseDiv = document.getElementById('responseMessage');

  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(response => response.text())
  .then(data => {
    responseDiv.innerHTML = data;

    if (data.toLowerCase().includes('thank you')) {
      form.reset();
    }
  })
  .catch(() => {
    responseDiv.innerHTML = '<span style="color: red;">An error occurred. Please try again.</span>';
  });
});
</script>
</body>
</html>
