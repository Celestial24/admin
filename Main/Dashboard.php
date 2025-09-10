<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - Admin</title>
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Chart.js (Missing in original) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />

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

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      lucide.createIcons();

      const userDropdownToggle = document.getElementById("userDropdownToggle");
      const userDropdown = document.getElementById("userDropdown");

      userDropdownToggle?.addEventListener("click", function () {
        userDropdown.classList.toggle("hidden");
      });

      document.addEventListener("click", function (event) {
        if (!userDropdown?.contains(event.target) && !userDropdownToggle?.contains(event.target)) {
          userDropdown?.classList.add("hidden");
        }
      });

      // Chart.js: Contract Analysis Overview Chart
      const ctx = document.getElementById('contractChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode(["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]); ?>,
          datasets: [
            {
              label: 'Contracts Analyzed',
              data: <?php echo json_encode([12, 18, 15, 17, 14, 13, 16, 18, 15, 17, 19, 21]); ?>,
              backgroundColor: 'rgba(59, 130, 246, 0.7)', // blue-500
            },
            {
              label: 'High Risk Contracts',
              data: <?php echo json_encode([2, 3, 1, 4, 2, 3, 2, 5, 3, 2, 4, 3]); ?>,
              backgroundColor: 'rgba(248, 113, 113, 0.7)', // red-400
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              max: 25,
            }
          },
          plugins: {
            legend: { position: 'top' },
            title: { display: false }
          }
        }
      });
    });
  </script>
</head>

<body class="flex h-screen bg-gray-50">

  <div class="flex flex-1 w-full">

    <!-- Sidebar -->
    <div id="sidebar">
      <?php include '../Components/sidebar/sidebar_admin.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">

      <main class="flex-1 overflow-y-auto w-full py-4 px-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between border-b pb-4">
          <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        <?php include __DIR__ . '../../profile.php'; ?>
        </div>

        <!-- Contract Analysis Overview -->
        <section class="xl:col-span-3 bg-white p-6 rounded-lg shadow mb-6">
          <h2 class="text-xl font-semibold text-blue-700 mb-4 flex items-center">
            📊 Contract Analysis Overview
          </h2>
          <canvas id="contractChart" height="100"></canvas>
          <p class="mt-6 text-gray-600">Recent Contract Analyses</p>
        </section>

        <!-- High Risk Contract Section -->
        <section class="bg-gray-900 rounded-lg w-70 shadow-lg p-6 text-white">
          <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
            <span>🚨</span> High Risk Contract
          </h2>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-800 rounded p-4">
              <p class="text-xs font-bold uppercase text-gray-400 mb-1">Contract Type</p>
              <p class="text-lg font-semibold">Licensing Agreement</p>
            </div>
            <div class="bg-gray-800 rounded p-4">
              <p class="text-xs font-bold uppercase text-gray-400 mb-1">Effective Date</p>
              <p class="text-lg font-semibold">12/12/2023</p>
            </div>
            <div class="bg-gray-800 rounded p-4">
              <p class="text-xs font-bold uppercase text-gray-400 mb-1">Expiration Date</p>
              <p class="text-lg font-semibold">12/12/2024</p>
            </div>
            <div class="bg-gray-800 rounded p-4">
              <p class="text-xs font-bold uppercase text-gray-400 mb-1">Risk Level</p>
              <p class="text-lg font-semibold text-red-500">High</p>
            </div>
          </div>

          <!-- Issues -->
          <div class="mb-4">
            <h3 class="text-sm font-semibold text-gray-300 uppercase mb-2">Issues</h3>
            <ul class="list-disc list-inside space-y-1 text-sm text-red-400">
              <li>Ambiguous renewal terms</li>
              <li>Unclear termination clause</li>
              <li>Missing confidentiality provisions</li>
            </ul>
          </div>

          <!-- Recommended Actions -->
          <div class="mb-6">
            <h3 class="text-sm font-semibold text-gray-300 uppercase mb-2">Recommended Actions</h3>
            <ul class="list-disc list-inside space-y-1 text-sm text-green-400">
              <li>Review renewal terms with legal</li>
              <li>Clarify termination conditions</li>
              <li>Add confidentiality clause</li>
            </ul>
          </div>

          <!-- Color Guide Legend -->
          <div class="bg-gray-800 rounded p-4 text-sm text-gray-300">
            <h3 class="font-semibold mb-2 uppercase">Color Guide</h3>
            <ul class="space-y-1">
              <li class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded-full bg-red-500"></span> High Risk / Issues
              </li>
              <li class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded-full bg-yellow-400"></span> Medium Risk / Warning
              </li>
              <li class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded-full bg-green-400"></span> Low Risk / Recommended Actions
              </li>
            </ul>
          </div>
        </section>

      </main>

      <!-- Footer -->
      <?php include '../Components/Footer/footer_admin.php'; ?>

    </div>
  </div>
</body>
</html>
