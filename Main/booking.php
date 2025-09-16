<?php 
// ======= DATABASE CONFIGURATION =======
$host = "localhost";
$user = "root";
$pass = "";
$db   = "booking";

// ======= CONNECT TO MYSQL =======
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Hotel & Booking</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
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
<body class="bg-gray-100 text-gray-800 flex">

  <!-- Sidebar -->
  <div class="shadow-lg h-screen fixed top-0 left-0 w-64 z-20">
    <?php include '../Components/sidebar/sidebar_user.php'; ?>
  </div>

  <!-- Main Content -->
  <div id="mainContent" class="ml-64 flex flex-col flex-1 overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between border-b pb-4 px-6 py-4 bg-white">
      <h2 class="text-xl font-semibold text-gray-800">Visitor Check-in</h2>
      <?php include __DIR__ . '/../profile.php'; ?>
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
      const occ = Math.min(100, Math.round((res.length/20)*100));
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

    // === Visitors ===
    const visitorForm = qs('#visitorForm');
    function renderVisitors() {
      const list = qs('#visitorsList'); list.innerHTML='';
      const arr=load(KEYS.VIS);
      arr.forEach((v,i)=>{
        const div=document.createElement('div');
        div.className='p-3 border rounded flex justify-between items-start';
        div.innerHTML=`
          <div>
            <div class="font-medium">${v.name} ${v.checkedOut?'<span class="text-xs text-red-600">(Left)</span>':''}</div>
            <div class="text-sm text-gray-600">ID: ${v.idNo||'—'} • Purpose: ${v.purpose||'—'}</div>
            <div class="text-xs text-gray-500">In: ${v.checkIn}</div>
          </div>
          <div class="flex flex-col items-end gap-2">
            <button data-i="${i}" class="badgeBtn px-2 py-1 rounded border text-sm">Badge</button>
            <button data-i="${i}" class="checkoutBtn px-2 py-1 rounded bg-red-600 text-white text-sm">Check-out</button>
          </div>`;
        list.appendChild(div);
      });
    }

    visitorForm.addEventListener('submit', e=>{
      e.preventDefault();
      const f=e.target;
      const data={ name:f.name.value.trim(), idNo:f.idNo.value.trim(), purpose:f.purpose.value.trim(), checkIn:new Date().toLocaleString(), checkedOut:false };
      const arr=load(KEYS.VIS); arr.push(data); save(KEYS.VIS,arr); renderVisitors(); f.reset();
    });

    qs('#visitorsList').addEventListener('click', e=>{
      const i = e.target.dataset.i ? Number(e.target.dataset.i) : null;
      if(e.target.classList.contains('badgeBtn')){
        const v = load(KEYS.VIS)[i];
        qs('#badgePreview').innerHTML = `
          <div class="p-4 w-64 border rounded">
            <h3 class="font-bold">${v.name}</h3>
            <div>Purpose: ${v.purpose}</div>
            <div>ID: ${v.idNo||'—'}</div>
            <div class="text-xs text-gray-500 mt-2">Checked in: ${v.checkIn}</div>
          </div>`;
      }
      if(e.target.classList.contains('checkoutBtn')){
        const arr = load(KEYS.VIS); arr[i].checkedOut=true; arr[i].checkOut=new Date().toLocaleString();
        save(KEYS.VIS,arr); renderVisitors();
      }
    });

    // Badge Print
    qs('#badgePreview').addEventListener('click', ()=>{
      if(qs('#badgePreview').innerHTML.trim()==='No badge selected') return alert('Select a visitor and click Badge first.');
      const w = window.open('', '_blank');
      w.document.write('<html><head><title>Badge</title></head><body>'+qs('#badgePreview').innerHTML+'</body></html>');
      w.print();
    });

    // Reset All
    qs('#resetData').addEventListener('click', ()=>{
      if(!confirm('⚠️ Are you sure you want to delete ALL saved data?')) return;
      localStorage.clear(); alert('✅ All data has been cleared.'); location.reload();
    });

    // Initial load
    renderReservations(); renderVisitors();
  </script>
</body>
</html>
