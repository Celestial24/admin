<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database connection
include_once('../backend/sql/db.php'); // Make sure this path is correct and sets up $conn (mysqli connection)

// Initialize user variable
$user = null;

// User ID to fetch
$user_id = 1; // Replace with dynamic user ID if needed

// Prepare and execute the statement
$stmt = mysqli_prepare($conn, "SELECT * FROM admin_user WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    // Get the result set from the statement
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $user = mysqli_fetch_assoc($result);
        if (!$user) {
            echo "User not found!";
            exit;
        }
    } else {
        echo "Error fetching user data: " . mysqli_error($conn);
        exit;
    }

    mysqli_stmt_close($stmt);
} else {
    echo "Failed to prepare statement: " . mysqli_error($conn);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/image/logo2.png"/>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <meta name="description" content="User Profile Page">
    <style>
        body {
            min-height: 100svh;
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(80% 60% at 8% 10%, rgba(255,255,255,.18) 0, transparent 60%),
                radial-gradient(80% 40% at 100% 0%, rgba(212,175,55,.08) 0, transparent 40%),
                linear-gradient(140deg, rgba(15,28,73,1) 50%, rgba(255,255,255,1) 50%);
        }
    </style>
</head>

<body>
<div class="flex justify-center items-center min-h-screen">
    <div class="max-w-sm w-full bg-white p-6 rounded-lg shadow-lg">

        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <a href="../Main/index.php">
                <img src="../assets/image/logo.png" alt="Logo" class="w-340 h-16 rounded-full">
            </a>
        </div>

        <!-- Avatar -->
        <div class="flex justify-center mb-4">
            <img src="../assets/image/Profile.png" alt="Profile" class="w-16 h-16 rounded-full">
        </div>

        <!-- User Info -->
        <h2 class="text-xl font-semibold text-center"><?= htmlspecialchars($user['name']); ?></h2>
        <p class="text-gray-600 text-center"><?= htmlspecialchars($user['email']); ?></p>
        <p class="text-gray-500 text-center">Employee ID: <?= str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></p>

        <!-- Password Display -->
        <div class="mt-4 flex items-center space-x-2">
            <p class="font-bold mb-0">Password:</p>
            <span id="passwordDots" class="text-black font-mono">•••</span>
            <span id="passwordReal" class="text-black font-mono hidden"><?= htmlspecialchars($user['password']); ?></span>
            <button onclick="togglePassword()" class="focus:outline-none">
                <!-- Eye Icon -->
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 hover:text-black" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5
                              c4.477 0 8.268 2.943 9.542 7
                              -1.274 4.057-5.065 7-9.542 7
                              -4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
        </div>

        <!-- Timestamps -->
        <div class="mt-2">
            <p><strong>Created At:</strong> <?= date('F d, Y h:i a', strtotime($user['created_at'])); ?></p>
            <p><strong>Updated At:</strong> <?= date('F d, Y h:i a', strtotime($user['updated_at'])); ?></p>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex justify-between">
            <a id="createUserLink" href="../Views/Create-user.php"
               class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 inline-block">Create User</a>

            <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Edit Profile</button>

            <a href="javascript:history.back()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 inline-block">Back</a>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" style="display:none; color: green; margin-top: 10px; text-align: center;">
            Loading, please wait...
        </div>
    </div>
</div>

<!-- Toggle Password Script -->
<script>
function togglePassword() {
    const dots = document.getElementById("passwordDots");
    const real = document.getElementById("passwordReal");
    const icon = document.getElementById("eyeIcon");

    const isHidden = real.classList.contains("hidden");

    if (isHidden) {
        dots.classList.add("hidden");
        real.classList.remove("hidden");
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13.875 18.825A10.05 10.05 0 0112 19
                    c-4.477 0-8.268-2.943-9.542-7
                    a9.958 9.958 0 012.181-3.308m3.217-2.13
                    A9.956 9.956 0 0112 5
                    c4.477 0 8.268 2.943 9.542 7
                    a9.972 9.972 0 01-4.507 5.311M15 12a3 3 0 11-6 0
                    3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3l18 18" />`;
    } else {
        dots.classList.remove("hidden");
        real.classList.add("hidden");
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5
                    c4.477 0 8.268 2.943 9.542 7
                    -1.274 4.057-5.065 7-9.542 7
                    -4.477 0-8.268-2.943-9.542-7z"/>`;
    }
}
</script>

<!-- Loading Script -->
<script>
document.getElementById('createUserLink').addEventListener('click', function () {
    document.getElementById('loadingIndicator').style.display = 'block';
});
</script>
</body>
</html>
