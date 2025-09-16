<head>
  <meta charset="UTF-8">
  <title>Landing page</title>
  <link rel="icon" type="image/png" href="assets/image/logo2.png" />
  
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>


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

<body class="font-sans bg-gray-50 text-gray-900">

  
  <section class="relative bg-cover bg-center h-screen" style="background-image: url('hotel-bg.jpg')">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="relative z-10 flex flex-col justify-center items-center h-full text-center text-white">
      <h1 class="text-5xl font-bold mb-4">Welcome to ATIERA Hotel & Restaurant</h1>
      <p class="text-lg max-w-xl">Experience unparalleled luxury and exceptional hospitality where comfort meets culinary excellence.</p>
      <div class="mt-6 space-x-4">
        <a href="#book" class="px-6 py-3 bg-yellow-500 rounded-lg font-semibold hover:bg-yellow-600">Book Your Stay</a>
        <a href="#dining" class="px-6 py-3 bg-white text-black rounded-lg font-semibold hover:bg-gray-200">Explore Dining</a>
      </div>
    </div>
  </section>


  <section class="py-16 bg-white" id="features">
    <div class="max-w-6xl mx-auto px-6">
      <h2 class="text-3xl font-bold text-center mb-12">Discover Our Smart System</h2>
      <div class="grid md:grid-cols-3 gap-8 text-center">
        <div class="p-6 bg-gray-100 rounded-xl shadow">
          <h3 class="font-semibold text-xl mb-2">Smart HR & Payroll</h3>
          <p>Automated attendance, salary computation, and employee management.</p>
        </div>
        <div class="p-6 bg-gray-100 rounded-xl shadow">
          <h3 class="font-semibold text-xl mb-2">Contract Oversight</h3>
          <p>Track contracts with employees, suppliers, and partners seamlessly.</p>
        </div>
        <div class="p-6 bg-gray-100 rounded-xl shadow">
          <h3 class="font-semibold text-xl mb-2">Risk & Compliance</h3>
          <p>AI-powered alerts for compliance gaps and legal risk factors.</p>
        </div>
      </div>
    </div>
  </section>


  <section class="py-16 bg-yellow-500 text-center text-white" id="book">
    <h2 class="text-3xl font-bold mb-4">Ready to Experience ATIERA?</h2>
    <p class="mb-6">Book your stay or reserve your table today.</p>
    <a href="auth/login.php" class="px-6 py-3 bg-black rounded-lg font-semibold hover:bg-gray-800">Login now</a>
  </section>


  <footer class="py-8 bg-gray-900 text-gray-300 text-center">
    <p>&copy; 2025 ATIERA Hotel & Restaurant | All Rights Reserved</p>
  </footer>
</body>