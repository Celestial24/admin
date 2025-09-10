<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Archived Documents with Filters</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

  <!-- Header -->
  <header class="bg-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
      <h1 class="text-xl font-bold text-gray-800">📁 Archive Center</h1>
      <span class="text-sm text-gray-500">Hotel & Restaurant Management</span>
    </div>
  </header>

  <!-- Main Content -->
  <main class="max-w-6xl mx-auto px-4 mt-8 space-y-8">

    <!-- Filters -->
    <section class="bg-white p-6 rounded-lg shadow">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Archived Documents</h2>
        <select id="departmentFilter" class="border rounded px-4 py-2 text-sm">
          <option value="all">All Departments</option>
          <option value="Front Desk">Front Desk</option>
          <option value="Kitchen">Kitchen</option>
          <option value="HR">HR</option>
          <option value="Housekeeping">Housekeeping</option>
        </select>
      </div>
    </section>

    <!-- Table Section -->
    <section class="bg-white p-6 rounded-lg shadow">
      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-300">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Document</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Department</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Claimed By</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Size</th>
              <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Archived Date</th>
            </tr>
          </thead>
          <tbody id="archiveTableBody" class="divide-y divide-gray-200">
            <!-- Rows go here -->
          </tbody>
        </table>
        <p id="emptyMessage" class="text-gray-500 mt-4">No archived documents found.</p>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="text-center text-gray-500 text-sm py-6">
    &copy; 2025 Hotel & Restaurant Management System
  </footer>

  <!-- Script -->
  <script>
    const ARCHIVE_KEY = "archivedDocuments";
    const archiveTableBody = document.getElementById("archiveTableBody");
    const departmentFilter = document.getElementById("departmentFilter");
    const emptyMessage = document.getElementById("emptyMessage");

    let archivedDocs = JSON.parse(localStorage.getItem(ARCHIVE_KEY)) || [];

    function renderTable(dept = "all") {
      archiveTableBody.innerHTML = "";

      const filtered = dept === "all"
        ? archivedDocs
        : archivedDocs.filter(doc => doc.department === dept);

      if (filtered.length === 0) {
        emptyMessage.style.display = "block";
        return;
      }

      emptyMessage.style.display = "none";

      filtered.forEach(doc => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td class="px-4 py-2 text-sm text-gray-800">${doc.name}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.department || "—"}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.claimedBy || "—"}</td>
          <td class="px-4 py-2 text-sm text-gray-600">${Math.round(doc.size / 1024)} KB</td>
          <td class="px-4 py-2 text-sm text-gray-600">${doc.archivedDate || "—"}</td>
        `;
        archiveTableBody.appendChild(row);
      });
    }

    departmentFilter.addEventListener("change", () => {
      renderTable(departmentFilter.value);
    });

    // Initial render
    renderTable();
  </script>
</body>
</html>
