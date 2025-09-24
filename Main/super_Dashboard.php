<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Dashboard</title>
  <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
  <link rel="stylesheet" href="../assets/css/Admin.css">
  <link rel="stylesheet" href="../assets/icon/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/icon/all.min.css">
  <style>
    body{ margin:0; font-family: system-ui, Arial, sans-serif; }
    header{ background:#0f1c49; color:#fff; padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    main{ padding:1.25rem; }
    .card{ border:1px solid #e5e7eb; border-radius:12px; padding:1rem; }
    a.btn{ display:inline-block; padding:.55rem .9rem; background:#0f1c49; color:#fff; border-radius:8px; text-decoration:none; }
  </style>
</head>
<body>
  <header>
    <div>
      <strong>Super Admin Dashboard</strong>
    </div>
    <nav>
      <a class="btn" href="../auth/logout.php">Logout</a>
    </nav>
  </header>
  <main>
    <div class="card">
      <h2>Hello, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Super Admin'); ?> 👋</h2>
      <p>You now have access to Super Admin tools. Build out widgets here.</p>
    </div>
  </main>
</body>
</html>


