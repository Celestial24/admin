<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Legal Management Dashboard</title>
  <link rel="icon" type="image/png" href="/admin/assets/image/logo2.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
  <div class="flex h-screen bg-gray-100">
    <aside class="w-64 bg-white shadow-lg p-6">
      <div class="text-xl font-bold mb-8">Logo</div>
      <nav class="space-y-2">
        <a href="#" class="block py-2.5 px-4 rounded hover:bg-gray-200 transition duration-200">Dashboard</a>
        <a href="#" class="block py-2.5 px-4 rounded hover:bg-gray-200 transition duration-200">Legal Documents</a>
        <a href="#" class="block py-2.5 px-4 rounded hover:bg-gray-200 transition duration-200">Compliance Tracking</a>
        <a href="#integration" class="block py-2.5 px-4 rounded hover:bg-gray-200 transition duration-200">Integrations</a>
      </nav>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
      <header class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Legal Management Dashboard</h1>
        <div class="flex items-center space-x-4">
          <span class="text-gray-600">User Profile</span>
          <div class="relative">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
              class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/2 -translate-y-1/2">3</span>
          </div>
        </div>
      </header>

      <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-gray-500 text-sm font-semibold mb-2">Pending Legal Cases</h2>
          <div class="text-4xl font-bold text-red-500">12</div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-gray-500 text-sm font-semibold mb-2">Upcoming Compliance Deadlines</h2>
          <div class="text-4xl font-bold text-blue-500">5</div>
        </div>
      </section>

      <section class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-lg font-semibold mb-4">Legal Documents</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Document Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap">Lease Agreement - Main Building</td>
                <td class="px-6 py-4 whitespace-nowrap">Contract</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section id="integration" class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-lg font-semibold mb-4">Integration Features</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-4 border rounded-lg">
            <div class="flex items-center space-x-3 mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 104 4H3v-4zM16 3v4a4 4 0 11-4 4H3" />
              </svg>
              <h3 class="font-semibold text-gray-700">API Status</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">Check connection status with third-party services.</p>
            <div class="flex items-center space-x-2">
              <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
              <span class="text-green-600 font-semibold">Connected</span>
            </div>
          </div>

          <div class="p-4 border rounded-lg">
            <div class="flex items-center space-x-3 mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <h3 class="font-semibold text-gray-700">Webhooks</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">Manage and configure webhook URLs for real-time updates.</p>
            <button class="px-3 py-1 text-sm bg-yellow-400 hover:bg-yellow-500 rounded text-white font-semibold">Configure</button>
          </div>

          <div class="p-4 border rounded-lg">
            <div class="flex items-center space-x-3 mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l4 2" />
              </svg>
              <h3 class="font-semibold text-gray-700">App Connections</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">Connect with apps like Slack, Google Drive, and more.</p>
            <button class="px-3 py-1 text-sm bg-purple-600 hover:bg-purple-700 rounded text-white font-semibold">Connect</button>
          </div>
        </div>

        <div class="mt-8">
          <h3 class="font-semibold text-gray-700 mb-4">Recent Sync Logs</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">2025-09-01 12:30</td>
                  <td class="px-6 py-4 whitespace-nowrap">Google Drive</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">2025-09-01 09:15</td>
                  <td class="px-6 py-4 whitespace-nowrap">Slack</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
