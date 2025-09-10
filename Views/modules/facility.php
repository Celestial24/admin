<!DOCTYPE html>
<html lang="en">
<head>
  <title>Facilities Reservation</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="flex">
 
  <div id="sidebar" class="bg-slate-900 shadow-lg h-screen fixed top-0 left-0">
    <?php include '../../Components/sidebar/sidebar_super-admin.php'; ?>
  </div>

  <div class="ml-64 flex-1 p-6 space-y-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white rounded-lg shadow p-5 text-center">
        <h3 class="text-lg font-semibold text-gray-700">Total Facilities</h3>
        <div id="total-facilities" class="text-3xl font-bold text-blue-600 mt-2">0</div>
        <p class="text-sm text-gray-500 mt-1">Available for reservation</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5 text-center">
        <h3 class="text-lg font-semibold text-gray-700">Active Reservations</h3>
        <div id="active-reservations" class="text-3xl font-bold text-green-600 mt-2">0</div>
        <p class="text-sm text-gray-500 mt-1">Current bookings</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5 text-center">
        <h3 class="text-lg font-semibold text-gray-700">Pending Requests</h3>
        <div id="pending-requests" class="text-3xl font-bold text-yellow-600 mt-2">0</div>
        <p class="text-sm text-gray-500 mt-1">Awaiting approval</p>
      </div>
      <div class="bg-white rounded-lg shadow p-5 text-center">
        <h3 class="text-lg font-semibold text-gray-700">Maintenance Issues</h3>
        <div id="maintenance-issues" class="text-3xl font-bold text-red-600 mt-2">0</div>
        <p class="text-sm text-gray-500 mt-1">Open tickets</p>
      </div>
    </div>

    <div class="flex space-x-4 border-b">
      <button class="tab active px-4 py-2 text-blue-600 border-b-2 border-blue-600 font-semibold" onclick="switchTab('facilities')">Facilities</button>
      <button class="tab px-4 py-2 text-gray-600 hover:text-blue-600" onclick="switchTab('reservations')">Reservations</button>
      <button class="tab px-4 py-2 text-gray-600 hover:text-blue-600" onclick="switchTab('maintenance')">Maintenance</button>
    </div>

    <div id="facilities-tab" class="tab-content">
      <div class="space-y-6">
        <div>
          <h2 class="text-xl font-semibold mb-4">Add New Facility</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="fname" class="block text-sm font-medium text-gray-700">Facility Name</label>
              <input id="fname" class="w-full mt-1 p-2 border border-gray-300 rounded">
            </div>
            <div>
              <label for="ftype" class="block text-sm font-medium text-gray-700">Facility Type</label>
              <input id="ftype" class="w-full mt-1 p-2 border border-gray-300 rounded">
            </div>
            <div>
              <label for="fcap" class="block text-sm font-medium text-gray-700">Capacity</label>
              <input id="fcap" type="number" class="w-full mt-1 p-2 border border-gray-300 rounded">
            </div>
            <div>
              <label for="fstatus" class="block text-sm font-medium text-gray-700">Status</label>
              <select id="fstatus" class="w-full mt-1 p-2 border border-gray-300 rounded">
                <option>Active</option>
                <option>Inactive</option>
                <option>Under Maintenance</option>
              </select>
            </div>
            <div class="md:col-span-2">
              <label for="fnotes" class="block text-sm font-medium text-gray-700">Notes</label>
              <textarea id="fnotes" class="w-full mt-1 p-2 border border-gray-300 rounded"></textarea>
            </div>
          </div>
          <button onclick="addFacility()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Facility</button>
        </div>
      </div>
    </div>

    <div id="reservations-tab" class="tab-content hidden">
      <div class="space-y-6">
        <div>
          <h2 class="text-xl font-semibold mb-4">Make a Reservation</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="resFacility" class="block text-sm font-medium text-gray-700">Select Facility</label>
              <select id="resFacility" class="w-full mt-1 p-2 border border-gray-300 rounded">
                <option>Loading facilities...</option>
              </select>
            </div>
            <div>
              <label for="reservedBy" class="block text-sm font-medium text-gray-700">Your Name</label>
              <input id="reservedBy" class="w-full mt-1 p-2 border border-gray-300 rounded">
            </div>
            <div class="md:col-span-2">
              <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose of Reservation</label>
              <textarea id="purpose" class="w-full mt-1 p-2 border border-gray-300 rounded"></textarea>
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Reservation Period</label>
              <div class="flex items-center space-x-2 mt-1">
                <input type="datetime-local" id="startTime" class="p-2 border border-gray-300 rounded">
                <span class="text-gray-500">to</span>
                <input type="datetime-local" id="endTime" class="p-2 border border-gray-300 rounded">
              </div>
            </div>
          </div>
          <button onclick="addReservation()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Submit Reservation</button>
        </div>

        <div>
          <h2 class="text-xl font-semibold mb-4">Reservation Requests</h2>
          <div class="overflow-auto">
            <table class="w-full table-auto border border-gray-300 text-sm">
              <thead class="bg-gray-100 text-left">
                <tr>
                  <th class="p-2">ID</th>
                  <th class="p-2">Facility</th>
                  <th class="p-2">Reserved By</th>
                  <th class="p-2">Purpose</th>
                  <th class="p-2">Start Time</th>
                  <th class="p-2">End Time</th>
                  <th class="p-2">Status</th>
                  <th class="p-2">Actions</th>
                </tr>
              </thead>
              <tbody id="resTable">
                <tr>
                  <td colspan="8" class="p-4 text-center text-gray-500">Loading reservations...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div id="maintenance-tab" class="tab-content hidden">
      <div class="space-y-6">
        <div>
          <h2 class="text-xl font-semibold mb-4">Add Maintenance Request</h2>
          <div class="space-y-4">
            <div>
              <label for="mrFacility" class="block text-sm font-medium text-gray-700">Select Facility</label>
              <select id="mrFacility" class="w-full mt-1 p-2 border border-gray-300 rounded">
                <option>Loading facilities...</option>
              </select>
            </div>
            <div>
              <label for="mrDesc" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea id="mrDesc" class="w-full mt-1 p-2 border border-gray-300 rounded"></textarea>
            </div>
          </div>
          <button onclick="addMR()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Add Request</button>
        </div>

        <div>
          <h2 class="text-xl font-semibold mb-4">Maintenance Requests</h2>
          <div class="overflow-auto">
            <table class="w-full table-auto border border-gray-300 text-sm">
              <thead class="bg-gray-100 text-left">
                <tr>
                  <th class="p-2">ID</th>
                  <th class="p-2">Facility</th>
                  <th class="p-2">Description</th>
                  <th class="p-2">Created At</th>
                  <th class="p-2">Action</th>
                </tr>
              </thead>
              <tbody id="mrTable">
                <tr>
                  <td colspan="5" class="p-4 text-center text-gray-500">Loading maintenance requests...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
