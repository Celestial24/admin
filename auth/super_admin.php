<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin</title>
    <link rel="icon" type="image/png" href="../assets/image/logo2.png" />
    <link rel="stylesheet" href="../assets/css/Admin.css">
    <link rel="stylesheet" href="../assets/icon/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/icon/all.min.css">
    <style>
        body{ font-family: system-ui, Arial, sans-serif; margin:0; padding:2rem; }
        .card{ max-width:720px; margin:0 auto; border:1px solid #e5e7eb; border-radius:12px; padding:1.5rem; }
        .actions a{ display:inline-block; padding:.6rem .9rem; background:#0f1c49; color:#fff; border-radius:8px; text-decoration:none; }
        .muted{ color:#64748b; }
    </style>
    </head>
<body>
    <div class="card">
        <h1>Welcome, Super Admin</h1>
        <p class="muted">Use the link below to access the Super Admin dashboard.</p>
        <div class="actions">
            <a href="../Main/super_Dashboard.php">Go to Super Dashboard</a>
        </div>
    </div>
</body>
</html>


