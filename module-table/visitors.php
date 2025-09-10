<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'visitor';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

$sql = "SELECT * FROM guest_submissions ORDER BY submitted_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <title>Visitors - Admin</title>
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
    <aside class="w-64 text-white">
      <?php include '../Components/sidebar/sidebar_admin.php'; ?>
    </aside>

    <main class="flex-1 rounded shadow-md p-6 min-h-screen main-content-scroll">
      <h1 class="p-6 font-bold text-3xl">Dashboard</h1>

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

      <h2 class="text-xl font-semibold p-6 bg-white">Recent Guests</h2>
      <div class="overflow-x-auto bg-white">
        <table class="min-w-full border-collapse border border-gray-200 text-sm">
          <thead>
            <tr class="bg-white-100 text-gray-700 font-semibold">
              <th class="border border-gray-200 px-4 py-2 text-left">Employee ID</th>
              <th class="border border-gray-200 px-4 py-2 text-left">Name</th>
              <th class="border border-gray-200 px-4 py-2 text-left">Email</th>
              <th class="border border-gray-200 px-4 py-2 text-left">Check-in</th>
              <th class="border border-gray-200 px-4 py-2 text-left">Check-out</th>
              <th class="border border-gray-200 px-4 py-2 text-left">Notes</th>
              <th class="border border-gray-200 px-4 py-2 text-center">Agreement</th>
              <th class="border border-gray-200 px-4 py-2 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php 
                $counter = 1;
                while ($row = $result->fetch_assoc()): 
              ?>
                <tr class="hover:bg-gray-50 border border-gray-200">
                  <td class="border border-gray-200 px-4 py-2 font-mono">
                    <?php echo str_pad($counter++, 3, '0', STR_PAD_LEFT); ?>
                  </td>
                  <td class="border border-gray-200 px-4 py-2"><?php echo htmlspecialchars($row['full_name']); ?></td>
                  <td class="border border-gray-200 px-4 py-2"><?php echo htmlspecialchars($row['email']); ?></td>
                  <td class="border border-gray-200 px-4 py-2 text-green-600 font-semibold">
                    <?php echo htmlspecialchars($row['submitted_at']); ?>
                  </td>
                  <td class="border border-gray-200 px-4 py-2 text-red-600 font-semibold">
                    <?php
                      if (!empty($row['checked_out_at'])) {
                        echo htmlspecialchars($row['checked_out_at']);
                      } else {
                        echo "<span class='text-red-600 font-semibold'>Still here</span>";
                      }
                    ?>
                  </td>
                  <td class="border border-gray-200 px-4 py-2"><?php echo htmlspecialchars($row['notes']); ?></td>
                  <td class="border border-gray-200 px-4 py-2 text-center text-lg">
                    <?php if (!empty($row['checked_out_at'])): ?>
                      <span class="text-green-600 font-bold" title="Agreed">&#10004;</span>
                    <?php else: ?>
                      <span class="text-red-600 font-bold" title="Not Agreed">&#10060;</span>
                    <?php endif; ?>
                  </td>
                  <td class="border border-gray-200 px-4 py-2 text-center">
                    <?php if (!empty($row['checked_out_at'])): ?>
                      <span class="text-gray-400 cursor-default">Checked Out</span>
                    <?php else: ?>
                      <form method="POST" action="checkout.php" onsubmit="return confirm('Mark this guest as checked out?');" class="inline">
                        <input type="hidden" name="guest_id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" class="text-blue-600 hover:underline">Check Out</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center p-4 text-gray-500">No visitors found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <!-- Ajax Submit Script -->
  <script>
    const agreementCheckbox = document.getElementById('agreement');
    const submitBtn = document.getElementById('submitBtn');
    const visitorForm = document.getElementById('visitorForm');
    const responseDiv = document.getElementById('responseMessage');

    agreementCheckbox?.addEventListener('change', () => {
      submitBtn.disabled = !agreementCheckbox.checked;
      submitBtn.classList.toggle('cursor-not-allowed', !agreementCheckbox.checked);
      submitBtn.classList.toggle('opacity-50', !agreementCheckbox.checked);
    });

    visitorForm?.addEventListener('submit', function(e) {
      e.preventDefault();

      responseDiv.innerHTML = '';

      if (!confirm('Mark this guest as checked out?')) {
        return;
      }

      const formData = new FormData(visitorForm);

      fetch('', {
        method: 'POST',
        body: formData,
      })
      .then(response => response.text())
      .then(data => {
        responseDiv.innerHTML = data;

        if (data.toLowerCase().includes('thank you')) {
          visitorForm.reset();
          submitBtn.disabled = true;
          submitBtn.classList.add('cursor-not-allowed', 'opacity-50');
        }
      })
      .catch(() => {
        responseDiv.innerHTML = '<span style="color: red;">An error occurred. Please try again.</span>';
      });
    });
  </script>
</body>
</html>
