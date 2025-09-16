<?php
session_start();

// --- Database Connection Section ---
// 1. Check if config file exists and is readable
$config_path = __DIR__ . '/../backend/sql/config.php';
if (!file_exists($config_path) || !is_readable($config_path)) {
    die("Error: Configuration file '$config_path' not found or not readable.");
}

// 2. Include the configuration file
include_once $config_path;

// Check if required variables are defined (basic check)
if (!isset($servername) || !isset($username) || !isset($password) || !isset($database)) {
     die("Error: Database configuration variables (\$servername, \$username, \$password, \$database) are missing in '$config_path'.");
}

// 3. Create connection using error suppression (@) to handle potential issues gracefully before our check
$conn = @new mysqli($servername, $username, $password, $database);

// 4. Check if the connection object was created successfully
if (!$conn) {
    die("Connection Error: Unable to create database connection object.");
}

// 5. Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 6. Set charset
if (!$conn->set_charset("utf8")) {
    die("Error setting charset: " . $conn->error);
}

// If connection is successful, $conn is now a valid mysqli object.
// You can use $conn for database operations later if needed.
// For now, since the rest is HTML/JS, we just needed to ensure the connection setup worked.
// We don't strictly need to keep $conn alive if not used further down, but good practice to confirm it works.
// Let's close it explicitly as it's not used beyond this point in the current script.
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Hotel & Booking</title>
  <!-- Removed extra space at the end of the CDN URL -->
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Moved style tag inside head */
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
<body class="bg-gray-100 text-gray-800 flex">

  <!-- Sidebar -->
  <div class="shadow-lg h-screen fixed top-0 left-0 w-64 z-20">
    <?php
       // Also add checks for the sidebar include if necessary
       $sidebar_path = '../Components/sidebar/sidebar_user.php';
       if (file_exists($sidebar_path) && is_readable($sidebar_path)) {
           include $sidebar_path;
       } else {
           echo "<p class='p-4 text-red-500'>Sidebar not found.</p>";
       }
    ?>
  </div>

  <!-- Main Content -->
  <div id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
      <h2 class="text-xl font-semibold text-gray-800">Visitor Check-in</h2>
      <?php
        // Check for profile include
        $profile_path = __DIR__ . '/../profile.php';
         if (file_exists($profile_path) && is_readable($profile_path)) {
             include $profile_path;
         } else {
             // Optionally render a placeholder or nothing
             // echo "<div class='bg-gray-200 rounded-full w-8 h-8'></div>";
         }
       ?>
    </div>

    <div class="max-w-6xl mx-auto p-6">
      <header class="mb-6">
        <h1 class="text-3xl font-bold">Hotel & Booking</h1>
      </header>

      <!-- Nav Tabs -->
      <nav class="mb-6 relative z-30">
        <div class="flex gap-2">
          <button data-tab="hotel" class="tab-btn px-4 py-2 rounded bg-indigo-600 text-white">Hotel Management</button>
          <button data-tab="visitor" class="tab-btn px-4 py-2 rounded bg-indigo-100 text-indigo-700">Booking</button>
          <button id="resetData" class="ml-auto px-3 py-2 rounded border bg-red-500 text-white">Reset All Data</button>
        </div>
      </nav>

      <main>
        <!-- Hotel Management -->
        <section id="hotel" class="tab-pane bg-white p-6 rounded shadow">
          <h2 class="text-2xl font-semibold mb-4">Hotel Management System</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Reservation Form -->
            <div class="p-4 border rounded">
              <h3 class="font-medium mb-2">Add Reservation</h3>
              <form id="reservationForm" class="space-y-3">
                <input required name="guest" placeholder="Guest name" class="w-full p-2 border rounded" />
                <select name="roomType" class="w-full p-2 border rounded">
                  <option value="Single">Single</option>
                  <option value="Double">Double</option>
                  <option value="Suite">Suite</option>
                </select>
                <div class="grid grid-cols-2 gap-2">
                  <input required type="date" name="checkIn" class="p-2 border rounded" />
                  <input required type="date" name="checkOut" class="p-2 border rounded" />
                </div>
                <input type="number" name="rate" placeholder="Rate per night (PHP)" class="w-full p-2 border rounded" />
                <div class="flex gap-2">
                  <button class="px-4 py-2 bg-indigo-600 text-white rounded" type="submit">Save</button>
                  <button id="clearReservation" type="button" class="px-4 py-2 border rounded">Clear</button>
                </div>
              </form>
            </div>

            <!-- Reservation List -->
            <div class="p-4 border rounded">
              <h3 class="font-medium mb-2">Reservations</h3>
              <div id="reservationsList" class="space-y-2 max-h-64 overflow-auto"></div>
              <div class="mt-4">
                <h4 class="font-medium">Reports</h4>
                <p id="occupancy" class="text-sm text-gray-600">Occupancy: —</p>
                <p id="revenue" class="text-sm text-gray-600">Total Revenue (booked): —</p>
              </div>
            </div>

          </div>
        </section>

        <!-- Visitor Management -->
        <section id="visitor" class="tab-pane hidden bg-white p-6 rounded shadow">
          <h2 class="text-2xl font-semibold mb-4">Visitor Management System (VMS)</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Visitor Form -->
            <div class="p-4 border rounded">
              <h3 class="font-medium mb-2">Register Visitor</h3>
              <form id="visitorForm" class="space-y-3">
                <input required name="name" placeholder="Full name" class="w-full p-2 border rounded" />
                <input name="idNo" placeholder="ID / Document" class="w-full p-2 border rounded" />
                <input name="purpose" placeholder="Purpose of visit" class="w-full p-2 border rounded" />
                <div class="flex gap-2">
                  <button class="px-4 py-2 bg-indigo-600 text-white rounded" type="submit">Register & Check-in</button>
                  <button id="clearVisitor" type="button" class="px-4 py-2 border rounded">Clear</button>
                </div>
              </form>
            </div>

            <!-- Visitors List -->
            <div class="p-4 border rounded">
              <h3 class="font-medium mb-2">Visitors / Log</h3>
              <div id="visitorsList" class="space-y-2 max-h-64 overflow-auto"></div>
            </div>

          </div>

          <!-- Badge Preview -->
          <div class="mt-4 p-4 border rounded">
            <h3 class="font-medium mb-2">Badge Preview (click to print)</h3>
            <div id="badgePreview" class="p-4 border rounded inline-block cursor-pointer">No badge selected</div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    // === Utility functions ===
    const qs = (s, root = document) => root.querySelector(s);
    const qsa = (s, root = document) => [...root.querySelectorAll(s)];

    // === Tabs ===
    qsa('.tab-btn').forEach(btn => btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      qsa('.tab-btn').forEach(b => b.classList.remove('bg-indigo-600','text-white'));
      btn.classList.add('bg-indigo-600','text-white');
      qsa('.tab-pane').forEach(p => p.classList.add('hidden'));
      qs('#'+tab).classList.remove('hidden');
    }));

    // === Local Storage Keys ===
    const KEYS = { RES: 'hms_reservations_v1', VIS: 'hms_visitors_v1' };
    const load = key => { try { return JSON.parse(localStorage.getItem(key)) || []; } catch { return []; } };
    const save = (key, data) => localStorage.setItem(key, JSON.stringify(data));

    // === Reservations ===
    const reservationForm = qs('#reservationForm');
    function renderReservations() {
      const list = qs('#reservationsList'); list.innerHTML='';
      const res = load(KEYS.RES);
      res.forEach((r,i)=>{
        const div = document.createElement('div');
        div.className='p-3 border rounded flex justify-between items-start';
        div.innerHTML = `
          <div>
            <div class="font-medium">${r.guest} — ${r.roomType}</div>
            <div class="text-sm text-gray-600">${r.checkIn} → ${r.checkOut}</div>
            <div class="text-sm text-gray-600">Rate/night: ${r.rate || '—'}</div>
          </div>
          <div class="flex flex-col items-end gap-2">
            <button data-i="${i}" class="checkinBtn px-2 py-1 rounded border text-sm">Check-in</button>
            <button data-i="${i}" class="invoiceBtn px-2 py-1 rounded bg-green-600 text-white text-sm">Invoice</button>
          </div>`;
        list.appendChild(div);
      });

      const revenue = res.reduce((s,r)=>{
        const nights = Math.max(0, (new Date(r.checkOut)-new Date(r.checkIn))/(1000*60*60*24));
        const rate = Number(r.rate) || (r.roomType==='Suite'?8000:r.roomType==='Double'?4000:2000);
        return s+nights*rate;
      },0);
      qs('#revenue').textContent = `Total Revenue (booked): ₱${Math.round(revenue)}`;
      const occ = Math.min(100, Math.round((res.length/20)*100)); // Assuming 20 rooms max
      qs('#occupancy').textContent = `Occupancy (approx): ${occ}%`;
    }

    reservationForm.addEventListener('submit', e=>{
      e.preventDefault();
      const f=e.target;
      const data={ guest:f.guest.value.trim(), roomType:f.roomType.value, checkIn:f.checkIn.value, checkOut:f.checkOut.value, rate:f.rate.value?Number(f.rate.value):undefined, createdAt:new Date().toISOString() };
      const res=load(KEYS.RES); res.push(data); save(KEYS.RES,res); renderReservations(); f.reset();
    });
    qs('#clearReservation').addEventListener('click', ()=>reservationForm.reset());

    // Invoice
    qs('#reservationsList').addEventListener('click', e=>{
      if(e.target.classList.contains('invoiceBtn')){
        const i = Number(e.target.dataset.i);
        const r = load(KEYS.RES)[i];
        const nights = Math.max(1, (new Date(r.checkOut)-new Date(r.checkIn))/(1000*60*60*24));
        const rate = r.rate || (r.roomType==='Suite'?8000:r.roomType==='Double'?4000:2000);
        alert(`Invoice for ${r.guest}\nNights: ${nights}\nRate/night: ₱${rate}\nTotal: ₱${nights*rate}`);
      }
    });

    // Check-in (Placeholder functionality - just removes from list or marks as checked in)
     qs('#reservationsList').addEventListener('click', e=>{
      if(e.target.classList.contains('checkinBtn')){
        const i = Number(e.target.dataset.i);
        const res = load(KEYS.RES);
        // Example: Remove reservation on check-in
        if (confirm(`Check-in ${res[i].guest}? This will remove the reservation.`)) {
             res.splice(i, 1);
             save(KEYS.RES, res);
             renderReservations(); // Re-render the list
        }
        // Alternatively, mark as checked in without removing:
        // res[i].checkedIn = true;
        // save(KEYS.RES, res);
        // renderReservations();
      }
    });


    // === Visitors ===
    const visitorForm = qs('#visitorForm');
    function renderVisitors() {
      const list = qs('#visitorsList'); list.innerHTML='';
      const arr=load(KEYS.VIS);
      arr.forEach((v,i)=>{
        const div=document.createElement('div');
        div.className='p-3 border rounded flex justify-between items-start';
        // Add strikethrough or different style if checked out
        const nameClass = v.checkedOut ? 'font-medium line-through text-gray-500' : 'font-medium';
        div.innerHTML=`
          <div>
            <div class="${nameClass}">${v.name} ${v.checkedOut?'<span class="text-xs text-red-600">(Left)</span>':''}</div>
            <div class="text-sm text-gray-600">ID: ${v.idNo||'—'} • Purpose: ${v.purpose||'—'}</div>
            <div class="text-xs text-gray-500">In: ${v.checkIn}</div>
            ${v.checkOut ? `<div class="text-xs text-gray-500">Out: ${v.checkOut}</div>` : ''}
          </div>
          <div class="flex flex-col items-end gap-2">
            <button data-i="${i}" class="badgeBtn px-2 py-1 rounded border text-sm">Badge</button>
            ${!v.checkedOut ? `<button data-i="${i}" class="checkoutBtn px-2 py-1 rounded bg-red-600 text-white text-sm">Check-out</button>` : `<span class="text-xs text-gray-500 px-2 py-1">Checked Out</span>`}
          </div>`;
        list.appendChild(div);
      });
    }

    visitorForm.addEventListener('submit', e=>{
      e.preventDefault();
      const f=e.target;
      const data={ name:f.name.value.trim(), idNo:f.idNo.value.trim(), purpose:f.purpose.value.trim(), checkIn:new Date().toLocaleString(), checkedOut:false, checkOut: null }; // Initialize checkOut
      const arr=load(KEYS.VIS); arr.push(data); save(KEYS.VIS,arr); renderVisitors(); f.reset();
    });

    qs('#visitorsList').addEventListener('click', e=>{
      const i = e.target.dataset.i ? Number(e.target.dataset.i) : null;
      if(i !== null && e.target.classList.contains('badgeBtn')){
        const v = load(KEYS.VIS)[i];
        if (v) { // Check if visitor exists
            qs('#badgePreview').innerHTML = `
              <div class="p-4 w-64 border rounded">
                <h3 class="font-bold">${v.name}</h3>
                <div>Purpose: ${v.purpose || 'N/A'}</div>
                <div>ID: ${v.idNo || 'N/A'}</div>
                <div class="text-xs text-gray-500 mt-2">Checked in: ${v.checkIn}</div>
              </div>`;
        }
      }
      if(i !== null && e.target.classList.contains('checkoutBtn')){
        const arr = load(KEYS.VIS);
        if (arr[i]) { // Check if visitor exists
            arr[i].checkedOut=true;
            arr[i].checkOut=new Date().toLocaleString();
            save(KEYS.VIS,arr);
            renderVisitors();
        }
      }
    });

    // Badge Print
    qs('#badgePreview').addEventListener('click', ()=>{
      const content = qs('#badgePreview').innerHTML.trim();
      if(content === 'No badge selected' || content === '') {
           alert('Select a visitor and click Badge first.');
           return;
       }
      // Basic print - consider opening in new window/tab for better control
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Visitor Badge</title>
            <style>
               body { font-family: sans-serif; text-align: center; margin: 20px; }
               .badge { border: 1px solid #ccc; padding: 15px; width: 200px; margin: 0 auto; }
               h3 { margin-top: 0; }
            </style>
          </head>
          <body>
            <div class="badge">
              ${content}
            </div>
            <script>
              window.onload = function() {
                window.print();
                // Optional: window.close(); // Close after print, might be blocked by browser
              }
            <\/script>
          </body>
        </html>
      `);
      printWindow.document.close(); // Necessary for some browsers
      // printWindow.print(); // Alternative, might print blank if content not loaded yet
    });

    // Reset All
    qs('#resetData').addEventListener('click', ()=>{
      if(!confirm('⚠️ Are you sure you want to delete ALL saved data?')) return;
      localStorage.removeItem(KEYS.RES); // More specific than clear
      localStorage.removeItem(KEYS.VIS);
      alert('✅ All data has been cleared.');
      renderReservations();
      renderVisitors();
      qs('#badgePreview').innerHTML = 'No badge selected'; // Reset badge preview
    });

    // Initial load
    renderReservations();
    renderVisitors();
  </script>
</body>
</html>